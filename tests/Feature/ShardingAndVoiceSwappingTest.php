<?php

use App\Http\Middleware\IdentifyTenantDatabaseShard;
use App\Models\Tenant;
use App\Models\TenantShard;
use App\Models\User;
use App\Services\SpeechPacingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('IdentifyTenantDatabaseShard dynamically overrides default Eloquent database connection', function () {
    // 1. Setup master tenant and user
    $tenant = Tenant::factory()->create([
        'name' => 'Acme Contractor Services',
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    // 2. Setup mock tenant database connection settings
    $shard = TenantShard::create([
        'tenant_id' => $tenant->id,
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);

    // 3. Setup middleware request
    $request = Request::create('/api/jobs', 'GET');
    $request->setUserResolver(fn () => $user);

    $middleware = new IdentifyTenantDatabaseShard;

    // Default connection before middleware
    $defaultBefore = DB::getDefaultConnection();

    $response = $middleware->handle($request, function ($req) {
        // Assert dynamic connection swap during request lifecycle
        expect(DB::getDefaultConnection())->toBe('tenant');
        expect(config('database.connections.tenant.driver'))->toBe('sqlite');
        expect(config('database.connections.tenant.database'))->toBe(':memory:');

        return response('success');
    });

    expect($response->getContent())->toBe('success');

    // Clean up connections
    DB::setDefaultConnection($defaultBefore);
});

test('IdentifyTenantDatabaseShard resolves tenant_id from headers when unauthenticated', function () {
    $tenant = Tenant::factory()->create();

    TenantShard::create([
        'tenant_id' => $tenant->id,
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);

    $request = Request::create('/api/telephony/webhook', 'POST');
    $request->headers->set('X-Tenant-ID', $tenant->id);

    $middleware = new IdentifyTenantDatabaseShard;
    $defaultBefore = DB::getDefaultConnection();

    $response = $middleware->handle($request, function ($req) {
        expect(DB::getDefaultConnection())->toBe('tenant');

        return response('success');
    });

    expect($response->getContent())->toBe('success');
    DB::setDefaultConnection($defaultBefore);
});

test('SpeechPacingService outputs and sends correct voice configurations based on distress sentiment', function () {
    Http::fake();

    $service = new SpeechPacingService;

    // 1. Test Vapi Emergency Voice (Domi)
    $resultVapiEmergency = $service->evaluateAndSwapVoice('vapi', 'call-123', 'emergency');

    expect($resultVapiEmergency['success'])->toBeTrue();
    expect($resultVapiEmergency['payload']['assistantOverrides']['voice']['voiceId'])->toBe('AZnzlk1XvdvUeBnXmlld');

    Http::assertSent(function (Illuminate\Http\Client\Request $request) {
        return $request->url() === 'https://api.vapi.ai/call/call-123' &&
            $request->method() === 'PATCH' &&
            $request['assistantOverrides']['voice']['voiceId'] === 'AZnzlk1XvdvUeBnXmlld';
    });

    // 2. Test Vapi Friendly Voice (Rachel)
    $resultVapiFriendly = $service->evaluateAndSwapVoice('vapi', 'call-123', 'normal');
    expect($resultVapiFriendly['payload']['assistantOverrides']['voice']['voiceId'])->toBe('21m00Tcm4TlvDq8ikWAM');

    // 3. Test Retell Emergency Voice (Domi)
    $resultRetellEmergency = $service->evaluateAndSwapVoice('retell', 'call-456', 'panic');
    expect($resultRetellEmergency['payload']['voice_id'])->toBe('11labs-Domi');

    Http::assertSent(function (Illuminate\Http\Client\Request $request) {
        return $request->url() === 'https://api.retellai.com/v2/calls/call-456' &&
            $request->method() === 'POST' &&
            $request['voice_id'] === '11labs-Domi';
    });

    // 4. Test Retell Friendly Voice (Rachel)
    $resultRetellFriendly = $service->evaluateAndSwapVoice('retell', 'call-456', 'pleasant');
    expect($resultRetellFriendly['payload']['voice_id'])->toBe('11labs-Rachel');
});
