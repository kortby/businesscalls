# Route: GET /user/two-factor-qr-code

Retrieves the two-factor QR code SVG.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/user/two-factor-qr-code` |
| **HTTP Methods** | `GET` |
| **Route Name** | `two-factor.qr-code` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController@show` |
| **Middleware** | `web`, `auth:web`, `password.confirm` |

## How it Works

GET: Returns the JSON response containing the SVG string of the QR code to sync with Google Authenticator.

## How to Use

Retrieve SVG representation to display in frontend configuration.
