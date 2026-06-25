# Route: POST /admin/experiments/create

Save/Create a new A/B experiment and its variants.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/experiments/create` |
| **HTTP Methods** | `POST` |
| **Route Name** | `admin.experiments.save` |
| **Controller Action** | `App\Http\Controllers\AdminController@saveExperiment` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Stores or persists model state to the database. Applies tenant isolation scoping rules to isolate company data.

## How to Use

Perform an HTTP POST request with the required payload parameters.
