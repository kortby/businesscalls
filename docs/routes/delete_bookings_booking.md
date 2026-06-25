# Route: DELETE /bookings/{booking}

Remove the specified booking from storage.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/bookings/{booking}` |
| **HTTP Methods** | `DELETE` |
| **Route Name** | `bookings.destroy` |
| **Controller Action** | `App\Http\Controllers\BookingController@destroy` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Deletes records or models from the database. Applies tenant isolation scoping rules to isolate company data. Dispatches real-time broadcast events or queued jobs.

## How to Use

Perform an HTTP DELETE request with the required payload parameters.
