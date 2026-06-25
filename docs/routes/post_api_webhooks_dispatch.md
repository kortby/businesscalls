# Route: POST /api/webhooks/dispatch

No description available.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/webhooks/dispatch` |
| **HTTP Methods** | `POST` |
| **Route Name** | *None* |
| **Controller Action** | `App\Http\Controllers\Api\DispatchWebhookController` |
| **Middleware** | `api`, `App\Http\Middleware\VerifyOAuthWebhookToken`, `App\Http\Middleware\WebhookGatewayMiddleware`, `App\Http\Middleware\RestrictToTelephonyIps`, `App\Http\Middleware\EnsureWebhookIdempotency` |

## How it Works

Standard routing endpoint.

## How to Use

Access via the specified HTTP method.
