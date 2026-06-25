# Route: GET /reset-password/{token}

Renders the password update/reset form.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/reset-password/{token}` |
| **HTTP Methods** | `GET` |
| **Route Name** | `password.reset` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\NewPasswordController@create` |
| **Middleware** | `web`, `guest:web` |

## How it Works

GET: Validates the password reset token in the URI and renders the Inertia view to input a new password.

## How to Use

Navigate to `/reset-password/{token}` containing the reset token received in the email.
