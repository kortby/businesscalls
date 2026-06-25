# Route: POST /admin/experiments/denoising

Toggle background denoising settings for the tenant.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/experiments/denoising` |
| **HTTP Methods** | `POST` |
| **Route Name** | `admin.experiments.denoising` |
| **Controller Action** | `App\Http\Controllers\AdminController@toggleDenoising` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Applies tenant isolation scoping rules to isolate company data.

## How to Use

Perform an HTTP POST request with the required payload parameters.
