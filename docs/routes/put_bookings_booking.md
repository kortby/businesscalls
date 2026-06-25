# Route: PUT /bookings/{booking}

Update the specified booking in storage.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/bookings/{booking}` |
| **HTTP Methods** | `PUT` |
| **Route Name** | `bookings.update` |
| **Controller Action** | `App\Http\Controllers\BookingController@update` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Applies tenant isolation scoping rules to isolate company data. Dispatches real-time broadcast events or queued jobs.

## Request Parameters

| Parameter | Type | Required | Rules / Constraints |
| --- | --- | --- | --- |
| `employee_id` | `string` | Yes | `required, Rule::exists(employees, id)->where(tenant_id, auth()->user()->tenant_id), ` |
| `customer_phone` | `string` | Yes | `required, string, max:20` |
| `job_details` | `string` | Yes | `required, string, max:255` |
| `scheduled_start` | `string` | Yes | `required, date` |

## How to Use

Perform an HTTP PUT request with the required payload parameters.

### Example Request Body

```json
{
    "employee_id": "value",
    "customer_phone": "value",
    "job_details": "value",
    "scheduled_start": "value"
}
```
