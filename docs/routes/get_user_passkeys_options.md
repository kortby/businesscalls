# Route: GET /user/passkeys/options

Retrieves registration options for passkeys.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/passkeys/options` |
| **HTTP Methods** | `GET` |
| **Route Name** | `passkey.registration-options` |
| **Controller Action** | `Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController@index` |
| **Middleware** | `web`, `auth:web`, `password.confirm`, `throttle:passkeys` |

## How it Works

GET: Generates registration challenge config details.

## How to Use

Fetch registration options values.
