# Route: POST /api/web-calls/whisper

Broadcast a supervisor whisper coaching event to the active technician.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/web-calls/whisper` |
| **HTTP Methods** | `POST` |
| **Route Name** | *None* |
| **Controller Action** | `App\Http\Controllers\Api\WebCallController@whisper` |
| **Middleware** | `api`, `auth:sanctum` |

## How it Works

Applies tenant isolation scoping rules to isolate company data. Dispatches real-time broadcast events or queued jobs.

## How to Use

Perform an HTTP POST request with the required payload parameters.
