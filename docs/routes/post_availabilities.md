# Schedule Technician Shifts

## Overview

Assign weekly shift hours to individual technicians.

## How it Works

Validates shift parameters and creates active schedule blocks. Blocks overlapping shifts.

## How to Use

Select a technician, choose the day of the week, input shift start and end times, and click "Save Shift".

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/availabilities` |
| **HTTP Method** | `POST` |
| **Route Name** | `availabilities.store` |
| **Action Code** | `App\Http\Controllers\AvailabilityController@store` |
| **Middleware** | `web`, `auth`, `verified` |
