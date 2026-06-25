# Purchase Mascot Avatar Skins

## Overview

Unlock custom skins using earned points.

## How it Works

Deducts dispatcher points and unlocks custom avatar skins.

## How to Use

Click "Purchase" on your desired skin inside the mascot shop.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/mascot-shop/purchase` |
| **HTTP Method** | `POST` |
| **Route Name** | `admin.mascot-shop.purchase` |
| **Action Code** | `App\Http\Controllers\AdminController@purchaseMascotSkin` |
| **Middleware** | `web`, `auth`, `verified` |
