# Route: POST /user/two-factor-recovery-codes

Regenerates a new set of two-factor recovery codes.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/two-factor-recovery-codes` |
| **HTTP Methods** | `POST` |
| **Route Name** | `two-factor.regenerate-recovery-codes` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\RecoveryCodeController@store` |
| **Middleware** | `web`, `auth:web`, `password.confirm` |

## How it Works

POST: Generates, encrypts, and saves 8 new recovery codes to replace the old ones.

## How to Use

Send a POST request to `/user/two-factor-recovery-codes`.
