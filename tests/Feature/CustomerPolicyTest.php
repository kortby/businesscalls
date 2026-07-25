<?php

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;

test('admin and supervisor can create, update, delete, and import customers', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => 'admin',
        'is_supervisor' => true,
    ]);

    // View customer list
    $response = $this->actingAs($admin)->get(route('customers.index'));
    $response->assertOk();

    // Create customer
    $response = $this->actingAs($admin)->post(route('customers.store'), [
        'name' => 'Jane Doe',
        'phone' => '+15551110000',
        'email' => 'jane@example.com',
        'notes' => 'VIP client',
    ]);
    $response->assertRedirect();
    expect(Customer::where('tenant_id', $tenant->id)->where('name', 'Jane Doe')->exists())->toBeTrue();

    $customer = Customer::where('tenant_id', $tenant->id)->where('name', 'Jane Doe')->first();

    // Update customer
    $response = $this->actingAs($admin)->put(route('customers.update', $customer->id), [
        'name' => 'Jane Doe Updated',
        'phone' => '+15551110000',
        'email' => 'jane.updated@example.com',
        'notes' => 'Updated notes',
    ]);
    $response->assertRedirect();
    expect($customer->fresh()->name)->toBe('Jane Doe Updated');

    // Import CSV
    $csvContent = "name,phone,email,notes\nMark Smith,+15552223333,mark@example.com,CSV test";
    $file = UploadedFile::fake()->createWithContent('customers.csv', $csvContent);

    $response = $this->actingAs($admin)->post(route('customers.import'), [
        'csv_file' => $file,
    ]);
    $response->assertRedirect();
    expect(Customer::where('tenant_id', $tenant->id)->where('name', 'Mark Smith')->exists())->toBeTrue();

    // Delete customer
    $response = $this->actingAs($admin)->delete(route('customers.destroy', $customer->id));
    $response->assertRedirect();
    expect(Customer::where('id', $customer->id)->exists())->toBeFalse();
});

test('technician can create and update customers but is forbidden from deleting or importing', function () {
    $tenant = Tenant::factory()->create();
    $technician = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => 'technician',
        'is_supervisor' => false,
    ]);

    $customer = Customer::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Tech Client',
        'phone' => '+15559990000',
    ]);

    // Create -> Allowed
    $response = $this->actingAs($technician)->post(route('customers.store'), [
        'name' => 'Field Client',
        'phone' => '+15558887777',
        'email' => 'field@example.com',
    ]);
    $response->assertRedirect();
    expect(Customer::where('tenant_id', $tenant->id)->where('name', 'Field Client')->exists())->toBeTrue();

    // Update -> Allowed
    $response = $this->actingAs($technician)->put(route('customers.update', $customer->id), [
        'name' => 'Tech Client Renamed',
        'phone' => '+15559990000',
        'email' => 'tech.client@example.com',
    ]);
    $response->assertRedirect();
    expect($customer->fresh()->name)->toBe('Tech Client Renamed');

    // Delete -> Forbidden
    $response = $this->actingAs($technician)->delete(route('customers.destroy', $customer->id));
    $response->assertForbidden();

    // Import -> Forbidden
    $csvContent = "name,phone\nUnauthorized,+15550000000";
    $file = UploadedFile::fake()->createWithContent('unauthorized.csv', $csvContent);

    $response = $this->actingAs($technician)->post(route('customers.import'), [
        'csv_file' => $file,
    ]);
    $response->assertForbidden();
});

test('cross-tenant customer access is scoped or rejected', function () {
    $tenantA = Tenant::factory()->create();
    $userA = User::factory()->create(['tenant_id' => $tenantA->id, 'role' => 'admin']);

    $tenantB = Tenant::factory()->create();
    $customerB = Customer::factory()->create(['tenant_id' => $tenantB->id]);

    $response = $this->actingAs($userA)->put(route('customers.update', $customerB->id), [
        'name' => 'Cross Tenant Edit',
        'phone' => $customerB->phone,
    ]);

    expect(in_array($response->status(), [403, 404]))->toBeTrue();
});
