# Route: PUT|PATCH /employees/{employee}

Update the specified employee.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/employees/{employee}` |
| **HTTP Methods** | `PUT|PATCH` |
| **Route Name** | `employees.update` |
| **Controller Action** | `App\Http\Controllers\EmployeeController@update` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Processes request through the controller action.

## Request Parameters

| Parameter | Type | Required | Rules / Constraints |
| --- | --- | --- | --- |
| `first_name` | `string` | Yes | `required, string, max:255` |
| `last_name` | `string` | Yes | `required, string, max:255` |
| `phone` | `string` | Yes | `required, string, max:50` |
| `skills` | `array` | No | `nullable, array` |
| `notification_preference` | `string` | Yes | `required, string, in:sms, email, both` |

## How to Use

Perform an HTTP PUT|PATCH request with the required payload parameters.
