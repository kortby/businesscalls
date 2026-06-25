# API Reference: POST /api/web-calls/whisper

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

Triggers WebSocket broadcasts.

## How to Use

Send HTTP request calls.
