# Route: GET /admin/supervisor-hud

Display the playful visual Supervisor HUD (Duolingo style UI).

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/supervisor-hud` |
| **HTTP Methods** | `GET` |
| **Route Name** | `admin.supervisor-hud` |
| **Controller Action** | `App\Http\Controllers\AdminController@supervisorHud` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Admin/SupervisorHUD` |

## How it Works

Renders the Inertia SPA view: `Admin/SupervisorHUD`. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
