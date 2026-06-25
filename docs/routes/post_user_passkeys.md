# Route: POST /user/passkeys

Registers a new passkey credential linked to the user.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/passkeys` |
| **HTTP Methods** | `POST` |
| **Route Name** | `passkey.store` |
| **Controller Action** | `Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController@store` |
| **Middleware** | `web`, `auth:web`, `password.confirm`, `throttle:passkeys` |

## How it Works

POST: Validates WebAuthn registration response details and stores public key credentials.

## How to Use

Sends WebAuthn registration payload.
