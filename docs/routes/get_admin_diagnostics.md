# Live Diagnostics HUD

## Overview

Infrastructure telemetry panel tracking server vitals.

## How it Works

Displays active WebSocket Reverb connections, queue load metrics, database latency, and average conversational latency.

## How to Use

Monitor system parameters to ensure high performance and low conversational delay times.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/diagnostics` |
| **HTTP Method** | `GET` |
| **Route Name** | `admin.diagnostics` |
| **Action Code** | `App\Http\Controllers\AdminController@diagnostics` |
| **Middleware** | `web`, `auth`, `verified` |
