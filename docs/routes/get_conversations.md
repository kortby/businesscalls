# Route: GET /conversations

Display a list of the conversations.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/conversations` |
| **HTTP Methods** | `GET` |
| **Route Name** | `conversations.index` |
| **Controller Action** | `App\Http\Controllers\ConversationsController@index` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Conversations/Index` |

## How it Works

Renders the Inertia SPA view: `Conversations/Index`. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
