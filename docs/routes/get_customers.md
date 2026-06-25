# Customer CRM Directory

## Overview

Manage customer profiles and client histories.

## How it Works

Compiles customer profiles automatically when calls are processed.

## How to Use

Search client entries by name or phone. Review recent job tickets associated with each client.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/customers` |
| **HTTP Method** | `GET` |
| **Route Name** | `customers.index` |
| **Action Code** | `App\Http\Controllers\CustomerController@index` |
| **Middleware** | `web`, `auth`, `verified` |
