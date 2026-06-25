# API Reference: GET /passkeys/confirm/options

Get passkey confirmation options for the authenticated user.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/passkeys/confirm/options` |
| **HTTP Methods** | `GET` |
| **Route Name** | `passkey.confirm-options` |
| **Controller Action** | `Laravel\Passkeys\Http\Controllers\PasskeyConfirmationController@index` |
| **Middleware** | `web`, `auth:web`, `throttle:passkeys` |

## How it Works

Processes request triggers.

## How to Use

Send HTTP request calls.
