<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantSettingsController extends Controller
{
    /**
     * Show the tenant settings / prompt editor page.
     */
    public function edit(Request $request): Response
    {
        $tenant = $request->user()->tenant;

        return Inertia::render('settings/PromptEditor', [
            'tenant' => $tenant,
            'settings' => $tenant->settings ?? [],
        ]);
    }

    /**
     * Update the tenant settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'ai_prompt' => ['required', 'string', 'max:2000'],
            'emergency_fee' => ['required', 'string', 'max:100'],
            'emergency_rules' => ['nullable', 'string', 'max:1000'],
            'pricing_list' => ['nullable', 'array'],
        ]);

        $settings = $tenant->settings ?? [];

        $settings['ai_prompt'] = $validated['ai_prompt'];
        $settings['emergency_fee'] = $validated['emergency_fee'];
        $settings['emergency_rules'] = $validated['emergency_rules'] ?? '';
        $settings['pricing_list'] = $validated['pricing_list'] ?? [];

        // Construct standard prompt with placeholder variables
        $settings['prompt'] = 'You are the AI voice dispatcher for {{business_name}}. Act professional, friendly, and efficient. Enforce technician active shifts and the mandatory 1.5-hour travel buffer on all bookings. Special Instructions: {{custom_instructions}}. Emergency fee is {{emergency_fee}}. Emergency rules: '.($validated['emergency_rules'] ?? '');

        $tenant->settings = $settings;
        $tenant->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('AI Prompt and pricing settings updated.')]);

        return redirect()->back();
    }
}
