<?php

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

beforeEach(function () {
    TenantScope::setTenantId(null);
    session()->forget('tenant_id');
});

test('mcp endpoint rejects unauthorized requests', function () {
    $response = $this->postJson(route('mcp.server'), [
        'jsonrpc' => '2.0',
        'method' => 'tools/list',
        'id' => 1,
    ]);

    $response->assertStatus(401);
});

test('mcp endpoint lists available tools for authorized tenant key', function () {
    $tenant = Tenant::factory()->create(['secret_key' => 'mcp-secret-123']);

    $response = $this->postJson(route('mcp.server'), [
        'jsonrpc' => '2.0',
        'method' => 'tools/list',
        'id' => 1,
    ], [
        'Authorization' => 'Bearer mcp-secret-123',
    ]);

    $response->assertOk()
        ->assertJsonPath('result.tools.0.name', 'check_inventory')
        ->assertJsonPath('result.tools.1.name', 'reschedule_appointment');
});

test('mcp check_inventory tool searches tenant settings or returns mock defaults', function () {
    $tenant = Tenant::factory()->create([
        'secret_key' => 'mcp-secret-123',
        'settings' => [
            'inventory' => [
                'faucet' => 4,
                'wrench' => 0,
            ],
        ],
    ]);

    // Test tool call check_inventory (available)
    $response = $this->postJson(route('mcp.server'), [
        'jsonrpc' => '2.0',
        'method' => 'tools/call',
        'params' => [
            'name' => 'check_inventory',
            'arguments' => [
                'part_name' => 'faucet',
            ],
        ],
        'id' => 2,
    ], [
        'Authorization' => 'Bearer mcp-secret-123',
    ]);

    $response->assertOk()
        ->assertJsonPath('result.content.0.text', "The part 'faucet' is in stock. Current quantity: 4.");

    // Test tool call check_inventory (out of stock)
    $response2 = $this->postJson(route('mcp.server'), [
        'jsonrpc' => '2.0',
        'method' => 'tools/call',
        'params' => [
            'name' => 'check_inventory',
            'arguments' => [
                'part_name' => 'wrench',
            ],
        ],
        'id' => 3,
    ], [
        'Authorization' => 'Bearer mcp-secret-123',
    ]);

    $response2->assertOk()
        ->assertJsonPath('result.content.0.text', "The part 'wrench' is out of stock.");
});

test('mcp reschedule_appointment tool enforces availability and travel buffer rules', function () {
    $tenant = Tenant::factory()->create(['secret_key' => 'mcp-secret-123']);
    TenantScope::setTenantId($tenant->id);

    // Create active employee and working availability
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    // Monday (day 1) 08:00 - 17:00
    Availability::create([
        'employee_id' => $employee->id,
        'day_of_week' => 1,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    $newTime = Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0, 0); // Mon 10:00

    $booking = Booking::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'customer_phone' => '+15550001111',
        'job_details' => 'Fix pipe',
        'status' => 'booked',
        'scheduled_start' => $newTime->copy()->subHours(5), // Monday 5 AM (outside availability)
    ]);

    // 1. Reschedule to 10:00 AM (Mon) - should succeed
    $response1 = $this->postJson(route('mcp.server'), [
        'jsonrpc' => '2.0',
        'method' => 'tools/call',
        'params' => [
            'name' => 'reschedule_appointment',
            'arguments' => [
                'booking_id' => $booking->id,
                'new_start_time' => $newTime->toIso8601String(),
            ],
        ],
        'id' => 10,
    ], [
        'Authorization' => 'Bearer mcp-secret-123',
    ]);

    $response1->assertOk()
        ->assertJsonPath('result.content.0.text', "Booking #{$booking->id} has been successfully rescheduled to ".$newTime->toIso8601String().'.');

    $booking->refresh();
    expect($booking->scheduled_start->toIso8601String())->toBe($newTime->toIso8601String());

    // 2. Reschedule to Mon 19:00 (outside shift) - should fail
    $outsideTime = $newTime->copy()->setTime(19, 0, 0);
    $response2 = $this->postJson(route('mcp.server'), [
        'jsonrpc' => '2.0',
        'method' => 'tools/call',
        'params' => [
            'name' => 'reschedule_appointment',
            'arguments' => [
                'booking_id' => $booking->id,
                'new_start_time' => $outsideTime->toIso8601String(),
            ],
        ],
        'id' => 11,
    ], [
        'Authorization' => 'Bearer mcp-secret-123',
    ]);

    $response2->assertOk()
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Rescheduling failed: The technician is not scheduled to work during this shift.');

    // 3. Reschedule to Mon 11:00 (violates 1.5 hour buffer with existing booking at 10:00)
    // First create another booking at Mon 10:00
    $otherBooking = Booking::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'customer_phone' => '+15558888888',
        'job_details' => 'Another job',
        'status' => 'booked',
        'scheduled_start' => $newTime, // Mon 10:00
    ]);

    $conflictTime = $newTime->copy()->addMinutes(60); // Mon 11:00
    $response3 = $this->postJson(route('mcp.server'), [
        'jsonrpc' => '2.0',
        'method' => 'tools/call',
        'params' => [
            'name' => 'reschedule_appointment',
            'arguments' => [
                'booking_id' => $booking->id,
                'new_start_time' => $conflictTime->toIso8601String(),
            ],
        ],
        'id' => 12,
    ], [
        'Authorization' => 'Bearer mcp-secret-123',
    ]);

    $response3->assertOk()
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Rescheduling failed: Conflict with an existing technician appointment (1.5-hour travel buffer enforced).');
});
