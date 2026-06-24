<?php

namespace App\Jobs;

use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncCallToCrmJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CallLog $callLog
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $tenant = $this->callLog->tenant;
        if (! $tenant) {
            Log::warning('SyncCallToCrmJob aborted: CallLog does not belong to a valid tenant.');

            return;
        }

        // Apply tenant database scoping inside queue worker thread
        TenantScope::setTenantId($tenant->id);

        try {
            // Extract call analysis details from the summary
            $summaryObj = json_decode($this->callLog->summary, true) ?: [];
            $callerName = $summaryObj['caller_name'] ?? 'AI Call';
            $sentiment = $summaryObj['sentiment'] ?? 'Neutral';
            $extractedSummary = $summaryObj['summary'] ?? $this->callLog->summary ?? 'No summary';
            $bookingOutcome = $summaryObj['booking_outcome'] ?? 'None';
            $transcript = $this->callLog->transcript ?? 'No transcript';

            // 1. Sync with HubSpot
            $this->syncToHubSpot($tenant, $callerName, $sentiment, $extractedSummary, $bookingOutcome);

            // 2. Sync with Salesforce
            $this->syncToSalesforce($tenant, $sentiment, $extractedSummary, $bookingOutcome, $transcript);

        } catch (\Exception $e) {
            Log::error('CRM Sync Job failed with general exception: '.$e->getMessage());
        } finally {
            // Always reset the tenant database scope
            TenantScope::setTenantId(null);
        }
    }

    /**
     * Synchronize interaction details with HubSpot.
     */
    protected function syncToHubSpot($tenant, string $callerName, string $sentiment, string $summary, string $outcome): void
    {
        $token = $tenant->getSetting('hubspot_token') ?: env('HUBSPOT_TOKEN');
        if (! $token) {
            Log::info('HubSpot sync skipped: no token configured.');

            return;
        }

        try {
            $phone = $this->callLog->customer_phone;

            // Search for existing contact by phone number
            $searchResponse = Http::withToken($token)
                ->post('https://api.hubapi.com/crm/v3/objects/contacts/search', [
                    'filterGroups' => [[
                        'filters' => [[
                            'propertyName' => 'phone',
                            'operator' => 'EQ',
                            'value' => $phone,
                        ]],
                    ]],
                ]);

            if ($searchResponse->status() === 401) {
                Log::error('HubSpot CRM Sync failed: Token expired or invalid.');

                return;
            }

            if (! $searchResponse->successful()) {
                Log::error('HubSpot contact search failed: '.$searchResponse->body());

                return;
            }

            $contactId = $searchResponse->json('results.0.id');

            // If contact is missing, create a new contact record
            if (! $contactId) {
                $nameParts = explode(' ', $callerName, 2);
                $firstName = $nameParts[0] ?? 'Caller';
                $lastName = $nameParts[1] ?? $phone;

                $createResponse = Http::withToken($token)
                    ->post('https://api.hubapi.com/crm/v3/objects/contacts', [
                        'properties' => [
                            'firstname' => $firstName,
                            'lastname' => $lastName,
                            'phone' => $phone,
                        ],
                    ]);

                if ($createResponse->successful()) {
                    $contactId = $createResponse->json('id');
                } else {
                    Log::error('HubSpot contact creation failed: '.$createResponse->body());

                    return;
                }
            }

            // Create customized Call Engagement
            $body = "Sentiment: {$sentiment}\nSummary: {$summary}\nOutcome: {$outcome}";
            $callResponse = Http::withToken($token)
                ->post('https://api.hubapi.com/crm/v3/objects/calls', [
                    'properties' => [
                        'hs_call_body' => $body,
                        'hs_call_duration' => (string) ($this->callLog->duration ?? 60),
                        'hs_call_from_number' => $phone,
                        'hs_call_title' => 'AI Voice Call Telemetry',
                        'hs_call_status' => 'COMPLETED',
                    ],
                ]);

            if (! $callResponse->successful()) {
                Log::error('HubSpot call logging failed: '.$callResponse->body());

                return;
            }

            $callId = $callResponse->json('id');

            // Associate the Call Engagement with the Contact record
            if ($contactId && $callId) {
                Http::withToken($token)
                    ->put("https://api.hubapi.com/crm/v4/objects/calls/{$callId}/associations/contacts/{$contactId}", [
                        [
                            'associationCategory' => 'HUBSPOT_DEFINED',
                            'associationTypeId' => 194, // Call to Contact
                        ],
                    ]);
            }

            Log::info("Successfully synchronized call telemetry to HubSpot for Contact ID: {$contactId}");

        } catch (\Exception $e) {
            Log::error('HubSpot Integration Error: '.$e->getMessage());
        }
    }

    /**
     * Synchronize interaction details with Salesforce.
     */
    protected function syncToSalesforce($tenant, string $sentiment, string $summary, string $outcome, string $transcript): void
    {
        $token = $tenant->getSetting('salesforce_token') ?: env('SALESFORCE_TOKEN');
        $instanceUrl = $tenant->getSetting('salesforce_instance_url') ?: env('SALESFORCE_INSTANCE_URL', 'https://login.salesforce.com');
        if (! $token) {
            Log::info('Salesforce sync skipped: no token configured.');

            return;
        }

        try {
            $phone = $this->callLog->customer_phone;
            $recordId = null;

            // Search for matching Contact by phone number
            $queryUrl = "{$instanceUrl}/services/data/v57.0/query";
            $contactQuery = "SELECT Id, Name FROM Contact WHERE Phone = '".addslashes($phone)."' LIMIT 1";

            $response = Http::withToken($token)->get($queryUrl, ['q' => $contactQuery]);

            if ($response->status() === 401) {
                Log::error('Salesforce CRM Sync failed: Token expired or invalid.');

                return;
            }

            if ($response->successful() && $response->json('totalSize') > 0) {
                $recordId = $response->json('records.0.Id');
            } else {
                // If not found in Contacts, check Leads
                $leadQuery = "SELECT Id, Name FROM Lead WHERE Phone = '".addslashes($phone)."' LIMIT 1";
                $leadResponse = Http::withToken($token)->get($queryUrl, ['q' => $leadQuery]);

                if ($leadResponse->successful() && $leadResponse->json('totalSize') > 0) {
                    $recordId = $leadResponse->json('records.0.Id');
                }
            }

            // Create a follow-up Task for sales reps with the call details and transcript
            $description = "AI voice call telemetry synchronized.\n"
                ."Sentiment: {$sentiment}\n"
                ."Summary: {$summary}\n"
                ."Outcome: {$outcome}\n\n"
                ."Transcript:\n".$transcript;

            $taskData = [
                'Subject' => 'AI Voice Call Follow-up - '.$sentiment,
                'Description' => $description,
                'Status' => 'Not Started',
                'Priority' => 'Normal',
            ];

            if ($recordId) {
                $taskData['WhoId'] = $recordId;
            }

            $taskResponse = Http::withToken($token)
                ->post("{$instanceUrl}/services/data/v57.0/sobjects/Task", $taskData);

            if ($taskResponse->status() === 401) {
                Log::error('Salesforce task creation failed due to expired token.');

                return;
            }

            if (! $taskResponse->successful()) {
                Log::error('Salesforce task creation failed: '.$taskResponse->body());

                return;
            }

            Log::info('Successfully synchronized follow-up Task to Salesforce. ID: '.$taskResponse->json('id'));

        } catch (\Exception $e) {
            Log::error('Salesforce Integration Error: '.$e->getMessage());
        }
    }
}
