# Route: POST /api/telephony/fallback-route/{tenant_id?}

Handle Twilio dynamic TwiML fallback voice routing.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/telephony/fallback-route/{tenant_id?}` |
| **HTTP Methods** | `POST` |
| **Route Name** | `telephony.fallback-route` |
| **Controller Action** | `App\Http\Controllers\Api\TelephonyFallbackController@handle` |
| **Middleware** | `api` |

## How it Works

Stores or persists model state to the database. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Perform an HTTP POST request with the required payload parameters.
