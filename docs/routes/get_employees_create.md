# Create Technician Form Page

## Overview

Form interface to add new staff members.

## How it Works

Renders the team registration input interface.

## How to Use

Fill out employee contact details and submit.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/employees/create` |
| **HTTP Method** | `GET` |
| **Route Name** | `employees.create` |
| **Action Code** | `App\Http\Controllers\EmployeeController@create` |
| **Middleware** | `web`, `auth`, `verified` |
