# Interactive Dispatch Map

## Overview

Visual map coordinates tracking active service locations.

## How it Works

Embeds coordinates of technician job bookings and visualizes them on a maps interface.

## How to Use

Zoom and drag the map to monitor technician travel routes and coordinate quick emergency dispatches.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/dispatch-map` |
| **HTTP Method** | `GET` |
| **Route Name** | `admin.dispatch-map` |
| **Action Code** | `App\Http\Controllers\AdminController@dispatchMap` |
| **Middleware** | `web`, `auth`, `verified` |
