<?php

namespace App\Jobs;

use App\Attributes\Queue;
use App\Attributes\Tries;
use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use App\Services\CallEvaluationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

#[Queue('evaluations')]
#[Tries(3)]
class EvaluateCallJob implements ShouldQueue
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
    public function handle(CallEvaluationService $evaluationService): void
    {
        $tenant = $this->callLog->tenant;
        if (! $tenant) {
            Log::warning('EvaluateCallJob aborted: CallLog does not belong to a valid tenant.');

            return;
        }

        // Apply tenant database scoping inside queue worker
        TenantScope::setTenantId($tenant->id);

        $evaluationService->evaluateCall($this->callLog);
    }
}
