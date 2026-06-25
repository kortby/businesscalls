# Route: GET /bookings

Display a listing of appointments.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/bookings` |
| **HTTP Methods** | `GET` |
| **Route Name** | `bookings.index` |
| **Controller Action** | `App\Http\Controllers\BookingController@index` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Bookings` |

## How it Works

Renders the Inertia SPA view: `Bookings`.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
