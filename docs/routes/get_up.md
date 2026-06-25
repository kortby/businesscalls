# Route: GET /up

Application health status endpoint.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/up` |
| **HTTP Methods** | `GET` |
| **Route Name** | *None* |
| **Controller Action** | `Closure` |
| **Middleware** | *None* |

## How it Works

Returns a basic HTTP response if the application is booted, signifying the server is active.

## How to Use

Send a GET request to `/up`. Used for server uptime check monitoring.
