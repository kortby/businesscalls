# Route: POST /reset-password

Performs the password reset operation.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/reset-password` |
| **HTTP Methods** | `POST` |
| **Route Name** | `password.update` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\NewPasswordController@store` |
| **Middleware** | `web`, `guest:web` |

## How it Works

POST: Validates the token, email, and new passwords, updates the user's record in the database, and redirects to the login page.

## How to Use

POST request payload:

```json
{
  "token": "secret-reset-token",
  "email": "user@example.com",
  "password": "new_password",
  "password_confirmation": "new_password"
}
```
