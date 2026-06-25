# Save AI Receptionist Prompt

## Overview

Update the AI greeting and operational guidelines.

## How it Works

Saves new instructions to tenant configurations.

## How to Use

Click "Update Prompt" after modifying prompt text.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/settings/prompt` |
| **HTTP Method** | `PATCH` |
| **Route Name** | `settings.prompt.update` |
| **Action Code** | `App\Http\Controllers\Settings\TenantSettingsController@update` |
| **Middleware** | `web`, `auth` |
