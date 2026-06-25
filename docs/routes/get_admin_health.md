# Route: GET /admin/health

Display the system health telemetry panel.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/health` |
| **HTTP Methods** | `GET` |
| **Route Name** | `admin.health` |
| **Controller Action** | `App\Http\Controllers\AdminController@health` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Admin/SystemHealth` |

## How it Works

Renders the Inertia SPA view: `Admin/SystemHealth`. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
