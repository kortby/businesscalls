# businesscalls: Non-Technical Onboarding & Interactive Roleplay Manual

Welcome to the team! This guide is created specifically for non-technical team members—such as Product Managers, Quality Assurance (QA) specialists, Customer Support representatives, and Business Operations team members. 

This manual will teach you how the platform works by having you roleplay as a **Business Owner** (managing the dashboard and technicians) and a **Client / Customer** (booking services and receiving updates).

---

## 1. Getting Started & Logging In

### A. The Business Owner Setup
To set up your simulation workspace:
1. Open the application in your browser (e.g., `http://localhost:8000`).
2. Register a new account or log in with the credentials provided for your test tenant.
3. Once logged in, you will be taken to the **Operations Dashboard** (`/dashboard`).

### B. The Client / Customer Setup
To play the role of the Customer, you will act as a homeowner needing trade services (plumbing, HVAC, electrical). You will trigger simulated calls, send texts, and receive notifications as if you were requesting service.

---

## 2. Feature-by-Feature Testing Playbook

---

### Feature A: Sandbox (Test) Mode vs. Live Mode
**How it Works**:
The software allows business owners to toggle their operational state. In **Sandbox Mode**, all billing operations, Stripe checkouts, and external telephony integrations are simulated so that you do not incur charges or place real phone calls. In **Live Mode**, actual APIs (Stripe, Vapi/Retell, Twilio) are engaged.

#### 1. Role: Business Owner
*   **Testing Steps**:
    1. Navigate to the dashboard.
    2. Click the **Toggle Sandbox** button or navigate to Settings.
    3. Observe the banner change at the top of the interface.
*   **What to Expect**:
    *   When Sandbox is active, a clear "Test Mode / Sandbox Active" badge is displayed.
    *   No real charges can occur.

#### 2. Role: Client
*   **Testing Steps**:
    1. Attempt to purchase a service or schedule an appointment while the business is in Sandbox Mode.
*   **What to Expect**:
    *   Checkout sessions redirect instantly with mock success queries (`?checkout=success&test_mode=true`).
    *   The platform updates your subscription plan instantly without requiring real credit card entries.

---

### Feature B: AI Voice Receptionist & Emergency Triage
**How it Works**:
The AI receptionist answers incoming customer phone calls, understands the problem, and dynamically classifies it as an **Emergency** or **Routine Maintenance** according to built-in rules:
*   **Plumbing**: Classified as an emergency if the customer reports a water leak.
*   **HVAC**: Classified as an emergency if the outdoor temperature is extreme ($\ge 101^\circ\text{F}$ or $\le 15^\circ\text{F}$).
*   **Electrical**: Classified as an emergency if there are sparking outlets or a burning smell.

If it is an emergency, the scheduling engine automatically reschedules any subsequent routine appointments for that technician, pushing them back by **120 minutes** (2 hours) to clear the technician's route for the emergency.

#### 1. Role: Business Owner
*   **Testing Steps**:
    1. Go to `/bookings` and schedule a **Routine Maintenance** job for technician "John Doe" at 11:00 AM on a specific date.
    2. Open `/admin/call-monitor` to prepare to watch the incoming call.
*   **What to Expect**:
    *   The routine booking is confirmed and scheduled at 11:00 AM.

#### 2. Role: Client
*   **Testing Steps**:
    1. Place a simulated call or trigger the dispatch webhook `/api/webhooks/dispatch` representing an emergency plumbing issue (e.g. payload: `{"service_type": "plumbing", "water_leak": true}`).
*   **What to Expect**:
    *   The AI registers the call as an **Emergency**.
    *   The system automatically schedules the emergency booking at the current hour.
    *   **Verify on Dashboard (Business Owner)**: Refresh the dashboard or calendar. John Doe's original 11:00 AM routine appointment is pushed back to 1:00 PM (120 minutes later).
    *   **Verify SMS Notification (Client)**: The client of the routine appointment receives an automated SMS saying their appointment window has been rescheduled.

---

### Feature C: Live Call Monitoring, Whispering & Barging
**How it Works**:
Supervisors can listen to live conversations between customers and the AI receptionist. If the AI receptionist gets stuck or if the customer gets frustrated, the supervisor can type helper prompts directly to the AI (Whispering) or click to take over the call entirely (Barging).

#### 1. Role: Business Owner (Supervisor)
*   **Testing Steps**:
    1. Navigate to the **Supervisor HUD** (`/admin/supervisor-hud`).
    2. When a live call starts, look for the active transcript box.
    3. Type a coaching instruction in the *Whisper box* and click **Send**.
    4. Click the red **Barge-in** button.
