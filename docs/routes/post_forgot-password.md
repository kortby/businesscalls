# API Reference: POST /forgot-password

Send a reset link to the given user.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/forgot-password` |
| **HTTP Methods** | `POST` |
| **Route Name** | `password.email` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\PasswordResetLinkController@store` |
| **Middleware** | `web`, `guest:web` |

## How it Works

Processes request triggers.

## How to Use

Send HTTP request calls.
