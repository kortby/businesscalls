# AI Receptionist Voice Instructions

## Overview

Set greeting prompts and rules for the AI receptionist.

## How it Works

Configures the LLM instruction prompts used during customer call dialogues.

## How to Use

Edit the text prompt field to change how the AI answers (tone, questions to ask, pricing rules) and click "Update Prompt".

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/settings/prompt` |
| **HTTP Method** | `GET` |
| **Route Name** | `settings.prompt.edit` |
| **Action Code** | `App\Http\Controllers\Settings\TenantSettingsController@edit` |
| **Middleware** | `web`, `auth` |
