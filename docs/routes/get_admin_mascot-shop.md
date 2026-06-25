# Route: GET /admin/mascot-shop

Display the playful Mascot Customization Shop.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/mascot-shop` |
| **HTTP Methods** | `GET` |
| **Route Name** | `admin.mascot-shop` |
| **Controller Action** | `App\Http\Controllers\AdminController@mascotShop` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Admin/MascotShop` |

## How it Works

Renders the Inertia SPA view: `Admin/MascotShop`. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
