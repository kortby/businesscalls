# Save Sync Timing Settings

## Overview

Configure sync intervals for external CRMs.

## How it Works

Saves cron sync schedules.

## How to Use

Select sync timing frequency (e.g. hourly, daily) and save.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/integrations/timing` |
| **HTTP Method** | `POST` |
| **Route Name** | `admin.integrations.timing` |
| **Action Code** | `App\Http\Controllers\AdminController@saveTimingSettings` |
| **Middleware** | `web`, `auth`, `verified` |
