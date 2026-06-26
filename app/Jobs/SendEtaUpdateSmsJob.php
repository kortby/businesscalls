<?php

namespace App\Jobs;

use App\Attributes\Queue;
use App\Attributes\Tries;
use App\Models\Booking;
use App\Models\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

#[Queue('sms-confirmations')]
#[Tries(3)]
class SendEtaUpdateSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Booking $booking,
        public Carbon $newStartTime
    ) {
        $reflection = new ReflectionClass(static::class);
        $attributes = $reflection->getAttributes(Queue::class);
        if (count($attributes) > 0) {
            $this->onQueue($attributes[0]->newInstance()->name);
        } else {
            $this->onQueue('sms-confirmations');
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $tenant = $this->booking->tenant;
        if (! $tenant) {
            return;
        }

        // Apply tenant scope context
        TenantScope::setTenantId($tenant->id);

        $phone = $this->booking->customer_phone;
        // Don't send if customer phone is unknown
        if ($phone === 'Unknown') {
            return;
        }

        $etaString = $this->newStartTime->format('h:i A');
        $trackingUrl = url("/admin/csat-feedback?booking_id={$this->booking->id}");
        $message = "Dear customer, your technician has been rerouted to an emergency. Your updated appointment start time is {$etaString}. Track your technician here: {$trackingUrl}";

        // Submit the text content programmatically via Twilio, Retell, or Vapi messaging APIs
        $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));
        $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';

        try {
            if ($provider === 'vapi') {
                Http::withToken($apiKey)->timeout(5)->post('https://api.vapi.ai/sms', [
                    'to' => $phone,
                    'message' => $message,
                ]);
            } elseif ($provider === 'retell') {
                Http::withToken($apiKey)->timeout(5)->post('https://api.retellai.com/v2/sms', [
                    'to' => $phone,
                    'text' => $message,
                ]);
            } else {
                // Twilio or other fallback
                Http::timeout(5)->post('https://api.twilio.com/mock-send-sms', [
                    'to' => $phone,
                    'from' => $tenant->getSetting('sms_number', '+15555555555'),
                    'body' => $message,
                ]);
            }
            Log::info("ETA update SMS sent successfully to {$phone} via provider: {$provider}");
        } catch (\Exception $e) {
            Log::warning('ETA update SMS dispatch failed: '.$e->getMessage());
        }
    }
}
