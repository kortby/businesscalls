# API Reference: POST /passkeys/login

Verify the passkey and log the user in.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/passkeys/login` |
| **HTTP Methods** | `POST` |
| **Route Name** | `passkey.login` |
| **Controller Action** | `Laravel\Passkeys\Http\Controllers\PasskeyLoginController@store` |
| **Middleware** | `web`, `guest:web`, `throttle:passkeys` |

## How it Works

Processes request triggers.

## How to Use

Send HTTP request calls.
