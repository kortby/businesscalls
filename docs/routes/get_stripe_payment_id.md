# Route: GET /stripe/payment/{id}

Displays the Stripe payment confirmation page.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/stripe/payment/{id}` |
| **HTTP Methods** | `GET` |
| **Route Name** | `cashier.payment` |
| **Controller Action** | `Laravel\Cashier\Http\Controllers\PaymentController@show` |
| **Middleware** | *None* |

## How it Works

GET: Renders a payment confirmation template for resolving 3D secure payments.

## How to Use

Redirect target from checkout flows.
