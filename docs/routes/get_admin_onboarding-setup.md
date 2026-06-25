# Route: GET /admin/onboarding-setup

Display the subscriber onboarding setup workspace.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/onboarding-setup` |
| **HTTP Methods** | `GET` |
| **Route Name** | `admin.onboarding-setup` |
| **Controller Action** | `App\Http\Controllers\AdminController@onboardingSetup` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Admin/Onboarding` |

## How it Works

Renders the Inertia SPA view: `Admin/Onboarding`. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
