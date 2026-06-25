# Save Prompt A/B Test Variations

## Overview

Save prompt greetings to A/B test groups.

## How it Works

Saves test variations.

## How to Use

Type your new greeting variant and click "Save Experiment".

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/experiments/create` |
| **HTTP Method** | `POST` |
| **Route Name** | `admin.experiments.save` |
| **Action Code** | `App\Http\Controllers\AdminController@saveExperiment` |
| **Middleware** | `web`, `auth`, `verified` |
