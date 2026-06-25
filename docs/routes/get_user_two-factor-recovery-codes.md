# API Reference: GET /user/two-factor-recovery-codes

Get the two factor authentication recovery codes for authenticated user.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/two-factor-recovery-codes` |
| **HTTP Methods** | `GET` |
| **Route Name** | `two-factor.recovery-codes` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\RecoveryCodeController@index` |
| **Middleware** | `web`, `auth:web`, `password.confirm` |

## How it Works

Processes request triggers.

## How to Use

Send HTTP request calls.
