# Technician Availabilities List

## Overview

View active shift schedules and hours for all registered technicians.

## How it Works

Displays weekly availability grids grouped by technician. Overlapping shift windows are prevented automatically.

## How to Use

Review the calendar grid to verify technician coverage for the upcoming week.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/availabilities` |
| **HTTP Method** | `GET` |
| **Route Name** | `availabilities.index` |
| **Action Code** | `App\Http\Controllers\AvailabilityController@index` |
| **Middleware** | `web`, `auth`, `verified` |
