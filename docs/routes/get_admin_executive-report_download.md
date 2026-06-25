# Route: GET /admin/executive-report/download

Download the gamified executive PDF report.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/executive-report/download` |
| **HTTP Methods** | `GET` |
| **Route Name** | `admin.report.download` |
| **Controller Action** | `App\Http\Controllers\AdminController@downloadReport` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Applies tenant isolation scoping rules to isolate company data.

## How to Use

Perform an HTTP GET request to retrieve the requested resource data.
