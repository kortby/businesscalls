# System Status Monitor HUD

## Overview

Overview of server uptime and active webhook queues.

## How it Works

Checks response latency and monitors system health state.

## How to Use

Review status lights to ensure all services are fully operational.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/status-hud` |
| **HTTP Method** | `GET` |
| **Route Name** | `admin.status-hud` |
| **Action Code** | `App\Http\Controllers\AdminController@statusHud` |
| **Middleware** | `web`, `auth`, `verified` |
