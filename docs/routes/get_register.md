# Route: GET /register

Renders the registration form or registers a new tenant user.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/register` |
| **HTTP Methods** | `GET` |
| **Route Name** | `register` |
| **Controller Action** | `Laravel\Fortify\Http\Controllers\RegisteredUserController@create` |
| **Middleware** | `web`, `guest:web` |

## How it Works

GET: Renders the Inertia user registration page. POST: Validates inputs (name, email, password, password_confirmation), creates a new User model, registers a corresponding default tenant organization, and logs the user in.

## How to Use

POST request payload:

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```
