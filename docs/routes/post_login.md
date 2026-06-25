# Route: POST /login

Attempt to authenticate a new session.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/login` |
| **HTTP Methods** | `POST` |
| **Route Name** | `login.store` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\AuthenticatedSessionController@store` |
| **Middleware** | `web`, `guest:web`, `throttle:login` |

## How it Works

Processes request through the controller action.

## How to Use

Perform an HTTP POST request with the required payload parameters.
