# Route: POST /api/telemetry/quality-degraded

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

Applies tenant isolation scoping rules to isolate company data. Dispatches real-time broadcast events or queued jobs.

## How to Use

Perform an HTTP POST request with the required payload parameters.
