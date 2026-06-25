# Application Routing Directory & Reference

This directory contains comprehensive, auto-generated documentation files for all routes registered in the application. Select a route from the categories below to view its purpose, backend implementation details, parameter validation rules, and usage examples.

## Categories

- [General / Public Pages](#general-/-public-pages)
- [Billing & Subscriptions](#billing-subscriptions)
- [Authentication & Security](#authentication-security)
- [Core API Endpoints](#core-api-endpoints)
- [Core Webhooks & Fallbacks](#core-webhooks-fallbacks)
- [Admin Panel](#admin-panel)
- [Resource & Operations Management](#resource-operations-management)
- [Settings & Configuration](#settings-configuration)

---

## General / Public Pages

| Method | URI | Route Name | Description |
| --- | --- | --- | --- |
| `GET` | [`/.well-known/passkey-endpoints`](routes/get_well-known_passkey-endpoints.md) | `well-known.passkeys` | No description available. |
| `GET` | [`//`](routes/get_root.md) | `home` | No description available. |
| `POST` | [`/_boost/browser-logs`](routes/post_boost_browser-logs.md) | `boost.browser-logs` | No description available. |
| `GET` | [`/about`](routes/get_about.md) | `about` | No description available. |
| `GET|POST` | [`/broadcasting/auth`](routes/get_post_broadcasting_auth.md) | *None* | Authenticate the request for channel access. |
| `GET` | [`/contact`](routes/get_contact.md) | `contact` | No description available. |
| `GET` | [`/dashboard`](routes/get_dashboard.md) | `dashboard` | No description available. |
| `GET` | [`/docs`](routes/get_docs.md) | `docs` | No description available. |
| `POST` | [`/email/verification-notification`](routes/post_email_verification-notification.md) | `verification.send` | Resends the email verification notification. |
| `GET` | [`/email/verify`](routes/get_email_verify.md) | `verification.notice` | Renders the email verification prompt view. |
| `GET` | [`/email/verify/{id}/{hash}`](routes/get_email_verify_id_hash.md) | `verification.verify` | Performs email verification validation. |
| `GET` | [`/pricing`](routes/get_pricing.md) | `pricing` | No description available. |
| `GET` | [`/sanctum/csrf-cookie`](routes/get_sanctum_csrf-cookie.md) | `sanctum.csrf-cookie` | Retrieve the CSRF protection cookie for Sanctum-authenticated SPA clients. |
| `GET|POST|PUT|PATCH|DELETE|OPTIONS` | [`/settings`](routes/get_post_put_patch_delete_options_settings.md) | *None* | No description available. |
| `GET` | [`/storage/{path}`](routes/get_storage_path.md) | `storage.local` | Serves local storage files. |
| `PUT` | [`/storage/{path}`](routes/put_storage_path.md) | `storage.local.upload` | Serves local storage files. |
| `GET` | [`/technician/dashboard`](routes/get_technician_dashboard.md) | `technician.dashboard` | Show the technician mobile PWA dashboard. |
| `GET` | [`/technician/login`](routes/get_technician_login.md) | `technician.login` | Show the technician passkey login view. |
| `GET` | [`/up`](routes/get_up.md) | *None* | Application health status endpoint. |

## Billing & Subscriptions

| Method | URI | Route Name | Description |
| --- | --- | --- | --- |
| `GET` | [`/stripe/payment/{id}`](routes/get_stripe_payment_id.md) | `cashier.payment` | Displays the Stripe payment confirmation page. |
| `POST` | [`/stripe/webhook`](routes/post_stripe_webhook.md) | `cashier.webhook` | Handles incoming Stripe billing events. |

## Authentication & Security

| Method | URI | Route Name | Description |
| --- | --- | --- | --- |
| `GET` | [`/forgot-password`](routes/get_forgot-password.md) | `password.request` | Renders the password reset request prompt page. |
| `POST` | [`/forgot-password`](routes/post_forgot-password.md) | `password.email` | Sends a password reset link to the specified user email address. |
| `GET` | [`/login`](routes/get_login.md) | `login` | Renders the login UI page or processes authentication credentials. |
| `POST` | [`/login`](routes/post_login.md) | `login.store` | Attempt to authenticate a new session. |
| `POST` | [`/logout`](routes/post_logout.md) | `logout` | Destroys the authenticated session and logs the user out. |
| `POST` | [`/passkeys/confirm`](routes/post_passkeys_confirm.md) | `passkey.confirm` | Validates user credentials via passkey signature confirmation. |
| `GET` | [`/passkeys/confirm/options`](routes/get_passkeys_confirm_options.md) | `passkey.confirm-options` | Retrieves credentials verification challenge options for passkeys. |
| `POST` | [`/passkeys/login`](routes/post_passkeys_login.md) | `passkey.login` | Authenticates user login sessions via passkey verification. |
| `GET` | [`/passkeys/login/options`](routes/get_passkeys_login_options.md) | `passkey.login-options` | Retrieves login challenge options for passkeys. |
| `GET` | [`/register`](routes/get_register.md) | `register` | Renders the registration form or registers a new tenant user. |
| `POST` | [`/register`](routes/post_register.md) | `register.store` | Create a new registered user. |
| `POST` | [`/reset-password`](routes/post_reset-password.md) | `password.update` | Performs the password reset operation. |
| `GET` | [`/reset-password/{token}`](routes/get_reset-password_token.md) | `password.reset` | Renders the password update/reset form. |
| `GET` | [`/two-factor-challenge`](routes/get_two-factor-challenge.md) | `two-factor.login` | Renders the two-factor authentication OTP login form. |
| `POST` | [`/two-factor-challenge`](routes/post_two-factor-challenge.md) | `two-factor.login.store` | Validates two-factor OTP credentials. |
| `GET` | [`/user/confirm-password`](routes/get_user_confirm-password.md) | `password.confirm` | Renders password confirmation view. |
| `POST` | [`/user/confirm-password`](routes/post_user_confirm-password.md) | `password.confirm.store` | Validates the password confirmation request. |
| `GET` | [`/user/confirmed-password-status`](routes/get_user_confirmed-password-status.md) | `password.confirmation` | Checks the password confirmation timeout status. |
| `POST` | [`/user/confirmed-two-factor-authentication`](routes/post_user_confirmed-two-factor-authentication.md) | `two-factor.confirm` | Enable two factor authentication for the user. |
| `POST` | [`/user/passkeys`](routes/post_user_passkeys.md) | `passkey.store` | Registers a new passkey credential linked to the user. |
| `GET` | [`/user/passkeys/options`](routes/get_user_passkeys_options.md) | `passkey.registration-options` | Retrieves registration options for passkeys. |
| `DELETE` | [`/user/passkeys/{passkey}`](routes/delete_user_passkeys_passkey.md) | `passkey.destroy` | Removes a registered passkey credential. |
| `POST` | [`/user/two-factor-authentication`](routes/post_user_two-factor-authentication.md) | `two-factor.enable` | Enables two-factor authentication for the authenticated user. |
| `DELETE` | [`/user/two-factor-authentication`](routes/delete_user_two-factor-authentication.md) | `two-factor.disable` | Disables two-factor authentication for the authenticated user. |
| `GET` | [`/user/two-factor-qr-code`](routes/get_user_two-factor-qr-code.md) | `two-factor.qr-code` | Retrieves the two-factor QR code SVG. |
| `GET` | [`/user/two-factor-recovery-codes`](routes/get_user_two-factor-recovery-codes.md) | `two-factor.recovery-codes` | Retrieves the active two-factor recovery codes. |
| `POST` | [`/user/two-factor-recovery-codes`](routes/post_user_two-factor-recovery-codes.md) | `two-factor.regenerate-recovery-codes` | Regenerates a new set of two-factor recovery codes. |
| `GET` | [`/user/two-factor-secret-key`](routes/get_user_two-factor-secret-key.md) | `two-factor.secret-key` | Retrieves the raw text two-factor secret key. |

## Core API Endpoints

| Method | URI | Route Name | Description |
| --- | --- | --- | --- |
| `POST` | [`/api/billing/checkout`](routes/post_api_billing_checkout.md) | `billing.checkout` | Generate a Stripe Checkout Session redirect URL for plan upgrades. |
| `GET` | [`/api/billing/portal`](routes/get_api_billing_portal.md) | `billing.portal` | Generate a Stripe Customer Portal redirect URL. |
| `PUT` | [`/api/bookings/{booking}/status`](routes/put_api_bookings_booking_status.md) | *None* | Update the dispatch booking status dynamically. |
| `POST` | [`/api/call-logs/{callLog}/redact`](routes/post_api_call-logs_callLog_redact.md) | *None* | Manually redact sensitive data in the specified call log transcript. |
| `GET|POST` | [`/api/mcp`](routes/get_post_api_mcp.md) | `mcp.server` | Handle incoming Model Context Protocol (MCP) server requests. |
| `POST` | [`/api/oauth/token`](routes/post_api_oauth_token.md) | `oauth.token` | Handle client credentials grant and return access tokens. |
| `POST` | [`/api/settings/branded-caller-id`](routes/post_api_settings_branded-caller-id.md) | *None* | Submit Branded Caller ID registration details via API. |
| `POST` | [`/api/settings/call-flow`](routes/post_api_settings_call-flow.md) | *None* | Store/update the tenant's visual call flow tree. |
| `POST` | [`/api/settings/dictionary`](routes/post_api_settings_dictionary.md) | *None* | Register a new phonetic dictionary entry for the tenant. |
| `POST` | [`/api/settings/specialized-keywords`](routes/post_api_settings_specialized-keywords.md) | *None* | Register specialized trade keywords for the active tenant. |
| `GET` | [`/api/settings/specialized-keywords`](routes/get_api_settings_specialized-keywords.md) | *None* | Get the registered specialized trade keywords for the active tenant. |
| `POST` | [`/api/settings/toggle-sandbox`](routes/post_api_settings_toggle-sandbox.md) | *None* | Toggle the sandbox/test mode state of the active tenant. |
| `GET` | [`/api/user`](routes/get_api_user.md) | *None* | No description available. |
| `POST` | [`/api/web-calls/barge`](routes/post_api_web-calls_barge.md) | *None* | Authenticate supervisor, exchange token for barge/monitor session, and broadcast Reverb event. |
| `POST` | [`/api/web-calls/token`](routes/post_api_web-calls_token.md) | *None* | Generate an ephemeral client access token or public key payload for WebRTC. |
| `POST` | [`/api/web-calls/whisper`](routes/post_api_web-calls_whisper.md) | *None* | Broadcast a supervisor whisper coaching event to the active technician. |

## Core Webhooks & Fallbacks

| Method | URI | Route Name | Description |
| --- | --- | --- | --- |
| `POST` | [`/api/telemetry/quality-degraded`](routes/post_api_telemetry_quality-degraded.md) | *None* | Broadcast call quality degradation metrics to supervisors. |
| `POST` | [`/api/telemetry/webrtc`](routes/post_api_telemetry_webrtc.md) | *None* | No description available. |
| `POST` | [`/api/telephony/fallback-route/{tenant_id?}`](routes/post_api_telephony_fallback-route_tenant_id.md) | `telephony.fallback-route` | Handle Twilio dynamic TwiML fallback voice routing. |
| `POST` | [`/api/webhooks/call-events/{tenant_id?}`](routes/post_api_webhooks_call-events_tenant_id.md) | `webhook.call-events` | Handle incoming telephony events from Retell/Vapi. |
| `POST` | [`/api/webhooks/dispatch`](routes/post_api_webhooks_dispatch.md) | *None* | No description available. |
| `POST` | [`/api/webhooks/ivr-keypress/{tenant_id?}`](routes/post_api_webhooks_ivr-keypress_tenant_id.md) | `webhook.ivr-keypress` | Handle incoming IVR digit presses / DTMF tones. |
| `POST` | [`/api/webhooks/ivr/{tenant_id?}`](routes/post_api_webhooks_ivr_tenant_id.md) | `webhook.ivr` | Handle incoming IVR digit presses / DTMF tones. |
| `POST` | [`/api/webhooks/sms/{tenant_id?}`](routes/post_api_webhooks_sms_tenant_id.md) | `webhook.sms` | Handle incoming SMS webhooks (from Twilio or other providers). |

## Admin Panel

| Method | URI | Route Name | Description |
| --- | --- | --- | --- |
| `GET` | [`/admin/achievements`](routes/get_admin_achievements.md) | `admin.achievements` | Display the achievements panel. |
| `GET` | [`/admin/audit-logs`](routes/get_admin_audit-logs.md) | `admin.audit-logs` | Display the playful admin audit logs terminal view. |
| `GET` | [`/admin/call-flow`](routes/get_admin_call-flow.md) | `admin.callflow` | Display the visual drag-and-drop call flow builder. |
| `GET` | [`/admin/call-monitor`](routes/get_admin_call-monitor.md) | `admin.call-monitor` | Display the Live Call Monitoring Hub. |
| `GET` | [`/admin/diagnostics`](routes/get_admin_diagnostics.md) | `admin.diagnostics` | Display the system diagnostic telemetry panel. |
| `GET` | [`/admin/dispatch-map`](routes/get_admin_dispatch-map.md) | `admin.dispatch-map` | Display the playful animated Live Dispatch Map (Duolingo style UI). |
| `GET` | [`/admin/executive-report/download`](routes/get_admin_executive-report_download.md) | `admin.report.download` | Download the gamified executive PDF report. |
| `GET` | [`/admin/experiments`](routes/get_admin_experiments.md) | `admin.experiments` | Display the Conversational A/B Prompt Split-Testing Experiments Panel. |
| `POST` | [`/admin/experiments/create`](routes/post_admin_experiments_create.md) | `admin.experiments.save` | Save/Create a new A/B experiment and its variants. |
| `POST` | [`/admin/experiments/denoising`](routes/post_admin_experiments_denoising.md) | `admin.experiments.denoising` | Toggle background denoising settings for the tenant. |
| `GET` | [`/admin/health`](routes/get_admin_health.md) | `admin.health` | Display the system health telemetry panel. |
| `GET` | [`/admin/integrations`](routes/get_admin_integrations.md) | `admin.integrations` | Display the playful visual Integrations Panel (Duolingo style UI). |
| `POST` | [`/admin/integrations`](routes/post_admin_integrations.md) | `admin.integrations.save` | Save or update a tenant integration status/details. |
| `POST` | [`/admin/integrations/timing`](routes/post_admin_integrations_timing.md) | `admin.integrations.timing` | Save customized speech timing settings. |
| `GET` | [`/admin/leaderboard`](routes/get_admin_leaderboard.md) | `admin.leaderboard` | Display the playful Technician Performance Leaderboard (Duolingo style UI). |
| `GET` | [`/admin/loyalty`](routes/get_admin_loyalty.md) | `admin.loyalty` | Display the playful customer loyalty panel. |
| `GET` | [`/admin/mascot-shop`](routes/get_admin_mascot-shop.md) | `admin.mascot-shop` | Display the playful Mascot Customization Shop. |
| `POST` | [`/admin/mascot-shop/purchase`](routes/post_admin_mascot-shop_purchase.md) | `admin.mascot-shop.purchase` | Purchase/activate a mascot skin. |
| `GET` | [`/admin/onboarding`](routes/get_admin_onboarding.md) | `admin.onboarding` | Display the onboarding journey quest map. |
| `GET` | [`/admin/onboarding-setup`](routes/get_admin_onboarding-setup.md) | `admin.onboarding-setup` | Display the subscriber onboarding setup workspace. |
| `GET` | [`/admin/pre-flight-audit`](routes/get_admin_pre-flight-audit.md) | `admin.preflight` | Display the playful pre-flight launch audit panel. |
| `GET` | [`/admin/reports`](routes/get_admin_reports.md) | `admin.reports` | Display the playful executive reports overview dashboard. |
| `GET` | [`/admin/status-hud`](routes/get_admin_status-hud.md) | `admin.status-hud` | Display the playful visual system status and health console. |
| `GET` | [`/admin/supervisor-hud`](routes/get_admin_supervisor-hud.md) | `admin.supervisor-hud` | Display the playful visual Supervisor HUD (Duolingo style UI). |

## Resource & Operations Management

| Method | URI | Route Name | Description |
| --- | --- | --- | --- |
| `GET` | [`/availabilities`](routes/get_availabilities.md) | `availabilities.index` | Display a listing of shift availabilities. |
| `POST` | [`/availabilities`](routes/post_availabilities.md) | `availabilities.store` | Store a newly created availability shift in storage. |
| `PUT` | [`/availabilities/{availability}`](routes/put_availabilities_availability.md) | `availabilities.update` | Update the specified availability shift in storage. |
| `DELETE` | [`/availabilities/{availability}`](routes/delete_availabilities_availability.md) | `availabilities.destroy` | Remove the specified availability shift from storage. |
| `GET` | [`/bookings`](routes/get_bookings.md) | `bookings.index` | Display a listing of appointments. |
| `POST` | [`/bookings`](routes/post_bookings.md) | `bookings.store` | Store a newly created manual booking in storage. |
| `PUT` | [`/bookings/{booking}`](routes/put_bookings_booking.md) | `bookings.update` | Update the specified booking in storage. |
| `DELETE` | [`/bookings/{booking}`](routes/delete_bookings_booking.md) | `bookings.destroy` | Remove the specified booking from storage. |
| `GET` | [`/conversations`](routes/get_conversations.md) | `conversations.index` | Display a list of the conversations. |
| `POST` | [`/conversations/{conversation}/messages`](routes/post_conversations_conversation_messages.md) | `conversations.messages.store` | Store a newly created chat message in storage. |
| `GET` | [`/customers`](routes/get_customers.md) | `customers.index` | Display a listing of distinct customers (phone numbers) and their activity. |
| `POST` | [`/customers`](routes/post_customers.md) | `customers.store` | Store a newly created customer. |
| `POST` | [`/customers/import`](routes/post_customers_import.md) | `customers.import` | Import customers from a CSV file. |
| `GET` | [`/employees`](routes/get_employees.md) | `employees.index` | Display a listing of the employees. |
| `POST` | [`/employees`](routes/post_employees.md) | `employees.store` | Store a newly created employee. |
| `GET` | [`/employees/create`](routes/get_employees_create.md) | `employees.create` | No description available. |
| `GET` | [`/employees/{employee}`](routes/get_employees_employee.md) | `employees.show` | No description available. |
| `PUT|PATCH` | [`/employees/{employee}`](routes/put_patch_employees_employee.md) | `employees.update` | Update the specified employee. |
| `DELETE` | [`/employees/{employee}`](routes/delete_employees_employee.md) | `employees.destroy` | Remove the specified employee from storage. |
| `GET` | [`/employees/{employee}/edit`](routes/get_employees_employee_edit.md) | `employees.edit` | No description available. |
| `GET` | [`/jobs`](routes/get_jobs.md) | `jobs.index` | Display a listing of service jobs with customer and employee lists. |
| `POST` | [`/jobs`](routes/post_jobs.md) | `jobs.store` | Store a newly created service job. |
| `GET` | [`/jobs/create`](routes/get_jobs_create.md) | `jobs.create` | No description available. |
| `GET` | [`/jobs/{job}`](routes/get_jobs_job.md) | `jobs.show` | No description available. |
| `PUT|PATCH` | [`/jobs/{job}`](routes/put_patch_jobs_job.md) | `jobs.update` | Update the specified service job. |
| `DELETE` | [`/jobs/{job}`](routes/delete_jobs_job.md) | `jobs.destroy` | Remove the specified service job. |
| `GET` | [`/jobs/{job}/edit`](routes/get_jobs_job_edit.md) | `jobs.edit` | No description available. |

## Settings & Configuration

| Method | URI | Route Name | Description |
| --- | --- | --- | --- |
| `GET` | [`/settings/appearance`](routes/get_settings_appearance.md) | `appearance.edit` | No description available. |
| `GET` | [`/settings/billing`](routes/get_settings_billing.md) | `settings.billing.index` | Show the billing settings view. |
| `PUT` | [`/settings/password`](routes/put_settings_password.md) | `user-password.update` | Update the user's password. |
| `GET` | [`/settings/profile`](routes/get_settings_profile.md) | `profile.edit` | Show the user's profile settings page. |
| `PATCH` | [`/settings/profile`](routes/patch_settings_profile.md) | `profile.update` | Update the user's profile information. |
| `DELETE` | [`/settings/profile`](routes/delete_settings_profile.md) | `profile.destroy` | Delete the user's profile. |
| `GET` | [`/settings/prompt`](routes/get_settings_prompt.md) | `settings.prompt.edit` | Show the tenant settings / prompt editor page. |
| `PATCH` | [`/settings/prompt`](routes/patch_settings_prompt.md) | `settings.prompt.update` | Update the tenant settings. |
| `GET` | [`/settings/security`](routes/get_settings_security.md) | `security.edit` | Show the user's security settings page. |

