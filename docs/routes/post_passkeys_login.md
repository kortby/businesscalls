# Route: POST /passkeys/login

Authenticates user login sessions via passkey verification.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/passkeys/login` |
| **HTTP Methods** | `POST` |
| **Route Name** | `passkey.login` |
| **Controller Action** | `Laravel\Passkeys\Http\Controllers\PasskeyLoginController@store` |
| **Middleware** | `web`, `guest:web`, `throttle:passkeys` |

## How it Works

POST: Validates the WebAuthn passkey assertion signature and logs the user in.

## How to Use

Sends assertion signature payload.
