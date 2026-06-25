# Client Conversations History

## Overview

View transcripts, recordings, and SMS messages between customer clients and the AI receptionist.

## How it Works

Logs live call events, analyzes customer intent, scores call quality (CQS), and compiles dialogue streams.

## How to Use

Browse active chat and call threads. Click on any contact name to review their full transcript logs or play back call recordings.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/conversations` |
| **HTTP Method** | `GET` |
| **Route Name** | `conversations.index` |
| **Action Code** | `App\Http\Controllers\ConversationsController@index` |
| **Middleware** | `web`, `auth`, `verified` |
