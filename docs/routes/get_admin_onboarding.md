# Route: GET /admin/onboarding

Display the onboarding journey quest map.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/onboarding` |
| **HTTP Methods** | `GET` |
| **Route Name** | `admin.onboarding` |
| **Controller Action** | `App\Http\Controllers\AdminController@onboardingQuest` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `Admin/OnboardingQuest` |

## How it Works

Renders the Inertia SPA view: `Admin/OnboardingQuest`. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
