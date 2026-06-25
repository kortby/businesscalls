# Operations Control Dashboard

## Overview

The central management control center for business owners and dispatchers.

## How it Works

Aggregates real-time statistics including Call Quality Score (CQS), Booking Streak, open dispatches, and logs recent technician bookings dynamically via Pusher Reverb.

## How to Use

Monitor booking metrics, browse active technician schedules, and click logs rows to inspect job details.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/dashboard` |
| **HTTP Method** | `GET` |
| **Route Name** | `dashboard` |
| **Action Code** | `Closure` |
| **Middleware** | `web`, `auth`, `verified` |
