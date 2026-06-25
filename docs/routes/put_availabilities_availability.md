# Route: PUT /availabilities/{availability}

Update the specified availability shift in storage.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/availabilities/{availability}` |
| **HTTP Methods** | `PUT` |
| **Route Name** | `availabilities.update` |
| **Controller Action** | `App\Http\Controllers\AvailabilityController@update` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Applies tenant isolation scoping rules to isolate company data.

## Request Parameters

| Parameter | Type | Required | Rules / Constraints |
| --- | --- | --- | --- |
| `employee_id` | `string` | Yes | `required, Rule::exists(employees, id)->where(tenant_id, auth()->user()->tenant_id), ` |
| `day_of_week` | `integer` | Yes | `required, integer, min:0, max:6` |
| `start_time` | `string` | Yes | `required, date_format:H:i` |
| `end_time` | `string` | Yes | `required, date_format:H:i, after:start_time` |
| `is_active` | `boolean` | No | `boolean` |

## How to Use

Perform an HTTP PUT request with the required payload parameters.

### Example Request Body

```json
{
    "employee_id": "value",
    "day_of_week": 1,
    "start_time": "value",
    "end_time": "value",
    "is_active": true
}
```
