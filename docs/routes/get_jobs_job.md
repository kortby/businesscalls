# Service Job Details

## Overview

Detailed view of an individual job ticket.

## How it Works

Compiles history logs, customer details, assigned technicians, and billing amounts.

## How to Use

Click on any job ID to view the full details panel.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/jobs/{job}` |
| **HTTP Method** | `GET` |
| **Route Name** | `jobs.show` |
| **Action Code** | `App\Http\Controllers\ServiceJobController@show` |
| **Middleware** | `web`, `auth`, `verified` |
