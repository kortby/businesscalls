# AI Receptionist Mascot Avatar skins

## Overview

Dispatcher shop to personalize your receptionist avatar skin.

## How it Works

Lets users spend points earned from booking achievements to unlock custom skins.

## How to Use

Browse available avatar designs, click "Purchase" using earned points, and equip skins.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/mascot-shop` |
| **HTTP Method** | `GET` |
| **Route Name** | `admin.mascot-shop` |
| **Action Code** | `App\Http\Controllers\AdminController@mascotShop` |
| **Middleware** | `web`, `auth`, `verified` |
