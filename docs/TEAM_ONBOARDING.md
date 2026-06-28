# businesscalls: Developer Onboarding & Architecture Manual

Welcome to the team! This document is designed to serve as a comprehensive, step-by-step technical guide to the architecture, database scoping, AI telephony routing, compliance middleware, billing events, and technician mobile portal of the `businesscalls` platform.

---

## 1. High-Level Architecture & Technology Stack

The platform is designed as a multi-tenant dispatch and customer communications hub powered by AI-steered voice receptionists and SMS chatbots.

- **Backend Framework**: Laravel 13 (PHP 8.4)
- **Frontend Layer**: Vue 3 + TailwindCSS v4 bundled via Vite, loaded as a Single Page Application (SPA) through Inertia.js v3 (using `@inertiajs/vite` for SSR in dev mode).
- **WebSockets / Broadcasting**: Laravel Reverb + Laravel Echo for real-time supervisor widgets (live barging, transcript streaming, and queue updates).
- **Queues & Asynchronous Jobs**: SQS with automated S3 offloading for large payloads (>256KB).
- **AI Integrations**: OpenAI API for SMS chatbots, Retell/Vapi APIs for live WebRTC call streaming, voice updates, and transcriber steering.
- **Billing System**: Laravel Cashier v16 (Stripe).
- **Testing Suite**: Pest PHP v4 / PHPUnit v12.

---

## 2. Multi-Tenant Scoping & Database Isolation

Database isolation is strictly enforced at the database query level to prevent cross-tenant data leaks. 

