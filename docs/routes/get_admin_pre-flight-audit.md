# Route: GET /admin/pre-flight-audit

Display the playful pre-flight launch audit panel.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/pre-flight-audit` |
| **HTTP Methods** | `GET` |
| **Route Name** | `admin.preflight` |
| **Controller Action** | `App\Http\Controllers\AdminController@preFlightAudit` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Admin/PreFlightAudit` |

## How it Works

Renders the Inertia SPA view: `Admin/PreFlightAudit`.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
