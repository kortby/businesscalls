<?php

namespace App\Jobs;

use App\Attributes\Queue;
use App\Attributes\Tries;
use App\Models\Scopes\TenantScope;
use App\Models\TenantWebhook;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

#[Queue('high-priority')]
#[Tries(3)]
class SendWebhookEventJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

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
        public TenantWebhook $webhook,
        public array $payload
    ) {
        $this->onConnection('high-priority');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Apply tenant database scoping context inside queue worker thread
        TenantScope::setTenantId($this->webhook->tenant_id);

        try {
            $jsonBody = json_encode($this->payload);
            $signature = hash_hmac('sha256', $jsonBody, $this->webhook->secret_key);

            $response = Http::withHeaders([
                'X-Webhook-Signature' => $signature,
                'Content-Type' => 'application/json',
            ])
                ->timeout(5)
                ->post($this->webhook->url, $this->payload);

            if ($response->failed()) {
                throw new \Exception('Webhook payload delivery returned status '.$response->status());
            }

            Log::info("Webhook successfully dispatched to {$this->webhook->url} for Tenant ID: {$this->webhook->tenant_id}");
        } catch (\Exception $e) {
            Log::error("Webhook dispatch failed to {$this->webhook->url}: ".$e->getMessage());
            throw $e; // Trigger queue retry mechanisms
        } finally {
            // Always reset scope
            TenantScope::setTenantId(null);
        }
    }
}
