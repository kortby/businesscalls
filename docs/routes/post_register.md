# Route: POST /register

Create a new registered user.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/register` |
| **HTTP Methods** | `POST` |
| **Route Name** | `register.store` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\RegisteredUserController@store` |
| **Middleware** | `web`, `guest:web` |

## How it Works

Dispatches real-time broadcast events or queued jobs.

## How to Use

Perform an HTTP POST request with the required payload parameters.
