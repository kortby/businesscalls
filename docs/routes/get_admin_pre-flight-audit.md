# Pre-Flight System Check

## Overview

Run connection tests on third-party service APIs.

## How it Works

Tests Twilio integration, Stripe API keys, Pusher connection, and database status.

## How to Use

Click "Run Verification" to check if the system is fully configured and ready for production calls.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/pre-flight-audit` |
| **HTTP Method** | `GET` |
| **Route Name** | `admin.preflight` |
| **Action Code** | `App\Http\Controllers\AdminController@preFlightAudit` |
| **Middleware** | `web`, `auth`, `verified` |
