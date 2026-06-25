<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;

class GenerateRouteDocs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:generate-routes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates comprehensive markdown user guides for all registered application routes';

    /**
     * User-oriented guides for all core user-facing UI screens.
     */
    protected array $userGuides = [
        'home' => [
            'title' => 'Welcome Page',
            'overview' => 'The public-facing landing page of the businesscalls platform.',
            'how_it_works' => 'Displays general product options, branding messages, and provides links to register or log into the application portal.',
            'how_to_use' => 'Navigate to the root URL (/) of the application in your browser to view the greeting layout.',
        ],
        'about' => [
            'title' => 'About Us Information',
            'overview' => 'Public information page describing the mission and technology of businesscalls.',
            'how_it_works' => 'Explains our real-time AI scheduling algorithms, team backgrounds, and customer success principles.',
            'how_to_use' => 'Click the "About" link in the navbar or go directly to the `/about` URL path.',
        ],
        'pricing' => [
            'title' => 'Subscription Pricing Plans',
            'overview' => 'Review active plan options, price structures, and custom feature tiers.',
            'how_it_works' => 'Presents tiered business options (Starter, Professional, Enterprise) and active features like CQS analysis, Reverb channels, and Twilio support.',
            'how_to_use' => 'Navigate to `/pricing` in your web browser to compare plans.',
        ],
        'contact' => [
            'title' => 'Contact & Support Request',
            'overview' => 'Send messages or support queries directly to the businesscalls service administrators.',
            'how_it_works' => 'Provides client details form fields to capture name, email, text notes, and logs queries.',
            'how_to_use' => 'Fill out the contact form fields and press "Send Inquiry".',
        ],
        'dashboard' => [
            'title' => 'Operations Control Dashboard',
            'overview' => 'The central management control center for business owners and dispatchers.',
            'how_it_works' => 'Aggregates real-time statistics including Call Quality Score (CQS), Booking Streak, open dispatches, and logs recent technician bookings dynamically via Pusher Reverb.',
            'how_to_use' => 'Monitor booking metrics, browse active technician schedules, and click logs rows to inspect job details.',
        ],
        'availabilities.index' => [
            'title' => 'Technician Availabilities List',
            'overview' => 'View active shift schedules and hours for all registered technicians.',
            'how_it_works' => 'Displays weekly availability grids grouped by technician. Overlapping shift windows are prevented automatically.',
            'how_to_use' => 'Review the calendar grid to verify technician coverage for the upcoming week.',
        ],
        'availabilities.store' => [
            'title' => 'Schedule Technician Shifts',
            'overview' => 'Assign weekly shift hours to individual technicians.',
            'how_it_works' => 'Validates shift parameters and creates active schedule blocks. Blocks overlapping shifts.',
            'how_to_use' => 'Select a technician, choose the day of the week, input shift start and end times, and click "Save Shift".',
        ],
        'availabilities.update' => [
            'title' => 'Edit Technician Shift Hours',
            'overview' => 'Update or adjust existing work hours for technicians.',
            'how_it_works' => 'Re-validates shift parameters and checks for schedule conflicts before updating the database.',
            'how_to_use' => 'Click on an existing shift box, modify the hours, and click "Update".',
        ],
        'availabilities.destroy' => [
            'title' => 'Remove Technician Shifts',
            'overview' => 'Delete scheduled shifts from a technician\'s calendar.',
            'how_it_works' => 'Removes availability blocks immediately, freeing up slots for rescheduling.',
            'how_to_use' => 'Click the "Delete" trash icon next to a shift block and confirm.',
        ],
        'bookings.index' => [
            'title' => 'Booking Calendar & Appointments',
            'overview' => 'The centralized dispatch board displaying all customer bookings and appointments.',
            'how_it_works' => 'Enforces travel buffers of 1.5 hours between bookings automatically to ensure technicians can arrive on time without overlap.',
            'how_to_use' => 'Browse jobs on the calendar by day, week, or month. Click any booking to inspect notes or coordinate manual dispatches.',
        ],
        'bookings.store' => [
            'title' => 'Log New Manual Bookings',
            'overview' => 'Log a customer booking manually when receiving calls directly.',
            'how_it_works' => 'Validates technician availability, parses trade skills, checks for conflicts, and enforces the 1.5-hour buffer.',
            'how_to_use' => 'Click "New Booking", input the customer phone number, select the technician, choose a slot, and type the service job details.',
        ],
        'bookings.update' => [
            'title' => 'Reschedule Service Bookings',
            'overview' => 'Reschedule or edit service details of logged appointments.',
            'how_it_works' => 'Checks scheduling rules for conflicts and updates booking information in the database.',
            'how_to_use' => 'Double click an appointment, modify the date/time or comments, and click "Save Changes".',
        ],
        'bookings.destroy' => [
            'title' => 'Cancel Customer Bookings',
            'overview' => 'Cancel an appointment and clear the technician\'s schedule.',
            'how_it_works' => 'Deletes the booking from the database and updates metrics instantly.',
            'how_to_use' => 'Click on a booking, press the "Cancel Booking" button, and confirm.',
        ],
        'conversations.index' => [
            'title' => 'Client Conversations History',
            'overview' => 'View transcripts, recordings, and SMS messages between customer clients and the AI receptionist.',
            'how_it_works' => 'Logs live call events, analyzes customer intent, scores call quality (CQS), and compiles dialogue streams.',
            'how_to_use' => 'Browse active chat and call threads. Click on any contact name to review their full transcript logs or play back call recordings.',
        ],
        'conversations.messages.store' => [
            'title' => 'Send Manual SMS Responses',
            'overview' => 'Send text messages to clients directly from the dashboard.',
            'how_it_works' => 'Saves and pushes messages via Reverb websocket channels to synchronize dialogue instantly.',
            'how_to_use' => 'Type your message into the text field at the bottom of the conversation thread and click "Send".',
        ],
        'employees.index' => [
            'title' => 'Technicians & Staff Directory',
            'overview' => 'View all registered technicians, their details, and trade skills.',
            'how_it_works' => 'Groups staff details, links shift schedules, and compiles skill specializations.',
            'how_to_use' => 'Search technicians by name. Click "View Profile" to check their active schedule or edit records.',
        ],
        'employees.store' => [
            'title' => 'Register New Technicians',
            'overview' => 'Add new staff members to your team.',
            'how_it_works' => 'Creates a technician profile, registers skill tags, and optionally generates login credentials.',
            'how_to_use' => 'Click "Add Staff", enter first name, last name, phone, trade skills (e.g. plumbing, HVAC), notification preference, and click "Save".',
        ],
        'employees.create' => [
            'title' => 'Create Technician Form Page',
            'overview' => 'Form interface to add new staff members.',
            'how_it_works' => 'Renders the team registration input interface.',
            'how_to_use' => 'Fill out employee contact details and submit.',
        ],
        'employees.show' => [
            'title' => 'Technician Profile Details',
            'overview' => 'Detailed view of an individual technician\'s performance and shifts.',
            'how_it_works' => 'Displays contact logs, active skills, calendar shifts, and job history charts.',
            'how_to_use' => 'Click on any technician to view their full profile panel.',
        ],
        'employees.update' => [
            'title' => 'Update Technician Records',
            'overview' => 'Modify contact info, skills, or notification preferences of existing technicians.',
            'how_it_works' => 'Updates employee record values in the database.',
            'how_to_use' => 'Click "Edit Profile", update fields, and click "Save Updates".',
        ],
        'employees.destroy' => [
            'title' => 'Deactivate Technician Profiles',
            'overview' => 'Deactivate or delete a technician from the roster.',
            'how_it_works' => 'Removes employee records and archives their history logs securely.',
            'how_to_use' => 'Press "Deactivate Staff" on the profile page and confirm.',
        ],
        'employees.edit' => [
            'title' => 'Edit Technician Form Page',
            'overview' => 'Form interface to edit existing employee records.',
            'how_it_works' => 'Pre-populates values of employee records into input fields.',
            'how_to_use' => 'Edit fields and submit updates.',
        ],
        'customers.index' => [
            'title' => 'Customer CRM Directory',
            'overview' => 'Manage customer profiles and client histories.',
            'how_it_works' => 'Compiles customer profiles automatically when calls are processed.',
            'how_to_use' => 'Search client entries by name or phone. Review recent job tickets associated with each client.',
        ],
        'customers.store' => [
            'title' => 'Add Customer Profile',
            'overview' => 'Manually register a new client contact.',
            'how_it_works' => 'Inserts a new customer record into the tenant scoped database.',
            'how_to_use' => 'Click "New Customer", input phone number, name, email, and click "Save".',
        ],
        'customers.import' => [
            'title' => 'Import Client Databases (CSV)',
            'overview' => 'Bulk upload customer lists from spreadsheets or CRM exports.',
            'how_it_works' => 'Parses CSV formats, matches phone logs, and bulk-inserts records securely.',
            'how_to_use' => 'Select a CSV file from your computer, match columns (Name, Phone), and click "Import Database".',
        ],
        'jobs.index' => [
            'title' => 'Service Jobs Board',
            'overview' => 'Track active work tickets and job details.',
            'how_it_works' => 'Groups job descriptions, assigned technicians, dates, and billing amounts.',
            'how_to_use' => 'Browse open jobs and monitor status cards from creation to completion.',
        ],
        'jobs.store' => [
            'title' => 'Create Service Jobs',
            'overview' => 'Log new work orders and link them to clients.',
            'how_it_works' => 'Creates a service job ticket and logs administrative compliance records.',
            'how_to_use' => 'Click "New Job", input job description, link to a customer booking, select technician, and save.',
        ],
        'jobs.create' => [
            'title' => 'Create Job Form Page',
            'overview' => 'Form interface to log new work orders.',
            'how_it_works' => 'Renders the job ticket input interface.',
            'how_to_use' => 'Fill out service details and submit.',
        ],
        'jobs.show' => [
            'title' => 'Service Job Details',
            'overview' => 'Detailed view of an individual job ticket.',
            'how_it_works' => 'Compiles history logs, customer details, assigned technicians, and billing amounts.',
            'how_to_use' => 'Click on any job ID to view the full details panel.',
        ],
        'jobs.update' => [
            'title' => 'Update Job Details',
            'overview' => 'Modify service descriptions, pricing, or status parameters of a job.',
            'how_it_works' => 'Updates job record values in the database.',
            'how_to_use' => 'Click "Edit Job", update comments or details, and click "Save Updates".',
        ],
        'jobs.destroy' => [
            'title' => 'Cancel Service Jobs',
            'overview' => 'Archive or cancel service tickets.',
            'how_it_works' => 'Deletes the job ticket and logs compliance events.',
            'how_to_use' => 'Press "Cancel Job" and confirm.',
        ],
        'jobs.edit' => [
            'title' => 'Edit Job Form Page',
            'overview' => 'Form interface to modify existing job tickets.',
            'how_it_works' => 'Pre-populates values of job records into input fields.',
            'how_to_use' => 'Edit fields and submit updates.',
        ],
        'admin.diagnostics' => [
            'title' => 'Live Diagnostics HUD',
            'overview' => 'Infrastructure telemetry panel tracking server vitals.',
            'how_it_works' => 'Displays active WebSocket Reverb connections, queue load metrics, database latency, and average conversational latency.',
            'how_to_use' => 'Monitor system parameters to ensure high performance and low conversational delay times.',
        ],
        'admin.loyalty' => [
            'title' => 'Customer Loyalty Analytics',
            'overview' => 'Monitor customer retention and VIP dispatches.',
            'how_it_works' => 'Calculates loyalty metrics, streaks, and billing statuses from customer booking history logs.',
            'how_to_use' => 'Review the loyalty graphs to spot top customers and target VIP accounts.',
        ],
        'admin.health' => [
            'title' => 'System Connection Health',
            'overview' => 'Monitor incoming webhook reliability, deduplication, and telephony API statuses.',
            'how_it_works' => 'Logs webhook events to check for duplicates, errors, and system recovery.',
            'how_to_use' => 'Review logs to debug call dropouts or connection problems with external providers.',
        ],
        'admin.callflow' => [
            'title' => 'Voice Interactive Planner',
            'overview' => 'Visual editor to configure voice response routing rules.',
            'how_it_works' => 'Sets call answering behavior, keypress triggers, and emergency fallback numbers.',
            'how_to_use' => 'Use the drag-and-drop tree to change voice prompts, key triggers, or fallback routing rules.',
        ],
        'admin.reports' => [
            'title' => 'Executive Performance Reports',
            'overview' => 'Summarize call activities, booking conversion rates, and metrics.',
            'how_it_works' => 'Generates summaries of call counts, booking rates, and technician performance.',
            'how_to_use' => 'Choose a reporting date range and click "Download Report" to export a PDF summary.',
        ],
        'admin.report.download' => [
            'title' => 'Download Performance Reports',
            'overview' => 'Export performance summaries directly.',
            'how_it_works' => 'Generates and downloads PDF documents directly.',
            'how_to_use' => 'Triggered automatically when downloading reports.',
        ],
        'admin.preflight' => [
            'title' => 'Pre-Flight System Check',
            'overview' => 'Run connection tests on third-party service APIs.',
            'how_it_works' => 'Tests Twilio integration, Stripe API keys, Pusher connection, and database status.',
            'how_to_use' => 'Click "Run Verification" to check if the system is fully configured and ready for production calls.',
        ],
        'admin.achievements' => [
            'title' => 'Achievements & Milestones',
            'overview' => 'Track dispatcher operational milestones.',
            'how_it_works' => 'Awards badges (like "Booking Streak Master" or "Response Hero") based on metrics.',
            'how_to_use' => 'View unlocked achievements to encourage dispatch team performance.',
        ],
        'admin.onboarding' => [
            'title' => 'Interactive Onboarding Guide',
            'overview' => 'Interactive step-by-step checklist to configure businesscalls.',
            'how_it_works' => 'Tracks progress through necessary setup steps (Add technician, define shifts, link Twilio).',
            'how_to_use' => 'Complete each item on the quest checklist to move the account from sandbox to live production mode.',
        ],
        'admin.onboarding-setup' => [
            'title' => 'Onboarding Status Reset',
            'overview' => 'Reset onboarding steps for testing.',
            'how_it_works' => 'Clears onboarding checkmarks to restart the setup walkthrough.',
            'how_to_use' => 'Click "Reset Setup" in settings to restart onboarding.',
        ],
        'admin.dispatch-map' => [
            'title' => 'Interactive Dispatch Map',
            'overview' => 'Visual map coordinates tracking active service locations.',
            'how_it_works' => 'Embeds coordinates of technician job bookings and visualizes them on a maps interface.',
            'how_to_use' => 'Zoom and drag the map to monitor technician travel routes and coordinate quick emergency dispatches.',
        ],
        'admin.leaderboard' => [
            'title' => 'Dispatcher Rankings',
            'overview' => 'Review rankings of team members based on booking success.',
            'how_it_works' => 'Calculates ranking positions based on successful call booking conversion rates.',
            'how_to_use' => 'Use rank lists to motivate and optimize team booking performance.',
        ],
        'admin.mascot-shop' => [
            'title' => 'AI Receptionist Mascot Avatar skins',
            'overview' => 'Dispatcher shop to personalize your receptionist avatar skin.',
            'how_it_works' => 'Lets users spend points earned from booking achievements to unlock custom skins.',
            'how_to_use' => 'Browse available avatar designs, click "Purchase" using earned points, and equip skins.',
        ],
        'admin.mascot-shop.purchase' => [
            'title' => 'Purchase Mascot Avatar Skins',
            'overview' => 'Unlock custom skins using earned points.',
            'how_it_works' => 'Deducts dispatcher points and unlocks custom avatar skins.',
            'how_to_use' => 'Click "Purchase" on your desired skin inside the mascot shop.',
        ],
        'admin.integrations' => [
            'title' => 'Third-Party Integration Hub',
            'overview' => 'Connect external CRM, invoicing, and messaging providers.',
            'how_it_works' => 'Saves API credentials and synchronization keys securely.',
            'how_to_use' => 'Enter API tokens for Twilio, ServiceTitan, or Housecall Pro to link calendars.',
        ],
        'admin.integrations.save' => [
            'title' => 'Save CRM Credentials',
            'overview' => 'Save external CRM credentials.',
            'how_it_works' => 'Saves CRM keys to securely link client databases.',
            'how_to_use' => 'Click "Save Integration" after inputting API credentials.',
        ],
        'admin.integrations.timing' => [
            'title' => 'Save Sync Timing Settings',
            'overview' => 'Configure sync intervals for external CRMs.',
            'how_it_works' => 'Saves cron sync schedules.',
            'how_to_use' => 'Select sync timing frequency (e.g. hourly, daily) and save.',
        ],
        'admin.call-monitor' => [
            'title' => 'Live Call Listening Panel',
            'overview' => 'Listen in real time to customer calls answered by the AI receptionist.',
            'how_it_works' => 'Connects to active WebRTC audio streams of active voice calls.',
            'how_to_use' => 'View active calls list and click "Listen" to monitor audio in real time.',
        ],
        'admin.supervisor-hud' => [
            'title' => 'Supervisor HUD coaching panel',
            'overview' => 'Advanced coaching controls for active customer calls.',
            'how_it_works' => 'Connects WebRTC streams and supports two-way whispering or full barging overrides.',
            'how_to_use' => 'During a live call, click "Whisper" to coach the agent silently, or "Barge In" to speak directly to the customer.',
        ],
        'admin.status-hud' => [
            'title' => 'System Status Monitor HUD',
            'overview' => 'Overview of server uptime and active webhook queues.',
            'how_it_works' => 'Checks response latency and monitors system health state.',
            'how_to_use' => 'Review status lights to ensure all services are fully operational.',
        ],
        'admin.audit-logs' => [
            'title' => 'Administrative Audit compliance log',
            'overview' => 'Verify administrative logs for security compliance audits.',
            'how_it_works' => 'Logs all system events, technician edits, call fallbacks, and configuration changes.',
            'how_to_use' => 'Search and filter logs by action type or date to verify system audit trail compliance.',
        ],
        'admin.experiments' => [
            'title' => 'AI Prompt Playground & Denoising',
            'overview' => 'Toggle experimental features and run prompt A/B tests.',
            'how_it_works' => 'Configures prompt variations and toggles background noise cancellation settings.',
            'how_to_use' => 'Toggle noise filters or test new prompt greetings, and review success rate statistics.',
        ],
        'admin.experiments.denoising' => [
            'title' => 'Toggle Call Noise Cancellation',
            'overview' => 'Enable or disable AI call noise cancellation filters.',
            'how_it_works' => 'Toggles raw audio cleanup processing models.',
            'how_to_use' => 'Click "Toggle Denoising" to enable or disable background audio cleaning.',
        ],
        'admin.experiments.save' => [
            'title' => 'Save Prompt A/B Test Variations',
            'overview' => 'Save prompt greetings to A/B test groups.',
            'how_it_works' => 'Saves test variations.',
            'how_to_use' => 'Type your new greeting variant and click "Save Experiment".',
        ],
        'technician.dashboard' => [
            'title' => 'Technician Mobile App',
            'overview' => 'Mobile portal for technicians to check schedules and update jobs.',
            'how_it_works' => 'Displays shift calendar and assigned bookings scoped to the technician.',
            'how_to_use' => 'Log in on a mobile browser. View your daily route, tap "En Route" when heading to a job, "On Site" when you arrive, and "Completed" to log notes and billing amounts.',
        ],
        'technician.login' => [
            'title' => 'Technician Login Screen',
            'overview' => 'Login portal for road technicians.',
            'how_it_works' => 'Verifies credentials or biometric passkey checks.',
            'how_to_use' => 'Log in using your password or registered biometric passkey.',
        ],
        'profile.edit' => [
            'title' => 'User Profile Settings',
            'overview' => 'Manage personal contact information.',
            'how_it_works' => 'Renders form to edit user account settings.',
            'how_to_use' => 'Modify your name, email, or telephone and click "Save Changes".',
        ],
        'profile.update' => [
            'title' => 'Update Profile Records',
            'overview' => 'Save changes to personal account details.',
            'how_it_works' => 'Updates account details in the database.',
            'how_to_use' => 'Modify fields and submit updates.',
        ],
        'profile.destroy' => [
            'title' => 'Deactivate Account Profile',
            'overview' => 'Deactivate or delete your user account profile.',
            'how_it_works' => 'Removes user records and log sessions.',
            'how_to_use' => 'Press "Deactivate Profile" and confirm.',
        ],
        'security.edit' => [
            'title' => 'MFA, Password, & Passkeys security panel',
            'overview' => 'Configure multi-factor security and biometric passkeys.',
            'how_it_works' => 'Enforces secure passwords, registers WebAuthn keys, and generates 2FA QR secrets.',
            'how_to_use' => 'Toggle Multi-Factor Authentication. Scan the QR code, or click "Register Passkey" to log in with touch ID/face ID.',
        ],
        'user-password.update' => [
            'title' => 'Change Account Password',
            'overview' => 'Update your login password.',
            'how_it_works' => 'Validates your old password and saves a new secure hash.',
            'how_to_use' => 'Input your current password, type the new password, and click "Save Password".',
        ],
        'appearance.edit' => [
            'title' => 'Branding Accent & Theme settings',
            'overview' => 'Set theme settings or branding brand accent colors.',
            'how_it_works' => 'Configures light, dark, or system mode styles and accent tokens.',
            'how_to_use' => 'Toggle between Light, Dark, or System mode, and select accent colors to brand the portal.',
        ],
        'settings.prompt.edit' => [
            'title' => 'AI Receptionist Voice Instructions',
            'overview' => 'Set greeting prompts and rules for the AI receptionist.',
            'how_it_works' => 'Configures the LLM instruction prompts used during customer call dialogues.',
            'how_to_use' => 'Edit the text prompt field to change how the AI answers (tone, questions to ask, pricing rules) and click "Update Prompt".',
        ],
        'settings.prompt.update' => [
            'title' => 'Save AI Receptionist Prompt',
            'overview' => 'Update the AI greeting and operational guidelines.',
            'how_it_works' => 'Saves new instructions to tenant configurations.',
            'how_to_use' => 'Click "Update Prompt" after modifying prompt text.',
        ],
        'settings.billing.index' => [
            'title' => 'Invoice Billing & Card Payments',
            'overview' => 'Manage Stripe subscription details, billing cards, and checkout.',
            'how_it_works' => 'Connects to the Stripe customer billing portal safely.',
            'how_to_use' => 'View current subscription details, click "Update Payment Method" or "View Invoices" to redirect to Stripe, or click "Upgrade Plan" to initiate checkout.',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting route user guides generation...');

        $routes = Route::getRoutes();
        $docsDir = base_path('docs/routes');
        if (! is_dir($docsDir)) {
            mkdir($docsDir, 0755, true);
        }

        $generatedCount = 0;
        $groupedRoutes = [];

        foreach ($routes as $route) {
            $uri = $route->uri();
            $methods = implode('|', array_filter($route->methods(), fn ($m) => $m !== 'HEAD'));
            if (empty($methods)) {
                $methods = 'GET';
            }

            $action = $route->getActionName();
            $name = $route->getName();
            $middleware = $route->middleware();

            $category = $this->determineCategory($uri, $name);
            $fileName = $this->generateFileName($methods, $uri);
            $filePath = $docsDir.'/'.$fileName;

            // Generate content
            $details = $this->parseRouteDetails($route, $methods, $uri, $action, $name, $middleware);

            // Write File
            $mdContent = $this->buildMarkdownContent($details);
            file_put_contents($filePath, $mdContent);

            $generatedCount++;
            $groupedRoutes[$category][] = [
                'uri' => $uri,
                'methods' => $methods,
                'name' => $name,
                'file' => 'routes/'.$fileName,
                'title' => $details['title'],
                'description' => $details['description'],
            ];
        }

        // Generate README.md Table of Contents
        $this->generateReadme($groupedRoutes);

        $this->info("Successfully generated {$generatedCount} route user guides in docs/routes/");
        $this->info('Central index generated at docs/README.md');

        return self::SUCCESS;
    }

    /**
     * Group routes into categories logically based on their prefix.
     */
    protected function determineCategory(string $uri, ?string $name): string
    {
        // Check if the route corresponds to a user guide
        $guideKey = $this->findGuideKey($uri, $name);
        if ($guideKey) {
            if ($uri === '/' || $uri === 'about' || $uri === 'pricing' || $uri === 'contact' || $uri === 'admin/onboarding') {
                return 'User Guide: Get Started';
            }
            if ($uri === 'dashboard') {
                return 'User Guide: Operations Dashboard';
            }
            if (str_starts_with($uri, 'availabilities') || str_starts_with($uri, 'bookings')) {
                return 'User Guide: Availability & Scheduling';
            }
            if (str_starts_with($uri, 'conversations')) {
                return 'User Guide: Communications';
            }
            if (str_starts_with($uri, 'employees') || str_starts_with($uri, 'customers') || str_starts_with($uri, 'jobs')) {
                return 'User Guide: Records Management';
            }
            if (str_starts_with($uri, 'admin/')) {
                return 'User Guide: Advanced Dispatch Tools';
            }
            if (str_starts_with($uri, 'settings/') || str_contains($uri, '/settings/')) {
                return 'User Guide: Account & Settings';
            }
            if (str_starts_with($uri, 'technician/')) {
                return 'User Guide: Technician Mobile App';
            }
        }

        return 'Developer API Reference';
    }

    /**
     * Locate the guide key based on the name or URI pattern.
     */
    protected function findGuideKey(string $uri, ?string $name): ?string
    {
        if ($name && isset($this->userGuides[$name])) {
            return $name;
        }

        // Fallback checks on URI patterns
        if ($uri === '/') {
            return 'home';
        }
        if ($uri === 'about') {
            return 'about';
        }
        if ($uri === 'pricing') {
            return 'pricing';
        }
        if ($uri === 'contact') {
            return 'contact';
        }
        if ($uri === 'dashboard') {
            return 'dashboard';
        }

        return null;
    }

    /**
     * Generate a safe, unique filename based on the method and URI.
     */
    protected function generateFileName(string $method, string $uri): string
    {
        $sanitizedMethod = strtolower(str_replace('|', '_', $method));
        $sanitizedUri = str_replace(['{', '}'], '', $uri);
        $sanitizedUri = preg_replace('/[^a-zA-Z0-9\-]/', '_', $sanitizedUri);
        $sanitizedUri = trim($sanitizedUri, '_');

        if (empty($sanitizedUri)) {
            $sanitizedUri = 'root';
        }

        return "{$sanitizedMethod}_{$sanitizedUri}.md";
    }

    /**
     * Retrieve details about a route, inspecting code where possible.
     */
    protected function parseRouteDetails($route, string $methods, string $uri, string $action, ?string $name, array $middleware): array
    {
        $guideKey = $this->findGuideKey($uri, $name);

        if ($guideKey) {
            $guide = $this->userGuides[$guideKey];

            return [
                'is_guide' => true,
                'title' => $guide['title'],
                'methods' => $methods,
                'uri' => $uri,
                'action' => $action,
                'name' => $name ?? 'None',
                'middleware' => $middleware,
                'description' => $guide['overview'],
                'how_it_works' => $guide['how_it_works'],
                'how_to_use' => $guide['how_to_use'],
                'parameters' => [],
                'renders_component' => null,
            ];
        }

        // Fallback to Developer Reference details
        $description = 'Developer API endpoint.';
        $howItWorks = 'Processes request triggers.';
        $howToUse = 'Send HTTP request calls.';
        $parameters = [];
        $rendersComponent = null;

        if ($action !== 'Closure' && str_contains($action, '@')) {
            [$controllerClass, $methodName] = explode('@', $action);
            if (class_exists($controllerClass) && method_exists($controllerClass, $methodName)) {
                $refClass = new ReflectionClass($controllerClass);
                $refMethod = new ReflectionMethod($controllerClass, $methodName);

                $docComment = $refMethod->getDocComment();
                if ($docComment) {
                    $description = $this->cleanDocComment($docComment);
                }

                $fileName = $refMethod->getFileName();
                $startLine = $refMethod->getStartLine();
                $endLine = $refMethod->getEndLine();

                if ($fileName && $startLine && $endLine) {
                    $lines = file($fileName);
                    $methodCode = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

                    if (preg_match('/\$request->validate\(\[\s*(.*?)\s*\]\)/s', $methodCode, $matches)) {
                        $validationContent = $matches[1];
                        preg_match_all("/['\"]([^'\"]+)['\"]\s*=>\s*\[\s*([^\]]+)\]/", $validationContent, $ruleMatches, PREG_SET_ORDER);
                        foreach ($ruleMatches as $ruleMatch) {
                            $paramName = $ruleMatch[1];
                            $rulesRaw = $ruleMatch[2];
                            $rulesClean = preg_replace('/[\'\"\s]/', '', $rulesRaw);
                            $parameters[] = [
                                'name' => $paramName,
                                'type' => str_contains($rulesClean, 'integer') ? 'integer' : (str_contains($rulesClean, 'numeric') ? 'numeric' : (str_contains($rulesClean, 'boolean') ? 'boolean' : (str_contains($rulesClean, 'array') ? 'array' : 'string'))),
                                'required' => str_contains($rulesClean, 'required') ? 'Yes' : 'No',
                                'rules' => str_replace(',', ', ', $rulesClean),
                            ];
                        }
                    }

                    if (preg_match("/Inertia::render\(\s*['\"]([^'\"]+)['\"]/i", $methodCode, $matches)) {
                        $rendersComponent = $matches[1];
                    }

                    $logicSummary = [];
                    if ($rendersComponent) {
                        $logicSummary[] = "Renders Inertia view: `{$rendersComponent}`.";
                    }
                    if (str_contains($methodCode, '::create(') || str_contains($methodCode, '::save(')) {
                        $logicSummary[] = 'Saves models updates.';
                    }
                    if (str_contains($methodCode, '::delete(') || str_contains($methodCode, '->delete(')) {
                        $logicSummary[] = 'Deletes records.';
                    }
                    if (str_contains($methodCode, 'TenantScope::')) {
                        $logicSummary[] = 'Applies tenant isolation scoping rules.';
                    }
                    if (str_contains($methodCode, 'event(new')) {
                        $logicSummary[] = 'Triggers WebSocket broadcasts.';
                    }

                    if (! empty($logicSummary)) {
                        $howItWorks = implode(' ', $logicSummary);
                    }
                }
            }
        }

        return [
            'is_guide' => false,
            'title' => "API Reference: {$methods} /{$uri}",
            'methods' => $methods,
            'uri' => $uri,
            'action' => $action,
            'name' => $name ?? 'None',
            'middleware' => $middleware,
            'description' => $description,
            'how_it_works' => $howItWorks,
            'how_to_use' => $howToUse,
            'parameters' => $parameters,
            'renders_component' => $rendersComponent,
        ];
    }

    /**
     * Clean up PHP DocBlock comments to standard text.
     */
    protected function cleanDocComment(string $docComment): string
    {
        $lines = explode("\n", $docComment);
        $cleanLines = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '/**' || $trimmed === '*/') {
                continue;
            }
            $cleanLine = ltrim($trimmed, '* ');
            if (empty($cleanLine) || str_starts_with($cleanLine, '@')) {
                continue;
            }
            $cleanLines[] = $cleanLine;
        }

        return implode(' ', $cleanLines);
    }

    /**
     * Build the markdown file content for a route.
     */
    protected function buildMarkdownContent(array $details): string
    {
        $middlewareStr = empty($details['middleware']) ? '*None*' : implode(', ', array_map(fn ($m) => "`{$m}`", $details['middleware']));

        $content = "# {$details['title']}\n\n";

        if ($details['is_guide']) {
            $content .= "## Overview\n\n";
            $content .= "{$details['description']}\n\n";

            $content .= "## How it Works\n\n";
            $content .= "{$details['how_it_works']}\n\n";

            $content .= "## How to Use\n\n";
            $content .= "{$details['how_to_use']}\n\n";

            $content .= "## Technical Details\n\n";
            $content .= "| Property | Value |\n";
            $content .= "| --- | --- |\n";
            $content .= "| **URL Path** | `/{$details['uri']}` |\n";
            $content .= "| **HTTP Method** | `{$details['methods']}` |\n";
            $content .= '| **Route Name** | '.($details['name'] !== 'None' ? "`{$details['name']}`" : '*None*')." |\n";
            $content .= "| **Action Code** | `{$details['action']}` |\n";
            $content .= "| **Middleware** | {$middlewareStr} |\n";
        } else {
            $content .= "{$details['description']}\n\n";
            $content .= "## Technical Details\n\n";
            $content .= "| Property | Value |\n";
            $content .= "| --- | --- |\n";
            $content .= "| **URI** | `/{$details['uri']}` |\n";
            $content .= "| **HTTP Methods** | `{$details['methods']}` |\n";
            $content .= '| **Route Name** | '.($details['name'] !== 'None' ? "`{$details['name']}`" : '*None*')." |\n";
            $content .= "| **Controller Action** | `{$details['action']}` |\n";
            $content .= "| **Middleware** | {$middlewareStr} |\n";
            if ($details['renders_component']) {
                $content .= "| **Inertia Page Component** | `{$details['renders_component']}` |\n";
            }
            $content .= "\n";

            $content .= "## How it Works\n\n";
            $content .= "{$details['how_it_works']}\n\n";

            if (! empty($details['parameters'])) {
                $content .= "## Request Parameters\n\n";
                $content .= "| Parameter | Type | Required | Rules / Constraints |\n";
                $content .= "| --- | --- | --- | --- |\n";
                foreach ($details['parameters'] as $param) {
                    $content .= "| `{$param['name']}` | `{$param['type']}` | {$param['required']} | `{$param['rules']}` |\n";
                }
                $content .= "\n";
            }

            $content .= "## How to Use\n\n";
            $content .= "{$details['how_to_use']}\n";

            if (! empty($details['parameters']) && in_array($details['methods'], ['POST', 'PUT', 'PATCH'])) {
                $payload = [];
                foreach ($details['parameters'] as $param) {
                    if ($param['type'] === 'integer') {
                        $payload[$param['name']] = 1;
                    } elseif ($param['type'] === 'numeric') {
                        $payload[$param['name']] = 99.99;
                    } elseif ($param['type'] === 'boolean') {
                        $payload[$param['name']] = true;
                    } elseif ($param['type'] === 'array') {
                        $payload[$param['name']] = [];
                    } else {
                        $payload[$param['name']] = 'value';
                    }
                }
                $jsonPayload = json_encode($payload, JSON_PRETTY_PRINT);
                $content .= "\n### Example Request Body\n\n```json\n{$jsonPayload}\n```\n";
            }
        }

        return $content;
    }

    /**
     * Generate the central docs/README.md file.
     */
    protected function generateReadme(array $groupedRoutes): void
    {
        $readmePath = base_path('docs/README.md');
        $parentDir = dirname($readmePath);
        if (! is_dir($parentDir)) {
            mkdir($parentDir, 0755, true);
        }

        $content = "# Application Routing Directory & Reference\n\n";
        $content .= "This directory contains comprehensive, auto-generated documentation files for all routes registered in the application. Select a route from the categories below to view its purpose, backend implementation details, parameter validation rules, and usage examples.\n\n";

        $content .= "## Categories\n\n";
        foreach (array_keys($groupedRoutes) as $category) {
            $anchor = strtolower(str_replace(' & ', '-', $category));
            $anchor = str_replace(' ', '-', $anchor);
            $content .= "- [{$category}](#{$anchor})\n";
        }
        $content .= "\n---\n\n";

        foreach ($groupedRoutes as $category => $routes) {
            $content .= "## {$category}\n\n";
            $content .= "| Method | URI | Route Name | Description |\n";
            $content .= "| --- | --- | --- | --- |\n";

            usort($routes, fn ($a, $b) => strcmp($a['uri'], $b['uri']));
            foreach ($routes as $route) {
                $routeNameStr = $route['name'] && $route['name'] !== 'None' ? "`{$route['name']}`" : '*None*';
                $content .= "| `{$route['methods']}` | [`/{$route['uri']}`]({$route['file']}) | {$routeNameStr} | {$route['description']} |\n";
            }
            $content .= "\n";
        }

        file_put_contents($readmePath, $content);
    }
}
