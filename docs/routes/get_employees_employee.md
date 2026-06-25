# Technician Profile Details

## Overview

Detailed view of an individual technician's performance and shifts.

## How it Works

Displays contact logs, active skills, calendar shifts, and job history charts.

## How to Use

Click on any technician to view their full profile panel.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/employees/{employee}` |
| **HTTP Method** | `GET` |
| **Route Name** | `employees.show` |
| **Action Code** | `App\Http\Controllers\EmployeeController@show` |
| **Middleware** | `web`, `auth`, `verified` |
