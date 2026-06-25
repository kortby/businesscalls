# Route: DELETE /user/passkeys/{passkey}

Removes a registered passkey credential.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/passkeys/{passkey}` |
| **HTTP Methods** | `DELETE` |
| **Route Name** | `passkey.destroy` |
| **Controller Action** | `Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController@destroy` |
| **Middleware** | `web`, `auth:web`, `password.confirm` |

## How it Works

DELETE: Locates and deletes the specified passkey record from the database.

## How to Use

Send DELETE request to `/user/passkeys/{passkey_id}`.
