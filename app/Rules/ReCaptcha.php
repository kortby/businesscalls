<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReCaptcha implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Bypass verification in testing or local environment if keys are not configured
        if (app()->environment('testing')) {
            return;
        }

        $secret = env('RECAPTCHA_SECRET_KEY') ?? config('services.recaptcha.secret') ?? '6LeIxAcTAAAAAGG-vFI1TnFTxWb0NccB1aY1MpwF';

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $value,
                ]);

            if ($response->failed() || ! $response->json('success')) {
                Log::warning('reCAPTCHA validation failed for token: '.substr($value, 0, 10).'...');
                $fail('The reCAPTCHA verification failed. Please try again.');
            }
        } catch (\Exception $e) {
            Log::error('reCAPTCHA verification exception: '.$e->getMessage());
            $fail('The verification service is currently unavailable.');
        }
    }
}
