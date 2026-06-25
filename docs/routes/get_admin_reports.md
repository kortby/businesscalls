# Route: GET /admin/reports

Display the playful executive reports overview dashboard.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/reports` |
| **HTTP Methods** | `GET` |
| **Route Name** | `admin.reports` |
| **Controller Action** | `App\Http\Controllers\AdminController@executiveReports` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Admin/ExecutiveReports` |

## How it Works

Renders the Inertia SPA view: `Admin/ExecutiveReports`. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
