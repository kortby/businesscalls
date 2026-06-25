# Route: POST /api/settings/call-flow

Store/update the tenant's visual call flow tree.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/settings/call-flow` |
| **HTTP Methods** | `POST` |
| **Route Name** | *None* |
| **Controller Action** | `App\Http\Controllers\Api\CallFlowController@store` |
| **Middleware** | `api`, `auth:sanctum` |

## How it Works

Applies tenant isolation scoping rules to isolate company data.

## How to Use

Perform an HTTP POST request with the required payload parameters.
