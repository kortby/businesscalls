# Reschedule Service Bookings

## Overview

Reschedule or edit service details of logged appointments.

## How it Works

Checks scheduling rules for conflicts and updates booking information in the database.

## How to Use

Double click an appointment, modify the date/time or comments, and click "Save Changes".

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/bookings/{booking}` |
| **HTTP Method** | `PUT` |
| **Route Name** | `bookings.update` |
| **Action Code** | `App\Http\Controllers\BookingController@update` |
| **Middleware** | `web`, `auth`, `verified` |
