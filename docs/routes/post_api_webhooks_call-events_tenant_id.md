# API Reference: POST /api/webhooks/call-events/{tenant_id?}

Handle incoming telephony events from Retell/Vapi.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/webhooks/call-events/{tenant_id?}` |
| **HTTP Methods** | `POST` |
| **Route Name** | `webhook.call-events` |
| **Controller Action** | `App\Http\Controllers\Api\CallWebhookController@handle` |
| **Middleware** | `api`, `App\Http\Middleware\BlockSuspendedTenantCalls`, `App\Http\Middleware\RestrictToTelephonyIps`, `App\Http\Middleware\EnsureWebhookIdempotency` |

## How it Works

Applies tenant isolation scoping rules. Triggers WebSocket broadcasts.

## How to Use

Send HTTP request calls.
