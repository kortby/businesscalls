# Route: POST /api/settings/toggle-sandbox

Toggle the sandbox/test mode state of the active tenant.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/settings/toggle-sandbox` |
| **HTTP Methods** | `POST` |
| **Route Name** | *None* |
| **Controller Action** | `App\Http\Controllers\Api\SandboxToggleController@toggle` |
| **Middleware** | `api`, `auth:sanctum` |

## How it Works

Stores or persists model state to the database. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Perform an HTTP POST request with the required payload parameters.
