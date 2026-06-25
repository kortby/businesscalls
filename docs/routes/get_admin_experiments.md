# Route: GET /admin/experiments

Display the Conversational A/B Prompt Split-Testing Experiments Panel.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/experiments` |
| **HTTP Methods** | `GET` |
| **Route Name** | `admin.experiments` |
| **Controller Action** | `App\Http\Controllers\AdminController@experiments` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Admin/Experiments` |

## How it Works

Renders the Inertia SPA view: `Admin/Experiments`. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
