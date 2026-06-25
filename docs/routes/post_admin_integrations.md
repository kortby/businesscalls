# Route: POST /admin/integrations

Save or update a tenant integration status/details.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/integrations` |
| **HTTP Methods** | `POST` |
| **Route Name** | `admin.integrations.save` |
| **Controller Action** | `App\Http\Controllers\AdminController@saveIntegration` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Applies tenant isolation scoping rules to isolate company data.

## How to Use

Perform an HTTP POST request with the required payload parameters.
