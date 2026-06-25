# Route: GET /login

Renders the login UI page or processes authentication credentials.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/login` |
| **HTTP Methods** | `GET` |
| **Route Name** | `login` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\AuthenticatedSessionController@create` |
| **Middleware** | `web`, `guest:web` |

## How it Works

GET: Renders the Inertia Welcome page or login form. POST: Validates the request credentials (email, password) and logs the user in using Fortify's session authentication.

## How to Use

POST request payload:

```json
{
  "email": "user@example.com",
  "password": "secret_password"
}
```
