# Application Routing Directory & Reference

This directory contains comprehensive, auto-generated documentation files for all routes registered in the application. Select a route from the categories below to view its purpose, backend implementation details, parameter validation rules, and usage examples.

## Categories

- [Developer API Reference](#developer-api-reference)
- [User Guide: Get Started](#user-guide:-get-started)
- [User Guide: Technician Mobile App](#user-guide:-technician-mobile-app)
- [User Guide: Advanced Dispatch Tools](#user-guide:-advanced-dispatch-tools)
- [User Guide: Operations Dashboard](#user-guide:-operations-dashboard)
- [User Guide: Availability & Scheduling](#user-guide:-availability-scheduling)
- [User Guide: Communications](#user-guide:-communications)
- [User Guide: Records Management](#user-guide:-records-management)
- [User Guide: Account & Settings](#user-guide:-account-settings)

---

## Developer API Reference

| Method | URI | Route Name | Description |
| --- | --- | --- | --- |
| `GET` | [`/.well-known/passkey-endpoints`](routes/get_well-known_passkey-endpoints.md) | `well-known.passkeys` | Developer API endpoint. |
| `POST` | [`/_boost/browser-logs`](routes/post_boost_browser-logs.md) | `boost.browser-logs` | Developer API endpoint. |
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
| `POST` | [`/api/telemetry/quality-degraded`](routes/post_api_telemetry_quality-degraded.md) | *None* | Broadcast call quality degradation metrics to supervisors. |
| `POST` | [`/api/telemetry/webrtc`](routes/post_api_telemetry_webrtc.md) | *None* | Developer API endpoint. |
| `POST` | [`/api/telephony/fallback-route/{tenant_id?}`](routes/post_api_telephony_fallback-route_tenant_id.md) | `telephony.fallback-route` | Handle Twilio dynamic TwiML fallback voice routing. |
| `GET` | [`/api/user`](routes/get_api_user.md) | *None* | Developer API endpoint. |
| `POST` | [`/api/web-calls/barge`](routes/post_api_web-calls_barge.md) | *None* | Authenticate supervisor, exchange token for barge/monitor session, and broadcast Reverb event. |
| `POST` | [`/api/web-calls/token`](routes/post_api_web-calls_token.md) | *None* | Generate an ephemeral client access token or public key payload for WebRTC. |
| `POST` | [`/api/web-calls/whisper`](routes/post_api_web-calls_whisper.md) | *None* | Broadcast a supervisor whisper coaching event to the active technician. |
| `POST` | [`/api/webhooks/call-events/{tenant_id?}`](routes/post_api_webhooks_call-events_tenant_id.md) | `webhook.call-events` | Handle incoming telephony events from Retell/Vapi. |
| `POST` | [`/api/webhooks/dispatch`](routes/post_api_webhooks_dispatch.md) | *None* | Developer API endpoint. |
| `POST` | [`/api/webhooks/ivr-keypress/{tenant_id?}`](routes/post_api_webhooks_ivr-keypress_tenant_id.md) | `webhook.ivr-keypress` | Handle incoming IVR digit presses / DTMF tones. |
| `POST` | [`/api/webhooks/ivr/{tenant_id?}`](routes/post_api_webhooks_ivr_tenant_id.md) | `webhook.ivr` | Handle incoming IVR digit presses / DTMF tones. |
| `POST` | [`/api/webhooks/sms/{tenant_id?}`](routes/post_api_webhooks_sms_tenant_id.md) | `webhook.sms` | Handle incoming SMS webhooks (from Twilio or other providers). |
| `GET|POST` | [`/broadcasting/auth`](routes/get_post_broadcasting_auth.md) | *None* | Authenticate the request for channel access. |
| `GET` | [`/docs`](routes/get_docs.md) | `docs` | Developer API endpoint. |
| `POST` | [`/email/verification-notification`](routes/post_email_verification-notification.md) | `verification.send` | Send a new email verification notification. |
| `GET` | [`/email/verify`](routes/get_email_verify.md) | `verification.notice` | Display the email verification prompt. |
| `GET` | [`/email/verify/{id}/{hash}`](routes/get_email_verify_id_hash.md) | `verification.verify` | Mark the authenticated user's email address as verified. |
| `GET` | [`/forgot-password`](routes/get_forgot-password.md) | `password.request` | Show the reset password link request view. |
| `POST` | [`/forgot-password`](routes/post_forgot-password.md) | `password.email` | Send a reset link to the given user. |
| `GET` | [`/login`](routes/get_login.md) | `login` | Show the login view. |
| `POST` | [`/login`](routes/post_login.md) | `login.store` | Attempt to authenticate a new session. |
| `POST` | [`/logout`](routes/post_logout.md) | `logout` | Destroy an authenticated session. |
| `POST` | [`/passkeys/confirm`](routes/post_passkeys_confirm.md) | `passkey.confirm` | Confirm the user's password via passkey verification. |
| `GET` | [`/passkeys/confirm/options`](routes/get_passkeys_confirm_options.md) | `passkey.confirm-options` | Get passkey confirmation options for the authenticated user. |
| `POST` | [`/passkeys/login`](routes/post_passkeys_login.md) | `passkey.login` | Verify the passkey and log the user in. |
| `GET` | [`/passkeys/login/options`](routes/get_passkeys_login_options.md) | `passkey.login-options` | Get passkey login options. |
| `GET` | [`/register`](routes/get_register.md) | `register` | Show the registration view. |
| `POST` | [`/register`](routes/post_register.md) | `register.store` | Create a new registered user. |
| `POST` | [`/reset-password`](routes/post_reset-password.md) | `password.update` | Reset the user's password. |
| `GET` | [`/reset-password/{token}`](routes/get_reset-password_token.md) | `password.reset` | Show the new password view. |
| `GET` | [`/sanctum/csrf-cookie`](routes/get_sanctum_csrf-cookie.md) | `sanctum.csrf-cookie` | Return an empty response simply to trigger the storage of the CSRF cookie in the browser. |
| `GET|POST|PUT|PATCH|DELETE|OPTIONS` | [`/settings`](routes/get_post_put_patch_delete_options_settings.md) | *None* | Developer API endpoint. |
| `GET` | [`/storage/{path}`](routes/get_storage_path.md) | `storage.local` | Developer API endpoint. |
| `PUT` | [`/storage/{path}`](routes/put_storage_path.md) | `storage.local.upload` | Developer API endpoint. |
| `GET` | [`/stripe/payment/{id}`](routes/get_stripe_payment_id.md) | `cashier.payment` | Display the form to gather additional payment verification for the given payment. |
| `POST` | [`/stripe/webhook`](routes/post_stripe_webhook.md) | `cashier.webhook` | Handle a Stripe webhook call. |
| `GET` | [`/two-factor-challenge`](routes/get_two-factor-challenge.md) | `two-factor.login` | Show the two factor authentication challenge view. |
| `POST` | [`/two-factor-challenge`](routes/post_two-factor-challenge.md) | `two-factor.login.store` | Attempt to authenticate a new session using the two factor authentication code. |
| `GET` | [`/up`](routes/get_up.md) | *None* | Developer API endpoint. |
| `GET` | [`/user/confirm-password`](routes/get_user_confirm-password.md) | `password.confirm` | Show the confirm password view. |
| `POST` | [`/user/confirm-password`](routes/post_user_confirm-password.md) | `password.confirm.store` | Confirm the user's password. |
| `GET` | [`/user/confirmed-password-status`](routes/get_user_confirmed-password-status.md) | `password.confirmation` | Get the password confirmation status. |
| `POST` | [`/user/confirmed-two-factor-authentication`](routes/post_user_confirmed-two-factor-authentication.md) | `two-factor.confirm` | Enable two factor authentication for the user. |
| `POST` | [`/user/passkeys`](routes/post_user_passkeys.md) | `passkey.store` | Store a new passkey for the authenticated user. |
| `GET` | [`/user/passkeys/options`](routes/get_user_passkeys_options.md) | `passkey.registration-options` | Get passkey registration options for the authenticated user. |
| `DELETE` | [`/user/passkeys/{passkey}`](routes/delete_user_passkeys_passkey.md) | `passkey.destroy` | Delete a passkey for the authenticated user. |
| `POST` | [`/user/two-factor-authentication`](routes/post_user_two-factor-authentication.md) | `two-factor.enable` | Enable two factor authentication for the user. |
| `DELETE` | [`/user/two-factor-authentication`](routes/delete_user_two-factor-authentication.md) | `two-factor.disable` | Disable two factor authentication for the user. |
| `GET` | [`/user/two-factor-qr-code`](routes/get_user_two-factor-qr-code.md) | `two-factor.qr-code` | Get the SVG element for the user's two factor authentication QR code. |
| `GET` | [`/user/two-factor-recovery-codes`](routes/get_user_two-factor-recovery-codes.md) | `two-factor.recovery-codes` | Get the two factor authentication recovery codes for authenticated user. |
| `POST` | [`/user/two-factor-recovery-codes`](routes/post_user_two-factor-recovery-codes.md) | `two-factor.regenerate-recovery-codes` | Generate a fresh set of two factor authentication recovery codes. |
| `GET` | [`/user/two-factor-secret-key`](routes/get_user_two-factor-secret-key.md) | `two-factor.secret-key` | Get the current user's two factor authentication setup / secret key. |

## User Guide: Get Started

| Method | URI | Route Name | Description |
| --- | --- | --- | --- |
| `GET` | [`//`](routes/get_root.md) | `home` | The public-facing landing page of the businesscalls platform. |
| `GET` | [`/about`](routes/get_about.md) | `about` | Public information page describing the mission and technology of businesscalls. |
| `GET` | [`/admin/onboarding`](routes/get_admin_onboarding.md) | `admin.onboarding` | Interactive step-by-step checklist to configure businesscalls. |
| `GET` | [`/contact`](routes/get_contact.md) | `contact` | Send messages or support queries directly to the businesscalls service administrators. |
| `GET` | [`/pricing`](routes/get_pricing.md) | `pricing` | Review active plan options, price structures, and custom feature tiers. |

## User Guide: Technician Mobile App

| Method | URI | Route Name | Description |
| --- | --- | --- | --- |
| `GET` | [`/technician/dashboard`](routes/get_technician_dashboard.md) | `technician.dashboard` | Mobile portal for technicians to check schedules and update jobs. |
| `GET` | [`/technician/login`](routes/get_technician_login.md) | `technician.login` | Login portal for road technicians. |

## User Guide: Advanced Dispatch Tools

| Method | URI | Route Name | Description |
| --- | --- | --- | --- |
| `GET` | [`/admin/achievements`](routes/get_admin_achievements.md) | `admin.achievements` | Track dispatcher operational milestones. |
| `GET` | [`/admin/audit-logs`](routes/get_admin_audit-logs.md) | `admin.audit-logs` | Verify administrative logs for security compliance audits. |
| `GET` | [`/admin/call-flow`](routes/get_admin_call-flow.md) | `admin.callflow` | Visual editor to configure voice response routing rules. |
| `GET` | [`/admin/call-monitor`](routes/get_admin_call-monitor.md) | `admin.call-monitor` | Listen in real time to customer calls answered by the AI receptionist. |
| `GET` | [`/admin/diagnostics`](routes/get_admin_diagnostics.md) | `admin.diagnostics` | Infrastructure telemetry panel tracking server vitals. |
| `GET` | [`/admin/dispatch-map`](routes/get_admin_dispatch-map.md) | `admin.dispatch-map` | Visual map coordinates tracking active service locations. |
| `GET` | [`/admin/executive-report/download`](routes/get_admin_executive-report_download.md) | `admin.report.download` | Export performance summaries directly. |
| `GET` | [`/admin/experiments`](routes/get_admin_experiments.md) | `admin.experiments` | Toggle experimental features and run prompt A/B tests. |
| `POST` | [`/admin/experiments/create`](routes/post_admin_experiments_create.md) | `admin.experiments.save` | Save prompt greetings to A/B test groups. |
| `POST` | [`/admin/experiments/denoising`](routes/post_admin_experiments_denoising.md) | `admin.experiments.denoising` | Enable or disable AI call noise cancellation filters. |
| `GET` | [`/admin/health`](routes/get_admin_health.md) | `admin.health` | Monitor incoming webhook reliability, deduplication, and telephony API statuses. |
| `GET` | [`/admin/integrations`](routes/get_admin_integrations.md) | `admin.integrations` | Connect external CRM, invoicing, and messaging providers. |
| `POST` | [`/admin/integrations`](routes/post_admin_integrations.md) | `admin.integrations.save` | Save external CRM credentials. |
| `POST` | [`/admin/integrations/timing`](routes/post_admin_integrations_timing.md) | `admin.integrations.timing` | Configure sync intervals for external CRMs. |
| `GET` | [`/admin/leaderboard`](routes/get_admin_leaderboard.md) | `admin.leaderboard` | Review rankings of team members based on booking success. |
| `GET` | [`/admin/loyalty`](routes/get_admin_loyalty.md) | `admin.loyalty` | Monitor customer retention and VIP dispatches. |
| `GET` | [`/admin/mascot-shop`](routes/get_admin_mascot-shop.md) | `admin.mascot-shop` | Dispatcher shop to personalize your receptionist avatar skin. |
| `POST` | [`/admin/mascot-shop/purchase`](routes/post_admin_mascot-shop_purchase.md) | `admin.mascot-shop.purchase` | Unlock custom skins using earned points. |
| `GET` | [`/admin/onboarding-setup`](routes/get_admin_onboarding-setup.md) | `admin.onboarding-setup` | Reset onboarding steps for testing. |
| `GET` | [`/admin/pre-flight-audit`](routes/get_admin_pre-flight-audit.md) | `admin.preflight` | Run connection tests on third-party service APIs. |
| `GET` | [`/admin/reports`](routes/get_admin_reports.md) | `admin.reports` | Summarize call activities, booking conversion rates, and metrics. |
| `GET` | [`/admin/status-hud`](routes/get_admin_status-hud.md) | `admin.status-hud` | Overview of server uptime and active webhook queues. |
| `GET` | [`/admin/supervisor-hud`](routes/get_admin_supervisor-hud.md) | `admin.supervisor-hud` | Advanced coaching controls for active customer calls. |

## User Guide: Operations Dashboard

| Method | URI | Route Name | Description |
| --- | --- | --- | --- |
| `GET` | [`/dashboard`](routes/get_dashboard.md) | `dashboard` | The central management control center for business owners and dispatchers. |

## User Guide: Availability & Scheduling

| Method | URI | Route Name | Description |
| --- | --- | --- | --- |
| `GET` | [`/availabilities`](routes/get_availabilities.md) | `availabilities.index` | View active shift schedules and hours for all registered technicians. |
| `POST` | [`/availabilities`](routes/post_availabilities.md) | `availabilities.store` | Assign weekly shift hours to individual technicians. |
| `PUT` | [`/availabilities/{availability}`](routes/put_availabilities_availability.md) | `availabilities.update` | Update or adjust existing work hours for technicians. |
| `DELETE` | [`/availabilities/{availability}`](routes/delete_availabilities_availability.md) | `availabilities.destroy` | Delete scheduled shifts from a technician's calendar. |
| `GET` | [`/bookings`](routes/get_bookings.md) | `bookings.index` | The centralized dispatch board displaying all customer bookings and appointments. |
| `POST` | [`/bookings`](routes/post_bookings.md) | `bookings.store` | Log a customer booking manually when receiving calls directly. |
| `PUT` | [`/bookings/{booking}`](routes/put_bookings_booking.md) | `bookings.update` | Reschedule or edit service details of logged appointments. |
| `DELETE` | [`/bookings/{booking}`](routes/delete_bookings_booking.md) | `bookings.destroy` | Cancel an appointment and clear the technician's schedule. |

## User Guide: Communications

| Method | URI | Route Name | Description |
| --- | --- | --- | --- |
| `GET` | [`/conversations`](routes/get_conversations.md) | `conversations.index` | View transcripts, recordings, and SMS messages between customer clients and the AI receptionist. |
| `POST` | [`/conversations/{conversation}/messages`](routes/post_conversations_conversation_messages.md) | `conversations.messages.store` | Send text messages to clients directly from the dashboard. |

## User Guide: Records Management

| Method | URI | Route Name | Description |
| --- | --- | --- | --- |
| `GET` | [`/customers`](routes/get_customers.md) | `customers.index` | Manage customer profiles and client histories. |
| `POST` | [`/customers`](routes/post_customers.md) | `customers.store` | Manually register a new client contact. |
| `POST` | [`/customers/import`](routes/post_customers_import.md) | `customers.import` | Bulk upload customer lists from spreadsheets or CRM exports. |
| `GET` | [`/employees`](routes/get_employees.md) | `employees.index` | View all registered technicians, their details, and trade skills. |
| `POST` | [`/employees`](routes/post_employees.md) | `employees.store` | Add new staff members to your team. |
| `GET` | [`/employees/create`](routes/get_employees_create.md) | `employees.create` | Form interface to add new staff members. |
| `GET` | [`/employees/{employee}`](routes/get_employees_employee.md) | `employees.show` | Detailed view of an individual technician's performance and shifts. |
| `PUT|PATCH` | [`/employees/{employee}`](routes/put_patch_employees_employee.md) | `employees.update` | Modify contact info, skills, or notification preferences of existing technicians. |
| `DELETE` | [`/employees/{employee}`](routes/delete_employees_employee.md) | `employees.destroy` | Deactivate or delete a technician from the roster. |
| `GET` | [`/employees/{employee}/edit`](routes/get_employees_employee_edit.md) | `employees.edit` | Form interface to edit existing employee records. |
| `GET` | [`/jobs`](routes/get_jobs.md) | `jobs.index` | Track active work tickets and job details. |
| `POST` | [`/jobs`](routes/post_jobs.md) | `jobs.store` | Log new work orders and link them to clients. |
| `GET` | [`/jobs/create`](routes/get_jobs_create.md) | `jobs.create` | Form interface to log new work orders. |
| `GET` | [`/jobs/{job}`](routes/get_jobs_job.md) | `jobs.show` | Detailed view of an individual job ticket. |
| `PUT|PATCH` | [`/jobs/{job}`](routes/put_patch_jobs_job.md) | `jobs.update` | Modify service descriptions, pricing, or status parameters of a job. |
| `DELETE` | [`/jobs/{job}`](routes/delete_jobs_job.md) | `jobs.destroy` | Archive or cancel service tickets. |
| `GET` | [`/jobs/{job}/edit`](routes/get_jobs_job_edit.md) | `jobs.edit` | Form interface to modify existing job tickets. |

## User Guide: Account & Settings

| Method | URI | Route Name | Description |
| --- | --- | --- | --- |
| `GET` | [`/settings/appearance`](routes/get_settings_appearance.md) | `appearance.edit` | Set theme settings or branding brand accent colors. |
| `GET` | [`/settings/billing`](routes/get_settings_billing.md) | `settings.billing.index` | Manage Stripe subscription details, billing cards, and checkout. |
| `PUT` | [`/settings/password`](routes/put_settings_password.md) | `user-password.update` | Update your login password. |
| `GET` | [`/settings/profile`](routes/get_settings_profile.md) | `profile.edit` | Manage personal contact information. |
| `PATCH` | [`/settings/profile`](routes/patch_settings_profile.md) | `profile.update` | Save changes to personal account details. |
| `DELETE` | [`/settings/profile`](routes/delete_settings_profile.md) | `profile.destroy` | Deactivate or delete your user account profile. |
| `GET` | [`/settings/prompt`](routes/get_settings_prompt.md) | `settings.prompt.edit` | Set greeting prompts and rules for the AI receptionist. |
| `PATCH` | [`/settings/prompt`](routes/patch_settings_prompt.md) | `settings.prompt.update` | Update the AI greeting and operational guidelines. |
| `GET` | [`/settings/security`](routes/get_settings_security.md) | `security.edit` | Configure multi-factor security and biometric passkeys. |

