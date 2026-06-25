# Create Service Jobs

## Overview

Log new work orders and link them to clients.

## How it Works

Creates a service job ticket and logs administrative compliance records.

## How to Use

Click "New Job", input job description, link to a customer booking, select technician, and save.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/jobs` |
| **HTTP Method** | `POST` |
| **Route Name** | `jobs.store` |
| **Action Code** | `App\Http\Controllers\ServiceJobController@store` |
| **Middleware** | `web`, `auth`, `verified` |
