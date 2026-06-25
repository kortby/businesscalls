# Route: POST /api/billing/checkout

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

Stores or persists model state to the database. Applies tenant isolation scoping rules to isolate company data.

## Request Parameters

| Parameter | Type | Required | Rules / Constraints |
| --- | --- | --- | --- |
| `plan` | `string` | Yes | `required, string, in:pro, enterprise` |

## How to Use

Perform an HTTP POST request with the required payload parameters.

### Example Request Body

```json
{
    "plan": "value"
}
```
