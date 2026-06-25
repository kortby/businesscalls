# Change Account Password

## Overview

Update your login password.

## How it Works

Validates your old password and saves a new secure hash.

## How to Use

Input your current password, type the new password, and click "Save Password".

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/settings/password` |
| **HTTP Method** | `PUT` |
| **Route Name** | `user-password.update` |
| **Action Code** | `App\Http\Controllers\Settings\SecurityController@update` |
| **Middleware** | `web`, `auth`, `verified`, `throttle:6,1` |
