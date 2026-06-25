# Route: POST /conversations/{conversation}/messages

Store a newly created chat message in storage.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/conversations/{conversation}/messages` |
| **HTTP Methods** | `POST` |
| **Route Name** | `conversations.messages.store` |
| **Controller Action** | `App\Http\Controllers\ConversationsController@storeMessage` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Stores or persists model state to the database. Applies tenant isolation scoping rules to isolate company data. Dispatches real-time broadcast events or queued jobs.

## How to Use

Perform an HTTP POST request with the required payload parameters.
