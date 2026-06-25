# Route: GET /admin/integrations

Display the playful visual Integrations Panel (Duolingo style UI).

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/integrations` |
| **HTTP Methods** | `GET` |
| **Route Name** | `admin.integrations` |
| **Controller Action** | `App\Http\Controllers\AdminController@integrations` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Admin/Integrations` |

## How it Works

Renders the Inertia SPA view: `Admin/Integrations`. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
