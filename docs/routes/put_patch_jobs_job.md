# Route: PUT|PATCH /jobs/{job}

Update the specified service job.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/jobs/{job}` |
| **HTTP Methods** | `PUT|PATCH` |
| **Route Name** | `jobs.update` |
| **Controller Action** | `App\Http\Controllers\ServiceJobController@update` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Processes request through the controller action.

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

Perform an HTTP PUT|PATCH request with the required payload parameters.
