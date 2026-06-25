# Route: PATCH /settings/profile

Update the user's profile information.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/settings/profile` |
| **HTTP Methods** | `PATCH` |
| **Route Name** | `profile.update` |
| **Controller Action** | `App\Http\Controllers\Settings\ProfileController@update` |
| **Middleware** | `web`, `auth` |

## How it Works

Processes request through the controller action.

## How to Use

Perform an HTTP PATCH request with the required payload parameters.
