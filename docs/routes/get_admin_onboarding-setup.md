# Onboarding Status Reset

## Overview

Reset onboarding steps for testing.

## How it Works

Clears onboarding checkmarks to restart the setup walkthrough.

## How to Use

Click "Reset Setup" in settings to restart onboarding.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/onboarding-setup` |
| **HTTP Method** | `GET` |
| **Route Name** | `admin.onboarding-setup` |
| **Action Code** | `App\Http\Controllers\AdminController@onboardingSetup` |
| **Middleware** | `web`, `auth`, `verified` |
