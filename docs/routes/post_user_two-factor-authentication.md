# Route: POST /user/two-factor-authentication

Enables two-factor authentication for the authenticated user.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/two-factor-authentication` |
| **HTTP Methods** | `POST` |
| **Route Name** | `two-factor.enable` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController@store` |
| **Middleware** | `web`, `auth:web`, `password.confirm` |

## How it Works

POST: Generates a 2FA secret key, recovery codes, and updates the user model state to active.

## How to Use

Requires password confirmation before enabling.
