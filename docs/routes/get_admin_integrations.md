# Third-Party Integration Hub

## Overview

Connect external CRM, invoicing, and messaging providers.

## How it Works

Saves API credentials and synchronization keys securely.

## How to Use

Enter API tokens for Twilio, ServiceTitan, or Housecall Pro to link calendars.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/integrations` |
| **HTTP Method** | `GET` |
| **Route Name** | `admin.integrations` |
| **Action Code** | `App\Http\Controllers\AdminController@integrations` |
| **Middleware** | `web`, `auth`, `verified` |
