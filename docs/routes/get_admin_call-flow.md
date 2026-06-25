# Voice Interactive Planner

## Overview

Visual editor to configure voice response routing rules.

## How it Works

Sets call answering behavior, keypress triggers, and emergency fallback numbers.

## How to Use

Use the drag-and-drop tree to change voice prompts, key triggers, or fallback routing rules.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/call-flow` |
| **HTTP Method** | `GET` |
| **Route Name** | `admin.callflow` |
| **Action Code** | `App\Http\Controllers\AdminController@callFlow` |
| **Middleware** | `web`, `auth`, `verified` |
