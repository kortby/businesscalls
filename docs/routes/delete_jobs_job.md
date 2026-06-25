# Cancel Service Jobs

## Overview

Archive or cancel service tickets.

## How it Works

Deletes the job ticket and logs compliance events.

## How to Use

Press "Cancel Job" and confirm.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/jobs/{job}` |
| **HTTP Method** | `DELETE` |
| **Route Name** | `jobs.destroy` |
| **Action Code** | `App\Http\Controllers\ServiceJobController@destroy` |
| **Middleware** | `web`, `auth`, `verified` |
