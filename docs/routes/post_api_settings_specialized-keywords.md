# Route: POST /api/settings/specialized-keywords

Register specialized trade keywords for the active tenant.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/settings/specialized-keywords` |
| **HTTP Methods** | `POST` |
| **Route Name** | *None* |
| **Controller Action** | `App\Http\Controllers\Api\SpecializedKeywordsController@store` |
| **Middleware** | `api`, `auth:sanctum` |

## How it Works

Processes request through the controller action.

## Request Parameters

| Parameter | Type | Required | Rules / Constraints |
| --- | --- | --- | --- |
| `keywords` | `array` | Yes | `required, array` |
| `keywords.*` | `string` | Yes | `required, string, max:255` |

## How to Use

Perform an HTTP POST request with the required payload parameters.

### Example Request Body

```json
{
    "keywords": [],
    "keywords.*": "value"
}
```
