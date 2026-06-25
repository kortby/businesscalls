# Route: POST /api/oauth/token

Handle client credentials grant and return access tokens.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/oauth/token` |
| **HTTP Methods** | `POST` |
| **Route Name** | `oauth.token` |
| **Controller Action** | `App\Http\Controllers\Api\OAuthController@token` |
| **Middleware** | `api` |

## How it Works

Stores or persists model state to the database. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Perform an HTTP POST request with the required payload parameters.
