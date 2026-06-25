# Route: GET /admin/call-flow

Display the visual drag-and-drop call flow builder.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/call-flow` |
| **HTTP Methods** | `GET` |
| **Route Name** | `admin.callflow` |
| **Controller Action** | `App\Http\Controllers\AdminController@callFlow` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Admin/CallFlowBuilder` |

## How it Works

Renders the Inertia SPA view: `Admin/CallFlowBuilder`. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
