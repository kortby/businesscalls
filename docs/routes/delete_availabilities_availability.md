# Route: DELETE /availabilities/{availability}

Remove the specified availability shift from storage.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/availabilities/{availability}` |
| **HTTP Methods** | `DELETE` |
| **Route Name** | `availabilities.destroy` |
| **Controller Action** | `App\Http\Controllers\AvailabilityController@destroy` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Deletes records or models from the database.

## How to Use

Perform an HTTP DELETE request with the required payload parameters.
