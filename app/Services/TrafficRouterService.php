<?php

namespace App\Services;

use App\Models\Experiment;
use App\Models\ExperimentVariant;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class TrafficRouterService
{
    /**
     * Resolve the active experiment variant for a tenant and apply dynamic routing.
     */
    public function route(Tenant $tenant): ?ExperimentVariant
    {
        $activeExperiment = Experiment::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->first();

        if (! $activeExperiment) {
            return null;
        }

        $variants = $activeExperiment->variants()->get();
        $variantA = $variants->where('version', 'A')->first();
        $variantB = $variants->where('version', 'B')->first();

        if (! $variantA || ! $variantB) {
            return null;
        }

        // Random roll for traffic split (percentage routed to Variant B)
        $roll = rand(1, 100);
        $selectedVariant = ($roll <= $activeExperiment->traffic_split) ? $variantB : $variantA;

        Log::info("TrafficRouter resolved Call to Experiment: {$activeExperiment->name}, Variant: {$selectedVariant->version}");

        return $selectedVariant;
    }

    /**
     * Apply experiment variant prompts and model overrides to a Vapi/Retell call payload.
     */
    public function applyExperimentOverrides(Tenant $tenant, array &$payload, ?ExperimentVariant &$assignedVariant = null): void
    {
        $assignedVariant = $this->route($tenant);

        if (! $assignedVariant) {
            return;
        }

        // 1. Inject experiment variant identification metadata for webhook mapping
        if (! isset($payload['assistantOverrides'])) {
            $payload['assistantOverrides'] = [];
        }

        $payload['assistantOverrides']['metadata']['experiment_variant_id'] = $assignedVariant->id;
        $payload['metadata']['experiment_variant_id'] = $assignedVariant->id;

        // 2. Inject Vapi systemPrompt overrides
        if (! isset($payload['assistantOverrides']['variableValues'])) {
            $payload['assistantOverrides']['variableValues'] = [];
        }
        $payload['assistantOverrides']['variableValues']['custom_instructions'] = $assignedVariant->prompt_instructions;

        // Inject Vapi model overrides
        if ($assignedVariant->model_provider) {
            $parts = explode('/', $assignedVariant->model_provider);
            if (count($parts) === 2) {
                $payload['assistantOverrides']['model']['provider'] = $parts[0];
                $payload['assistantOverrides']['model']['model'] = $parts[1];
            } else {
                $payload['assistantOverrides']['model']['model'] = $assignedVariant->model_provider;
            }
        }

        // 3. Inject Retell variables overrides
        if (! isset($payload['retell_llm_dynamic_variables'])) {
            $payload['retell_llm_dynamic_variables'] = [];
        }
        $payload['retell_llm_dynamic_variables']['custom_instructions'] = $assignedVariant->prompt_instructions;
        $payload['retell_llm_dynamic_variables']['model'] = $assignedVariant->model_provider;
    }
}
