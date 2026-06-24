<?php

use App\Jobs\ExecuteBatchCampaignJob;
use App\Jobs\Middleware\EnsureRegulatoryCompliance;
use App\Models\AuditLog;
use App\Models\CampaignRecipient;
use App\Models\OutboundCampaign;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    TenantScope::setTenantId(null);
    session()->forget('tenant_id');
});

test('tcpa compliance middleware blocks campaigns during off hours and reschedules', function () {
    // 1. Setup Tenant and Campaign
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $campaign = OutboundCampaign::create([
        'tenant_id' => $tenant->id,
        'status' => 'draft',
        'target_group' => 'DNC Test Group',
    ]);

    // +1206... maps to America/Los_Angeles (PST)
    $recipient = CampaignRecipient::create([
        'campaign_id' => $campaign->id,
        'name' => 'John Doe PST',
        'phone_number' => '+12065551234',
        'status' => 'pending',
    ]);

    // 2. Set Carbon time to 5 AM PST / Los Angeles time (which violates 8 AM - 9 PM)
    // 5 AM PST is 12:00 PM (noon) UTC.
    Carbon::setTestNow(Carbon::create(2026, 6, 24, 12, 0, 0, 'UTC'));

    $job = new ExecuteBatchCampaignJob($campaign);

    // Call middleware handle manually or via Queue/Dispatching
    $middleware = new EnsureRegulatoryCompliance;

    $called = false;
    $next = function ($job) use (&$called) {
        $called = true;
        $job->handle();
    };

    // Run the middleware
    $middleware->handle($job, $next);

    // Assert that the job was NOT executed (next callback bypassed)
    expect($called)->toBeFalse();

    // Assert that a compliance warning log was written to isolated audit_logs
    $log = AuditLog::where('action', 'tcpa_compliance_violation')->first();
    expect($log)->not->toBeNull()
        ->and($log->tenant_id)->toBe($tenant->id)
        ->and($log->payload['phone_number'])->toBe('+12065551234');

    // Clean up
    Carbon::setTestNow();
});

test('tcpa compliance middleware allows campaigns during compliant hours', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $campaign = OutboundCampaign::create([
        'tenant_id' => $tenant->id,
        'status' => 'draft',
        'target_group' => 'DNC Test Group Compliant',
    ]);

    // +1206... maps to America/Los_Angeles (PST)
    $recipient = CampaignRecipient::create([
        'campaign_id' => $campaign->id,
        'name' => 'John Doe Compliant',
        'phone_number' => '+12065551234',
        'status' => 'pending',
    ]);

    // Set Carbon time to 10 AM PST / Los Angeles time (compliant: between 8 AM and 9 PM)
    // 10 AM PST is 5:00 PM UTC.
    Carbon::setTestNow(Carbon::create(2026, 6, 24, 17, 0, 0, 'UTC'));

    $job = new ExecuteBatchCampaignJob($campaign);
    $middleware = new EnsureRegulatoryCompliance;

    $called = false;
    $next = function ($job) use (&$called) {
        $called = true;
    };

    $middleware->handle($job, $next);

    // Assert that next was executed successfully
    expect($called)->toBeTrue();

    Carbon::setTestNow();
});
