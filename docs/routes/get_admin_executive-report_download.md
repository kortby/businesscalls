# Download Performance Reports

## Overview

Export performance summaries directly.

## How it Works

Generates and downloads PDF documents directly.

## How to Use

Triggered automatically when downloading reports.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/executive-report/download` |
| **HTTP Method** | `GET` |
| **Route Name** | `admin.report.download` |
| **Action Code** | `App\Http\Controllers\AdminController@downloadReport` |
| **Middleware** | `web`, `auth`, `verified` |
