# Import Client Databases (CSV)

## Overview

Bulk upload customer lists from spreadsheets or CRM exports.

## How it Works

Parses CSV formats, matches phone logs, and bulk-inserts records securely.

## How to Use

Select a CSV file from your computer, match columns (Name, Phone), and click "Import Database".

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/customers/import` |
| **HTTP Method** | `POST` |
| **Route Name** | `customers.import` |
| **Action Code** | `App\Http\Controllers\CustomerController@import` |
| **Middleware** | `web`, `auth`, `verified` |
