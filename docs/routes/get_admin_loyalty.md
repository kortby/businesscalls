# Customer Loyalty Analytics

## Overview

Monitor customer retention and VIP dispatches.

## How it Works

Calculates loyalty metrics, streaks, and billing statuses from customer booking history logs.

## How to Use

Review the loyalty graphs to spot top customers and target VIP accounts.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/loyalty` |
| **HTTP Method** | `GET` |
| **Route Name** | `admin.loyalty` |
| **Action Code** | `App\Http\Controllers\AdminController@loyalty` |
| **Middleware** | `web`, `auth`, `verified` |