*   **What to Expect**:
    *   The whisper text will be sent to the AI without the customer hearing it.
    *   Clicking **Barge-in** disconnects the AI from the line and places you (the supervisor) in a live voice conversation with the customer. The call log record is flagged as `supervisor_barged`.

#### 2. Role: Client
*   **Testing Steps**:
    1. Speak to the AI receptionist.
    2. Listen for the moment the supervisor barges in.
*   **What to Expect**:
    *   You will hear the AI receptionist stop speaking, and a human operator (the supervisor) will speak to you directly.

---

### Feature D: Interactive Voice Response (IVR) DTMF Menus
**How it Works**:
When a customer calls, they can navigate menu systems by pressing numbers on their phone keypad (DTMF tones). The platform supports:
*   **Single-digit presses**: (e.g., Press 1 for dispatch, Press 2 for billing).
*   **Multi-digit sequences**: Caches keypresses (e.g. pressing 2 then 1) to match submenus or routing destinations, clearing the cache once a match occurs.

#### 1. Role: Business Owner
*   **Testing Steps**:
    1. Go to the **Call Flow Builder** (`/admin/call-flow`).
    2. Configure a keypress mapping: set `1` to transfer to an emergency technician queue, and `2` to enter a billing submenu.
*   **What to Expect**:
    *   The visual layout displays your routing map.

#### 2. Role: Client
*   **Testing Steps**:
    1. Place a call. When prompted by the AI receptionist's IVR menu, press `1` on your phone keypad.
*   **What to Expect**:
    *   The call is transferred to the emergency technician.
    *   **Verify in Logs**: The call log confirms a keypress of `1` was received and routed.

---

### Feature E: AI SMS Chatbot & Conflict Checking
**How it Works**:
Customers can text the business number to book appointments. The SMS Chatbot automatically parses the message to find the requested service type and time. It then checks the technician roster:
*   The technician must have the required skills.
*   The technician must have active shift availability for that day.
*   The requested slot must not conflict with other bookings, enforcing a **1.5-hour travel buffer** between appointments.

#### 1. Role: Business Owner
*   **Testing Steps**:
    1. Navigate to `/employees` and verify you have a technician with the "HVAC" skill.
    2. Go to `/availabilities` and set their working shift for Thursdays from 8:00 AM to 5:00 PM.
    3. Create a conflict: manually book them for a job next Thursday at 11:00 AM.

#### 2. Role: Client
*   **Testing Steps**:
    1. Test 1 (Success): Text the bot: *"I need an HVAC service next Thursday at 3:00 PM"*.
    2. Test 2 (Collision Conflict): Text the bot: *"I need HVAC service next Thursday at 10:00 AM"*.
*   **What to Expect**:
    *   **Test 1 Outcome**: The chatbot replies *"Dispatch Confirmed! [Technician Name] will arrive next Thursday at 3:00 PM"*.
    *   **Test 2 Outcome**: The chatbot blocks the booking due to the 1.5-hour travel buffer collision with the 11:00 AM appointment. It replies: *"No available technician matches your request at that time"*.

---

### Feature F: Outbound Campaigns & Compliance (TCPA)
**How it Works**:
Businesses can launch bulk automated call or text campaigns to list recipients. To remain compliant with TCPA regulations, the system automatically checks the local time of each recipient based on their phone number area code. Outbound calls are blocked if the local time is outside compliant hours: **8:00 AM to 9:00 PM**.

#### 1. Role: Business Owner
*   **Testing Steps**:
    1. Go to the campaigns section.
    2. Create a new campaign and add a recipient with a West Coast area code (e.g., Seattle `+1206`).
    3. Simulate launching the campaign when the local time in Seattle is 5:00 AM.
*   **What to Expect**:
    *   The system blocks the dialer.
    *   An audit log record is created: `action => 'tcpa_compliance_violation'`.

#### 2. Role: Client
*   **What to Expect**:
    *   You will not receive any call or text during restricted hours.

---

### Feature G: Voicemail Drops & Answering Machine Detection (AMD)
**How it Works**:
When the system alerts a technician about a new job assignment via phone call, it checks if a human or a voicemail machine answers. If it detects a machine, it automatically deposits a pre-recorded voicemail message (voicemail drop) and updates the booking status.

#### 1. Role: Business Owner
*   **Testing Steps**:
    1. Dispatch a technician alert call.
*   **What to Expect**:
    *   An outbound call is placed to the technician's phone.

#### 2. Role: Technician (Playing the Role)
*   **Testing Steps**:
    1. Test 1: Answer the call and speak.
    2. Test 2: Let the call go to your voicemail.
