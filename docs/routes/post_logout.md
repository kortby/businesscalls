# Route: POST /logout

Destroys the authenticated session and logs the user out.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/logout` |
| **HTTP Methods** | `POST` |
| **Route Name** | `logout` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\AuthenticatedSessionController@destroy` |
| **Middleware** | `web`, `auth:web` |

## How it Works

POST: Clears the authenticated session cookie, invalidates the session, and redirects the user to the home page.

## How to Use

Send a POST request to `/logout` with a valid CSRF token header/cookie.
