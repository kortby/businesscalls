<?php

use App\Jobs\TriggerWorkflowIntegrationJob;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\TenantIntegration;
use App\Services\WorkflowIntegrationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    TenantScope::setTenantId(null);
});

test('workflow service dispatches TriggerWorkflowIntegrationJob for active integrations', function () {
    Queue::fake();

    $tenant = Tenant::factory()->create();

    // Create active integration
    TenantIntegration::create([
        'tenant_id' => $tenant->id,
        'platform_name' => 'make',
        'webhook_url' => 'https://hook.us1.make.com/abc',
        'is_active' => true,
    ]);

    // Create inactive integration
    TenantIntegration::create([
        'tenant_id' => $tenant->id,
        'platform_name' => 'gohighlevel',
        'webhook_url' => 'https://services.leadconnectorhq.com/xyz',
        'is_active' => false,
    ]);

    $service = app(WorkflowIntegrationService::class);
    $service->triggerExternalWorkflow($tenant, 'test_event', ['data' => '123']);

    Queue::assertPushed(TriggerWorkflowIntegrationJob::class, function ($job) use ($tenant) {
        return $job->webhookUrl === 'https://hook.us1.make.com/abc'
            && $job->payload['event_name'] === 'test_event'
            && $job->payload['tenant_id'] === $tenant->id
            && $job->payload['data'] === '123';
    });

    Queue::assertNotPushed(TriggerWorkflowIntegrationJob::class, function ($job) {
        return $job->webhookUrl === 'https://services.leadconnectorhq.com/xyz';
    });
});

test('workflow integration job makes correct HTTP request', function () {
    Http::fake();

    $job = new TriggerWorkflowIntegrationJob('https://hook.us1.make.com/abc', [
        'event_name' => 'test_event',
        'tenant_id' => 1,
    ]);

    $job->handle();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://hook.us1.make.com/abc'
            && $request->method() === 'POST'
            && $request['event_name'] === 'test_event'
            && $request['tenant_id'] === 1;
    });
});
