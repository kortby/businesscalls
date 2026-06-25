# Route: GET /email/verify

Renders the email verification prompt view.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/email/verify` |
| **HTTP Methods** | `GET` |
| **Route Name** | `verification.notice` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\EmailVerificationPromptController@__invoke` |
| **Middleware** | `web`, `auth:web` |

## How it Works

GET: Renders the verification notice requesting the user to confirm their email address before accessing features.

## How to Use

Navigate to `/email/verify` in the web browser.
