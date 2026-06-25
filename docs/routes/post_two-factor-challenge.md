# Route: POST /two-factor-challenge

Validates two-factor OTP credentials.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/two-factor-challenge` |
| **HTTP Methods** | `POST` |
| **Route Name** | `two-factor.login.store` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController@store` |
| **Middleware** | `web`, `guest:web`, `throttle:two-factor` |

## How it Works

POST: Validates either the 2FA one-time code or a backup recovery code against the user's encrypted database record.

## How to Use

POST request payload:

```json
{
  "code": "123456"
}
```
Or with recovery code:
```json
{
  "recovery_code": "abcd-efgh-ijkl"
}
```
