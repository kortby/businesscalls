# AI Prompt Playground & Denoising

## Overview

Toggle experimental features and run prompt A/B tests.

## How it Works

Configures prompt variations and toggles background noise cancellation settings.

## How to Use

Toggle noise filters or test new prompt greetings, and review success rate statistics.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/experiments` |
| **HTTP Method** | `GET` |
| **Route Name** | `admin.experiments` |
| **Action Code** | `App\Http\Controllers\AdminController@experiments` |
| **Middleware** | `web`, `auth`, `verified` |
