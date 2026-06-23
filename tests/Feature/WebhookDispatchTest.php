<?php

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

beforeEach(function () {
    TenantScope::setTenantId(null);
});

test('webhook requires HMAC signature validation if tenant has a secret key', function () {
    $tenant = Tenant::factory()->create([
        'secret_key' => 'super-secret',
    ]);

    $payload = [
        'tenant_id' => $tenant->id,
        'customer_phone' => '+15550001111',
        'service_type' => 'plumbing',
        'requested_time' => '2026-06-23 10:00:00',
    ];

    // Request with invalid signature
    $response = $this->postJson('/api/webhooks/dispatch', $payload, [
        'x-signature' => 'invalid-signature',
    ]);

    $response->assertStatus(401);

    // Request with valid signature
    $rawPayload = json_encode($payload);
    $validSignature = hash_hmac('sha256', $rawPayload, 'super-secret');

    // Set up matching technician to avoid 422
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['plumbing'],
    ]);

    $dayOfWeek = Carbon::parse('2026-06-23 10:00:00')->dayOfWeek;
    Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => $dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    $response = $this->call('POST', '/api/webhooks/dispatch', [], [], [], [
        'HTTP_X_SIGNATURE' => $validSignature,
        'CONTENT_TYPE' => 'application/json',
    ], $rawPayload);

    $response->assertOk();
    $response->assertJsonPath('status', 'success');
});

test('webhook dispatches successfully when technician is available and has no overlaps', function () {
    $tenant = Tenant::factory()->create(['secret_key' => null]);
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['AC diagnostics'],
    ]);

    $requestedTime = '2026-06-23 14:00:00';
    $dayOfWeek = Carbon::parse($requestedTime)->dayOfWeek;

    Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => $dayOfWeek,
        'start_time' => '12:00:00',
        'end_time' => '18:00:00',
        'is_active' => true,
    ]);

    $payload = [
        'tenant_id' => $tenant->id,
        'customer_phone' => '+15559999999',
        'service_type' => 'AC diagnostics',
        'requested_time' => $requestedTime,
    ];

    $response = $this->postJson('/api/webhooks/dispatch', $payload);

    $response->assertOk();
    $response->assertJsonPath('status', 'success');
    expect(Booking::count())->toBe(1)
        ->and(Booking::first()->customer_phone)->toBe('+15559999999');
});

test('webhook fails when requested time is outside technician availability', function () {
    $tenant = Tenant::factory()->create(['secret_key' => null]);
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['plumbing'],
    ]);

    $requestedTime = '2026-06-23 20:00:00'; // Outside shift hours
    $dayOfWeek = Carbon::parse($requestedTime)->dayOfWeek;

    Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => $dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    $payload = [
        'tenant_id' => $tenant->id,
        'customer_phone' => '+15559999999',
        'service_type' => 'plumbing',
        'requested_time' => $requestedTime,
    ];

    $response = $this->postJson('/api/webhooks/dispatch', $payload);

    $response->assertStatus(422);
    $response->assertJsonPath('status', 'error');
    expect(Booking::count())->toBe(0);
});

test('webhook fails when booking overlaps with existing appointment within 1.5 hour buffer', function () {
    $tenant = Tenant::factory()->create(['secret_key' => null]);
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['high-voltage'],
    ]);

    $dayOfWeek = Carbon::parse('2026-06-23 14:00:00')->dayOfWeek;
    Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => $dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    // Create existing booking at 13:00 (1 hour before 14:00, within 1.5h buffer)
    Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'scheduled_start' => '2026-06-23 13:00:00',
        'status' => 'booked',
    ]);

    $payload = [
        'tenant_id' => $tenant->id,
        'customer_phone' => '+15559999999',
        'service_type' => 'high-voltage',
        'requested_time' => '2026-06-23 14:00:00',
    ];

    $response = $this->postJson('/api/webhooks/dispatch', $payload);

    $response->assertStatus(422);
    $response->assertJsonPath('status', 'error');
    expect(Booking::count())->toBe(1); // Only the pre-existing one
});

test('webhook wraps Vapi response standard when payload nested structure is provided', function () {
    $tenant = Tenant::factory()->create(['secret_key' => null]);
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['plumbing'],
    ]);

    $dayOfWeek = Carbon::parse('2026-06-23 10:00:00')->dayOfWeek;
    Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => $dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    $vapiPayload = [
        'message' => [
            'type' => 'tool-calls',
            'toolCalls' => [
                [
                    'id' => 'call-id-abc',
                    'type' => 'function',
                    'function' => [
                        'name' => 'book_appointment',
                        'arguments' => [
                            'tenant_id' => $tenant->id,
                            'customer_phone' => '+15551112222',
                            'service_type' => 'plumbing',
                            'requested_time' => '2026-06-23 10:00:00',
                        ],
                    ],
                ],
            ],
        ],
    ];

    $response = $this->postJson('/api/webhooks/dispatch', $vapiPayload);

    $response->assertOk();
    $response->assertJsonStructure([
        'results' => [
            [
                'toolCallId',
                'result' => [
                    'status',
                    'message',
                    'booking_id',
                    'employee_name',
                    'scheduled_time',
                ],
            ],
        ],
    ]);
    $response->assertJsonPath('results.0.toolCallId', 'call-id-abc');
});
