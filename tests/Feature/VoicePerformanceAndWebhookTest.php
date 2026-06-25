<?php

use App\Jobs\SendWebhookEventJob;
use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\TenantWebhook;
use App\Models\User;
use App\Services\VoicePerformanceService;
use App\Services\WebhookNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    TenantScope::setTenantId(null);
});

test('VoicePerformanceService computes performance index correctly and prevents division by zero', function () {
    $service = app(VoicePerformanceService::class);

    // Test division by zero
    $scoreZero = $service->calculatePerformanceScore([]);
    expect($scoreZero)->toBe(0.0);

    // Test calculation matching:
    // Turn 1: latency = 500, intelligibility = 0.90 => term = (1 - 500/2000) * 0.90 = 0.75 * 0.90 = 0.675
    // Turn 2: latency = 1000, intelligibility = 0.80 => term = (1 - 1000/2000) * 0.80 = 0.50 * 0.80 = 0.40
    // Sum = 1.075 / 2 = 0.5375
    $turns = [
        ['latency' => 500, 'intelligibility' => 0.90],
        ['latency' => 1000, 'intelligibility' => 0.80],
    ];

    $score = $service->calculatePerformanceScore($turns, 2000.0);
    expect($score)->toEqualWithDelta(0.5375, 0.0001);

    // Assert saving on CallLog
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-perf-111',
        'status' => 'ended',
        'customer_phone' => '+15550190',
    ]);

    $savedScore = $service->calculateAndSave($callLog, $turns);
    expect($savedScore)->toEqualWithDelta(0.5375, 0.0001);

    $callLog->refresh();
    expect($callLog->performance_score)->toEqualWithDelta(0.5375, 0.0001);
});

test('WebhookNotificationService queues SendWebhookEventJob and SendWebhookEventJob signs requests using HMAC SHA256', function () {
    Http::fake([
        'https://example.com/webhook-destination' => Http::response([], 200),
    ]);

    $tenant = Tenant::factory()->create(['name' => 'Webhook Tenant']);
    TenantScope::setTenantId($tenant->id);

    $webhook = TenantWebhook::create([
        'tenant_id' => $tenant->id,
        'url' => 'https://example.com/webhook-destination',
        'event_type' => 'job_booked',
        'secret_key' => 'super-secret-key-123',
        'is_active' => true,
    ]);

    $payload = ['job_id' => 99, 'amount' => 150.00];

    // Assert dispatching dispatches queued job
    Queue::fake();

    $notificationService = app(WebhookNotificationService::class);
    $notificationService->dispatchWebhookEvent($tenant, 'job_booked', $payload);

    Queue::assertPushed(SendWebhookEventJob::class, function ($job) use ($webhook, $payload) {
        return $job->webhook->id === $webhook->id &&
            $job->payload === $payload &&
            $job->connection === 'high-priority';
    });
});

test('SendWebhookEventJob delivers signed HTTP payload and enforces tenant boundaries', function () {
    Http::fake([
        'https://example.com/webhook-destination' => Http::response([], 200),
    ]);

    $tenant = Tenant::factory()->create(['name' => 'Webhook Boundary Tenant']);
    TenantScope::setTenantId($tenant->id);

    $webhook = TenantWebhook::create([
        'tenant_id' => $tenant->id,
        'url' => 'https://example.com/webhook-destination',
        'event_type' => 'job_booked',
        'secret_key' => 'super-secret-key-123',
        'is_active' => true,
    ]);

    $payload = ['job_id' => 99];
    $jsonBody = json_encode($payload);
    $expectedSignature = hash_hmac('sha256', $jsonBody, 'super-secret-key-123');

    // Run job synchronously
    $job = new SendWebhookEventJob($webhook, $payload);
    $job->handle();

    Http::assertSent(function (Request $request) use ($expectedSignature) {
        return $request->url() === 'https://example.com/webhook-destination' &&
            $request->hasHeader('X-Webhook-Signature', $expectedSignature) &&
            $request['job_id'] === 99;
    });
});

test('Authenticated users can load Streak Calendar Hub', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('admin.streak-hub'));

    $response->assertOk();
});
