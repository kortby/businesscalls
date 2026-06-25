# Route: GET /availabilities

Display a listing of shift availabilities.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/availabilities` |
| **HTTP Methods** | `GET` |
| **Route Name** | `availabilities.index` |
| **Controller Action** | `App\Http\Controllers\AvailabilityController@index` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Availabilities` |

## How it Works

Renders the Inertia SPA view: `Availabilities`.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
