# Edit Job Form Page

## Overview

Form interface to modify existing job tickets.

## How it Works

Pre-populates values of job records into input fields.

## How to Use

Edit fields and submit updates.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/jobs/{job}/edit` |
| **HTTP Method** | `GET` |
| **Route Name** | `jobs.edit` |
| **Action Code** | `App\Http\Controllers\ServiceJobController@edit` |
| **Middleware** | `web`, `auth`, `verified` |
