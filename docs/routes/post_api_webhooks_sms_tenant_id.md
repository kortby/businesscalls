# API Reference: POST /api/webhooks/sms/{tenant_id?}

Handle incoming SMS webhooks (from Twilio or other providers).

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/webhooks/sms/{tenant_id?}` |
| **HTTP Methods** | `POST` |
| **Route Name** | `webhook.sms` |
| **Controller Action** | `App\Http\Controllers\Api\SmsWebhookController@handle` |
| **Middleware** | `api`, `App\Http\Middleware\RestrictToTelephonyIps`, `App\Http\Middleware\EnsureWebhookIdempotency` |

## How it Works

Saves models updates. Applies tenant isolation scoping rules. Triggers WebSocket broadcasts.

## How to Use

Send HTTP request calls.
