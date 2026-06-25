# API Reference: PUT /api/bookings/{booking}/status

Update the dispatch booking status dynamically.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/bookings/{booking}/status` |
| **HTTP Methods** | `PUT` |
| **Route Name** | *None* |
| **Controller Action** | `App\Http\Controllers\Api\BookingStatusController@update` |
| **Middleware** | `api`, `auth:sanctum` |

## How it Works

Applies tenant isolation scoping rules. Triggers WebSocket broadcasts.

## Request Parameters

| Parameter | Type | Required | Rules / Constraints |
| --- | --- | --- | --- |
| `status` | `string` | Yes | `required, string, in:en_route, on_site, completed` |
| `feedback` | `string` | No | `nullable, string, max:500` |
| `billing_amount` | `numeric` | No | `nullable, numeric` |

## How to Use

Send HTTP request calls.

### Example Request Body

```json
{
    "status": "value",
    "feedback": "value",
    "billing_amount": 99.99
}
```
