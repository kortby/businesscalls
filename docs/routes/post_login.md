# API Reference: POST /login

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

Processes request triggers.

## How to Use

Send HTTP request calls.
