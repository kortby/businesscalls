# Executive Performance Reports

## Overview

Summarize call activities, booking conversion rates, and metrics.

## How it Works

Generates summaries of call counts, booking rates, and technician performance.

## How to Use

Choose a reporting date range and click "Download Report" to export a PDF summary.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/reports` |
| **HTTP Method** | `GET` |
| **Route Name** | `admin.reports` |
| **Action Code** | `App\Http\Controllers\AdminController@executiveReports` |
| **Middleware** | `web`, `auth`, `verified` |
