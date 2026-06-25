# Route: PATCH /settings/prompt

Update the tenant settings.

## Technical Details

| Property | Value |
| --- | --- |
| **URI** | `/settings/prompt` |
| **HTTP Methods** | `PATCH` |
| **Route Name** | `settings.prompt.update` |
| **Controller Action** | `App\Http\Controllers\Settings\TenantSettingsController@update` |
| **Middleware** | `web`, `auth` |

## How it Works

Stores or persists model state to the database. Applies tenant isolation scoping rules to isolate company data.

## Request Parameters

| Parameter | Type | Required | Rules / Constraints |
| --- | --- | --- | --- |
| `ai_prompt` | `string` | Yes | `required, string, max:2000` |
| `emergency_fee` | `string` | Yes | `required, string, max:100` |
| `emergency_rules` | `string` | No | `nullable, string, max:1000` |
| `pricing_list` | `array` | No | `nullable, array` |

## How to Use

Perform an HTTP PATCH request with the required payload parameters.

### Example Request Body

```json
{
    "ai_prompt": "value",
    "emergency_fee": "value",
    "emergency_rules": "value",
    "pricing_list": []
}
```
