# Route: POST /email/verification-notification

Resends the email verification notification.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/email/verification-notification` |
| **HTTP Methods** | `POST` |
| **Route Name** | `verification.send` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController@store` |
| **Middleware** | `web`, `auth:web`, `throttle:6,1` |

## How it Works

POST: Throttles request rates and triggers a new email verification notification flow.

## How to Use

Send a POST request to `/email/verification-notification`.
