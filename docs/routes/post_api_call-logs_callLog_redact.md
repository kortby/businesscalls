# Route: POST /api/call-logs/{callLog}/redact

Manually redact sensitive data in the specified call log transcript.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/call-logs/{callLog}/redact` |
| **HTTP Methods** | `POST` |
| **Route Name** | *None* |
| **Controller Action** | `App\Http\Controllers\Api\CallRedactionController@redact` |
| **Middleware** | `api`, `auth:sanctum` |

## How it Works

Stores or persists model state to the database. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Perform an HTTP POST request with the required payload parameters.
