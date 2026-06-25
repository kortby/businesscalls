# Route: POST /bookings

Store a newly created manual booking in storage.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/bookings` |
| **HTTP Methods** | `POST` |
| **Route Name** | `bookings.store` |
| **Controller Action** | `App\Http\Controllers\BookingController@store` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Stores or persists model state to the database. Applies tenant isolation scoping rules to isolate company data. Dispatches real-time broadcast events or queued jobs.

## Request Parameters

| Parameter | Type | Required | Rules / Constraints |
| --- | --- | --- | --- |
| `employee_id` | `string` | Yes | `required, Rule::exists(employees, id)->where(tenant_id, auth()->user()->tenant_id), ` |
| `customer_phone` | `string` | Yes | `required, string, max:20` |
| `job_details` | `string` | Yes | `required, string, max:255` |
| `scheduled_start` | `string` | Yes | `required, date` |
| `recaptcha_token` | `string` | Yes | `app()->environment(testing)?nullable:required, newReCaptcha, ` |

## How to Use

Perform an HTTP POST request with the required payload parameters.

### Example Request Body

```json
{
    "employee_id": "value",
    "customer_phone": "value",
    "job_details": "value",
    "scheduled_start": "value",
    "recaptcha_token": "value"
}
```
