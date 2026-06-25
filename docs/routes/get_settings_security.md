# MFA, Password, & Passkeys security panel

## Overview

Configure multi-factor security and biometric passkeys.

## How it Works

Enforces secure passwords, registers WebAuthn keys, and generates 2FA QR secrets.

## How to Use

Toggle Multi-Factor Authentication. Scan the QR code, or click "Register Passkey" to log in with touch ID/face ID.

## Technical Details

| Property | Value |
| --- | --- |
| **URL Path** | `/settings/security` |
| **HTTP Method** | `GET` |
| **Route Name** | `security.edit` |
| **Action Code** | `App\Http\Controllers\Settings\SecurityController@edit` |
| **Middleware** | `web`, `auth`, `verified`, `Illuminate\Auth\Middleware\RequirePassword` |
