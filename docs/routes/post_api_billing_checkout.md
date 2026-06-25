# API Reference: POST /api/billing/checkout

Generate a Stripe Checkout Session redirect URL for plan upgrades.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/billing/checkout` |
| **HTTP Methods** | `POST` |
| **Route Name** | `billing.checkout` |
| **Controller Action** | `App\Http\Controllers\Api\StripeBillingController@checkout` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Saves models updates. Applies tenant isolation scoping rules.

## Request Parameters

| Parameter | Type | Required | Rules / Constraints |
| --- | --- | --- | --- |
| `plan` | `string` | Yes | `required, string, in:pro, enterprise` |

## How to Use

Send HTTP request calls.

### Example Request Body

```json
{
    "plan": "value"
}
```
