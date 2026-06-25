# Service Jobs Board

## Overview

Track active work tickets and job details.

## How it Works

Groups job descriptions, assigned technicians, dates, and billing amounts.

## How to Use

Browse open jobs and monitor status cards from creation to completion.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/jobs` |
| **HTTP Method** | `GET` |
| **Route Name** | `jobs.index` |
| **Action Code** | `App\Http\Controllers\ServiceJobController@index` |
| **Middleware** | `web`, `auth`, `verified` |
