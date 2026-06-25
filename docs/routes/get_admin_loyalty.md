# Route: GET /admin/loyalty

Display the playful customer loyalty panel.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/loyalty` |
| **HTTP Methods** | `GET` |
| **Route Name** | `admin.loyalty` |
| **Controller Action** | `App\Http\Controllers\AdminController@loyalty` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Admin/LoyaltyPanel` |

## How it Works

Renders the Inertia SPA view: `Admin/LoyaltyPanel`. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
