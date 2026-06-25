# Route: POST /passkeys/confirm

Validates user credentials via passkey signature confirmation.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/passkeys/confirm` |
| **HTTP Methods** | `POST` |
| **Route Name** | `passkey.confirm` |
| **Controller Action** | `Laravel\Passkeys\Http\Controllers\PasskeyConfirmationController@store` |
| **Middleware** | `web`, `auth:web`, `throttle:passkeys` |

## How it Works

POST: Verifies a passkey assertion signature for high-security actions.

## How to Use

Sends the WebAuthn signature response payload.
