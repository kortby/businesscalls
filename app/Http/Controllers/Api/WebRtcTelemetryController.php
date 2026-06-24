<?php

namespace App\Http\Controllers\Api;

use App\Events\WebRtcTelemetryUpdated;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebRtcTelemetryController extends Controller
{
    /**
     * Broadcast incoming WebRTC connection telemetry metrics.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer',
            'call_id' => 'required|string',
            'jitter' => 'required|numeric',
            'latency' => 'required|numeric',
            'packet_loss' => 'required|numeric',
        ]);

        event(new WebRtcTelemetryUpdated(
            (int) $validated['tenant_id'],
            $validated['call_id'],
            (float) $validated['jitter'],
            (float) $validated['latency'],
            (float) $validated['packet_loss']
        ));

        return response()->json(['status' => 'success']);
    }
}
