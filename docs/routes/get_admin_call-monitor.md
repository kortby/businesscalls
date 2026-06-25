# Route: GET /admin/call-monitor

Display the Live Call Monitoring Hub.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/call-monitor` |
| **HTTP Methods** | `GET` |
| **Route Name** | `admin.call-monitor` |
| **Controller Action** | `App\Http\Controllers\AdminController@callMonitor` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Admin/CallMonitor` |

## How it Works

Renders the Inertia SPA view: `Admin/CallMonitor`. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
