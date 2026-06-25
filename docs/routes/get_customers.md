# Route: GET /customers

Display a listing of distinct customers (phone numbers) and their activity.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/customers` |
| **HTTP Methods** | `GET` |
| **Route Name** | `customers.index` |
| **Controller Action** | `App\Http\Controllers\CustomerController@index` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `customers/Index` |

## How it Works

Renders the Inertia SPA view: `customers/Index`.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
