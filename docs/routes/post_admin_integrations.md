# Save CRM Credentials

## Overview

Save external CRM credentials.

## How it Works

Saves CRM keys to securely link client databases.

## How to Use

Click "Save Integration" after inputting API credentials.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/integrations` |
| **HTTP Method** | `POST` |
| **Route Name** | `admin.integrations.save` |
| **Action Code** | `App\Http\Controllers\AdminController@saveIntegration` |
| **Middleware** | `web`, `auth`, `verified` |
