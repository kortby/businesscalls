# API Reference: GET /email/verify/{id}/{hash}

Mark the authenticated user's email address as verified.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/email/verify/{id}/{hash}` |
| **HTTP Methods** | `GET` |
| **Route Name** | `verification.verify` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\VerifyEmailController@__invoke` |
| **Middleware** | `web`, `auth:web`, `signed`, `throttle:6,1` |

## How it Works

Triggers WebSocket broadcasts.

## How to Use

Send HTTP request calls.
