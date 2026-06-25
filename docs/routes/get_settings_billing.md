# Invoice Billing & Card Payments

## Overview

Manage Stripe subscription details, billing cards, and checkout.

## How it Works

Connects to the Stripe customer billing portal safely.

## How to Use

View current subscription details, click "Update Payment Method" or "View Invoices" to redirect to Stripe, or click "Upgrade Plan" to initiate checkout.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/settings/billing` |
| **HTTP Method** | `GET` |
| **Route Name** | `settings.billing.index` |
| **Action Code** | `App\Http\Controllers\Api\StripeBillingController@index` |
| **Middleware** | `web`, `auth` |
