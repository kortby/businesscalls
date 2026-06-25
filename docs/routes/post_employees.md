# Route: POST /employees

Store a newly created employee.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/employees` |
| **HTTP Methods** | `POST` |
| **Route Name** | `employees.store` |
| **Controller Action** | `App\Http\Controllers\EmployeeController@store` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Stores or persists model state to the database. Applies tenant isolation scoping rules to isolate company data.

## Request Parameters

| Parameter | Type | Required | Rules / Constraints |
| --- | --- | --- | --- |
| `first_name` | `string` | Yes | `required, string, max:255` |
| `last_name` | `string` | Yes | `required, string, max:255` |
| `phone` | `string` | Yes | `required, string, max:50` |
| `skills` | `array` | No | `nullable, array` |
| `notification_preference` | `string` | Yes | `required, string, in:sms, email, both` |
| `email` | `string` | No | `nullable, email, max:255` |

## How to Use

Perform an HTTP POST request with the required payload parameters.

### Example Request Body

```json
{
    "first_name": "value",
    "last_name": "value",
    "phone": "value",
    "skills": [],
    "notification_preference": "value",
    "email": "value"
}
```
