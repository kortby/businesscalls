# API Reference: GET /user/two-factor-secret-key

Get the current user's two factor authentication setup / secret key.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/two-factor-secret-key` |
| **HTTP Methods** | `GET` |
| **Route Name** | `two-factor.secret-key` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController@show` |
| **Middleware** | `web`, `auth:web`, `password.confirm` |

## How it Works

Processes request triggers.

## How to Use

Send HTTP request calls.
