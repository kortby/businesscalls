# Route: GET /admin/achievements

Display the achievements panel.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/achievements` |
| **HTTP Methods** | `GET` |
| **Route Name** | `admin.achievements` |
| **Controller Action** | `App\Http\Controllers\AdminController@achievements` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Admin/Achievements` |

## How it Works

Renders the Inertia SPA view: `Admin/Achievements`. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
