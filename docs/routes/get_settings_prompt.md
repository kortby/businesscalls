# Route: GET /settings/prompt

Show the tenant settings / prompt editor page.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/settings/prompt` |
| **HTTP Methods** | `GET` |
| **Route Name** | `settings.prompt.edit` |
| **Controller Action** | `App\Http\Controllers\Settings\TenantSettingsController@edit` |
| **Middleware** | `web`, `auth` |
| **Inertia Page Component** | `settings/PromptEditor` |

## How it Works

Renders the Inertia SPA view: `settings/PromptEditor`.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
