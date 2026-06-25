# Route: GET /forgot-password

Renders the password reset request prompt page.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/forgot-password` |
| **HTTP Methods** | `GET` |
| **Route Name** | `password.request` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\PasswordResetLinkController@create` |
| **Middleware** | `web`, `guest:web` |

## How it Works

GET: Renders the password recovery view where users input their email address to receive recovery links.

## How to Use

Navigate to `/forgot-password` in the web browser.
