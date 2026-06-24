<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageReceived;
use App\Helpers\PromptCompiler;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\ComplianceSanitizerService;
use App\Services\LlmService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
