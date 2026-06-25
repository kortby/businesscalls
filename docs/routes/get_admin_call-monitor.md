# Live Call Listening Panel

## Overview

Listen in real time to customer calls answered by the AI receptionist.

## How it Works

Connects to active WebRTC audio streams of active voice calls.

## How to Use

View active calls list and click "Listen" to monitor audio in real time.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/call-monitor` |
| **HTTP Method** | `GET` |
| **Route Name** | `admin.call-monitor` |
| **Action Code** | `App\Http\Controllers\AdminController@callMonitor` |
| **Middleware** | `web`, `auth`, `verified` |
