# Edit Technician Form Page

## Overview

Form interface to edit existing employee records.

## How it Works

Pre-populates values of employee records into input fields.

## How to Use

Edit fields and submit updates.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/employees/{employee}/edit` |
| **HTTP Method** | `GET` |
| **Route Name** | `employees.edit` |
| **Action Code** | `App\Http\Controllers\EmployeeController@edit` |
| **Middleware** | `web`, `auth`, `verified` |
