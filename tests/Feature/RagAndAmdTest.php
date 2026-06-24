<?php

use App\Jobs\SendTechnicianAlertJob;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeChunk;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\RAGKnowledgeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    TenantScope::setTenantId(null);
    session()->forget('tenant_id');
});

test('str toembeddings macro generates deterministic unit vector', function () {
    $text = 'HVAC unit error code 4';
    $vector = Str::toEmbeddings($text);

    expect($vector)->toBeArray()
        ->and(count($vector))->toBe(1536);

    // Verify it is a unit vector (magnitude is approx 1)
    $sumOfSquares = 0.0;
    foreach ($vector as $val) {
        $sumOfSquares += $val * $val;
    }
    expect(sqrt($sumOfSquares))->toBeGreaterThan(0.99)
        ->toBeLessThan(1.01);

    // Verify Stringable macro works
    $vector2 = Str::of($text)->toEmbeddings();
    expect($vector2)->toBeArray()
        ->and(count($vector2))->toBe(1536)
        ->and($vector2[0])->toBe($vector[0]);
});

test('rag knowledge service ingests and searches documents with rank decay', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $kb = KnowledgeBase::factory()->create(['tenant_id' => $tenant->id]);
    $service = new RAGKnowledgeService;

    $text = 'This is segment one of manual. Air conditioners require filter cleaning. '.
            'This is segment two of manual. Heat pumps work by shifting thermal energy. '.
            'This is segment three of manual. Thermostats control temperature triggers.';

    // Ingest text into KB
    $service->ingest($kb, $text, 80, 10);

    // Assert chunks were created in database
    expect(KnowledgeChunk::count())->toBeGreaterThan(1);

    // Perform search
    $results = $service->search($tenant, 'filter cleaning', 3, 2.0);

    expect($results)->toBeArray()
        ->and(count($results))->toBeGreaterThan(0);

    // Assert the first result has rank = 0 and score equals similarity
    $first = $results[0];
    expect($first['rank'])->toBe(0)
        ->and($first['score'])->toBe($first['similarity']);

    // If there is a second result, check its rank and score decay
    if (count($results) > 1) {
        $second = $results[1];
        expect($second['rank'])->toBe(1);

        $expectedDecay = 1.0 - (log(2.0) / (1.0 + 2.0));
        expect(abs($second['score'] - ($second['similarity'] * $expectedDecay)))->toBeLessThan(0.0001);
    }
});

test('outbound technician alert job passes amd config and maps call_id', function () {
    SendTechnicianAlertJob::$shouldRunInTests = true;

    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'notification_preference' => 'voice',
        'phone' => '+15551234567',
    ]);

    $booking = Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'status' => 'registered',
        'scheduled_start' => now()->addDay(),
    ]);

    // Test with Vapi provider
    config(['services.telephony.provider' => 'vapi']);
    Http::fake([
        'api.vapi.ai/call' => Http::response(['id' => 'vapi_call_999'], 200),
    ]);

    // Run job
    $job = new SendTechnicianAlertJob($booking);
    $job->handle();

    // Verify request payload included AMD
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.vapi.ai/call' &&
            $request['answeringMachineDetectionConfiguration']['enabled'] === true;
    });

    // Verify call_id is mapped to booking_id in cache
    expect(Cache::get('call_booking_map:vapi_call_999'))->toBe($booking->id);

    // Verify booking status remained notifying
    $booking->refresh();
    expect($booking->status)->toBe('notifying');
});

test('webhook amd payloads trigger voicemail drops and update booking status', function () {
    $tenant = Tenant::factory()->create([
        'secret_key' => 'test-secret-key',
    ]);
    TenantScope::setTenantId($tenant->id);

    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'phone' => '+15558889999',
    ]);

    $booking = Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'status' => 'notifying',
        'scheduled_start' => now()->addDays(2),
    ]);

    // Map call ID to booking in cache
    $callId = 'test-call-amd-123';
    Cache::put("call_booking_map:{$callId}", $booking->id, 600);

    // 1. Test AMD Machine detection webhook
    config(['services.telephony.provider' => 'vapi']);
    Http::fake([
        "api.vapi.ai/call/{$callId}/voicemail-drop" => Http::response(['success' => true], 200),
    ]);

    $payloadMachine = [
        'type' => 'call.updated',
        'call' => [
            'id' => $callId,
            'answeringMachineDetectionResult' => 'machine',
            'customer_phone_number' => $employee->phone,
        ],
    ];

    $response = $this->withHeaders([
        'X-Vapi-Secret' => 'test-secret-key',
    ])->postJson(route('webhook.call-events', ['tenant_id' => $tenant->id]), $payloadMachine);

    $response->assertStatus(200);

    // Verify voicemail drop API was called
    Http::assertSent(function ($request) use ($callId) {
        return $request->url() === "https://api.vapi.ai/call/{$callId}/voicemail-drop" &&
            str_contains($request['message'], 'Hi') &&
            str_contains($request['message'], 'HVAC dispatch');
    });

    // Verify booking transitioned to voicemail_alerted
    $booking->refresh();
    expect($booking->status)->toBe('voicemail_alerted');

    // Reset booking state for human test
    $booking->status = 'notifying';
    $booking->save();

    // 2. Test AMD Human detection webhook
    $payloadHuman = [
        'type' => 'call.updated',
        'call' => [
            'id' => $callId,
            'answeringMachineDetectionResult' => 'human',
            'customer_phone_number' => $employee->phone,
        ],
    ];

    $response2 = $this->withHeaders([
        'X-Vapi-Secret' => 'test-secret-key',
    ])->postJson(route('webhook.call-events', ['tenant_id' => $tenant->id]), $payloadHuman);

    $response2->assertStatus(200);

    // Verify booking transitioned to booked
    $booking->refresh();
    expect($booking->status)->toBe('booked');
});
