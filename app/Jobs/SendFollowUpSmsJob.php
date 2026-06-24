<?php

namespace App\Jobs;

use App\Events\ChatMessageReceived;
use App\Models\CallLog;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendFollowUpSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
            return;
        }

        // Apply tenant scope context
        TenantScope::setTenantId($tenant->id);

        // Prevent duplicate follow-up texts
        $lockKey = "sms_followup_sent_for_call_{$this->callLog->id}";
        if (! Cache::add($lockKey, true, now()->addHour())) {
            return;
        }

        // Don't send if customer phone is unknown
        if ($this->callLog->customer_phone === 'Unknown') {
            return;
        }

        // Build follow-up body with booking link
        $bookingLink = url('/bookings');
        $body = "Hi! Thank you for calling {$tenant->name}. Here is the link to book or check your appointment: {$bookingLink}";

        // Save conversation and message details
        $conversation = Conversation::firstOrCreate([
            'tenant_id' => $tenant->id,
            'customer_phone' => $this->callLog->customer_phone,
        ], [
            'subject' => 'SMS Follow-up',
            'status' => 'open',
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'agent',
            'body' => $body,
        ]);

        // Broadcast to Omni-Channel chat dashboard UI
        event(new ChatMessageReceived($tenant->id, $message));

        // Programmatic webhook post simulation (e.g. Twilio API)
        try {
            Http::timeout(10)->post('https://api.twilio.com/mock-send-sms', [
                'to' => $this->callLog->customer_phone,
                'from' => $tenant->getSetting('sms_number', '+15555555555'),
                'body' => $body,
            ]);
        } catch (\Exception $e) {
            Log::warning('Simulated SMS dispatch failed: '.$e->getMessage());
        }
    }
}
