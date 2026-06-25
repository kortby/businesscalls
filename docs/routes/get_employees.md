# Technicians & Staff Directory

## Overview

View all registered technicians, their details, and trade skills.

## How it Works

Groups staff details, links shift schedules, and compiles skill specializations.

## How to Use

Search technicians by name. Click "View Profile" to check their active schedule or edit records.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/employees` |
| **HTTP Method** | `GET` |
| **Route Name** | `employees.index` |
| **Action Code** | `App\Http\Controllers\EmployeeController@index` |
| **Middleware** | `web`, `auth`, `verified` |
