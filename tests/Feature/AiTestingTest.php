<?php

use App\Models\Availability;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\AiTestingService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    TenantScope::setTenantId(null);
});

test('ai testing service calculates accuracy index correctly and logs results', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    // Create a technician who is only available for HVAC
    $hvacTech = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['hvac'],
    ]);

    // Set shift availability for hvac on Tue, Wed, Thu, Fri (which matches default scenarios)
    foreach ([1, 2, 3, 4] as $day) {
        Availability::factory()->create([
            'tenant_id' => $tenant->id,
            'employee_id' => $hvacTech->id,
            'day_of_week' => $day,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_active' => true,
        ]);
    }

    // Plumbing tech is NOT created, so plumbing scenarios will fail, while HVAC will succeed
    // Default scenarios from service:
    // 1. HVAC, weight 3 -> should succeed (mu=1)
    // 2. Plumbing, weight 1 -> should fail (mu=0)
    // 3. HVAC, weight 2 -> should succeed (mu=1)
    // 4. Plumbing, weight 3 -> should fail (mu=0)
    // Formula: sum(mu_i * w_i) / N = (1*3 + 0*1 + 1*2 + 0*3) / 4 = 5 / 4 = 1.25

    Http::fake([
        'api.vapi.ai/*' => Http::response(['status' => 'success']),
    ]);

    $service = new AiTestingService;
    $results = $service->runBatchSimulation($tenant);

    expect($results['total_cases'])->toBe(4)
        ->and($results['accuracy_index'])->toBe(1.25)
        ->and($results['results'][0]['outcome'])->toBe(1)
        ->and($results['results'][1]['outcome'])->toBe(0);

    // Refresh tenant to check if logged
    $tenant->refresh();
    $simulations = $tenant->getSetting('qa_simulations');
    expect($simulations)->toBeArray()
        ->and(count($simulations))->toBe(1)
        ->and($simulations[0]['accuracy_index'])->toBe(1.25);
});

test('console command ai:simulate-batch runs successfully', function () {
    $tenant = Tenant::factory()->create();

    $this->artisan('ai:simulate-batch', ['tenant_id' => $tenant->id])
        ->assertExitCode(0);
});
