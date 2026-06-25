# API Reference: POST /api/web-calls/barge

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

Triggers WebSocket broadcasts.

## How to Use

Send HTTP request calls.
