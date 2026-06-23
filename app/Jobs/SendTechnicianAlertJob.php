<?php

namespace App\Jobs;

use App\Attributes\Queue;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

#[Queue('high-priority')]
class SendTechnicianAlertJob implements ShouldQueue
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

        $booking = $this->booking;
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

            if ($provider === 'vapi') {
                $url = 'https://api.vapi.ai/call';
                $payload = [
                    'assistantId' => $assistantId,
                    'customer' => [
                        'number' => $employee->phone,
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
            } else {
                $url = 'https://api.retellai.com/create-phone-call';
                $payload = [
                    'assistant_id' => $assistantId,
                    'to_number' => $employee->phone,
                    'from_number' => $tenant?->getSetting('telephony_phone_number') ?? '+15550000000',
                    'override_values' => [
                        'first_name' => $employee->first_name,
                        'scheduled_start' => $scheduledStart,
                    ],
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($url, $payload);

            if ($response->failed()) {
                Log::error("Outbound voice call failed for employee {$employee->id}: ".$response->body());
                throw new \Exception('Outbound voice call alert failed: '.$response->body());
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
    }
}
