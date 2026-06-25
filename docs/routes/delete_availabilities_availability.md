# Remove Technician Shifts

## Overview

Delete scheduled shifts from a technician's calendar.

## How it Works

Removes availability blocks immediately, freeing up slots for rescheduling.

## How to Use

Click the "Delete" trash icon next to a shift block and confirm.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/availabilities/{availability}` |
| **HTTP Method** | `DELETE` |
| **Route Name** | `availabilities.destroy` |
| **Action Code** | `App\Http\Controllers\AvailabilityController@destroy` |
| **Middleware** | `web`, `auth`, `verified` |
