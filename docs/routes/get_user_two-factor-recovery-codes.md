# Route: GET /user/two-factor-recovery-codes

Retrieves the active two-factor recovery codes.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/two-factor-recovery-codes` |
| **HTTP Methods** | `GET` |
| **Route Name** | `two-factor.recovery-codes` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\RecoveryCodeController@index` |
| **Middleware** | `web`, `auth:web`, `password.confirm` |

## How it Works

GET: Decrypts and returns the collection of recovery codes.

## How to Use

Retrieve codes collection array.
