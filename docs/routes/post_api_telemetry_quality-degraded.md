# API Reference: POST /api/telemetry/quality-degraded

Broadcast call quality degradation metrics to supervisors.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/telemetry/quality-degraded` |
| **HTTP Methods** | `POST` |
| **Route Name** | *None* |
| **Controller Action** | `App\Http\Controllers\Api\WebRtcTelemetryController@degraded` |
| **Middleware** | `api`, `auth:sanctum` |

## How it Works

Triggers WebSocket broadcasts.

## How to Use

Send HTTP request calls.
