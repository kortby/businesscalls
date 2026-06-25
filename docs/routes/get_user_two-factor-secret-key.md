# Route: GET /user/two-factor-secret-key

Retrieves the raw text two-factor secret key.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/two-factor-secret-key` |
| **HTTP Methods** | `GET` |
| **Route Name** | `two-factor.secret-key` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController@show` |
| **Middleware** | `web`, `auth:web`, `password.confirm` |

## How it Works

GET: Decrypts and returns the raw secret key for manual OTP enrollment.

## How to Use

Retrieve raw key string value for display.
