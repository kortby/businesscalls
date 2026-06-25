# API Reference: POST /passkeys/confirm

Confirm the user's password via passkey verification.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/passkeys/confirm` |
| **HTTP Methods** | `POST` |
| **Route Name** | `passkey.confirm` |
| **Controller Action** | `Laravel\Passkeys\Http\Controllers\PasskeyConfirmationController@store` |
| **Middleware** | `web`, `auth:web`, `throttle:passkeys` |

## How it Works

Processes request triggers.

## How to Use

Send HTTP request calls.
