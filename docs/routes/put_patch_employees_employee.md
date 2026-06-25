# Update Technician Records

## Overview

Modify contact info, skills, or notification preferences of existing technicians.

## How it Works

Updates employee record values in the database.

## How to Use

Click "Edit Profile", update fields, and click "Save Updates".

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/employees/{employee}` |
| **HTTP Method** | `PUT|PATCH` |
| **Route Name** | `employees.update` |
| **Action Code** | `App\Http\Controllers\EmployeeController@update` |
| **Middleware** | `web`, `auth`, `verified` |
