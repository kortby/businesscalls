# Route: GET /user/confirmed-password-status

Checks the password confirmation timeout status.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/confirmed-password-status` |
| **HTTP Methods** | `GET` |
| **Route Name** | `password.confirmation` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\ConfirmedPasswordStatusController@show` |
| **Middleware** | `web`, `auth:web` |

## How it Works

GET: Returns a JSON response indicating whether the user's password has been confirmed within the timeout limit.

## How to Use

Perform GET request to check status.
