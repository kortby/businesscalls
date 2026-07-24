<?php

use App\Models\Availability;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

beforeEach(function () {
    TenantScope::setTenantId(null);
});

test('availability webhook returns the first 3 technician availability options', function () {
    $tenant = Tenant::factory()->create(['secret_key' => null]);
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['plumbing'],
    ]);

    // Create active availability shifts for today, tomorrow, and day after
    $today = Carbon::today();
    for ($i = 0; $i < 3; $i++) {
        $day = $today->copy()->addDays($i + 1);
        Availability::factory()->create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'day_of_week' => $day->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_active' => true,
        ]);
    }

    $payload = [
        'tenant_id' => $tenant->id,
        'service_type' => 'plumbing',
    ];

    $response = $this->postJson('/api/webhooks/availabilities', $payload);

    $response->assertOk();
    $response->assertJsonPath('status', 'success');
    $response->assertJsonPath('count', 3);
    expect(count($response->json('options')))->toBe(3);
    expect($response->json('message'))->toContain('The first 3 available technician options are:');
});

test('availability webhook supports Vapi toolCall format', function () {
    $tenant = Tenant::factory()->create(['secret_key' => null]);
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['hvac'],
    ]);

    $tomorrow = Carbon::today()->addDay();
    Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => $tomorrow->dayOfWeek,
        'start_time' => '10:00:00',
        'end_time' => '16:00:00',
        'is_active' => true,
    ]);

    $vapiPayload = [
        'message' => [
            'type' => 'tool-calls',
            'toolCalls' => [
                [
                    'id' => 'vapi-tool-call-123',
                    'type' => 'function',
                    'function' => [
                        'name' => 'get_first_three_availabilities',
                        'arguments' => [
                            'tenant_id' => $tenant->id,
                            'service_type' => 'hvac',
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
                    'count',
                    'options',
                    'message',
                ],
            ],
        ],
    ]);
    $response->assertJsonPath('results.0.toolCallId', 'vapi-tool-call-123');
});
