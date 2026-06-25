# Route: POST /admin/integrations/timing

Save customized speech timing settings.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/integrations/timing` |
| **HTTP Methods** | `POST` |
| **Route Name** | `admin.integrations.timing` |
| **Controller Action** | `App\Http\Controllers\AdminController@saveTimingSettings` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Applies tenant isolation scoping rules to isolate company data.

## How to Use

Perform an HTTP POST request with the required payload parameters.
