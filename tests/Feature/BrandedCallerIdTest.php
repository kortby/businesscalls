<?php

use App\Jobs\ExecuteBatchCampaignJob;
use App\Models\CampaignRecipient;
use App\Models\OutboundCampaign;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\BrandedCallerIdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    TenantScope::setTenantId(null);
    session()->forget('tenant_id');
});

test('branded caller id service registers phone lines successfully with provider', function () {
    Http::fake([
        'https://api.vapi.ai/branded-caller-id' => Http::response([
            'status' => 'pending',
            'trunk_id' => 'vapi_trunk_12345',
        ], 200),
        'https://api.retellai.com/v2/register-branded-caller-id' => Http::response([
            'status' => 'pending',
            'trunk_id' => 'vapi_trunk_12345',
        ], 200),
    ]);

    $tenant = Tenant::factory()->create();
    $service = new BrandedCallerIdService;

    $businessData = [
        'legal_business_name' => 'Acme Plumbing',
        'brand_logo_url' => 'https://acme.com/logo.png',
        'physical_address' => '456 Elm St, Seattle, WA 98102',
        'phone_numbers' => ['+15551234567'],
    ];

    $result = $service->registerBrandedCallerId($tenant, $businessData);

    expect($result['status'])->toBe('success')
        ->and($result['trunk_id'])->toBe('vapi_trunk_12345');

    $tenant->refresh();
    expect($tenant->getSetting('branded_caller_id_trunk_id'))->toBe('vapi_trunk_12345')
        ->and($tenant->getSetting('branded_caller_id_status'))->toBe('verified')
        ->and($tenant->getSetting('branded_business_name'))->toBe('Acme Plumbing');
});

test('branded trunk ID is dynamically injected during background campaign execution', function () {
    Http::fake([
        'https://api.vapi.ai/call' => Http::response([
            'id' => 'vapi_call_9999',
        ], 200),
    ]);

    $tenant = Tenant::factory()->create([
        'settings' => [
            'branded_caller_id_trunk_id' => 'verified_trunk_999',
            'voice_assistant_id' => 'assistant_id_123',
            'telephony_phone_number_id' => 'phone_number_id_456',
        ],
    ]);

    TenantScope::setTenantId($tenant->id);

    $campaign = OutboundCampaign::create([
        'tenant_id' => $tenant->id,
        'status' => 'draft',
        'target_group' => 'Inactive Users',
    ]);

    CampaignRecipient::create([
        'campaign_id' => $campaign->id,
        'name' => 'John Doe',
        'phone_number' => '+15551112222',
        'status' => 'pending',
    ]);

    // Force run campaign in tests using the API driver instead of mock simulation
    ExecuteBatchCampaignJob::$shouldRunInTests = true;
    config()->set('services.telephony.provider', 'vapi');

    $job = new ExecuteBatchCampaignJob($campaign);
    $job->handle();

    // Verify trunkId was injected in the payload
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.vapi.ai/call'
            && isset($request['sipvbcTrunkId'])
            && $request['sipvbcTrunkId'] === 'verified_trunk_999';
    });

    ExecuteBatchCampaignJob::$shouldRunInTests = false;
});
