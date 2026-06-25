# Toggle Call Noise Cancellation

## Overview

Enable or disable AI call noise cancellation filters.

## How it Works

Toggles raw audio cleanup processing models.

## How to Use

Click "Toggle Denoising" to enable or disable background audio cleaning.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/experiments/denoising` |
| **HTTP Method** | `POST` |
| **Route Name** | `admin.experiments.denoising` |
| **Action Code** | `App\Http\Controllers\AdminController@toggleDenoising` |
| **Middleware** | `web`, `auth`, `verified` |
