<?php

use App\Models\Customer;
use App\Models\Employee;
use App\Models\ServiceJob;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can view jobs list', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $customer = Customer::create([
        'tenant_id' => $tenant->id,
        'name' => 'SpongeBob SquarePants',
        'phone' => '+15551111111',
    ]);

    ServiceJob::create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'title' => 'Fix spatula handle',
        'description' => 'Spatula handle is broken',
        'status' => 'pending',
        'steps' => ['Inspect spatula', 'Order part'],
    ]);

    $response = $this->actingAs($user)->get(route('jobs.index'));

    $response->assertStatus(200)
        ->assertSee('Fix spatula handle')
        ->assertSee('SpongeBob SquarePants');
});

test('user can create a job with initial steps', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $customer = Customer::create([
        'tenant_id' => $tenant->id,
        'name' => 'Patrick Star',
        'phone' => '+15552222222',
    ]);

    $employee = Employee::create([
        'tenant_id' => $tenant->id,
        'first_name' => 'Squidward',
        'last_name' => 'Tentacles',
        'phone' => '555-333-3333',
        'skills' => [],
        'notification_preference' => 'sms',
    ]);

    $response = $this->actingAs($user)->post(route('jobs.store'), [
        'customer_id' => $customer->id,
        'employee_id' => $employee->id,
        'title' => 'Fix sand house roof',
        'description' => 'Leaking sand',
        'status' => 'in_progress',
        'steps' => ['Scaffold the dome', 'Apply adhesive sealant'],
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('service_jobs', [
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'employee_id' => $employee->id,
        'title' => 'Fix sand house roof',
        'status' => 'in_progress',
    ]);

    $job = ServiceJob::first();
    $this->assertEquals(['Scaffold the dome', 'Apply adhesive sealant'], $job->steps);
});

test('user can update job status and modify steps list', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $customer = Customer::create([
        'tenant_id' => $tenant->id,
        'name' => 'Sandy Cheeks',
        'phone' => '+15554444444',
    ]);

    $job = ServiceJob::create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'title' => 'Calibrate air helmet',
        'status' => 'pending',
        'steps' => ['Inspect pressure valve'],
    ]);

    $response = $this->actingAs($user)->put(route('jobs.update', $job->id), [
        'customer_id' => $customer->id,
        'employee_id' => null,
        'title' => 'Calibrate air helmet - Done',
        'description' => 'Pressure holds up fine now.',
        'status' => 'completed',
        'steps' => ['Inspect pressure valve', 'Tighten gasket seals', 'Re-pressurized and verified correct PSI levels'],
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('service_jobs', [
        'id' => $job->id,
        'title' => 'Calibrate air helmet - Done',
        'status' => 'completed',
    ]);

    $job->refresh();
    $this->assertEquals([
        'Inspect pressure valve',
        'Tighten gasket seals',
        'Re-pressurized and verified correct PSI levels',
    ], $job->steps);
});

test('user cannot access or edit another tenant jobs', function () {
    $tenantA = Tenant::factory()->create();
    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);

    $tenantB = Tenant::factory()->create();
    $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

    $customerB = Customer::create([
        'tenant_id' => $tenantB->id,
        'name' => 'Plankton',
        'phone' => '+15559999999',
    ]);

    $jobB = ServiceJob::create([
        'tenant_id' => $tenantB->id,
        'customer_id' => $customerB->id,
        'title' => 'Chum Bucket maintenance',
        'status' => 'pending',
        'steps' => [],
    ]);

    // 1. User A should not see Tenant B's job
    $response = $this->actingAs($userA)->get(route('jobs.index'));
    $response->assertStatus(200);
    $response->assertDontSee('Chum Bucket maintenance');

    // 2. User A should get 404 trying to update/delete Tenant B's job due to tenant scope isolation
    $response = $this->actingAs($userA)->put(route('jobs.update', $jobB->id), [
        'customer_id' => $customerB->id,
        'title' => 'Hacked title',
        'status' => 'completed',
    ]);
    $response->assertStatus(404);

    $response = $this->actingAs($userA)->delete(route('jobs.destroy', $jobB->id));
    $response->assertStatus(404);
});
