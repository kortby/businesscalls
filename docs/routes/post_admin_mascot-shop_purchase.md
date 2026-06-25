# Route: POST /admin/mascot-shop/purchase

Purchase/activate a mascot skin.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/admin/mascot-shop/purchase` |
| **HTTP Methods** | `POST` |
| **Route Name** | `admin.mascot-shop.purchase` |
| **Controller Action** | `App\Http\Controllers\AdminController@purchaseMascotSkin` |
| **Middleware** | `web`, `auth`, `verified` |

## How it Works

Applies tenant isolation scoping rules to isolate company data.

## How to Use

Perform an HTTP POST request with the required payload parameters.
