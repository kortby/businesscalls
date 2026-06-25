# API Reference: POST /user/two-factor-recovery-codes

Generate a fresh set of two factor authentication recovery codes.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/two-factor-recovery-codes` |
| **HTTP Methods** | `POST` |
| **Route Name** | `two-factor.regenerate-recovery-codes` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\RecoveryCodeController@store` |
| **Middleware** | `web`, `auth:web`, `password.confirm` |

## How it Works

Processes request triggers.

## How to Use

Send HTTP request calls.
