# API Reference: DELETE /user/two-factor-authentication

Disable two factor authentication for the user.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/two-factor-authentication` |
| **HTTP Methods** | `DELETE` |
| **Route Name** | `two-factor.disable` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController@destroy` |
| **Middleware** | `web`, `auth:web`, `password.confirm` |

## How it Works

Processes request triggers.

## How to Use

Send HTTP request calls.
