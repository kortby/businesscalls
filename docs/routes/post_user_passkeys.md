# API Reference: POST /user/passkeys

Store a new passkey for the authenticated user.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/passkeys` |
| **HTTP Methods** | `POST` |
| **Route Name** | `passkey.store` |
| **Controller Action** | `Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController@store` |
| **Middleware** | `web`, `auth:web`, `password.confirm`, `throttle:passkeys` |

## How it Works

Processes request triggers.

## How to Use

Send HTTP request calls.
