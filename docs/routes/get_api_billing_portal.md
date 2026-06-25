# API Reference: GET /api/billing/portal

Generate a Stripe Customer Portal redirect URL.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/billing/portal` |
| **HTTP Methods** | `GET` |
| **Route Name** | `billing.portal` |
| **Controller Action** | `App\Http\Controllers\Api\StripeBillingController@portal` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Applies tenant isolation scoping rules.

## How to Use

Send HTTP request calls.
