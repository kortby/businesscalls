<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TriggerWorkflowIntegrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $webhookUrl,
        public array $payload
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Triggering workflow integration to: {$this->webhookUrl}");

        $response = Http::timeout(10)->post($this->webhookUrl, $this->payload);

        if ($response->failed()) {
            Log::error("Workflow integration failed for {$this->webhookUrl}: ".$response->body());
            throw new \Exception('Workflow integration webhook call failed: '.$response->status());
        }

        Log::info("Workflow integration triggered successfully to {$this->webhookUrl}.");
    }
}
