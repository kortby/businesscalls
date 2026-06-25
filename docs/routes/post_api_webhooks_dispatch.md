# API Reference: POST /api/webhooks/dispatch

Developer API endpoint.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/webhooks/dispatch` |
| **HTTP Methods** | `POST` |
| **Route Name** | *None* |
| **Controller Action** | `App\Http\Controllers\Api\DispatchWebhookController` |
| **Middleware** | `api`, `App\Http\Middleware\VerifyOAuthWebhookToken`, `App\Http\Middleware\WebhookGatewayMiddleware`, `App\Http\Middleware\RestrictToTelephonyIps`, `App\Http\Middleware\EnsureWebhookIdempotency` |

## How it Works

Processes request triggers.

## How to Use

Send HTTP request calls.
