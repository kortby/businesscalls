# User Profile Settings

## Overview

Manage personal contact information.

## How it Works

Renders form to edit user account settings.

## How to Use

Modify your name, email, or telephone and click "Save Changes".

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/settings/profile` |
| **HTTP Method** | `GET` |
| **Route Name** | `profile.edit` |
| **Action Code** | `App\Http\Controllers\Settings\ProfileController@edit` |
| **Middleware** | `web`, `auth` |
