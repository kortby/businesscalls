# Cancel Customer Bookings

## Overview

Cancel an appointment and clear the technician's schedule.

## How it Works

Deletes the booking from the database and updates metrics instantly.

## How to Use

Click on a booking, press the "Cancel Booking" button, and confirm.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/bookings/{booking}` |
| **HTTP Method** | `DELETE` |
| **Route Name** | `bookings.destroy` |
| **Action Code** | `App\Http\Controllers\BookingController@destroy` |
| **Middleware** | `web`, `auth`, `verified` |
