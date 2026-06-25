<?php

use App\Http\Middleware\BlockSuspendedTenantCalls;
use App\Models\CallLog;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

test('middleware allows call when spend usage is below limit', function () {
    $tenant = Tenant::factory()->create([
        'settings' => [
            'spend_limit' => 50.0,
            'blended_rate' => 0.15,
            'voice_assistant_active' => true,
        ],
    ]);

    // Create call logs with total spend below the limit
    // spend = (120/60) * 0.15 = 0.30
    CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-1',
        'customer_phone' => '+15551234567',
        'status' => 'ended',
        'duration' => 120,
        'cost' => 0.30,
        'created_at' => now(),
    ]);

    $request = Request::create('/api/webhooks/call-events/'.$tenant->id, 'POST');
    $request->setRouteResolver(function () use ($tenant) {
        return new class($tenant)
        {
            public function __construct(private $tenant) {}

            public function parameter($key, $default = null)
            {
                return $key === 'tenant_id' ? $this->tenant->id : $default;
            }
        };
    });

    $middleware = new BlockSuspendedTenantCalls;

    $response = $middleware->handle($request, function ($req) {
        return new Response('passed');
    });

    expect($response->getContent())->toBe('passed');

    $tenant->refresh();
    expect($tenant->getSetting('voice_assistant_active'))->toBeTrue();
});

test('middleware blocks call and disables voice assistant when spend usage exceeds limit', function () {
    $tenant = Tenant::factory()->create([
        'settings' => [
            'spend_limit' => 1.0, // Low limit
            'blended_rate' => 0.50,
            'voice_assistant_active' => true,
        ],
    ]);

    // Create call logs with total spend exceeding limit
    // spend = (300/60) * 0.50 = 2.50 > 1.0
    CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-2',
        'customer_phone' => '+15551234567',
        'status' => 'ended',
        'duration' => 300,
        'cost' => 2.50,
        'created_at' => now(),
    ]);

    $request = Request::create('/api/webhooks/call-events/'.$tenant->id, 'POST');
    $request->setRouteResolver(function () use ($tenant) {
        return new class($tenant)
        {
            public function __construct(private $tenant) {}

            public function parameter($key, $default = null)
            {
                return $key === 'tenant_id' ? $this->tenant->id : $default;
            }
        };
    });

    $middleware = new BlockSuspendedTenantCalls;

    $response = $middleware->handle($request, function ($req) {
        return new Response('passed');
    });

    expect($response->getStatusCode())->toBe(402);

    $json = json_decode($response->getContent(), true);
    expect($json['error'])->toBe('Payment Required');
    expect($json['message'])->toContain('spend limit exceeded');

    $tenant->refresh();
    expect($tenant->getSetting('voice_assistant_active'))->toBeFalse();
});
