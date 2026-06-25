# Booking Calendar & Appointments

## Overview

The centralized dispatch board displaying all customer bookings and appointments.

## How it Works

Enforces travel buffers of 1.5 hours between bookings automatically to ensure technicians can arrive on time without overlap.

## How to Use

Browse jobs on the calendar by day, week, or month. Click any booking to inspect notes or coordinate manual dispatches.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/bookings` |
| **HTTP Method** | `GET` |
| **Route Name** | `bookings.index` |
| **Action Code** | `App\Http\Controllers\BookingController@index` |
| **Middleware** | `web`, `auth`, `verified` |
