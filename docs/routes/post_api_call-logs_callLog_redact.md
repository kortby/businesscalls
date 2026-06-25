# API Reference: POST /api/call-logs/{callLog}/redact

Manually redact sensitive data in the specified call log transcript.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/call-logs/{callLog}/redact` |
| **HTTP Methods** | `POST` |
| **Route Name** | *None* |
| **Controller Action** | `App\Http\Controllers\Api\CallRedactionController@redact` |
| **Middleware** | `api`, `auth:sanctum` |

## How it Works

Saves models updates.

## How to Use

Send HTTP request calls.
