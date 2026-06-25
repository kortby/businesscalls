# API Reference: POST /user/two-factor-authentication

Enable two factor authentication for the user.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/two-factor-authentication` |
| **HTTP Methods** | `POST` |
| **Route Name** | `two-factor.enable` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController@store` |
| **Middleware** | `web`, `auth:web`, `password.confirm` |

## How it Works

Processes request triggers.

## How to Use

Send HTTP request calls.