*   **What to Expect**:
    *   **Test 1 Outcome**: The system recognizes a human voice. The booking status updates to `booked`.
    *   **Test 2 Outcome**: The system detects a voicemail machine. It triggers a voicemail drop and updates the booking status to `voicemail_alerted` on the supervisor dashboard.

---

### Feature H: PCI-Compliant Call Payments & Redaction
**How it Works**:
If a customer makes a credit card payment over the phone:
*   The AI receptionist pauses call recording to prevent capturing sensitive card numbers.
*   The system charges the payment through Stripe.
*   The system automatically redacts credit card numbers or SSNs from the text transcript, replacing them with `[CARD REDACTED]`.

#### 1. Role: Business Owner
*   **Testing Steps**:
    1. Go to `/admin/call-monitor` or check call logs.
    2. Review the transcript of a completed call where a payment was collected.
*   **What to Expect**:
    *   The customer's card numbers are masked as `[REDACTED]` or `[CARD REDACTED]`.
    *   The call recording file is split or paused during card collection.

#### 2. Role: Client
*   **Testing Steps**:
    1. Speak your credit card details to the AI receptionist during a payment flow.
*   **What to Expect**:
    *   The payment processes successfully.
    *   Your card number is secure and never stored in plain text anywhere in the database.

---

### Feature I: Mascot Customization Shop
**How it Works**:
Business owners earn loyalty points based on customer satisfaction (CSAT) ratings. These points can be spent in the Mascot Shop to unlock custom receptionist avatar skins (e.g., standard, corporate, gold).

#### 1. Role: Business Owner
*   **Testing Steps**:
    1. Go to `/admin/mascot-shop`.
    2. View your current points total (calculated as `10 * Average CSAT rating`).
    3. Click **Purchase** on a locked skin (e.g., *Victory Gold*).
*   **What to Expect**:
    *   If you have enough points, the skin unlocks and activates.
    *   Your avatar changes visually throughout the dashboard.
    *   Points are deducted from your balance.

---

### Feature J: Technician Mobile App
**How it Works**:
Technicians log in via passkeys to view their routes, log transit times, and update work tickets. The PWA calculates a technician efficiency score ($\Lambda$) and adjusts a mascot avatar's facial expression based on route delay indicators.

#### 1. Role: Technician
*   **Testing Steps**:
    1. Go to `/technician/login` and log in.
    2. View today's route schedules and look at the mascot status.
    3. Update a booking status to **En Route**, then **On Site**, then **Completed**.
*   **What to Expect**:
    *   The mobile screen displays travel times and metrics.
    *   If you complete jobs successfully, the mascot displays a **Victory / Happy** state.
    *   If an active job's scheduled time passes without completion, the mascot turns **Sad / Error**, notifying you of a route delay.

#### 2. Role: Business Owner
*   **Testing Steps**:
    1. Navigate to `/admin/leaderboard`.
*   **What to Expect**:
    *   Technicians are ranked live based on jobs completed, transit speeds, and customer ratings.

---

## 3. Comprehensive Onboarding Testing Checklist

Use this checklist during onboarding to verify you understand the platform features:

| Task / Feature | Persona | Action to Take | Success Criteria | Done? |
| :--- | :--- | :--- | :--- | :---: |
| **Sandbox Switch** | Business Owner | Toggle test mode in settings | Top banner displays "Sandbox Active" | [ ] |
| **Routine Booking** | Business Owner | Create booking for tomorrow at 1:00 PM | Booking shows up on calendar | [ ] |
| **Emergency Collision**| Client / Bot | Trigger emergency plumbing call for same tech | Routine booking is pushed back 120 mins | [ ] |
| **Barge Call** | Supervisor | Open HUD and click "Barge-in" during call | Call log shows `supervisor_barged` | [ ] |
| **SMS Scheduling** | Client | Text: *"I need HVAC service Thursday at 2 PM"* | Bot books job and texts confirmation | [ ] |
| **Compliance Filter** | Business Owner | Run campaign to West Coast at 5:00 AM | Dialing blocked; audit log warning written | [ ] |
| **Card Redaction** | Client | Speak credit card details to AI receptionist | Call log shows `[CARD REDACTED]` | [ ] |
| **Mascot Shop** | Business Owner | Purchase receptionist skin in shop | Skin status updates; avatar graphics change | [ ] |
| **Tech Status Update**| Technician | Mark job status as "Completed" in portal | Leaderboard metrics update in real time | [ ] |

---

*Need help setting up dummy numbers or test accounts? Contact your training lead for assistance.*
