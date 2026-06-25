# Route: GET /email/verify/{id}/{hash}

Performs email verification validation.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/email/verify/{id}/{hash}` |
| **HTTP Methods** | `GET` |
| **Route Name** | `verification.verify` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\VerifyEmailController@__invoke` |
| **Middleware** | `web`, `auth:web`, `signed`, `throttle:6,1` |

## How it Works

GET: Validates the signed URL signature containing the user id and email hash, marks the email as verified in the DB, and redirects to dashboard.

## How to Use

Accessed by clicking the verification link sent via email.
