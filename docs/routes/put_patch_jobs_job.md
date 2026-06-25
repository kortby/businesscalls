# Update Job Details

## Overview

Modify service descriptions, pricing, or status parameters of a job.

## How it Works

Updates job record values in the database.

## How to Use

Click "Edit Job", update comments or details, and click "Save Updates".

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/jobs/{job}` |
| **HTTP Method** | `PUT|PATCH` |
| **Route Name** | `jobs.update` |
| **Action Code** | `App\Http\Controllers\ServiceJobController@update` |
| **Middleware** | `web`, `auth`, `verified` |
