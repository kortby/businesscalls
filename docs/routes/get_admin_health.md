# System Connection Health

## Overview

Monitor incoming webhook reliability, deduplication, and telephony API statuses.

## How it Works

Logs webhook events to check for duplicates, errors, and system recovery.

## How to Use

Review logs to debug call dropouts or connection problems with external providers.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/health` |
| **HTTP Method** | `GET` |
| **Route Name** | `admin.health` |
| **Action Code** | `App\Http\Controllers\AdminController@health` |
| **Middleware** | `web`, `auth`, `verified` |
