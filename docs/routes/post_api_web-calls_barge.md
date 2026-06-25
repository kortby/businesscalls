# Route: POST /api/web-calls/barge

Authenticate supervisor, exchange token for barge/monitor session, and broadcast Reverb event.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/web-calls/barge` |
| **HTTP Methods** | `POST` |
| **Route Name** | *None* |
| **Controller Action** | `App\Http\Controllers\Api\WebCallController@barge` |
| **Middleware** | `api`, `auth:sanctum` |

## How it Works

Dispatches real-time broadcast events or queued jobs.

## How to Use

Perform an HTTP POST request with the required payload parameters.
