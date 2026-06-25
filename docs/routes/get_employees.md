# Route: GET /employees

Display a listing of the employees.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/employees` |
| **HTTP Methods** | `GET` |
| **Route Name** | `employees.index` |
| **Controller Action** | `App\Http\Controllers\EmployeeController@index` |
| **Middleware** | `web`, `auth`, `verified` |
| **Inertia Page Component** | `employees/Index` |

## How it Works

Renders the Inertia SPA view: `employees/Index`.

## How to Use

Open the URL path in the web browser or perform a client-side Inertia navigation to view the rendered page.
