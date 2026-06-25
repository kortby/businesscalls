# Log New Manual Bookings

## Overview

Log a customer booking manually when receiving calls directly.

## How it Works

Validates technician availability, parses trade skills, checks for conflicts, and enforces the 1.5-hour buffer.

## How to Use

Click "New Booking", input the customer phone number, select the technician, choose a slot, and type the service job details.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/bookings` |
| **HTTP Method** | `POST` |
| **Route Name** | `bookings.store` |
| **Action Code** | `App\Http\Controllers\BookingController@store` |
| **Middleware** | `web`, `auth`, `verified` |
