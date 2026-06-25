# API Reference: POST /api/settings/toggle-sandbox

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

Saves models updates. Applies tenant isolation scoping rules.

## How to Use

Send HTTP request calls.
