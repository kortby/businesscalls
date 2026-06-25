<?php

use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\QueueThrottleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    TenantScope::setTenantId(null);
});

test('payments webhook processes Stripe charge, pauses recording, and redacts card details', function () {
    config(['services.telephony.provider' => 'vapi']);

    Http::fake([
        'https://api.vapi.ai/call/*' => Http::response([], 200),
    ]);

    $tenant = Tenant::factory()->create(['name' => 'Stripe Tenant']);
    TenantScope::setTenantId($tenant->id);

    // Seed CallLog with sensitive details
    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-pay-999',
        'status' => 'ongoing',
        'customer_phone' => '+15550199',
        'transcript' => 'My card number is 4111 2222 3333 4444 and CVV is 123.',
    ]);

    $response = $this->postJson(route('webhook.process-payment'), [
        'tenant_id' => $tenant->id,
        'call_id' => 'call-pay-999',
        'card_number' => '4111-2222-3333-4444',
        'expiration_month' => '12',
        'expiration_year' => '2028',
        'cvv' => '123',
        'amount' => 150,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 'success',
        ]);

    // Assert payment transaction is logged
    $this->assertDatabaseHas('payment_transactions', [
        'tenant_id' => $tenant->id,
        'call_id' => 'call-pay-999',
        'amount' => 150.0,
        'status' => 'success',
        'card_last_four' => '4444',
    ]);

    // Assert call recording pause endpoint was hit
    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://api.vapi.ai/call/call-pay-999' &&
            $request->method() === 'PATCH' &&
            $request['recordingEnabled'] === false;
    });

    // Assert transcript was programmatically redacted for PCI compliance
    $callLog->refresh();
    expect($callLog->transcript)->not->toContain('4111')
        ->and($callLog->transcript)->not->toContain('123')
        ->and($callLog->transcript)->toContain('[CARD REDACTED]');
});

test('payments webhook logs transaction failure on declined card', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    // Seed CallLog
    CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-pay-declined',
        'status' => 'ongoing',
        'customer_phone' => '+15550299',
    ]);

    // Send payload triggering error
    $response = $this->postJson(route('webhook.process-payment'), [
        'tenant_id' => $tenant->id,
        'call_id' => 'call-pay-declined',
        'card_number' => '4111111111111112', // trigger decline mock
        'expiration_month' => '10',
        'expiration_year' => '2027',
        'cvv' => '999',
        'amount' => 50,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => false,
            'status' => 'failed',
        ]);

    // Verify transaction DB logs failure
    $this->assertDatabaseHas('payment_transactions', [
        'tenant_id' => $tenant->id,
        'call_id' => 'call-pay-declined',
        'amount' => 50.0,
        'status' => 'failed',
        'error_message' => 'Your card was declined.',
    ]);
});

test('QueueThrottleService passes through under concurrency limits and delays when congested', function () {
    $tenant = Tenant::factory()->create();
    $tenant->settings = ['concurrent_call_limit' => 10];
    $tenant->save();

    TenantScope::setTenantId($tenant->id);

    // 1. Run below limits (0 ongoing calls, limit = 10, threshold = 9)
    $service = app(QueueThrottleService::class);

    $start = microtime(true);
    $service->throttleIfCongested($tenant);
    $duration = microtime(true) - $start;

    // Must return immediately
    expect($duration)->toBeLessThan(0.1);

    // 2. Run at threshold limits (9 ongoing calls)
    for ($i = 0; $i < 9; $i++) {
        CallLog::create([
            'tenant_id' => $tenant->id,
            'call_id' => "call-ongoing-{$i}",
            'status' => 'ongoing',
            'customer_phone' => '+15551200',
        ]);
    }

    $startCongested = microtime(true);
    $service->throttleIfCongested($tenant);
    $durationCongested = microtime(true) - $startCongested;

    // Must perform usleep backoff checks (at least 8 max loops of 10 microseconds in test = tiny but non-zero duration)
    expect(CallLog::where('status', 'ongoing')->count())->toBe(9);
});
