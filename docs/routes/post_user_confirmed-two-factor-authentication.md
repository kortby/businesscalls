# API Reference: POST /user/confirmed-two-factor-authentication

Enable two factor authentication for the user.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/confirmed-two-factor-authentication` |
| **HTTP Methods** | `POST` |
| **Route Name** | `two-factor.confirm` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController@store` |
| **Middleware** | `web`, `auth:web`, `password.confirm` |

## How it Works

Processes request triggers.

## How to Use

Send HTTP request calls.
