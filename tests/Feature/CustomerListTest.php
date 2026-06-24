<?php

use App\Models\Booking;
use App\Models\CallLog;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

test('authenticated user can view customers aggregated list', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $employee = Employee::create([
        'tenant_id' => $tenant->id,
        'first_name' => 'Eugene',
        'last_name' => 'Krabs',
        'phone' => '555-555-5555',
        'skills' => [],
        'notification_preference' => 'sms',
    ]);

    // Create a booking with customer phone
    Booking::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'customer_phone' => '+15551112222',
        'job_details' => 'Faucet replacement',
        'status' => 'booked',
        'scheduled_start' => now(),
    ]);

    // Create a call log with customer phone
    CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-crm-cust-1',
        'customer_phone' => '+15551112222',
        'status' => 'ended',
        'summary' => json_encode([
            'caller_name' => 'Eugene Krabs',
            'sentiment' => 'Neutral',
            'summary' => 'Krabs calls about plumbing',
        ]),
    ]);

    $response = $this->actingAs($user)->get(route('customers.index'));

    $response->assertStatus(200)
        ->assertSee('+15551112222')
        ->assertSee('Eugene Krabs');
});

test('user can add customer with validation', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    // 1. Missing name validation
    $response = $this->actingAs($user)->post(route('customers.store'), [
        'name' => '',
        'phone' => '+15559990001',
    ]);
    $response->assertSessionHasErrors(['name']);

    // 2. Successful customer creation
    $response = $this->actingAs($user)->post(route('customers.store'), [
        'name' => 'SpongeBob SquarePants',
        'phone' => '+15559990001',
        'email' => 'sponge@bikinibottom.com',
        'notes' => 'Loves jellyfishing',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('customers', [
        'tenant_id' => $tenant->id,
        'name' => 'SpongeBob SquarePants',
        'phone' => '+15559990001',
        'email' => 'sponge@bikinibottom.com',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'tenant_id' => $tenant->id,
        'action' => 'customer_created',
    ]);

    // 3. Unique phone validation per tenant
    $response = $this->actingAs($user)->post(route('customers.store'), [
        'name' => 'Patrick Star',
        'phone' => '+15559990001',
    ]);
    $response->assertSessionHasErrors(['phone']);

    // 4. Same phone is allowed on a different tenant
    $otherTenant = Tenant::factory()->create();
    $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

    $response = $this->actingAs($otherUser)->post(route('customers.store'), [
        'name' => 'Patrick Star',
        'phone' => '+15559990001',
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('customers', [
        'tenant_id' => $otherTenant->id,
        'name' => 'Patrick Star',
        'phone' => '+15559990001',
    ]);
});

test('user can import customers from csv file', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $csvContent = "name,phone,email,notes\n";
    $csvContent .= "Gary Snail,+15552223333,gary@snails.com,Meow\n";
    $csvContent .= "Plankton,+15554445555,,Stealing formula\n";

    $file = UploadedFile::fake()->createWithContent('customers.csv', $csvContent);

    $response = $this->actingAs($user)->post(route('customers.import'), [
        'csv_file' => $file,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('customers', [
        'tenant_id' => $tenant->id,
        'name' => 'Gary Snail',
        'phone' => '+15552223333',
        'email' => 'gary@snails.com',
        'notes' => 'Meow',
    ]);

    $this->assertDatabaseHas('customers', [
        'tenant_id' => $tenant->id,
        'name' => 'Plankton',
        'phone' => '+15554445555',
        'email' => null,
        'notes' => 'Stealing formula',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'tenant_id' => $tenant->id,
        'action' => 'customers_imported',
    ]);
});

test('import csv validation fails on missing headers', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $csvContent = "email,notes\n";
    $csvContent .= "gary@snails.com,Meow\n";

    $file = UploadedFile::fake()->createWithContent('customers.csv', $csvContent);

    $response = $this->actingAs($user)->post(route('customers.import'), [
        'csv_file' => $file,
    ]);

    $response->assertRedirect();

    // Ensure no customers were imported
    $this->assertEquals(0, Customer::count());
});
