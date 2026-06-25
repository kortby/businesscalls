# Route: POST /api/settings/branded-caller-id

Submit Branded Caller ID registration details via API.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/settings/branded-caller-id` |
| **HTTP Methods** | `POST` |
| **Route Name** | *None* |
| **Controller Action** | `App\Http\Controllers\AdminController@submitBrandedCallerId` |
| **Middleware** | `api`, `auth:sanctum` |

## How it Works

Applies tenant isolation scoping rules to isolate company data.

## How to Use

Perform an HTTP POST request with the required payload parameters.
