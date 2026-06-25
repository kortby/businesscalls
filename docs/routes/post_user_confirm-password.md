# Route: POST /user/confirm-password

Validates the password confirmation request.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/confirm-password` |
| **HTTP Methods** | `POST` |
| **Route Name** | `password.confirm.store` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\ConfirmablePasswordController@store` |
| **Middleware** | `web`, `auth:web` |

## How it Works

POST: Validates the password, stores confirmation timestamp in session, and redirects to target route.

## How to Use

POST request payload:

```json
{
  "password": "secret_password"
}
```
