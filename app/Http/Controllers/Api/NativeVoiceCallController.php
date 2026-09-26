<?php

namespace App\Http\Controllers\Api;

use App\AI\Tools\BookAppointmentTool;
use App\AI\Tools\CheckInventoryTool;
use App\AI\Tools\CheckTechnicianEtaTool;
use App\AI\Tools\GetAvailabilitySlotsTool;
use App\AI\Tools\GetFirstThreeAvailabilitiesTool;
use App\AI\Tools\RescheduleAppointmentTool;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Ai\Text;

class NativeVoiceCallController extends Controller
{
    /**
     * Start a native audio session via Laravel Reverb and Laravel AI.
     */
    public function startSession(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id')
            ?? $request->input('tenant_slug')
            ?? auth()->user()?->tenant_id;

        if (! $tenantId) {
            return response()->json(['error' => 'Tenant ID or slug is required.'], 400);
        }

        $tenant = Tenant::where('id', $tenantId)
            ->orWhere('slug', $tenantId)
            ->first();

        if (! $tenant) {
            return response()->json(['error' => 'Tenant not found.'], 404);
        }

        TenantScope::setTenantId($tenant->id);

        $callId = 'native_call_'.Str::random(16);
        $customerPhone = $request->input('customer_phone', 'Web Session Caller');

        $callLog = CallLog::create([
            'call_id' => $callId,
            'tenant_id' => $tenant->id,
            'status' => 'ongoing',
            'customer_phone' => $customerPhone,
        ]);

        return response()->json([
            'status' => 'success',
            'call_id' => $callId,
            'tenant_id' => $tenant->id,
            'websocket_channel' => "private-voice-call.{$callId}",
            'reverb_host' => config('reverb.apps.apps.0.options.host', 'localhost'),
            'reverb_port' => config('reverb.apps.apps.0.options.port', 8080),
        ]);
    }

    /**
     * Process native text/speech turns using Laravel AI and execute PHP tools.
     */
    public function processTurn(Request $request): JsonResponse
    {
        $callId = $request->input('call_id');
        $userInput = trim($request->input('message') ?? $request->input('transcript') ?? '');
        $tenantId = $request->input('tenant_id');

        if (! $callId || ! $userInput) {
            return response()->json(['error' => 'call_id and message are required.'], 400);
        }

        $callLog = CallLog::where('call_id', $callId)->first();
        $tenant = $callLog?->tenant ?? Tenant::find($tenantId);

        if (! $tenant) {
            return response()->json(['error' => 'Tenant context not found.'], 404);
        }

        TenantScope::setTenantId($tenant->id);

        $toolsExecuted = [];
        $responseMessage = '';
        $lowered = strtolower($userInput);

        // Native Intent & Tool Routing Pipeline
        if (preg_match('/\b(first 3|3 options|earliest|next available|options)\b/i', $lowered)) {
            $tool = new GetFirstThreeAvailabilitiesTool;
            $res = $tool->handle($tenant->id);
            $toolsExecuted[] = 'get_first_three_availabilities';
            $responseMessage = $res['message'];
        } elseif (preg_match('/\b(slots|available times|open times|tomorrow|schedule)\b/i', $lowered) && ! str_contains($lowered, 'book')) {
            $tool = new GetAvailabilitySlotsTool;
            $res = $tool->handle($tenant->id, now()->addDay()->toDateString());
            $toolsExecuted[] = 'get_availability_slots';
            $responseMessage = $res['message'];
        } elseif (preg_match('/\b(book|appointment|schedule job)\b/i', $lowered)) {
            $tool = new BookAppointmentTool;
            $res = $tool->handle(
                $tenant->id,
                $callLog?->customer_phone ?? '+15550001111',
                $userInput,
                now()->addDay()->setTime(10, 0, 0)->toIso8601String()
            );
            $toolsExecuted[] = 'book_appointment';
            $responseMessage = $res['message'];
        } elseif (preg_match('/\b(reschedule|move appointment|change time)\b/i', $lowered)) {
            $booking = Booking::where('tenant_id', $tenant->id)->latest()->first();
            if ($booking) {
                $tool = new RescheduleAppointmentTool;
                $res = $tool->handle($tenant->id, $booking->id, now()->addDays(2)->setTime(14, 0, 0)->toIso8601String());
                $toolsExecuted[] = 'reschedule_appointment';
                $responseMessage = $res['message'];
            } else {
                $responseMessage = 'I could not find an active booking to reschedule for your phone line.';
            }
        } elseif (preg_match('/\b(where|eta|technician|arriving|gps)\b/i', $lowered)) {
            $booking = Booking::where('tenant_id', $tenant->id)->latest()->first();
            if ($booking) {
                $tool = new CheckTechnicianEtaTool;
                $res = $tool->handle($tenant->id, $booking->id);
                $toolsExecuted[] = 'check_technician_eta';
                $responseMessage = $res['message'];
            } else {
                $responseMessage = 'No active technician dispatch was found for your account.';
            }
        } elseif (preg_match('/\b(stock|part|faucet|pipe|thermostat|inventory)\b/i', $lowered)) {
            $tool = new CheckInventoryTool;
            $res = $tool->handle($tenant->id, 'thermostat');
            $toolsExecuted[] = 'check_inventory';
            $responseMessage = $res['message'];
        } else {
            // General AI response via Laravel AI
            try {
                $systemPrompt = "You are the native voice assistant for {$tenant->name}. Speak briskly and concisely in 1-2 short, direct sentences. Get straight to the chase without filler or repetition.";
                $aiResponse = Text::prompt("System Context: {$systemPrompt}\nUser Message: {$userInput}");
                $responseMessage = trim($aiResponse);
            } catch (\Exception $e) {
                $responseMessage = "Thank you for contacting {$tenant->name}. How can I assist you today?";
            }
        }

        return response()->json([
            'status' => 'success',
            'call_id' => $callId,
            'response' => $responseMessage,
            'tools_executed' => $toolsExecuted,
        ]);
    }

    /**
     * End native voice call session.
     */
    public function endSession(Request $request): JsonResponse
    {
        $callId = $request->input('call_id');
        if (! $callId) {
            return response()->json(['error' => 'call_id is required.'], 400);
        }

        $callLog = CallLog::where('call_id', $callId)->first();
        if ($callLog) {
            $callLog->update([
                'status' => 'ended',
                'call_end_reason' => 'user_hung_up',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'call_id' => $callId,
            'message' => 'Native voice call session ended successfully.',
        ]);
    }
}
