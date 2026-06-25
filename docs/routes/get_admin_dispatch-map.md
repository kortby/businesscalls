# Route: GET /admin/dispatch-map

Display the playful animated Live Dispatch Map (Duolingo style UI).

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/dispatch-map` |
| **HTTP Methods** | `GET` |
| **Route Name** | `admin.dispatch-map` |
| **Controller Action** | `App\Http\Controllers\AdminController@dispatchMap` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Admin/LiveDispatchMap` |

## How it Works

Renders the Inertia SPA view: `Admin/LiveDispatchMap`.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
