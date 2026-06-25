# Route: GET /settings/security

Show the user's security settings page.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/settings/security` |
| **HTTP Methods** | `GET` |
| **Route Name** | `security.edit` |
| **Controller Action** | `App\Http\Controllers\Settings\SecurityController@edit` |
| **Middleware** | `web`, `auth`, `verified`, `Illuminate\Auth\Middleware\RequirePassword` |
| **Inertia Page Component** | `settings/Security` |

## How it Works

Renders the Inertia SPA view: `settings/Security`.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
