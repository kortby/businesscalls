# Route: GET /admin/status-hud

Display the playful visual system status and health console.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/status-hud` |
| **HTTP Methods** | `GET` |
| **Route Name** | `admin.status-hud` |
| **Controller Action** | `App\Http\Controllers\AdminController@statusHud` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Admin/StatusHUD` |

## How it Works

Renders the Inertia SPA view: `Admin/StatusHUD`. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
