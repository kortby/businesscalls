# Route: POST /jobs

Store a newly created service job.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/jobs` |
| **HTTP Methods** | `POST` |
| **Route Name** | `jobs.store` |
| **Controller Action** | `App\Http\Controllers\ServiceJobController@store` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Stores or persists model state to the database. Applies tenant isolation scoping rules to isolate company data.

## Request Parameters

| Parameter | Type | Required | Rules / Constraints |
| --- | --- | --- | --- |
| `customer_id` | `string` | Yes | `required, exists:customers, id` |
| `employee_id` | `string` | No | `nullable, exists:employees, id` |
| `title` | `string` | Yes | `required, string, max:255` |
| `description` | `string` | No | `nullable, string, max:1000` |
| `status` | `string` | Yes | `required, string, in:pending, in_progress, completed, cancelled` |
| `steps` | `array` | No | `nullable, array` |

## How to Use

Perform an HTTP POST request with the required payload parameters.

### Example Request Body

```json
{
    "customer_id": "value",
    "employee_id": "value",
    "title": "value",
    "description": "value",
    "status": "value",
    "steps": []
}
```
