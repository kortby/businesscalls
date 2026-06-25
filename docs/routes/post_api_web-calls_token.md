# API Reference: POST /api/web-calls/token

Generate an ephemeral client access token or public key payload for WebRTC.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/web-calls/token` |
| **HTTP Methods** | `POST` |
| **Route Name** | *None* |
| **Controller Action** | `App\Http\Controllers\Api\WebCallController@token` |
| **Middleware** | `api`, `auth:sanctum`, `App\Http\Middleware\ThrottleTenantTelephony`, `App\Http\Middleware\TrafficRouterMiddleware` |

## How it Works

Processes request triggers.

## Request Parameters

| Parameter | Type | Required | Rules / Constraints |
| --- | --- | --- | --- |
| `recaptcha_token` | `string` | Yes | `app()->environment(testing)?nullable:required, newReCaptcha, ` |

## How to Use

Send HTTP request calls.

### Example Request Body

```json
{
    "recaptcha_token": "value"
}
```
