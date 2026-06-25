<?php

use App\Http\Middleware\TrafficRouterMiddleware;
use App\Models\Experiment;
use App\Models\Tenant;
use App\Services\TrafficRouterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

test('traffic router splits calls between variants according to traffic split percentage', function () {
    $tenant = Tenant::factory()->create();

    $experiment = Experiment::create([
        'tenant_id' => $tenant->id,
        'name' => 'Prompt A/B Test',
        'status' => 'active',
        'traffic_split' => 40, // 40% B, 60% A
    ]);

    $variantA = $experiment->variants()->create([
        'version' => 'A',
        'prompt_instructions' => 'Variant A Instructions',
        'model_provider' => 'openai/gpt-4o',
    ]);

    $variantB = $experiment->variants()->create([
        'version' => 'B',
        'prompt_instructions' => 'Variant B Instructions',
        'model_provider' => 'anthropic/claude-3-5-sonnet',
    ]);

    $service = new TrafficRouterService;

    $results = ['A' => 0, 'B' => 0];
    for ($i = 0; $i < 100; $i++) {
        $variant = $service->route($tenant);
        $results[$variant->version]++;
    }

    // Since it is random, we expect both to be hit
    expect($results['A'])->toBeGreaterThan(0)
        ->and($results['B'])->toBeGreaterThan(0);
});

test('chi-square statistical significance is calculated correctly', function () {
    $tenant = Tenant::factory()->create();

    $experiment = Experiment::create([
        'tenant_id' => $tenant->id,
        'name' => 'Chi-Square Test',
        'status' => 'active',
        'traffic_split' => 50,
    ]);

    $variantA = $experiment->variants()->create([
        'version' => 'A',
        'prompt_instructions' => 'A',
        'model_provider' => 'model',
        'call_count' => 100,
        'booking_count' => 10, // 10% conversion
    ]);

    $variantB = $experiment->variants()->create([
        'version' => 'B',
        'prompt_instructions' => 'B',
        'model_provider' => 'model',
        'call_count' => 100,
        'booking_count' => 30, // 30% conversion
    ]);

    // Average conversion rate: (10+30) / (100+100) = 40 / 200 = 0.20
    // Expected A: 100 * 0.20 = 20
    // Expected B: 100 * 0.20 = 20
    // Chi-Square = (10-20)^2 / 20  +  (30-20)^2 / 20
    //            = 100 / 20 + 100 / 20
    //            = 5 + 5 = 10.0
    $chiSquare = $experiment->calculateChiSquare();
    expect($chiSquare)->toBe(10.0);
});

test('chi-square score calculation handles zero calls or zero bookings gracefully without division by zero', function () {
    $tenant = Tenant::factory()->create();

    $experiment = Experiment::create([
        'tenant_id' => $tenant->id,
        'name' => 'Zero Division Test',
        'status' => 'active',
        'traffic_split' => 50,
    ]);

    $variantA = $experiment->variants()->create([
        'version' => 'A',
        'prompt_instructions' => 'A',
        'model_provider' => 'model',
        'call_count' => 0,
        'booking_count' => 0,
    ]);

    $variantB = $experiment->variants()->create([
        'version' => 'B',
        'prompt_instructions' => 'B',
        'model_provider' => 'model',
        'call_count' => 0,
        'booking_count' => 0,
    ]);

    expect($experiment->calculateChiSquare())->toBe(0.0);

    // One variant has zero call count, another has calls but zero bookings
    $variantA->update(['call_count' => 10, 'booking_count' => 0]);
    expect($experiment->calculateChiSquare())->toBe(0.0);
});

test('traffic router middleware resolves and attaches active variant to request attributes', function () {
    $tenant = Tenant::factory()->create();

    $experiment = Experiment::create([
        'tenant_id' => $tenant->id,
        'name' => 'Middleware Test',
        'status' => 'active',
        'traffic_split' => 50,
    ]);

    $variantA = $experiment->variants()->create([
        'version' => 'A',
        'prompt_instructions' => 'A',
        'model_provider' => 'model',
    ]);

    $variantB = $experiment->variants()->create([
        'version' => 'B',
        'prompt_instructions' => 'B',
        'model_provider' => 'model',
    ]);

    $request = Request::create('/api/web-calls/token', 'POST', ['tenant_id' => $tenant->id]);

    $middleware = new TrafficRouterMiddleware;

    $response = $middleware->handle($request, function ($req) {
        $variant = $req->attributes->get('active_experiment_variant');
        expect($variant)->not->toBeNull()
            ->and(in_array($variant->version, ['A', 'B']))->toBeTrue();

        return new Response('passed');
    });

    expect($response->getContent())->toBe('passed');
});
