<?php

namespace App\Jobs;

use App\Attributes\Queue;
use App\Models\Booking;
use App\Models\CustomVoice;
use App\Models\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Interruptible;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

#[Queue('high-priority')]
class SendTechnicianAlertJob implements Interruptible, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Controls whether this job executes during testing environment runs.
     */
    public static bool $shouldRunInTests = false;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Booking $booking
    ) {
        $reflection = new ReflectionClass(static::class);
        $attributes = $reflection->getAttributes(Queue::class);
        if (count($attributes) > 0) {
            $this->onQueue($attributes[0]->newInstance()->name);
        } else {
            $this->onQueue('high-priority');
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (app()->environment('testing') && ! static::$shouldRunInTests) {
            Log::info('Skipping outbound technician alert in test run.');

            return;
        }

        $lock = Cache::lock('booking-alert-lock:'.$this->booking->id, 60);
        if (! $lock->get()) {
            Log::warning("Could not acquire lock for Booking ID: {$this->booking->id}, skipping.");

            return;
        }

        try {
            $booking = $this->booking;
            $booking->status = 'notifying';
            $booking->save();

            $employee = $booking->employee;

            if (! $employee || ! $employee->phone) {
                Log::warning("No employee or phone number registered for Booking ID: {$booking->id}");

                return;
            }

            $preference = $employee->notification_preference ?? 'sms';
            $tenant = $booking->tenant;
            $scheduledStart = $booking->scheduled_start->format('Y-m-d H:i');

            Log::info("Processing outbound dispatch alert for technician: {$employee->first_name} {$employee->last_name}, type: {$preference}");

            if ($preference === 'voice') {
                // Outbound Voice Call Alert
                $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';
                $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));
                $assistantId = $tenant?->getSetting('voice_assistant_id') ?? 'default-assistant-id';
                $phoneNumberId = $tenant?->getSetting('telephony_phone_number_id');
                $customVoice = $tenant ? CustomVoice::where('tenant_id', $tenant->id)
                    ->where('status', 'active')
                    ->latest()
                    ->first() : null;

                if ($provider === 'vapi') {
                    $url = 'https://api.vapi.ai/call';
                    $payload = [
                        'assistantId' => $assistantId,
                        'customer' => [
                            'number' => $employee->phone,
                        ],
                        'answeringMachineDetectionConfiguration' => [
                            'enabled' => true,
                        ],
                        'assistantOverrides' => [
                            'variableValues' => [
                                'first_name' => $employee->first_name,
                                'scheduled_start' => $scheduledStart,
                            ],
                        ],
                    ];
                    if ($phoneNumberId) {
                        $payload['phoneNumberId'] = $phoneNumberId;
                    }
                    if ($customVoice) {
                        $payload['assistantOverrides']['voice'] = [
                            'provider' => 'elevenlabs',
                            'voiceId' => $customVoice->provider_voice_id,
                        ];
                    }
                } else {
                    $url = 'https://api.retellai.com/create-phone-call';
                    $payload = [
                        'assistant_id' => $assistantId,
                        'to_number' => $employee->phone,
                        'from_number' => $tenant?->getSetting('telephony_phone_number') ?? '+15550000000',
                        'machine_detection' => true,
                        'override_values' => [
                            'first_name' => $employee->first_name,
                            'scheduled_start' => $scheduledStart,
                        ],
                    ];
                    if ($customVoice) {
                        $payload['assistant_overrides'] = [
                            'voice_id' => $customVoice->provider_voice_id,
                        ];
                    }
                }

                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])->timeout(10)->post($url, $payload);

                if ($response->failed()) {
                    Log::error("Outbound voice call failed for employee {$employee->id}: ".$response->body());
                    throw new \Exception('Outbound voice call alert failed: '.$response->body());
                }

                $callId = $response->json('id') ?? $response->json('call_id');
                if ($callId) {
                    Cache::put("call_booking_map:{$callId}", $booking->id, 86400); // 1 day cache
                }

                Log::info("Voice call alert queued successfully for employee {$employee->id}.");
            } else {
                // Outbound SMS Alert
                $sid = env('TWILIO_ACCOUNT_SID') ?? 'dummy-twilio-sid';
                $token = env('TWILIO_AUTH_TOKEN') ?? 'dummy-twilio-token';
                $from = env('TWILIO_FROM_NUMBER') ?? '+15550001111';

                $dispatchMapUrl = url("/bookings/{$booking->id}/map");
                $smsBody = "Hi {$employee->first_name}, you have been assigned a new HVAC dispatch at {$scheduledStart}. Please check your portal. View map: {$dispatchMapUrl}";

                $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

                $response = Http::withBasicAuth($sid, $token)
                    ->asForm()
                    ->timeout(10)
                    ->post($url, [
                        'From' => $from,
                        'To' => $employee->phone,
                        'Body' => $smsBody,
                    ]);

                if ($response->failed()) {
                    Log::error("SMS alert failed for employee {$employee->id}: ".$response->body());
                    throw new \Exception('Outbound SMS alert failed: '.$response->body());
                }

                Log::info("SMS alert sent successfully to employee {$employee->id}.");
            }

            if ($preference !== 'voice') {
                $booking->status = 'booked';
                $booking->save();
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * Handle the interruption signal.
     */
    public function interrupted(int $signal): void
    {
        if ($this->booking->tenant_id) {
            TenantScope::setTenantId($this->booking->tenant_id);
        }

        Log::info("Outbound Alert Job interrupted by signal {$signal} for Booking ID: {$this->booking->id} under Tenant: {$this->booking->tenant_id}");

        // Gracefully release the lock
        $lock = Cache::lock('booking-alert-lock:'.$this->booking->id);
        $lock->forceRelease();

        // Set call status back to 'registered'
        $this->booking->status = 'registered';
        $this->booking->save();

        Log::info("Booking Alert Lock released and status reset to 'registered' for Booking ID: {$this->booking->id}");
    }
}
