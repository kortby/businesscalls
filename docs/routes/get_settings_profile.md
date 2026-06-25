# Route: GET /settings/profile

Show the user's profile settings page.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/settings/profile` |
| **HTTP Methods** | `GET` |
| **Route Name** | `profile.edit` |
| **Controller Action** | `App\Http\Controllers\Settings\ProfileController@edit` |
| **Middleware** | `web`, `auth` |
| **Inertia Page Component** | `settings/Profile` |

## How it Works

Renders the Inertia SPA view: `settings/Profile`.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
