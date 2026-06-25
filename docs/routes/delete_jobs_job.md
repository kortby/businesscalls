# Route: DELETE /jobs/{job}

Remove the specified service job.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/jobs/{job}` |
| **HTTP Methods** | `DELETE` |
| **Route Name** | `jobs.destroy` |
| **Controller Action** | `App\Http\Controllers\ServiceJobController@destroy` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Deletes records or models from the database.

## How to Use

Perform an HTTP DELETE request with the required payload parameters.
