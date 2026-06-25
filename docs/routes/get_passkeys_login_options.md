# Route: GET /passkeys/login/options

Retrieves login challenge options for passkeys.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/passkeys/login/options` |
| **HTTP Methods** | `GET` |
| **Route Name** | `passkey.login-options` |
| **Controller Action** | `Laravel\Passkeys\Http\Controllers\PasskeyLoginController@index` |
| **Middleware** | `web`, `guest:web`, `throttle:passkeys` |

## How it Works

GET: Generates login challenge config details.

## How to Use

Fetch login challenge values.