### Implementation Flow
1. Active models (e.g., [Employee](file:///Users/benk/Documents/work/laravel/businesscalls/app/Models/Employee.php), `Availability`, `Booking`, `CallLog`, `Customer`, `Invoice`) utilize the [BelongsToTenant](file:///Users/benk/Documents/work/laravel/businesscalls/app/Concerns/BelongsToTenant.php) trait.
2. The trait registers the global query scope [TenantScope](file:///Users/benk/Documents/work/laravel/businesscalls/app/Models/Scopes/TenantScope.php).
3. During execution, `TenantScope` inspects:
   - Static memory (`TenantScope::$tenantId`)
   - The authenticated user's `tenant_id`
   - The HTTP Session `tenant_id` (if session is active)
4. If a tenant ID is identified, query builders automatically append `where('tenant_id', $tenantId)`.
5. For transactional tables (`bookings`, `call_logs`, and `invoices`), `TenantScope` also filters by `is_test_mode` matching the tenant's current test/live mode status.
6. The `creating()` boot event binds `tenant_id` automatically to any new model instance if it was not explicitly supplied.

### How to Test Scoping
Ensure that queries remain isolated when authenticated as different users:
```bash
php artisan test --filter="dashboard numbers are scoped to the authenticated user tenant"
php artisan test --filter="tenant isolation restricts rebalancing"
```

---

## 3. Core Features & Core Business Logic

### A. Sandbox & Test Mode
A tenant can toggle between "Live" and "Test" (Sandbox) modes via `POST /api/settings/toggle-sandbox`.

- **Auto-binding**: Bookings and call logs automatically bind their `is_test_mode` column to match the active tenant's mode upon creation.
- **Stripe Mocking**: If `is_test_mode` is `true`, requests to generate Checkout Sessions (`POST /api/billing/checkout`) bypass Stripe's API and immediately return a mock redirect URL:
  `https://stripe.com/mock-redirect?checkout=success&test_mode=true`
  The plan is upgraded in the database instantly.
- **How to Test**:
  ```bash
  php artisan test tests/Feature/SandboxToggleTest.php
  ```

---

### B. AI Dispatch & Emergency Triage Engine
Calls and webhook events directed to `/api/webhooks/dispatch` determine which jobs are scheduled, prioritizing emergency bookings and managing technician shifts.

1. **Emergency Triage**:
   Incoming telemetry payloads classify priority states dynamically based on the trade category and keyword indicators:
   - **Plumbing**: Set to `emergency` if `water_leak` is `true` or `"yes"`.
   - **HVAC**: Set to `emergency` if the outdoor temperature (`outdoor_temp`) is $\ge 101^\circ\text{F}$ or $\le 15^\circ\text{F}$.
   - **Electrical**: Set to `emergency` if `sparking_outlets` or `burning_smell` is `true`.
   - **Explicit Override**: Overridden to emergency if `emergency_triage` is passed.
   Otherwise, bookings default to `routine_maintenance`.
2. **Technician Compatibility Scoring ($\theta$)**:
   The engine searches for eligible, active technicians who have matching skills and certifications (e.g. `Master_Plumber`, `EPA_608`). It calculates a compatibility score $\theta$ bounded between `0.0` and `1.0`:
   $$\theta = \max\left(0, 1 - \frac{\text{Distance in Kilometers}}{111}\right)$$
   Technicians located closer to the job receive higher scores, and the closest eligible candidate is assigned.
3. **Dynamic Schedule Rebalancing**:
   If an emergency booking is scheduled at a time slot, it triggers schedule rebalancing:
   - All subsequent `routine_maintenance` bookings assigned to that technician are pushed back by exactly **120 minutes**.
   - The queue dispatches a background job `SendEtaUpdateSmsJob` to alert customers about rescheduled windows.
- **How to Test**:
  ```bash
  php artisan test tests/Feature/TradeDispatchTest.php
  ```

---

### C. Live Monitoring, Coaching, & Handover
Supervisors can view ongoing calls in real time on the **Supervisor HUD** (`/admin/supervisor-hud`) and perform active call interventions:

1. **Barging & Whisper**:
   - **Whisper Coaching**: Injects private audio hints to the technician (`POST /api/web-calls/whisper`).
   - **Barge-In**: Terminates the AI speech receiver and routes the supervisor into the active call (`POST /api/web-calls/barge`).
   - Both actions fire real-time Broadcast Events (`SupervisorBarged`) via Laravel Reverb to synchronize administrative interfaces.
2. **Contextual Subagent Handover**:
   The `AgentTransferService` manages the transfer of calls between primary AI receptionists and task-specific subagents (e.g., routing to a payments agent or a CSAT survey agent). It computes a Handover Match Index ($\Phi$) which is logged to the `CallLog`:
   $$\Phi = \frac{\text{Shared Parameter Count}}{\text{Total Required Parameters}} \times \left(1 - \frac{\text{Handover Latency in ms}}{1000}\right)$$
   - *Boundaries*: $\Phi$ is forced to `0.0` if latency exceeds `1000ms`, or if the parameter count is `0`. Negative latencies bound the latency factor to `1.0`.
- **How to Test**:
  ```bash
  php artisan test tests/Feature/SupervisorBargingTest.php
  php artisan test tests/Feature/SubagentHandoverAndOnboardingTest.php
  ```

---

### D. Interactive Voice Response (IVR) & Transfers
The system processes caller DTMF keypresses at `/api/webhooks/ivr-keypress/{tenant_id}`.

- **Sequence Caching**: Handles both single-digit tool calls and multi-digit extensions by caching sequential inputs under `ivr_sequence_{callId}`. If a match is found (e.g., "21" mapped to a billing submenu), the action is executed and the cache is cleared.
- **Warm Transfers**: The `AgentTransferService` compiles warm transfer payloads for the telephony provider (e.g., Vapi), packaging the full `parent_call_id` and `transcript_history` as context overrides for the target SIP operator or subagent.
- **How to Test**:
  ```bash
  php artisan test tests/Feature/IvrWarmTransferTest.php
  ```

---

### E. Dynamic Language Swap Middleware
Incoming webhook requests are intercepted by `LanguageDetectionMiddleware` to support multilingual callers dynamically.

- **AI Inference**: Calls `App\Ai\Text` to analyze the incoming transcript.
- **Hot-Swapping**: If the language is detected as Spanish (`es`) or French (`fr`), the middleware fires a PATCH request to the active telephony provider's API (Vapi/Retell) to swap the active call's transcriber voice and pronunciation dictionary language on the fly. English (`en`) defaults require no voice patching.
- **How to Test**:
  ```bash
  php artisan test --filter="LanguageDetectionMiddleware"
  ```

---

### F. SMS Chatbot & Automatic Text Booking
Twilio SMS webhooks arrive at `/api/webhooks/sms/{tenant_id}`.

1. **Auto Contact Generation**: Creates a `Customer` record if the phone number is unrecognized (defaulting name format to `SMS User [Last 4 digits]`).
2. **AI Message Flow**: Generates a reply using OpenAI GPT models, respecting the customer's language preference.
3. **Conversational Booking**: If the customer requests a service:
   - The LLM parses the trade category and requested date/time into a JSON response.
   - The chatbot scans the technician schedule database.
   - If a technician is available (matching skills and shifts) and does not trigger the **1.5-hour travel collision buffer** with existing appointments, a booking is created and the system replies: `"Dispatch Confirmed! [Technician First Name]..."`.
   - If blocked, it replies: `"No available technician..."`.
- **How to Test**:
  ```bash
  php artisan test tests/Feature/SmsChatbotTest.php
  php artisan test tests/Feature/SmsSchedulerTest.php
  ```

---

### G. Outbound Campaigns & TCPA Regulations
Bulk marketing campaigns are scheduled via `OutboundCampaign` records and run through the `ExecuteBatchCampaignJob`.

1. **Compliance Gate**: The `EnsureRegulatoryCompliance` job middleware checks call timing rules before triggering outbound API calls.
2. **Timezone Extraction**: Extracts the customer area code (e.g., `+1206` for Seattle -> `America/Los_Angeles`).
3. **Calling Window**: Blocks calls outside local compliant hours (**8:00 AM to 9:00 PM**). Violating attempts trigger audit log entries with `action => 'tcpa_compliance_violation'` and bypass dispatch execution.
- **How to Test**:
  ```bash
  php artisan test tests/Feature/TcpaComplianceMiddlewareTest.php
  php artisan test tests/Feature/CampaignBatchJobTest.php
  ```

---

### H. Outbound Alerts & Answering Machine Detection (AMD)
Technician dispatch alerts are triggered via `SendTechnicianAlertJob`.

- **AMD Integration**: Instructs Vapi/Retell to dial the technician with Answering Machine Detection enabled. The call mapping is cached as `call_booking_map:call_id` pointing to the `booking_id`.
- **Machine Hook**: If webhook responses specify `answeringMachineDetectionResult` as `machine`, the system makes a POST request to trigger a voicemail drop message (e.g. `voicemail-drop` API) and transitions the booking status to `voicemail_alerted`.
- **Human Hook**: If a `human` is detected, the status transitions to `booked`.
- **How to Test**:
  ```bash
  php artisan test tests/Feature/RagAndAmdTest.php
  ```

---

### I. RAG Knowledge Base & Rank Decay
To answer specific product/manual questions during calls, the AI receptionist searches the tenant's `KnowledgeBase` records via `RAGKnowledgeService`.

- **Deterministic Embeddings**: Generates 1536-dimensional unit vectors utilizing the `Str::toEmbeddings($text)` macro.
- **Rank Decay Formula**: Similarity scores are adjusted to penalize lower-ranked search results dynamically using a lambda rank decay decay:
  $$\text{Adjusted Score} = \text{Similarity} \times \left(1 - \frac{\ln(\text{Rank} + 1)}{1 + \lambda}\right)$$
  Where $\text{Rank}$ is the 0-indexed position and $\lambda$ is the decay coefficient.
- **How to Test**:
  ```bash
  php artisan test --filter="rag knowledge service ingests and searches"
  ```

---

### J. Mean Opinion Score (MOS) Evaluation
When calls end, the queue schedules `EvaluateVoiceQualityJob` to assess call quality dynamically.

- **Formula**:
  $$\text{MOS} = \alpha \cdot \Theta_{\text{intelligibility}} + \beta \cdot \left(1 - \frac{L_{\text{tts}}}{1500}\right) + \gamma \cdot \Phi_{\text{emotion}}$$
  - $\Theta_{\text{intelligibility}}$: Acoustic intelligibility score.
  - $L_{\text{tts}}$: Text-to-speech delay (latency in milliseconds).
  - $\Phi_{\text{emotion}}$: Vocal inflection variance.
  - $\alpha, \beta, \gamma$: Configured weights (defined in `config/telephony.php` under `mos_weights`).
- **How to Test**:
  ```bash
  php artisan test --filter="evaluate voice quality job calculates MOS"
  ```

---

### K. PCI Compliance, Redaction, & Voice Payments
Telemetry calls can collect diagnostics fees or invoice balances securely (`POST /api/webhooks/process-payment`).

1. **Recording Pauses**: Before collecting billing details, a PATCH request goes to `https://api.vapi.ai/call/{call_id}` to toggle `recordingEnabled => false` to stop active recording streams.
2. **PII Masking**: Credit cards and Social Security Numbers are redacted inside the `CallLog` transcript using the `ComplianceSanitizerService`. Matches are replaced with `[REDACTED]` or `[CARD REDACTED]`.
3. **Audit Trails**: Logs successful or failed transactions to `PaymentTransaction` tables. Manual supervisor redactions are recorded in `AuditLog` under `manual_redaction`.
- **How to Test**:
  ```bash
  php artisan test tests/Feature/VoicePaymentsAndQueueThrottleTest.php
  php artisan test tests/Feature/PIIRedactionTest.php
  ```

---

### L. Call Concurrency Throttling
To protect telephony channels from overloading, the `QueueThrottleService` restricts active phone lines.

- **Congestion Backoff**: Compares ongoing calls with the tenant's `concurrent_call_limit`. If ongoing calls reach the threshold ($\text{Limit} - 1$), the queue thread pauses using exponential `usleep` backoff loops until capacity becomes available.
- **How to Test**:
  ```bash
  php artisan test --filter="QueueThrottleService passes through under concurrency limits"
  ```

---

### M. Audio Telemetry & Latency Drift Warnings
Telephonies broadcast latency telemetries at the end of calls, processed via `ProcessLatencyDriftJob`.

- **Calculation**: Compares turn-by-turn delay durations ($audio\_out\_ms - audio\_in\_ms$) against the target base threshold ($600\text{ms}$):
  $$\text{Latency Drift} = \text{Average Turn Duration} - 600\text{ms}$$
- **Incident Escalation**: If three consecutive calls exceed the high-drift threshold ($1200\text{ms}$), the system:
  1. Sends a POST payload to Vapi's latency warning endpoint.
  2. Creates a critical incident log in the database: `action => 'high_priority_incident'`, `alert_type => 'high_latency_drift'`.
- **How to Test**:
  ```bash
  php artisan test tests/Feature/TelemetryAndMigrationTest.php
  ```

---

### N. LLM & TTS Resilient Failovers
If external AI voice providers experience downtime, local backups are engaged:

- **TTS Outages**: If ElevenLabs speech rendering latency exceeds $1500\text{ms}$, `TtsFallbackService` swaps the active voice output provider to Cartesia.
- **LLM Outages**: If the primary OpenAI endpoint fails consecutively, `BackupLlmRouter` switches the prompt parsing system to local Ollama (`llama3`).
- **Resilience Score ($R$)**: The Status HUD (`/admin/status-hud`) renders the tenant's resilience score:
  $$R = \left(1 - \frac{\text{Downtime in Seconds}}{\text{Total Call Duration}}\right) \times \frac{\text{Successful Failovers}}{\text{Total Failovers}}$$
- **How to Test**:
  ```bash
  php artisan test tests/Feature/SystemStatusHUDTest.php
  ```

---

### O. Preventative Maintenance & Capacity Gates
The platform features an automated preventative maintenance scan run via Artisan.

- **Command**: `php artisan app:process-maintenance-agreements --tenant={id}`.
- **Shifts Audit**: Scans active maintenance contracts. Before dispatching notifications, it reviews the technician calendar. If the team is already overbooked (existing appointments exceed scheduled technician hours), the notification campaign is skipped to prevent capacity overload.
- **How to Test**:
  ```bash
  php artisan test --filter="ProcessMaintenanceAgreements"
  ```

---

### P. SaaS Subscriptions & Billing Events
Tenant billing hooks map Stripe webhook events directly to platform limits:

- **`invoice.payment_succeeded`**: Upgrades tenant plan level to `pro`, removes `dispatch_locked` status, and raises call limits to 1000 calls.
- **`invoice.payment_failed`**: Locks the supervisor dashboard dispatch capabilities (`settings.dispatch_locked = true`).
- **`customer.subscription.deleted`**: Reverts the tenant to the `free` tier, resetting limits to 100 calls.
- **How to Test**:
  ```bash
  php artisan test tests/Feature/StripeBillingTest.php
  ```

---

### Q. Technician Mobile Portal
Road technicians log in via Passkeys (`/technician/login`) to manage their routing:

1. **Performance Lambda ($\Lambda$)**: Computes operational efficiency displayed on the mobile PWA page:
   $$\Lambda = \frac{j_{\text{completed}}}{t_{\text{scheduled}} + sum\_travel}$$
   - $j_{\text{completed}}$: Bookings completed today.
   - $t_{\text{scheduled}}$: Shift window in hours.
   - $sum\_travel$: Cumulative transit travel duration in hours.
2. **Mascot Mood States**:
   - **State 0 (Idle)**: Default state.
   - **State 1 (Scanning)**: Active if high-priority emergency bookings are scheduled.
   - **State 2 (Victory)**: Active if jobs are completed today with positive CSAT ratings ($\ge 80\%$).
   - **State 3 (Sad/Error)**: Active if a route delay occurs (scheduled start is in the past, but the job status is still `booked`/`en_route`).
- **How to Test**:
  ```bash
  php artisan test tests/Feature/TechnicianPortalTest.php
  ```

---

## 4. Developer Deployment & Maintenance

### Code Style Guidelines
Before checking in code changes, format the PHP codebase using Pint:
```bash
vendor/bin/pint --dirty --format agent
```

### Zero-Downtime DB Column Migration Pattern
To rename or modify columns safely without application downtime:
1. **Expand Phase**: Add the new column (e.g. `booking_notes` next to `job_details`) in a migration.
2. **Replicate Phase**: Attach Model Observers (like `BookingObserver`) to write to both columns on saving.
3. **Contract Phase**: Once the code is completely deployed, run contract migrations to drop the old column:
   ```bash
   RUN_CONTRACT_MIGRATIONS=true php artisan migrate
   ```

### Deploy Promote Command
Deployments between environments (dev $\rightarrow$ uat $\rightarrow$ prod) are triggered via:
```bash
php artisan deploy:promote {environment}
```
This updates assistant definitions and endpoints mapping to the definitions inside [environments.yaml](file:///Users/benk/Documents/work/laravel/businesscalls/config/deploy/environments.yaml). Following updates, reload key daemons:
```bash
php artisan queue:restart
php artisan reverb:restart
```

---

*Welcome aboard! Reach out to the lead developer if you have questions regarding API keys or sandbox provisioning.*
