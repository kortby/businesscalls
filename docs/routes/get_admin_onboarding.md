# Interactive Onboarding Guide

## Overview

Interactive step-by-step checklist to configure businesscalls.

## How it Works

Tracks progress through necessary setup steps (Add technician, define shifts, link Twilio).

## How to Use

Complete each item on the quest checklist to move the account from sandbox to live production mode.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/onboarding` |
| **HTTP Method** | `GET` |
| **Route Name** | `admin.onboarding` |
| **Action Code** | `App\Http\Controllers\AdminController@onboardingQuest` |
| **Middleware** | `web`, `auth`, `verified` |
