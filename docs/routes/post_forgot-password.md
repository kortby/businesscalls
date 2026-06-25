# Route: POST /forgot-password

Sends a password reset link to the specified user email address.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/forgot-password` |
| **HTTP Methods** | `POST` |
| **Route Name** | `password.email` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\PasswordResetLinkController@store` |
| **Middleware** | `web`, `guest:web` |

## How it Works

POST: Validates the email address, generates a unique secure token, and dispatches a password recovery email notifications.

## How to Use

POST request payload:

```json
{
  "email": "user@example.com"
}
```
