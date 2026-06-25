# API Reference: DELETE /user/passkeys/{passkey}

Delete a passkey for the authenticated user.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/passkeys/{passkey}` |
| **HTTP Methods** | `DELETE` |
| **Route Name** | `passkey.destroy` |
| **Controller Action** | `Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController@destroy` |
| **Middleware** | `web`, `auth:web`, `password.confirm` |

## How it Works

Processes request triggers.

## How to Use

Send HTTP request calls.
