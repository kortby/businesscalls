# Administrative Audit compliance log

## Overview

Verify administrative logs for security compliance audits.

## How it Works

Logs all system events, technician edits, call fallbacks, and configuration changes.

## How to Use

Search and filter logs by action type or date to verify system audit trail compliance.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/admin/audit-logs` |
| **HTTP Method** | `GET` |
| **Route Name** | `admin.audit-logs` |
| **Action Code** | `App\Http\Controllers\AdminController@auditLogs` |
| **Middleware** | `web`, `auth`, `verified` |
