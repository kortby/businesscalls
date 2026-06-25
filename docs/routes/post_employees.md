# Register New Technicians

## Overview

Add new staff members to your team.

## How it Works

Creates a technician profile, registers skill tags, and optionally generates login credentials.

## How to Use

Click "Add Staff", enter first name, last name, phone, trade skills (e.g. plumbing, HVAC), notification preference, and click "Save".

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/employees` |
| **HTTP Method** | `POST` |
| **Route Name** | `employees.store` |
| **Action Code** | `App\Http\Controllers\EmployeeController@store` |
| **Middleware** | `web`, `auth`, `verified` |
