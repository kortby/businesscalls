# Route: GET /settings/billing

Show the billing settings view.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/settings/billing` |
| **HTTP Methods** | `GET` |
| **Route Name** | `settings.billing.index` |
| **Controller Action** | `App\Http\Controllers\Api\StripeBillingController@index` |
| **Middleware** | `web`, `auth` |
| **Inertia Page Component** | `settings/Billing` |

## How it Works

Renders the Inertia SPA view: `settings/Billing`.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
