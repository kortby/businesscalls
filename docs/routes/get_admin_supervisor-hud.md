# Supervisor HUD coaching panel

## Overview

Advanced coaching controls for active customer calls.

## How it Works

Connects WebRTC streams and supports two-way whispering or full barging overrides.

## How to Use

During a live call, click "Whisper" to coach the agent silently, or "Barge In" to speak directly to the customer.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/supervisor-hud` |
| **HTTP Method** | `GET` |
| **Route Name** | `admin.supervisor-hud` |
| **Action Code** | `App\Http\Controllers\AdminController@supervisorHud` |
| **Middleware** | `web`, `auth`, `verified` |
