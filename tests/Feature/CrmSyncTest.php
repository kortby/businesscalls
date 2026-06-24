<?php

use App\Events\CallAnalyzed;
use App\Jobs\SyncCallToCrmJob;
use App\Models\CallLog;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('SyncCallToCrmJob is dispatched when CallAnalyzed event is fired', function () {
    Queue::fake();

    $tenant = Tenant::factory()->create();
    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-test-1',
        'status' => 'ongoing',
        'customer_phone' => '+15551234567',
    ]);

    event(new CallAnalyzed($tenant->id, $callLog));

    Queue::assertPushed(SyncCallToCrmJob::class, function ($job) use ($callLog) {
        return $job->callLog->id === $callLog->id;
    });
});

test('SyncCallToCrmJob synchronizes successfully to HubSpot and Salesforce', function () {
    $tenant = Tenant::factory()->create([
        'settings' => [
            'hubspot_token' => 'mock_hubspot_token',
            'salesforce_token' => 'mock_salesforce_token',
            'salesforce_instance_url' => 'https://mock-sf.my.salesforce.com',
        ],
    ]);

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-test-2',
        'status' => 'ended',
        'customer_phone' => '+15551234567',
        'duration' => 120,
        'summary' => json_encode([
            'caller_name' => 'John Doe',
            'sentiment' => 'Positive',
            'summary' => 'Customer requested a plumber for toilet leak.',
            'booking_outcome' => 'Scheduled',
        ]),
        'transcript' => 'Customer: I need a plumber. AI: OK I will book it.',
    ]);

    // Mock API requests
    Http::fake([
        'https://api.hubapi.com/crm/v3/objects/contacts/search' => Http::response([
            'results' => [
                ['id' => 'hub_contact_999'],
            ],
        ], 200),
        'https://api.hubapi.com/crm/v3/objects/calls' => Http::response([
            'id' => 'hub_call_888',
        ], 200),
        'https://api.hubapi.com/crm/v4/objects/calls/*' => Http::response([], 200),
        'https://mock-sf.my.salesforce.com/services/data/v57.0/query?q=*' => Http::response([
            'totalSize' => 1,
            'records' => [
                ['Id' => 'sf_contact_777'],
            ],
        ], 200),
        'https://mock-sf.my.salesforce.com/services/data/v57.0/sobjects/Task' => Http::response([
            'id' => 'sf_task_666',
        ], 200),
    ]);

    $job = new SyncCallToCrmJob($callLog);
    $job->handle();

    // Verify HubSpot API sequences
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.hubapi.com/crm/v3/objects/contacts/search'
            && $request['filterGroups'][0]['filters'][0]['value'] === '+15551234567';
    });

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.hubapi.com/crm/v3/objects/calls'
            && (str_contains($request['properties']['hs_call_body'], 'Positive')
                || str_contains($request['properties']['hs_call_body'], 'toilet leak'));
    });

    // Verify Salesforce API sequences
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'services/data/v57.0/query')
            && str_contains(urldecode($request->toPsrRequest()->getUri()->getQuery()), '+15551234567');
    });

    Http::assertSent(function ($request) {
        return $request->url() === 'https://mock-sf.my.salesforce.com/services/data/v57.0/sobjects/Task'
            && $request['WhoId'] === 'sf_contact_777'
            && (str_contains($request['Description'], 'Positive')
                || str_contains($request['Description'], 'plumber'));
    });
});

test('SyncCallToCrmJob handles token expirations gracefully', function () {
    $tenant = Tenant::factory()->create([
        'settings' => [
            'hubspot_token' => 'expired_hubspot_token',
            'salesforce_token' => 'expired_salesforce_token',
            'salesforce_instance_url' => 'https://mock-sf.my.salesforce.com',
        ],
    ]);

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-test-3',
        'status' => 'ended',
        'customer_phone' => '+15551234567',
        'duration' => 120,
        'summary' => json_encode([
            'caller_name' => 'John Doe',
            'sentiment' => 'Positive',
            'summary' => 'Customer requested a plumber for toilet leak.',
            'booking_outcome' => 'Scheduled',
        ]),
        'transcript' => 'Customer: I need a plumber. AI: OK I will book it.',
    ]);

    // Mock API requests to return 401 Unauthorized
    Http::fake([
        'https://api.hubapi.com/crm/v3/objects/contacts/search' => Http::response([], 401),
        'https://mock-sf.my.salesforce.com/services/data/v57.0/query?q=*' => Http::response([], 401),
    ]);

    $job = new SyncCallToCrmJob($callLog);

    // Execute job: it must complete successfully without throwing exceptions
    $job->handle();

    // Verify that the search endpoints were indeed hit
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.hubapi.com/crm/v3/objects/contacts/search';
    });

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'services/data/v57.0/query');
    });

    // Verify HubSpot calls create was NOT hit because of early exit on 401 search
    Http::assertNotSent(function ($request) {
        return $request->url() === 'https://api.hubapi.com/crm/v3/objects/calls';
    });
});
