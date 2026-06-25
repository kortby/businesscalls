<?php

namespace App\Jobs;

use App\Attributes\Queue;
use App\Attributes\Tries;
use App\Events\ChatMessageReceived;
use App\Models\Booking;
use App\Models\CallLog;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Scopes\TenantScope;
use App\Services\ComplianceSanitizerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

#[Queue('sms-confirmations')]
#[Tries(3)]
class SendSmsConfirmationJob implements ShouldQueue
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
        public CallLog $callLog
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
        $tenant = $this->callLog->tenant;
        if (! $tenant) {
            return;
        }

        // Apply tenant scope context
        TenantScope::setTenantId($tenant->id);

        // Prevent duplicate confirmation texts
        $lockKey = "sms_confirmation_sent_for_call_{$this->callLog->id}";
        if (! Cache::add($lockKey, true, now()->addHour())) {
            return;
        }

        // Don't send if customer phone is unknown
        if ($this->callLog->customer_phone === 'Unknown') {
            return;
        }

        // Retrieve the successful booking from cache mapping (created during dispatch webhook)
        $bookingId = Cache::get("call_booking_map:{$this->callLog->call_id}");
        if (! $bookingId) {
            // Fallback: look for the latest booking for this customer phone
            $booking = Booking::where('customer_phone', $this->callLog->customer_phone)
                ->orderBy('id', 'desc')
                ->first();
        } else {
            $booking = Booking::find($bookingId);
        }

        if (! $booking || $booking->status !== 'booked') {
            Log::info("Skipping SMS confirmation for Call ID: {$this->callLog->call_id} because no active booking was found.");

            return;
        }

        // Build mobile-friendly tracking link
        $trackingLink = url("/admin/csat-feedback?booking_id={$booking->id}");
        $body = "Your appointment is confirmed! Track your technician here: {$trackingLink}";

        // Save conversation and message details
        $conversation = Conversation::firstOrCreate([
            'tenant_id' => $tenant->id,
            'customer_phone' => $this->callLog->customer_phone,
        ], [
            'subject' => 'SMS Booking Confirmation',
            'status' => 'open',
        ]);

        $sanitizer = app(ComplianceSanitizerService::class);
        $sanitizedBody = $sanitizer->sanitize($body);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'agent',
            'body' => $sanitizedBody,
        ]);

        // Broadcast to Omni-Channel chat dashboard UI
        event(new ChatMessageReceived($tenant->id, $message));

        // Submit the text content programmatically via Twilio, Retell, or Vapi messaging APIs
        $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));
        $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';

        try {
            if ($provider === 'vapi') {
                Http::withToken($apiKey)->timeout(5)->post('https://api.vapi.ai/sms', [
                    'to' => $this->callLog->customer_phone,
                    'message' => $sanitizedBody,
                ]);
            } elseif ($provider === 'retell') {
                Http::withToken($apiKey)->timeout(5)->post('https://api.retellai.com/v2/sms', [
                    'to' => $this->callLog->customer_phone,
                    'text' => $sanitizedBody,
                ]);
            } else {
                // Twilio or other fallback
                Http::timeout(5)->post('https://api.twilio.com/mock-send-sms', [
                    'to' => $this->callLog->customer_phone,
                    'from' => $tenant->getSetting('sms_number', '+15555555555'),
                    'body' => $sanitizedBody,
                ]);
            }
            Log::info("SMS confirmation sent to customer {$this->callLog->customer_phone} via provider: {$provider}");
        } catch (\Exception $e) {
            Log::warning('SMS confirmation dispatch failed: '.$e->getMessage());
        }
    }
}
