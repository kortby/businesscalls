# Route: GET /user/confirm-password

Renders password confirmation view.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/confirm-password` |
| **HTTP Methods** | `GET` |
| **Route Name** | `password.confirm` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\ConfirmablePasswordController@show` |
| **Middleware** | `web`, `auth:web` |

## How it Works

GET: Displays the prompt requiring password verification before performing administrative actions.

## How to Use

Navigate to `/user/confirm-password` in the browser.
