# Edit Technician Shift Hours

## Overview

Update or adjust existing work hours for technicians.

## How it Works

Re-validates shift parameters and checks for schedule conflicts before updating the database.

## How to Use

Click on an existing shift box, modify the hours, and click "Update".

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/availabilities/{availability}` |
| **HTTP Method** | `PUT` |
| **Route Name** | `availabilities.update` |
| **Action Code** | `App\Http\Controllers\AvailabilityController@update` |
| **Middleware** | `web`, `auth`, `verified` |
