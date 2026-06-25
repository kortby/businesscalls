# Create Job Form Page

## Overview

Form interface to log new work orders.

## How it Works

Renders the job ticket input interface.

## How to Use

Fill out service details and submit.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/jobs/create` |
| **HTTP Method** | `GET` |
| **Route Name** | `jobs.create` |
| **Action Code** | `App\Http\Controllers\ServiceJobController@create` |
| **Middleware** | `web`, `auth`, `verified` |
