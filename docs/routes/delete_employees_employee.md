# Deactivate Technician Profiles

## Overview

Deactivate or delete a technician from the roster.

## How it Works

Removes employee records and archives their history logs securely.

## How to Use

Press "Deactivate Staff" on the profile page and confirm.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/employees/{employee}` |
| **HTTP Method** | `DELETE` |
| **Route Name** | `employees.destroy` |
| **Action Code** | `App\Http\Controllers\EmployeeController@destroy` |
| **Middleware** | `web`, `auth`, `verified` |
