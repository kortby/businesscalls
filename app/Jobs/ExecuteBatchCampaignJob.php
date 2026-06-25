<?php

namespace App\Jobs;

use App\Attributes\Queue;
use App\Attributes\Tries;
use App\Jobs\Middleware\EnsureRegulatoryCompliance;
use App\Models\Booking;
use App\Models\OutboundCampaign;
use App\Models\Scopes\TenantScope;
use App\Services\ComplianceSanitizerService;
use App\Services\TrafficRouterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

#[Queue('outbound-campaigns')]
#[Tries(3)]
class ExecuteBatchCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Controls whether this job executes during testing environment runs.
     */
    public static bool $shouldRunInTests = false;

    /**
     * Create a new job instance.
     */
    public function __construct(public OutboundCampaign $campaign)
    {
        $reflection = new ReflectionClass(static::class);

        $queueAttrs = $reflection->getAttributes(Queue::class);
        if (count($queueAttrs) > 0) {
            $this->onQueue($queueAttrs[0]->newInstance()->name);
        } else {
            $this->onQueue('outbound-campaigns');
        }

        $triesAttrs = $reflection->getAttributes(Tries::class);
        if (count($triesAttrs) > 0) {
            $this->tries = $triesAttrs[0]->newInstance()->count;
        } else {
            $this->tries = 3;
        }
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new EnsureRegulatoryCompliance];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Enforce isolated multi-tenant parameters
        TenantScope::setTenantId($this->campaign->tenant_id);

        $this->campaign->update(['status' => 'processing']);

        $recipients = $this->campaign->recipients()->whereNull('call_id')->get();

        $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));
        $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';
        $tenant = $this->campaign->tenant;

        $assistantId = $tenant->getSetting('voice_assistant_id') ?? 'default-assistant-id';
        $phoneNumberId = $tenant->getSetting('telephony_phone_number_id') ?? 'default-phone-id';
        $phoneNumber = $tenant->getSetting('telephony_phone_number') ?? '+15550001111';

        $router = app(TrafficRouterService::class);

        foreach ($recipients as $recipient) {
            if (app()->environment('testing') && ! static::$shouldRunInTests) {
                // In test mode without explicit override, simulate telephony API call
                $callId = 'test_call_'.uniqid().'_'.$recipient->id;

                $variant = $router->route($tenant);
                if ($variant) {
                    Cache::put("call_variant_map:{$callId}", $variant->id, 3600);
                }

                $recipient->update([
                    'call_id' => $callId,
                    'status' => 'called',
                ]);

                continue;
            }

            try {
                $variant = null;
                if ($provider === 'vapi') {
                    $payload = [
                        'assistantId' => $assistantId,
                        'phoneNumberId' => $phoneNumberId,
                        'customer' => [
                            'number' => $recipient->phone_number,
                            'name' => $recipient->name,
                        ],
                        'assistantOverrides' => [
                            'variableValues' => [
                                'name' => $recipient->name,
                                'appointments' => 'Your scheduled appointment',
                            ],
                        ],
                    ];

                    $router->applyExperimentOverrides($tenant, $payload, $variant);

                    $brandedTrunkId = $tenant->getSetting('branded_caller_id_trunk_id');
                    if ($brandedTrunkId) {
                        $payload['sipvbcTrunkId'] = $brandedTrunkId;
                        $payload['trunkId'] = $brandedTrunkId;
                    }

                    if ($this->campaign->schedule_time) {
                        $payload['schedulePlan'] = [
                            'earliestAt' => $this->campaign->schedule_time->toIso8601String(),
                        ];
                    }

                    $complianceService = app(ComplianceSanitizerService::class);
                    $complianceService->applyCompliance($tenant, $payload);

                    $response = Http::withToken($apiKey)
                        ->timeout(10)
                        ->post('https://api.vapi.ai/call', $payload);
                } else {
                    $payload = [
                        'from_number' => $phoneNumber,
                        'to_number' => $recipient->phone_number,
                        'override_agent_id' => $assistantId,
                        'retell_llm_dynamic_variables' => [
                            'name' => $recipient->name,
                            'appointments' => 'Your scheduled appointment',
                        ],
                    ];

                    $router->applyExperimentOverrides($tenant, $payload, $variant);

                    $brandedTrunkId = $tenant->getSetting('branded_caller_id_trunk_id');
                    if ($brandedTrunkId) {
                        $payload['telephony_trunk_id'] = $brandedTrunkId;
                    }

                    if ($this->campaign->schedule_time) {
                        $payload['trigger_timestamp'] = $this->campaign->schedule_time->timestamp * 1000;
                    }

                    $complianceService = app(ComplianceSanitizerService::class);
                    $complianceService->applyCompliance($tenant, $payload);

                    $response = Http::withToken($apiKey)
                        ->timeout(10)
                        ->post('https://api.retellai.com/v2/create-phone-call', $payload);
                }

                if ($response->successful()) {
                    $callId = $response->json('id') ?? $response->json('call_id');

                    if ($variant) {
                        Cache::put("call_variant_map:{$callId}", $variant->id, 3600);
                    }

                    $recipient->update([
                        'call_id' => $callId,
                        'status' => 'called',
                    ]);
                } else {
                    Log::error("Telephony campaign dispatch failed for recipient {$recipient->id}: ".$response->body());
                    $recipient->update(['status' => 'failed']);
                }
            } catch (\Exception $e) {
                Log::error("Exception in campaign dispatch for recipient {$recipient->id}: ".$e->getMessage());
                $recipient->update(['status' => 'failed']);
            }
        }

        // Calculate Campaign Conversion Coefficient (Phi_conversion)
        $placedCalls = $this->campaign->recipients()->whereNotNull('call_id')->get();
        $totalPlaced = $placedCalls->count();
        $conversions = 0;

        foreach ($placedCalls as $placedRecipient) {
            $callId = $placedRecipient->call_id;

            // 1. Check cache for dynamic voice bookings mapping
            $bookingId = Cache::get("call_booking_map:{$callId}");

            if ($bookingId) {
                $exists = Booking::where('id', $bookingId)->exists();
                if ($exists) {
                    $conversions++;
                    $placedRecipient->update(['status' => 'completed']);

                    continue;
                }
            }

            // 2. Check as fallback if a booking exists for this phone number and was created after the campaign began
            $existsFallback = Booking::where('customer_phone', $placedRecipient->phone_number)
                ->where('created_at', '>=', $this->campaign->created_at)
                ->exists();

            if ($existsFallback) {
                $conversions++;
                $placedRecipient->update(['status' => 'completed']);
            }
        }

        $conversionCoefficient = $totalPlaced > 0 ? (float) ($conversions / $totalPlaced) : 0.0;

        $this->campaign->update([
            'status' => 'completed',
            'conversion_coefficient' => $conversionCoefficient,
        ]);

        Log::info("ExecuteBatchCampaignJob completed for Campaign {$this->campaign->id}. Coefficient: {$conversionCoefficient}");
    }
}
