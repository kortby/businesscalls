# Route: GET|POST /api/mcp

Handle incoming Model Context Protocol (MCP) server requests.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/api/mcp` |
| **HTTP Methods** | `GET|POST` |
| **Route Name** | `mcp.server` |
| **Controller Action** | `App\Http\Controllers\Api\McpController@handle` |
| **Middleware** | `api` |

## How it Works

Applies tenant isolation scoping rules to isolate company data.

## How to Use

Perform an HTTP GET|POST request with the required payload parameters.
