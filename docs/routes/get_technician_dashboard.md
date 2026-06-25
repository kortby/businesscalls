# Technician Mobile App

## Overview

Mobile portal for technicians to check schedules and update jobs.

## How it Works

Displays shift calendar and assigned bookings scoped to the technician.

## How to Use

Log in on a mobile browser. View your daily route, tap "En Route" when heading to a job, "On Site" when you arrive, and "Completed" to log notes and billing amounts.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/technician/dashboard` |
| **HTTP Method** | `GET` |
| **Route Name** | `technician.dashboard` |
| **Action Code** | `App\Http\Controllers\TechnicianController@dashboard` |
| **Middleware** | `web`, `auth`, `verified` |
