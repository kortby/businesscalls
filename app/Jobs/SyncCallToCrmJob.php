<?php

namespace App\Jobs;

use App\Attributes\Queue;
use App\Attributes\Tries;
use App\Models\CallLog;
use App\Models\CrmCredential;
use App\Models\Scopes\TenantScope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

#[Queue('crm-sync')]
#[Tries(3)]
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

        $start = microtime(true);
        $status = 'skipped';

        try {
            // Extract call analysis details from the summary
            $summaryObj = json_decode($this->callLog->summary, true) ?: [];
            $callerName = $summaryObj['caller_name'] ?? 'AI Call';
            $sentiment = $summaryObj['sentiment'] ?? 'Neutral';
            $extractedSummary = $summaryObj['summary'] ?? $this->callLog->summary ?? 'No summary';
            $bookingOutcome = $summaryObj['booking_outcome'] ?? 'None';
            $transcript = $this->callLog->transcript ?? 'No transcript';

            // Find credentials scoped automatically by TenantScope
            $hubspotCred = CrmCredential::where('platform_name', 'hubspot')->first();
            $salesforceCred = CrmCredential::where('platform_name', 'salesforce')->first();

            // Backward compatibility fallbacks if no database credential records exist
            if (! $hubspotCred && $tenant->getSetting('hubspot_token')) {
                $hubspotCred = new CrmCredential([
                    'tenant_id' => $tenant->id,
                    'platform_name' => 'hubspot',
                    'access_token' => $tenant->getSetting('hubspot_token'),
                    'settings_json' => ['is_active' => true],
                ]);
            }

            if (! $salesforceCred && $tenant->getSetting('salesforce_token')) {
                $salesforceCred = new CrmCredential([
                    'tenant_id' => $tenant->id,
                    'platform_name' => 'salesforce',
                    'access_token' => $tenant->getSetting('salesforce_token'),
                    'settings_json' => [
                        'is_active' => true,
                        'instance_url' => $tenant->getSetting('salesforce_instance_url', 'https://login.salesforce.com'),
                    ],
                ]);
            }

            $syncedAny = false;
            $failedAny = false;

            // 1. Sync with HubSpot if credential exists
            if ($hubspotCred && ($hubspotCred->settings_json['is_active'] ?? true)) {
                $hubspotSuccess = $this->syncToHubSpotWithRetry($hubspotCred, $callerName, $sentiment, $extractedSummary, $bookingOutcome);
                if ($hubspotSuccess) {
                    $syncedAny = true;
                } else {
                    $failedAny = true;
                }
            }

            // 2. Sync with Salesforce if credential exists
            if ($salesforceCred && ($salesforceCred->settings_json['is_active'] ?? true)) {
                $salesforceSuccess = $this->syncToSalesforceWithRetry($salesforceCred, $callerName, $sentiment, $extractedSummary, $bookingOutcome, $transcript);
                if ($salesforceSuccess) {
                    $syncedAny = true;
                } else {
                    $failedAny = true;
                }
            }

            if ($syncedAny && ! $failedAny) {
                $status = 'success';
            } elseif ($syncedAny && $failedAny) {
                $status = 'partial';
            } elseif (! $syncedAny && $failedAny) {
                $status = 'failed';
            } else {
                $status = 'skipped';
            }

        } catch (\Exception $e) {
            Log::error('CRM Sync Job failed with general exception: '.$e->getMessage());
            $status = 'failed';
        } finally {
            $elapsedMs = (int) ((microtime(true) - $start) * 1000);

            // Save performance metrics on the call log
            $this->callLog->crm_sync_status = $status;
            $this->callLog->crm_sync_latency = $elapsedMs;
            $this->callLog->save();

            // Always reset the tenant database scope
            TenantScope::setTenantId(null);
        }
    }

    /**
     * HubSpot sync helper wrapping token check/retry.
     */
    protected function syncToHubSpotWithRetry(CrmCredential $credential, string $callerName, string $sentiment, string $summary, string $outcome): bool
    {
        $token = $this->ensureHubSpotToken($credential);
        if (! $token) {
            Log::error('HubSpot sync failed: token missing/expired and refresh failed.');

            return false;
        }

        $success = $this->performHubSpotSync($token, $callerName, $sentiment, $summary, $outcome);

        if (! $success) {
            // Check if it was a 401 and try refreshing once
            $token = $this->refreshHubSpotToken($credential);
            if ($token) {
                return $this->performHubSpotSync($token, $callerName, $sentiment, $summary, $outcome);
            }
        }

        return $success;
    }

    /**
     * Perform the actual HubSpot HTTP requests.
     */
    protected function performHubSpotSync(string $token, string $callerName, string $sentiment, string $summary, string $outcome): bool
    {
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
                return false;
            }

            if (! $searchResponse->successful()) {
                Log::error('HubSpot contact search failed: '.$searchResponse->body());

                return false;
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

                    return false;
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

                return false;
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

            return true;

        } catch (\Exception $e) {
            Log::error('HubSpot Integration Error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Ensure HubSpot access token is valid (not expired).
     */
    protected function ensureHubSpotToken(CrmCredential $credential): ?string
    {
        if ($credential->token_expires_at && $credential->token_expires_at->isPast()) {
            return $this->refreshHubSpotToken($credential);
        }

        return $credential->access_token;
    }

    /**
     * Refresh HubSpot access token.
     */
    protected function refreshHubSpotToken(CrmCredential $credential): ?string
    {
        $clientId = $credential->settings_json['client_id'] ?? env('HUBSPOT_CLIENT_ID');
        $clientSecret = $credential->settings_json['client_secret'] ?? env('HUBSPOT_CLIENT_SECRET');
        $refreshToken = $credential->refresh_token;

        if (! $refreshToken) {
            return null;
        }

        try {
            $response = Http::asForm()->post('https://api.hubapi.com/oauth/v1/token', [
                'grant_type' => 'refresh_token',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($credential->exists) {
                    $credential->update([
                        'access_token' => $data['access_token'],
                        'refresh_token' => $data['refresh_token'] ?? $refreshToken,
                        'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 1800),
                    ]);
                } else {
                    $credential->access_token = $data['access_token'];
                    if (isset($data['refresh_token'])) {
                        $credential->refresh_token = $data['refresh_token'];
                    }
                    $credential->token_expires_at = now()->addSeconds($data['expires_in'] ?? 1800);
                }

                return $data['access_token'];
            }
        } catch (\Exception $e) {
            Log::error('HubSpot exception during token refresh: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Salesforce sync helper wrapping token check/retry.
     */
    protected function syncToSalesforceWithRetry(CrmCredential $credential, string $callerName, string $sentiment, string $summary, string $outcome, string $transcript): bool
    {
        $token = $this->ensureSalesforceToken($credential);
        $instanceUrl = $credential->settings_json['instance_url'] ?? env('SALESFORCE_INSTANCE_URL', 'https://login.salesforce.com');
        if (! $token) {
            Log::error('Salesforce sync failed: token missing/expired and refresh failed.');

            return false;
        }

        $success = $this->performSalesforceSync($token, $instanceUrl, $callerName, $sentiment, $summary, $outcome, $transcript);

        if (! $success) {
            // Check if it was a 401 and try refreshing once
            $token = $this->refreshSalesforceToken($credential);
            if ($token) {
                $instanceUrl = $credential->settings_json['instance_url'] ?? env('SALESFORCE_INSTANCE_URL', 'https://login.salesforce.com');

                return $this->performSalesforceSync($token, $instanceUrl, $callerName, $sentiment, $summary, $outcome, $transcript);
            }
        }

        return $success;
    }

    /**
     * Perform the actual Salesforce HTTP requests.
     */
    protected function performSalesforceSync(string $token, string $instanceUrl, string $callerName, string $sentiment, string $summary, string $outcome, string $transcript): bool
    {
        try {
            $phone = $this->callLog->customer_phone;
            $recordId = null;

            // Search for matching Contact by phone number
            $queryUrl = "{$instanceUrl}/services/data/v57.0/query";
            $contactQuery = "SELECT Id, Name FROM Contact WHERE Phone = '".addslashes($phone)."' LIMIT 1";

            $response = Http::withToken($token)->get($queryUrl, ['q' => $contactQuery]);

            if ($response->status() === 401) {
                return false;
            }

            if ($response->successful() && $response->json('totalSize') > 0) {
                $recordId = $response->json('records.0.Id');
            } else {
                // If not found in Contacts, check Leads
                $leadQuery = "SELECT Id, Name FROM Lead WHERE Phone = '".addslashes($phone)."' LIMIT 1";
                $leadResponse = Http::withToken($token)->get($queryUrl, ['q' => $leadQuery]);

                if ($leadResponse->status() === 401) {
                    return false;
                }

                if ($leadResponse->successful() && $leadResponse->json('totalSize') > 0) {
                    $recordId = $leadResponse->json('records.0.Id');
                }
            }

            // Generate Contact Record dynamically if not found
            if (! $recordId) {
                $nameParts = explode(' ', $callerName, 2);
                $lastName = $nameParts[1] ?? $nameParts[0] ?? $phone;
                $firstName = isset($nameParts[1]) ? $nameParts[0] : null;

                $contactFields = [
                    'LastName' => $lastName,
                    'Phone' => $phone,
                ];
                if ($firstName) {
                    $contactFields['FirstName'] = $firstName;
                }

                $createResponse = Http::withToken($token)
                    ->post("{$instanceUrl}/services/data/v57.0/sobjects/Contact", $contactFields);

                if ($createResponse->status() === 401) {
                    return false;
                }

                if ($createResponse->successful()) {
                    $recordId = $createResponse->json('id');
                } else {
                    Log::error('Salesforce contact creation failed: '.$createResponse->body());

                    return false;
                }
            }

            // Create a follow-up Task
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
                return false;
            }

            if (! $taskResponse->successful()) {
                Log::error('Salesforce task creation failed: '.$taskResponse->body());

                return false;
            }

            Log::info('Successfully synchronized follow-up Task to Salesforce. ID: '.$taskResponse->json('id'));

            return true;

        } catch (\Exception $e) {
            Log::error('Salesforce Integration Error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Ensure Salesforce access token is valid (not expired).
     */
    protected function ensureSalesforceToken(CrmCredential $credential): ?string
    {
        if ($credential->token_expires_at && $credential->token_expires_at->isPast()) {
            return $this->refreshSalesforceToken($credential);
        }

        return $credential->access_token;
    }

    /**
     * Refresh Salesforce access token.
     */
    protected function refreshSalesforceToken(CrmCredential $credential): ?string
    {
        $clientId = $credential->settings_json['client_id'] ?? env('SALESFORCE_CLIENT_ID');
        $clientSecret = $credential->settings_json['client_secret'] ?? env('SALESFORCE_CLIENT_SECRET');
        $refreshToken = $credential->refresh_token;
        $instanceUrl = $credential->settings_json['instance_url'] ?? env('SALESFORCE_INSTANCE_URL', 'https://login.salesforce.com');

        if (! $refreshToken) {
            return null;
        }

        try {
            $response = Http::asForm()->post("{$instanceUrl}/services/oauth2/token", [
                'grant_type' => 'refresh_token',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $newSettings = $credential->settings_json ?? [];
                if (isset($data['instance_url'])) {
                    $newSettings['instance_url'] = $data['instance_url'];
                }
                if ($credential->exists) {
                    $credential->update([
                        'access_token' => $data['access_token'],
                        'settings_json' => $newSettings,
                        'token_expires_at' => now()->addSeconds(3600), // Default safe expiry
                    ]);
                } else {
                    $credential->access_token = $data['access_token'];
                    $credential->settings_json = $newSettings;
                    $credential->token_expires_at = now()->addSeconds(3600);
                }

                return $data['access_token'];
            }
        } catch (\Exception $e) {
            Log::error('Salesforce exception during token refresh: '.$e->getMessage());
        }

        return null;
    }
}
