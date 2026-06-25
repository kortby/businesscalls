# Route: DELETE /user/two-factor-authentication

Disables two-factor authentication for the authenticated user.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/two-factor-authentication` |
| **HTTP Methods** | `DELETE` |
| **Route Name** | `two-factor.disable` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController@destroy` |
| **Middleware** | `web`, `auth:web`, `password.confirm` |

## How it Works

DELETE: Clears the user's two-factor secret key, recovery codes, and deactivates 2FA.

## How to Use

Requires password confirmation before disabling.
