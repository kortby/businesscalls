# Route: GET /admin/diagnostics

Display the system diagnostic telemetry panel.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/diagnostics` |
| **HTTP Methods** | `GET` |
| **Route Name** | `admin.diagnostics` |
| **Controller Action** | `App\Http\Controllers\AdminController@diagnostics` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Admin/DiagnosticPanel` |

## How it Works

Renders the Inertia SPA view: `Admin/DiagnosticPanel`.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
