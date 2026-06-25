# Route: GET /api/settings/specialized-keywords

Get the registered specialized trade keywords for the active tenant.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/settings/specialized-keywords` |
| **HTTP Methods** | `GET` |
| **Route Name** | *None* |
| **Controller Action** | `App\Http\Controllers\Api\SpecializedKeywordsController@index` |
| **Middleware** | `api`, `auth:sanctum` |

## How it Works

Processes request through the controller action.

## How to Use

Perform an HTTP GET request to retrieve the requested resource data.
