# Route: GET /jobs

Display a listing of service jobs with customer and employee lists.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/jobs` |
| **HTTP Methods** | `GET` |
| **Route Name** | `jobs.index` |
| **Controller Action** | `App\Http\Controllers\ServiceJobController@index` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `jobs/Index` |

## How it Works

Renders the Inertia SPA view: `jobs/Index`.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
