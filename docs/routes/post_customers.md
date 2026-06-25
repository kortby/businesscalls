# Route: POST /customers

Store a newly created customer.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/customers` |
| **HTTP Methods** | `POST` |
| **Route Name** | `customers.store` |
| **Controller Action** | `App\Http\Controllers\CustomerController@store` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Stores or persists model state to the database. Applies tenant isolation scoping rules to isolate company data.

## Request Parameters

| Parameter | Type | Required | Rules / Constraints |
| --- | --- | --- | --- |
| `name` | `string` | Yes | `required, string, max:255` |
| `phone` | `string` | Yes | `required, string, max:50, Rule::unique(customers)->where(tenant_id, $tenantId)` |
| `email` | `string` | No | `nullable, email, max:255` |
| `notes` | `string` | No | `nullable, string, max:1000` |

## How to Use

Perform an HTTP POST request with the required payload parameters.

### Example Request Body

```json
{
    "name": "value",
    "phone": "value",
    "email": "value",
    "notes": "value"
}
```
