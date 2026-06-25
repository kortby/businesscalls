# Route: GET /admin/audit-logs

Display the playful admin audit logs terminal view.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/audit-logs` |
| **HTTP Methods** | `GET` |
| **Route Name** | `admin.audit-logs` |
| **Controller Action** | `App\Http\Controllers\AdminController@auditLogs` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Admin/AuditLogs` |

## How it Works

Renders the Inertia SPA view: `Admin/AuditLogs`. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
