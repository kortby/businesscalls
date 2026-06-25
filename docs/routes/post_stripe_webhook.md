# Route: POST /stripe/webhook

Handles incoming Stripe billing events.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/stripe/webhook` |
| **HTTP Methods** | `POST` |
| **Route Name** | `cashier.webhook` |
| **Controller Action** | `App\Http\Controllers\StripeWebhookController@handleWebhook` |
| **Middleware** | `web` |

## How it Works

POST: Validates the Stripe webhook signature, routes the event type, and triggers corresponding handlers (such as updating subscriber states).

## How to Use

Configured in the Stripe dashboard to forward webhook events.
