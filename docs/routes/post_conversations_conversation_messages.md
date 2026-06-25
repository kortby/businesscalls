# Send Manual SMS Responses

## Overview

Send text messages to clients directly from the dashboard.

## How it Works

Saves and pushes messages via Reverb websocket channels to synchronize dialogue instantly.

## How to Use

Type your message into the text field at the bottom of the conversation thread and click "Send".

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/conversations/{conversation}/messages` |
| **HTTP Method** | `POST` |
| **Route Name** | `conversations.messages.store` |
| **Action Code** | `App\Http\Controllers\ConversationsController@storeMessage` |
| **Middleware** | `web`, `auth`, `verified` |
