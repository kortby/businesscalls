# Route: GET /sanctum/csrf-cookie

Retrieve the CSRF protection cookie for Sanctum-authenticated SPA clients.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/sanctum/csrf-cookie` |
| **HTTP Methods** | `GET` |
| **Route Name** | `sanctum.csrf-cookie` |
| **Controller Action** | `Laravel\Sanctum\Http\Controllers\CsrfCookieController@show` |
| **Middleware** | `web` |

## How it Works

Initiates a stateful session and sets the HTTP-only cookie (`XSRF-TOKEN`) required for subsequent state-mutating requests (POST, PUT, DELETE) to protect against Cross-Site Request Forgery.

## How to Use

Make a GET request to `/sanctum/csrf-cookie` before sending any authentication requests (such as login or register).

```bash
curl -X GET http://localhost/sanctum/csrf-cookie -i
```
