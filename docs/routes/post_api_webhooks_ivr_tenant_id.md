# API Reference: POST /api/webhooks/ivr/{tenant_id?}

Handle incoming IVR digit presses / DTMF tones.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/webhooks/ivr/{tenant_id?}` |
| **HTTP Methods** | `POST` |
| **Route Name** | `webhook.ivr` |
| **Controller Action** | `App\Http\Controllers\Api\IvrController@handle` |
| **Middleware** | `api`, `App\Http\Middleware\RestrictToTelephonyIps`, `App\Http\Middleware\EnsureWebhookIdempotency` |

## How it Works

Applies tenant isolation scoping rules.

## How to Use

Send HTTP request calls.
