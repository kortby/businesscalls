# Route: DELETE /settings/profile

Delete the user's profile.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/settings/profile` |
| **HTTP Methods** | `DELETE` |
| **Route Name** | `profile.destroy` |
| **Controller Action** | `App\Http\Controllers\Settings\ProfileController@destroy` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Deletes records or models from the database.

## How to Use

Perform an HTTP DELETE request with the required payload parameters.
