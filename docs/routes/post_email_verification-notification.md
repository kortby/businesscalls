# API Reference: POST /email/verification-notification

Send a new email verification notification.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/email/verification-notification` |
| **HTTP Methods** | `POST` |
| **Route Name** | `verification.send` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController@store` |
| **Middleware** | `web`, `auth:web`, `throttle:6,1` |

## How it Works

Processes request triggers.

## How to Use

Send HTTP request calls.
