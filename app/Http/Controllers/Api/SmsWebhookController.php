<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageReceived;
use App\Helpers\PromptCompiler;
use App\Http\Controllers\Controller;
use App\Jobs\SendTechnicianAlertJob;
use App\Models\Availability;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Message;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\ComplianceSanitizerService;
use App\Services\LlmService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Text;

class SmsWebhookController extends Controller
{
    /**
     * Handle incoming SMS webhooks (from Twilio or other providers).
     */
    public function handle(Request $request, ?string $tenant_id, LlmService $llmService): Response
    {
        // 1. Resolve Tenant ID
        $resolvedTenantId = $tenant_id
            ?? $request->query('tenant_id')
            ?? $request->input('tenant_id')
            ?? $request->input('tenant_slug');

        if (! $resolvedTenantId) {
            // Alternatively, look up by the 'To' field in the payload
            $toPhone = $request->input('To');
            if ($toPhone) {
                $tenant = Tenant::where('settings->sms_number', $toPhone)
                    ->orWhere('settings->phone_number', $toPhone)
                    ->first();
            }
        }

        if (! isset($tenant)) {
            $tenant = Tenant::where('id', $resolvedTenantId)
                ->orWhere('slug', $resolvedTenantId)
                ->first();
        }

        if (! $tenant) {
            return response('<Response><Message>Tenant not found.</Message></Response>', 404)
                ->header('Content-Type', 'text/xml');
        }

        // Apply tenant scope context
        TenantScope::setTenantId($tenant->id);

        // 2. Extract sender (From) and text body
        $from = $request->input('From');
        $body = $request->input('Body');

        if (! $from || $body === null) {
            return response('<Response><Message>Missing From or Body parameters.</Message></Response>', 400)
                ->header('Content-Type', 'text/xml');
        }

        // --- Start Autonomous SMS Scheduler Agent ---
        $allSkills = Employee::all()->pluck('skills')->flatten()->unique()->toArray();
        $skillsList = implode(', ', $allSkills);

        $smsPrompt = "You are an SMS scheduling assistant. Analyze the incoming customer SMS message: '{$body}'
Current local time is: ".Carbon::now()->toDateTimeString()."
Available trade categories are: {$skillsList}

Perform zero-shot entity extraction parsing the customer's requested trade category and time preferences.
You must return a valid JSON object matching this schema exactly. If the customer is not requesting to book or schedule a service, return null for both fields:
{
  \"trade_category\": \"one of the available trade categories (or null if not specified/applicable)\",
  \"requested_time\": \"the requested date and time in YYYY-MM-DD HH:MM:SS format (or null if not specified/applicable)\"
}";

        $bookingCommitted = false;
        $smsResponseText = null;

        try {
            $aiResponse = Text::prompt($smsPrompt);
            $parsed = json_decode($aiResponse, true);

            if ($parsed && ! empty($parsed['trade_category']) && ! empty($parsed['requested_time'])) {
                $serviceType = $parsed['trade_category'];
                $requestedTime = $parsed['requested_time'];
                $requestedTimeCarbon = Carbon::parse($requestedTime);
                $dayOfWeek = $requestedTimeCarbon->dayOfWeek;
                $timeOnly = $requestedTimeCarbon->format('H:i:s');

                // Match available employee
                $employees = Employee::get()->filter(function ($employee) use ($serviceType) {
                    return is_array($employee->skills) && in_array($serviceType, $employee->skills);
                });

                $assignedEmployee = null;

                foreach ($employees as $employee) {
                    $isAvailable = Availability::where('employee_id', $employee->id)
                        ->where('day_of_week', $dayOfWeek)
                        ->where('is_active', true)
                        ->where('start_time', '<=', $timeOnly)
                        ->where('end_time', '>=', $timeOnly)
                        ->exists();

                    if (! $isAvailable) {
                        continue;
                    }

                    // Check booking collision with 1.5-hour buffer
                    $bufferMinutes = 90;
                    $startBuffer = $requestedTimeCarbon->copy()->subMinutes($bufferMinutes);
                    $endBuffer = $requestedTimeCarbon->copy()->addMinutes($bufferMinutes);

                    $hasOverlap = Booking::where('employee_id', $employee->id)
                        ->where('status', 'booked')
                        ->whereBetween('scheduled_start', [$startBuffer, $endBuffer])
                        ->exists();

                    if (! $hasOverlap) {
                        $assignedEmployee = $employee;
                        break;
                    }
                }

                if ($assignedEmployee) {
                    $booking = Booking::create([
                        'tenant_id' => $tenant->id,
                        'employee_id' => $assignedEmployee->id,
                        'customer_phone' => $from,
                        'job_details' => "SMS Booked AI dispatch for {$serviceType}",
                        'status' => 'booked',
                        'scheduled_start' => $requestedTimeCarbon,
                    ]);

                    SendTechnicianAlertJob::dispatch($booking);

                    $smsResponseText = "Dispatch Confirmed! We booked {$assignedEmployee->first_name} for {$serviceType} on ".$requestedTimeCarbon->format('M d, Y \a\t g:i A').'.';
                    $bookingCommitted = true;
                } else {
                    $smsResponseText = "No available technician with skill '{$serviceType}' was found for that time slot. Please try another time.";
                    $bookingCommitted = true; // prevent fallback to standard LLM chat
                }
            }
        } catch (\Exception $e) {
            Log::error('SMS Scheduler Agent error: '.$e->getMessage());
        }

        if ($bookingCommitted && $smsResponseText) {
            // Find or create customer
            $customer = Customer::where('phone', $from)->first();
            if (! $customer) {
                $customer = Customer::create([
                    'tenant_id' => $tenant->id,
                    'name' => 'SMS User '.substr($from, -4),
                    'phone' => $from,
                    'language' => 'en',
                ]);
            }

            // Find or create Conversation thread
            $conversation = Conversation::firstOrCreate([
                'tenant_id' => $tenant->id,
                'customer_phone' => $from,
            ], [
                'subject' => 'SMS Chat with '.$customer->name,
                'status' => 'open',
            ]);

            // Save customer message
            $body = app(ComplianceSanitizerService::class)->sanitize($body);
            $customerMessage = Message::create([
                'conversation_id' => $conversation->id,
                'sender' => 'customer',
                'body' => $body,
            ]);
            event(new ChatMessageReceived($tenant->id, $customerMessage));

            // Save agent message
            $smsResponseText = app(ComplianceSanitizerService::class)->sanitize($smsResponseText);
            $agentMessage = Message::create([
                'conversation_id' => $conversation->id,
                'sender' => 'agent',
                'body' => $smsResponseText,
            ]);
            event(new ChatMessageReceived($tenant->id, $agentMessage));

            $conversation->touch();

            $xmlContent = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
                          "<Response>\n".
                          '    <Message>'.htmlspecialchars($smsResponseText)."</Message>\n".
                          '</Response>';

            return response($xmlContent, 200)
                ->header('Content-Type', 'text/xml');
        }
        // --- End Autonomous SMS Scheduler Agent ---

        // Find or create customer
        $customer = Customer::where('phone', $from)->first();
        if (! $customer) {
            $customer = Customer::create([
                'tenant_id' => $tenant->id,
                'name' => 'SMS User '.substr($from, -4),
                'phone' => $from,
                'language' => 'en',
            ]);
        }

        // 3. Find or create Conversation thread
        $conversation = Conversation::firstOrCreate([
            'tenant_id' => $tenant->id,
            'customer_phone' => $from,
        ], [
            'subject' => 'SMS Chat with '.$customer->name,
            'status' => 'open',
        ]);

        // Save incoming customer message
        $body = app(ComplianceSanitizerService::class)->sanitize($body);
        $customerMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'customer',
            'body' => $body,
        ]);

        // Broadcast incoming message to dashboard UI
        event(new ChatMessageReceived($tenant->id, $customerMessage));

        // 4. Construct conversation history for the LLM
        $messageHistory = $conversation->messages()
            ->oldest()
            ->take(20)
            ->get()
            ->map(fn ($msg) => [
                'role' => $msg->sender === 'customer' ? 'user' : 'assistant',
                'content' => $msg->body,
            ])
            ->toArray();

        // 5. Compile system instruction template with variables
        $systemTemplate = $tenant->getSetting('prompt')
            ?? 'You are the AI voice/SMS assistant for {{business_name}}. Help the customer scheduling/checking bookings.';

        $compiledPrompt = PromptCompiler::compile($systemTemplate, $tenant, $customer);

        // 6. Generate reply from LLM service
        $replyText = $llmService->generateResponse($compiledPrompt, $messageHistory);
        $replyText = app(ComplianceSanitizerService::class)->sanitize($replyText);

        // Save outgoing agent message
        $agentMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'agent',
            'body' => $replyText,
        ]);

        // Broadcast reply to dashboard UI
        event(new ChatMessageReceived($tenant->id, $agentMessage));

        // Touch conversation updated_at
        $conversation->touch();

        // 7. Output standard TwiML XML
        $xmlContent = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
                      "<Response>\n".
                      '    <Message>'.htmlspecialchars($replyText)."</Message>\n".
                      '</Response>';

        return response($xmlContent, 200)
            ->header('Content-Type', 'text/xml');
    }
}
