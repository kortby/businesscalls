<?php

use App\Mcp\Servers\BusinessCallsServer;
use App\Mcp\Tools\BookAppointmentTool;
use App\Mcp\Tools\CancelBookingTool;
use App\Mcp\Tools\CheckAvailabilityTool;
use App\Mcp\Tools\CheckInventoryTool;
use App\Mcp\Tools\CheckTechnicianEtaTool;
use App\Mcp\Tools\DispatchTechnicianTool;
use App\Mcp\Tools\LookupBookingTool;
use App\Mcp\Tools\RescheduleAppointmentTool;
use App\Models\Availability;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

beforeEach(function () {
    TenantScope::setTenantId(null);
    session()->forget('tenant_id');
});

test('business calls mcp server lists primary tools and tool search tools', function () {
    $toolsResponse = BusinessCallsServer::tools();

    // Primary tools directly registered
    $toolsResponse->assertRegistered([
        CheckAvailabilityTool::class,
        BookAppointmentTool::class,
        LookupBookingTool::class,
    ]);

    // Catalog tools should NOT be registered at the top level
    $toolsResponse->assertNotRegistered([
        CheckInventoryTool::class,
        RescheduleAppointmentTool::class,
        CancelBookingTool::class,
        CheckTechnicianEtaTool::class,
        DispatchTechnicianTool::class,
    ]);
});

test('business calls mcp server executes primary tools directly', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'skills' => ['plumbing'],
    ]);

    Availability::create([
        'employee_id' => $employee->id,
        'day_of_week' => 1,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    $checkResponse = BusinessCallsServer::tool(CheckAvailabilityTool::class, [
        'tenant_id' => (string) $tenant->id,
        'service_type' => 'plumbing',
    ]);

    $checkResponse->assertOk();
    $checkResponse->assertSee('Available technician options');
});

test('business calls mcp server books appointment directly', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'first_name' => 'Mark',
        'last_name' => 'Miller',
    ]);

    Availability::create([
        'employee_id' => $employee->id,
        'day_of_week' => 1,
        'start_time' => '08:00:00',
        'end_time' => '18:00:00',
        'is_active' => true,
    ]);

    $bookingTime = Carbon::now()->next(Carbon::MONDAY)->setTime(9, 0, 0);

    $bookResponse = BusinessCallsServer::tool(BookAppointmentTool::class, [
        'tenant_id' => (string) $tenant->id,
        'customer_phone' => '+15559990000',
        'job_details' => 'Water pipe inspection',
        'scheduled_start' => $bookingTime->toIso8601String(),
        'employee_id' => $employee->id,
    ]);

    $bookResponse->assertOk();
    $bookResponse->assertSee('confirmed');
});

test('business calls mcp server handles search and execute over HTTP JSON-RPC protocol', function () {
    $this->withoutExceptionHandling();

    $tenant = Tenant::factory()->create([
        'settings' => [
            'inventory' => [
                'faucet' => 8,
                'pipe' => 0,
            ],
        ],
    ]);

    // 1. tools/list via HTTP POST /mcp/businesscalls
    $listResponse = $this->postJson('/mcp/businesscalls', [
        'jsonrpc' => '2.0',
        'method' => 'tools/list',
        'id' => 1,
    ]);

    $listResponse->assertOk();
    $tools = $listResponse->json('result.tools');
    $toolNames = array_column($tools, 'name');

    expect($toolNames)->toContain('check_availability')
        ->toContain('book_appointment')
        ->toContain('lookup_booking')
        ->toContain('search_tools')
        ->toContain('execute_tools');

    // 2. tools/call search_tools via HTTP
    $searchResponse = $this->postJson('/mcp/businesscalls', [
        'jsonrpc' => '2.0',
        'method' => 'tools/call',
        'params' => [
            'name' => 'search_tools',
            'arguments' => [
                'query' => 'inventory',
            ],
        ],
        'id' => 2,
    ], [
        'Accept' => 'application/json, text/event-stream',
    ]);

    $searchResponse->assertOk();
    $searchText = $searchResponse->json('result.content.0.text');
    $catalogData = json_decode($searchText, true);
    expect($catalogData['ok'])->toBeTrue();
    $catalogToolNames = array_column($catalogData['tools'], 'name');
    expect($catalogToolNames)->toContain('check_inventory');

    // 3. tools/call execute_tools via HTTP
    $executeResponse = $this->postJson('/mcp/businesscalls', [
        'jsonrpc' => '2.0',
        'method' => 'tools/call',
        'params' => [
            'name' => 'execute_tools',
            'arguments' => [
                'calls' => [
                    [
                        'name' => 'check_inventory',
                        'arguments' => [
                            'part_name' => 'faucet',
                            'tenant_id' => (string) $tenant->id,
                        ],
                    ],
                ],
            ],
        ],
        'id' => 3,
    ]);

    $executeResponse->assertOk();
    $streamContent = $executeResponse->streamedContent();
    expect($streamContent)->toContain("The part 'faucet' is in stock. Current quantity: 8.");
});
