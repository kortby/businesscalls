# Route: GET /admin/leaderboard

Display the playful Technician Performance Leaderboard (Duolingo style UI).

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/leaderboard` |
| **HTTP Methods** | `GET` |
| **Route Name** | `admin.leaderboard` |
| **Controller Action** | `App\Http\Controllers\AdminController@leaderboard` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Admin/Leaderboard` |

## How it Works

Renders the Inertia SPA view: `Admin/Leaderboard`. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
