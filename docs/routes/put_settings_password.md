# Route: PUT /settings/password

Update the user's password.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/settings/password` |
| **HTTP Methods** | `PUT` |
| **Route Name** | `user-password.update` |
| **Controller Action** | `App\Http\Controllers\Settings\SecurityController@update` |
| **Middleware** | `web`, `auth`, `verified`, `throttle:6,1` |

## How it Works

Processes request through the controller action.

## How to Use

Perform an HTTP PUT request with the required payload parameters.
