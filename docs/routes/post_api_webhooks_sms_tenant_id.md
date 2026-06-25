# Route: POST /api/webhooks/sms/{tenant_id?}

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

Stores or persists model state to the database. Applies tenant isolation scoping rules to isolate company data. Dispatches real-time broadcast events or queued jobs.

## How to Use

Perform an HTTP POST request with the required payload parameters.
