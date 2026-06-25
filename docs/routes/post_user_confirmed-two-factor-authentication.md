# Route: POST /user/confirmed-two-factor-authentication

Enable two factor authentication for the user.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/confirmed-two-factor-authentication` |
| **HTTP Methods** | `POST` |
| **Route Name** | `two-factor.confirm` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController@store` |
| **Middleware** | `web`, `auth:web`, `password.confirm` |

## How it Works

Processes request through the controller action.

## How to Use

Perform an HTTP POST request with the required payload parameters.
