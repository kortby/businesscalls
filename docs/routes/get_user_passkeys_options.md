# API Reference: GET /user/passkeys/options

Get passkey registration options for the authenticated user.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/passkeys/options` |
| **HTTP Methods** | `GET` |
| **Route Name** | `passkey.registration-options` |
| **Controller Action** | `Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController@index` |
| **Middleware** | `web`, `auth:web`, `password.confirm`, `throttle:passkeys` |

## How it Works

Processes request triggers.

## How to Use

Send HTTP request calls.
