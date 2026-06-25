# Add Customer Profile

## Overview

Manually register a new client contact.

## How it Works

Inserts a new customer record into the tenant scoped database.

## How to Use

Click "New Customer", input phone number, name, email, and click "Save".

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/customers` |
| **HTTP Method** | `POST` |
| **Route Name** | `customers.store` |
| **Action Code** | `App\Http\Controllers\CustomerController@store` |
| **Middleware** | `web`, `auth`, `verified` |
