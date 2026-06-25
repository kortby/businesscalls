<?php

use App\Models\CallLog;
use App\Models\FailoverLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BackupLlmRouter;
use App\Services\TtsFallbackService;

test('TtsFallbackService triggers Cartesia dynamic voice swap on high latency or outage', function () {
    $tenant = Tenant::factory()->create(['is_test_mode' => true]);
    $service = app(TtsFallbackService::class);

    // Latency within bounds (no swap)
    $result = $service->handleTtsFailure($tenant, 'call-123', 'vapi', 800.0);
    expect($result['triggered'])->toBeFalse();
    expect(FailoverLog::count())->toBe(0);

    // High latency (triggers fallback)
    $result = $service->handleTtsFailure($tenant, 'call-123', 'vapi', 1800.0);
    expect($result['triggered'])->toBeTrue();
    expect($result['primary_provider'])->toBe('elevenlabs');
    expect($result['fallback_provider'])->toBe('cartesia');
    expect(FailoverLog::count())->toBe(1);

    $log = FailoverLog::first();
    expect($log->tenant_id)->toBe($tenant->id);
    expect($log->call_id)->toBe('call-123');
    expect($log->type)->toBe('tts');
    expect($log->primary_provider)->toBe('elevenlabs');
    expect($log->fallback_provider)->toBe('cartesia');
    expect($log->is_successful)->toBeTrue();
});

test('BackupLlmRouter redirects request to local Ollama when primary LLM fails consecutively', function () {
    $tenant = Tenant::factory()->create(['is_test_mode' => true]);
    $router = app(BackupLlmRouter::class);

    // Single failure (no fallback)
    $result = $router->routeLlmRequest($tenant, 'call-456', 'What is the pricing?', 1);
    expect($result['triggered'])->toBeFalse();
    expect($result['provider'])->toBe('openai');
    expect(FailoverLog::count())->toBe(0);

    // Multi failure (triggers fallback to local Ollama)
    $result = $router->routeLlmRequest($tenant, 'call-456', 'What is the pricing?', 2);
    expect($result['triggered'])->toBeTrue();
    expect($result['provider'])->toBe('ollama');
    expect($result['model'])->toBe('llama3');
    expect(FailoverLog::count())->toBe(1);

    $log = FailoverLog::first();
    expect($log->tenant_id)->toBe($tenant->id);
    expect($log->call_id)->toBe('call-456');
    expect($log->type)->toBe('llm');
    expect($log->primary_provider)->toBe('openai');
    expect($log->fallback_provider)->toBe('ollama');
    expect($log->is_successful)->toBeTrue();
});

test('BackupLlmRouter calculates the operational resilience score accurately', function () {
    $tenant = Tenant::factory()->create(['is_test_mode' => true]);
    $router = app(BackupLlmRouter::class);

    // No logs -> 100% resilience
    expect($router->calculateResilienceScore($tenant))->toBe(1.0);

    // Record mock CallLog with duration
    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-789',
        'status' => 'ended',
        'customer_phone' => '123-456-7890',
        'duration' => 200,
        'is_test_mode' => true,
    ]);

    // Record one successful failover log (10 seconds downtime)
    FailoverLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-789',
        'type' => 'llm',
        'primary_provider' => 'openai',
        'fallback_provider' => 'ollama',
        'downtime_seconds' => 10,
        'is_successful' => true,
    ]);

    // Math calculation: (1 - 10 / 200) * (1 / 1) = 0.95
    $score = $router->calculateResilienceScore($tenant);
    expect($score)->toBe(0.95);
});

test('guests are redirected from status-hud page', function () {
    $response = $this->get('/admin/status-hud');
    $response->assertRedirect('/login');
});

test('supervisors can access status-hud and receive inertia payload', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'is_supervisor' => true,
    ]);

    $response = $this->actingAs($user)->get('/admin/status-hud');
    $response->assertOk();
});
