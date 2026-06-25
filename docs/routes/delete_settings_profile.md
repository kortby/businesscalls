# Deactivate Account Profile

## Overview

Deactivate or delete your user account profile.

## How it Works

Removes user records and log sessions.

## How to Use

Press "Deactivate Profile" and confirm.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/settings/profile` |
| **HTTP Method** | `DELETE` |
| **Route Name** | `profile.destroy` |
| **Action Code** | `App\Http\Controllers\Settings\ProfileController@destroy` |
| **Middleware** | `web`, `auth`, `verified` |
