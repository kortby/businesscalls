# Route: DELETE /employees/{employee}

Remove the specified employee from storage.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/employees/{employee}` |
| **HTTP Methods** | `DELETE` |
| **Route Name** | `employees.destroy` |
| **Controller Action** | `App\Http\Controllers\EmployeeController@destroy` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Deletes records or models from the database.

## How to Use

Perform an HTTP DELETE request with the required payload parameters.
