# Route: POST /customers/import

Import customers from a CSV file.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/customers/import` |
| **HTTP Methods** | `POST` |
| **Route Name** | `customers.import` |
| **Controller Action** | `App\Http\Controllers\CustomerController@import` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Stores or persists model state to the database. Applies tenant isolation scoping rules to isolate company data.

## Request Parameters

| Parameter | Type | Required | Rules / Constraints |
| --- | --- | --- | --- |
| `csv_file` | `string` | Yes | `required, file, mimes:csv, txt, max:2048` |

## How to Use

Perform an HTTP POST request with the required payload parameters.

### Example Request Body

```json
{
    "csv_file": "value"
}
```
