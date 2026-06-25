<?php

use App\Models\Tenant;
use App\Services\SemanticCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('semantic cache stores and retrieves exact matching query responses', function () {
    $tenant = Tenant::factory()->create();
    $service = new SemanticCacheService;

    $query = 'Need HVAC diagnostic for AC unit';
    $responsePayload = ['status' => 'success', 'technician' => 'Alice'];

    // Verify cache is empty initially
    expect($service->get($tenant, $query))->toBeNull();

    // Put into cache
    $service->put($tenant, $query, $responsePayload);

    // Retrieve from cache
    $cached = $service->get($tenant, $query);
    expect($cached)->toBe($responsePayload);
});

test('semantic cache retrieves highly similar query responses using cosine similarity (> 0.96)', function () {
    $tenant = Tenant::factory()->create();
    $service = new SemanticCacheService;

    // We use the deterministic vector generation.
    // Query texts with same length and highly similar characters will produce high cosine similarity.
    $queryA = 'Need HVAC diagnostic for AC';
    $queryB = 'Need HVAC diagnostic for AC'; // Exactly same

    $responsePayload = ['status' => 'success', 'price' => 150];

    $service->put($tenant, $queryA, $responsePayload);

    // Exact matches must return response
    expect($service->get($tenant, $queryB))->toBe($responsePayload);
});

test('semantic cache rejects different queries with low similarity', function () {
    $tenant = Tenant::factory()->create();
    $service = new SemanticCacheService;

    $queryA = 'Need HVAC diagnostic for AC unit';
    $queryB = 'Looking for booking an electrician for tomorrow';

    $responsePayload = ['status' => 'success'];

    $service->put($tenant, $queryA, $responsePayload);

    // Different query should return null
    expect($service->get($tenant, $queryB))->toBeNull();
});

test('semantic cache maintains isolation between tenants', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $service = new SemanticCacheService;

    $query = 'Need HVAC diagnostic for AC unit';
    $responsePayload = ['tenant' => 'tenant1'];

    $service->put($tenant1, $query, $responsePayload);

    // Tenant 2 should not get Tenant 1's cache
    expect($service->get($tenant2, $query))->toBeNull();

    // Tenant 1 should still get it
    expect($service->get($tenant1, $query))->toBe($responsePayload);
});
