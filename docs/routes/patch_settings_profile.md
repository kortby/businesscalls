# Update Profile Records

## Overview

Save changes to personal account details.

## How it Works

Updates account details in the database.

## How to Use

Modify fields and submit updates.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/settings/profile` |
| **HTTP Method** | `PATCH` |
| **Route Name** | `profile.update` |
| **Action Code** | `App\Http\Controllers\Settings\ProfileController@update` |
| **Middleware** | `web`, `auth` |
