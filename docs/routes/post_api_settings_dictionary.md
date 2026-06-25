# API Reference: POST /api/settings/dictionary

Register a new phonetic dictionary entry for the tenant.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/settings/dictionary` |
| **HTTP Methods** | `POST` |
| **Route Name** | *None* |
| **Controller Action** | `App\Http\Controllers\Api\PronunciationDictionaryController@store` |
| **Middleware** | `api`, `auth:sanctum` |

## How it Works

Processes request triggers.

## Request Parameters

| Parameter | Type | Required | Rules / Constraints |
| --- | --- | --- | --- |
| `word` | `string` | Yes | `required, string, max:255` |
| `phonetic` | `string` | Yes | `required, string, max:255` |

## How to Use

Send HTTP request calls.

### Example Request Body

```json
{
    "word": "value",
    "phonetic": "value"
}
```
