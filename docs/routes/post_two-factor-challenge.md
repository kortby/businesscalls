# API Reference: POST /two-factor-challenge

Attempt to authenticate a new session using the two factor authentication code.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/two-factor-challenge` |
| **HTTP Methods** | `POST` |
| **Route Name** | `two-factor.login.store` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController@store` |
| **Middleware** | `web`, `guest:web`, `throttle:two-factor` |

## How it Works

Triggers WebSocket broadcasts.

## How to Use

Send HTTP request calls.
