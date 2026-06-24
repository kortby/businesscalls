<?php

use App\Attributes\Queue;
use App\Attributes\Tries;
use App\Jobs\ExecuteBatchCampaignJob;
use App\Models\Booking;
use App\Models\CampaignRecipient;
use App\Models\Employee;
use App\Models\OutboundCampaign;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    TenantScope::setTenantId(null);
    session()->forget('tenant_id');
    ExecuteBatchCampaignJob::$shouldRunInTests = false;
    putenv('TELEPHONY_API_KEY=dummy-telephony-api-key');
    $_ENV['TELEPHONY_API_KEY'] = 'dummy-telephony-api-key';
    $_SERVER['TELEPHONY_API_KEY'] = 'dummy-telephony-api-key';
});

test('reflection reads tries and queue attributes from execute batch campaign job', function () {
    $reflection = new ReflectionClass(ExecuteBatchCampaignJob::class);

    // Assert Queue attribute is present
    $queueAttrs = $reflection->getAttributes(Queue::class);
    expect($queueAttrs)->toHaveCount(1)
        ->and($queueAttrs[0]->newInstance()->name)->toBe('outbound-campaigns');

    // Assert Tries attribute is present
    $triesAttrs = $reflection->getAttributes(Tries::class);
    expect($triesAttrs)->toHaveCount(1)
        ->and($triesAttrs[0]->newInstance()->count)->toBe(3);
});

test('campaign batch job dispatches simulated calls and calculates conversion metrics', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

    // Create campaign
    $campaign = OutboundCampaign::create([
        'tenant_id' => $tenant->id,
        'status' => 'draft',
        'target_group' => 'Inactive Customers',
    ]);

    // Create recipients
    $recipient1 = CampaignRecipient::create([
        'campaign_id' => $campaign->id,
        'name' => 'Alice Smith',
        'phone_number' => '+15551112222',
        'status' => 'pending',
    ]);

    $recipient2 = CampaignRecipient::create([
        'campaign_id' => $campaign->id,
        'name' => 'Bob Jones',
        'phone_number' => '+15553334444',
        'status' => 'pending',
    ]);

    // Run job in simulated mode (default)
    $job = new ExecuteBatchCampaignJob($campaign);
    $job->handle();

    // Verify campaign processing to completed
    $campaign->refresh();
    expect($campaign->status)->toBe('completed')
        ->and((float) $campaign->conversion_coefficient)->toBe(0.0); // No bookings yet

    // Reset status to test conversion calculations
    $campaign->update(['status' => 'processing']);

    // Refresh recipients to get generated call IDs from the simulation
    $recipient1->refresh();
    $recipient2->refresh();

    expect($recipient1->call_id)->not->toBeNull();
    expect($recipient2->call_id)->not->toBeNull();

    // Mock Booking for Recipient 1 using Cache Map
    $booking1 = Booking::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'customer_phone' => $recipient1->phone_number,
        'job_details' => 'Call booking',
        'status' => 'booked',
        'scheduled_start' => now()->addDay(),
    ]);
    Cache::put("call_booking_map:{$recipient1->call_id}", $booking1->id);

    // Mock Booking for Recipient 2 using Fallback Phone Number Lookup (created after campaign start)
    $booking2 = Booking::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'customer_phone' => $recipient2->phone_number,
        'job_details' => 'Phone booking',
        'status' => 'booked',
        'scheduled_start' => now()->addDay(),
    ]);

    // Re-run job to check conversion calculations
    $job = new ExecuteBatchCampaignJob($campaign);
    $job->handle();

    $campaign->refresh();
    // 2 conversions out of 2 calls = 1.0 (100% conversion)
    expect($campaign->status)->toBe('completed')
        ->and((float) $campaign->conversion_coefficient)->toBe(1.0);

    // Verify recipient statuses updated to completed
    $recipient1->refresh();
    $recipient2->refresh();
    expect($recipient1->status)->toBe('completed')
        ->and($recipient2->status)->toBe('completed');
});

test('campaign batch job dispatches live vapi calls with authentication headers and payloads', function () {
    config(['services.telephony.provider' => 'vapi']);
    ExecuteBatchCampaignJob::$shouldRunInTests = true;

    Http::fake([
        'https://api.vapi.ai/call' => Http::response(['id' => 'vapi_call_999'], 200),
    ]);

    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $campaign = OutboundCampaign::create([
        'tenant_id' => $tenant->id,
        'status' => 'draft',
        'target_group' => 'Active',
    ]);

    $recipient = CampaignRecipient::create([
        'campaign_id' => $campaign->id,
        'name' => 'Charlie Brown',
        'phone_number' => '+15557778888',
        'status' => 'pending',
    ]);

    $job = new ExecuteBatchCampaignJob($campaign);
    $job->handle();

    $recipient->refresh();
    expect($recipient->status)->toBe('called')
        ->and($recipient->call_id)->toBe('vapi_call_999');

    $recorded = Http::recorded();
    expect($recorded)->toHaveCount(1);
    
    $request = $recorded[0][0];
    expect($request->url())->toBe('https://api.vapi.ai/call')
        ->and($request->header('Authorization')[0] ?? '')->toBe('Bearer dummy-telephony-api-key')
        ->and($request['customer']['number'] ?? '')->toBe('+15557778888')
        ->and($request['customer']['name'] ?? '')->toBe('Charlie Brown');
});
