# Route: GET /two-factor-challenge

Renders the two-factor authentication OTP login form.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/two-factor-challenge` |
| **HTTP Methods** | `GET` |
| **Route Name** | `two-factor.login` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController@create` |
| **Middleware** | `web`, `guest:web` |

## How it Works

GET: Displays the interface to input a two-factor authentication code or recovery code.

## How to Use

Redirected automatically if 2FA is active for the logging-in account.
