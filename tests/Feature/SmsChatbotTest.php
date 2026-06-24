<?php

use App\Events\ChatMessageReceived;
use App\Jobs\SendFollowUpSmsJob;
use App\Models\CallLog;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    TenantScope::setTenantId(null);
    session()->forget('tenant_id');
});

test('sms webhook creates conversation and customer message, then returns llm reply in twiml', function () {
    Event::fake([ChatMessageReceived::class]);
    Http::fake([
        'https://api.openai.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => 'Simulated LLM response for customer',
                    ],
                ],
            ],
        ], 200),
    ]);

    $tenant = Tenant::factory()->create(['name' => 'Acme Plumbing']);

    $response = $this->postJson(route('webhook.sms', ['tenant_id' => $tenant->id]), [
        'From' => '+15559876543',
        'Body' => 'Hello! Can you help me fix a leaky pipe?',
    ]);

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
    $this->assertStringContainsString('Simulated LLM response for customer', $response->getContent());

    // Verify database entries
    TenantScope::setTenantId($tenant->id);

    $customer = Customer::where('phone', '+15559876543')->first();
    expect($customer)->not->toBeNull()
        ->and($customer->name)->toBe('SMS User 6543');

    $conversation = Conversation::where('customer_phone', '+15559876543')->first();
    expect($conversation)->not->toBeNull();

    $messages = $conversation->messages()->oldest()->get();
    expect($messages)->toHaveCount(2)
        ->and($messages[0]->sender)->toBe('customer')
        ->and($messages[0]->body)->toBe('Hello! Can you help me fix a leaky pipe?')
        ->and($messages[1]->sender)->toBe('agent')
        ->and($messages[1]->body)->toBe('Simulated LLM response for customer');

    Event::assertDispatched(ChatMessageReceived::class, 2);
});

test('sms webhook supports multilingual prompts automatically', function () {
    Http::fake([
        'https://api.openai.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => 'Hola! Claro que si',
                    ],
                ],
            ],
        ], 200),
    ]);

    $tenant = Tenant::factory()->create();

    TenantScope::setTenantId($tenant->id);
    $customer = Customer::create([
        'tenant_id' => $tenant->id,
        'name' => 'Carlos Hernandez',
        'phone' => '+15551112222',
        'language' => 'es',
    ]);

    $response = $this->postJson(route('webhook.sms', ['tenant_id' => $tenant->id]), [
        'From' => '+15551112222',
        'Body' => 'Hola',
    ]);

    $response->assertOk();
    $this->assertStringContainsString('Hola! Claro que si', $response->getContent());
});

test('follow up sms job dispatches simulated twilio post and avoids duplicate sending', function () {
    Http::fake([
        'https://api.twilio.com/*' => Http::response(['status' => 'queued'], 200),
    ]);

    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-sms-1',
        'status' => 'ended',
        'customer_phone' => '+15559990000',
    ]);

    // Dispatch the job
    SendFollowUpSmsJob::dispatchSync($callLog);

    // Assert that a message was created and sent
    $conversation = Conversation::where('customer_phone', '+15559990000')->first();
    expect($conversation)->not->toBeNull();

    $messages = $conversation->messages()->get();
    expect($messages)->toHaveCount(1)
        ->and($messages[0]->sender)->toBe('agent')
        ->and($messages[0]->body)->toContain('book or check your appointment');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.twilio.com/mock-send-sms'
            && $request['to'] === '+15559990000'
            && str_contains($request['body'], 'book or check your appointment');
    });

    // Reset HTTP mock call logs to verify duplicate prevention
    Http::fake([
        'https://api.twilio.com/*' => Http::response(['status' => 'queued'], 200),
    ]);

    // Dispatch again - should be skipped
    SendFollowUpSmsJob::dispatchSync($callLog);

    // Call count should remain unchanged
    Http::assertNothingSent();
});
