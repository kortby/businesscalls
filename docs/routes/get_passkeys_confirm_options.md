# Route: GET /passkeys/confirm/options

Retrieves credentials verification challenge options for passkeys.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/passkeys/confirm/options` |
| **HTTP Methods** | `GET` |
| **Route Name** | `passkey.confirm-options` |
| **Controller Action** | `Laravel\Passkeys\Http\Controllers\PasskeyConfirmationController@index` |
| **Middleware** | `web`, `auth:web`, `throttle:passkeys` |

## How it Works

GET: Generates and stores verification challenge details for WebAuthn API call.

## How to Use

Retrieve challenge configuration settings.
