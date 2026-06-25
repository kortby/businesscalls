# Route: GET /technician/dashboard

Show the technician mobile PWA dashboard.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/technician/dashboard` |
| **HTTP Methods** | `GET` |
| **Route Name** | `technician.dashboard` |
| **Controller Action** | `App\Http\Controllers\TechnicianController@dashboard` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `technician/Dashboard` |

## How it Works

Renders the Inertia SPA view: `technician/Dashboard`. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
