export interface IndustryFaq {
    question: string;
    answer: string;
}

export interface IndustryData {
    slug: string;
    name: string;
    tradeName: string;
    heroTitle: string;
    heroSubtitle: string;
    metaTitle: string;
    metaDescription: string;
    keywords: string[];
    initialScenarioId: string;
    badgeText: string;
    gradient: string;
    icon: string;
    stats: {
        value: string;
        label: string;
    }[];
    painPoints: {
        title: string;
        description: string;
    }[];
    keyFeatures: {
        title: string;
        description: string;
    }[];
    faqs: IndustryFaq[];
}

export const industriesData: Record<string, IndustryData> = {
    'plumbing-answering-service': {
        slug: 'plumbing-answering-service',
        name: 'Plumbing & Drain Services',
        tradeName: 'Plumbers & Drain Techs',
        heroTitle: '24/7 AI Voice Receptionist & Emergency Dispatch for Plumbers',
        heroSubtitle:
            'Never miss a $1,500 burst pipe or water heater emergency call again. Our voice AI answers instantly, triages main line clogs, and books jobs directly to your schedule with travel buffer protection.',
        metaTitle: 'AI Answering Service & Dispatch for Plumbers | JustMascot',
        metaDescription:
            '24/7 AI voice phone receptionist for plumbing contractors. Qualify burst pipes, water heaters, and drain clogs with automated dispatch and zero hold time.',
        keywords: [
            'plumbing answering service',
            'AI receptionist for plumbers',
            'emergency plumbing dispatch software',
            'after hours plumbing call center',
            'automated plumber booking',
        ],
        initialScenarioId: 'emergency',
        badgeText: 'Built For Licensed Plumbing Contractors',
        gradient: 'from-blue-600 to-cyan-500',
        icon: 'Droplets',
        stats: [
            { value: '< 1s', label: 'Response Pickup Time' },
            { value: '100%', label: 'After-Hours Emergency Capture' },
            { value: '$0.15', label: 'Effective Cost Per Call' },
            { value: '1.5h', label: 'Auto Travel Buffer Enforced' },
        ],
        painPoints: [
            {
                title: 'Lost Revenue from Missed Burst Pipes',
                description:
                    'When homeowners have water pouring through ceilings at 2 AM, they call down Google listings until someone answers. If you miss the call, a competitor gets the $2,000 job.',
            },
            {
                title: 'Burnout from Night-Shift On-Call Rotations',
                description:
                    'Your master plumbers hate waking up for non-urgent dripping faucets. JustMascot AI triages true emergencies from routine maintenance inquiries automatically.',
            },
            {
                title: 'Clustered Bookings Without Travel Time',
                description:
                    'Human call centers double-book plumbers across opposite sides of town. Our AI calculates route drive times and buffers 90 minutes between job dispatches.',
            },
        ],
        keyFeatures: [
            {
                title: 'Emergency Shutoff Guidance',
                description:
                    'Voice AI calmly instructs callers on isolating their main water valve while simultaneously dispatching your on-call technician.',
            },
            {
                title: 'EPA & Master License Skill Routing',
                description:
                    'Matches gas line replacements, backflow certifications, and sewer camera scopes strictly to qualified technicians.',
            },
            {
                title: 'Instant SMS & Calendar Sync',
                description:
                    'Sends address, gate codes, customer recordings, and diagnostic notes straight to technician mobile apps.',
            },
        ],
        faqs: [
            {
                question: 'Can the AI diagnose water heater vs main line issues?',
                answer: 'Yes. The voice agent understands homeowner terminology, asks follow-up qualification questions (tankless vs tank, gas vs electric), and categorizes job urgency.',
            },
            {
                question: 'What happens during a severe winter freeze or high call surge?',
                answer: 'JustMascot handles unlimited concurrent incoming phone lines simultaneously with zero busy signals or hold queues.',
            },
            {
                question: 'Can we forward our existing business phone number?',
                answer: 'Yes. You simply enable conditional or 24/7 call forwarding from your current VoIP or carrier line (Verizon, AT&T, Vonage, RingCentral).',
            },
        ],
    },
    'hvac-ai-receptionist': {
        slug: 'hvac-ai-receptionist',
        name: 'HVAC & Climate Control',
        tradeName: 'HVAC Contractors & Technicians',
        heroTitle: '24/7 AI Receptionist & Smart Shift Dispatch for HVAC Companies',
        heroSubtitle:
            'Capture every peak summer AC breakdown and winter furnace outage. Our voice AI verifies EPA 608 certifications, quotes standard diagnostic fees, and locks in appointments in real time.',
        metaTitle: 'AI Answering Service & Dispatch for HVAC Contractors | JustMascot',
        metaDescription:
            'Autonomous voice receptionist for HVAC businesses. Qualify AC repairs, compressor diagnostic fees, and heat pump failures with sub-second AI voice response.',
        keywords: [
            'HVAC answering service',
            'AI voice receptionist for HVAC',
            'after hours HVAC call center',
            'AC repair dispatch software',
            'contractor answering service',
        ],
        initialScenarioId: 'hvac',
        badgeText: 'Optimized For Heating & Air Companies',
        gradient: 'from-sky-500 to-indigo-600',
        icon: 'Wind',
        stats: [
            { value: '0 sec', label: 'Hold Time During Heatwaves' },
            { value: '94%', label: 'First-Call Diagnostic Booking Rate' },
            { value: 'EPA 608', label: 'Automated Tech Skill Matching' },
            { value: '24/7/365', label: 'Uninterrupted Peak Coverage' },
        ],
        painPoints: [
            {
                title: 'Peak Heatwave Call Abandonment',
                description:
                    'When temperatures hit 100°F, call volume spikes 500%. Traditional receptionists drop calls, leaving frantic customers to hire the next HVAC contractor.',
            },
            {
                title: 'Wrong Technician Dispatched for Specialty Units',
                description:
                    'Sending a junior installer to a complex VRF or commercial heat pump diagnostic wastes valuable truck rolls and billable hours.',
            },
            {
                title: 'Uncollected Diagnostic Fees',
                description:
                    'AI communicates standard trip/diagnostic fees upfront, setting clear expectations before your technician rolls out.',
            },
        ],
        keyFeatures: [
            {
                title: 'Automated Diagnostic Fee Authorization',
                description:
                    'Explains dispatch pricing, collects customer agreement, and eliminates billing pushback on arrival.',
            },
            {
                title: 'Refrigerant & System Triage',
                description:
                    'Gathers equipment make, tonnage, error codes, and age before scheduling to prepare your technician with the right parts.',
            },
            {
                title: 'Smart On-Call Rotation Management',
                description:
                    'Respects technician shift limits and prevents dispatching exhausted crew members beyond maximum allowable hours.',
            },
        ],
        faqs: [
            {
                question: 'How does the AI handle commercial vs residential HVAC calls?',
                answer: 'The system identifies commercial rooftop units (RTUs) vs residential split systems and routes calls to designated commercial technicians.',
            },
            {
                question: 'Can the AI collect deposit or diagnostic authorizations?',
                answer: 'Yes, it confirms customer payment terms and can send instant payment authorization links via SMS before dispatch.',
            },
            {
                question: 'Does it integrate with existing scheduling workflows?',
                answer: 'Yes, JustMascot syncs with your technician schedules, respects travel buffers, and logs call recordings and summaries.',
            },
        ],
    },
    'electrical-contractor-dispatch': {
        slug: 'electrical-contractor-dispatch',
        name: 'Electrical & Power Systems',
        tradeName: 'Electrical Contractors',
        heroTitle: 'AI Phone Dispatch & Urgent Safety Triage for Electricians',
        heroSubtitle:
            'Isolate sparking panels, commercial power loss, and EV charger installs. Voice AI qualifies voltage requirements, matches Master Electrician certifications, and books high-ticket jobs 24/7.',
        metaTitle: 'AI Answering Service for Electricians | JustMascot',
        metaDescription:
            'AI voice dispatch and receptionist for electrical contractors. Triage breaker trips, panel upgrades, and commercial electrical emergencies with zero missed calls.',
        keywords: [
            'electrical contractor answering service',
            'AI receptionist for electricians',
            'electrician phone dispatch system',
            'after hours electrical emergency answering',
            'commercial electrical booking',
        ],
        initialScenarioId: 'electrical',
        badgeText: 'Engineered For Master & Journeyman Electricians',
        gradient: 'from-amber-500 to-yellow-500',
        icon: 'Zap',
        stats: [
            { value: '100%', label: 'Urgent Safety Hazard Flagging' },
            { value: '200A+', label: 'Panel Triage Qualification' },
            { value: '$450+', label: 'Average Ticket Value Protected' },
            { value: 'Instant', label: 'Technician SMS Alert' },
        ],
        painPoints: [
            {
                title: 'Dangerous Fire & Sparking Hazards Mishandled',
                description:
                    'Generic call centers do not understand electrical fire hazards. Our AI prioritizes urgent safety guidance immediately.',
            },
            {
                title: 'High-Value Panel Upgrade Leads Slipping Away',
                description:
                    'EV charger installations and 200A main panel upgrade inquiries often call during evening hours and choose whoever replies first.',
            },
            {
                title: 'Journeyman vs Master Electrician Misassignment',
                description:
                    'Ensure permitted commercial work and service changes are only scheduled with properly licensed team members.',
            },
        ],
        keyFeatures: [
            {
                title: 'Safety Protocol Instruction',
                description:
                    'Advises callers to avoid touching sparked receptacles and shut off subpanels when safe while dispatching emergency crew.',
            },
            {
                title: 'Residential vs Commercial Triage',
                description:
                    'Distinguishes between 3-phase industrial power faults and residential breaker resets seamlessly.',
            },
            {
                title: 'Route Distance & Drive Time Guard',
                description:
                    'Enforces 1.5-hour travel buffers between job sites to ensure electrical crews arrive on time with complete truck kits.',
            },
        ],
        faqs: [
            {
                question: 'Can the AI distinguish low-voltage vs high-voltage jobs?',
                answer: 'Yes, it tags low-voltage smart home/security wiring separately from high-voltage 240V/480V panel work.',
            },
            {
                question: 'Can we customize the emergency dispatch criteria?',
                answer: 'Absolutely. You define which conditions trigger immediate phone escalations vs regular next-day calendar slots.',
            },
            {
                question: 'What sounds do callers hear when they call?',
                answer: 'Callers hear an ultra-natural, friendly voice agent with zero mechanical robotic pauses and sub-second voice synthesis.',
            },
        ],
    },
    'roofing-emergency-call-handling': {
        slug: 'roofing-emergency-call-handling',
        name: 'Roofing & Storm Protection',
        tradeName: 'Roofing Contractors',
        heroTitle: '24/7 AI Call Handling & Storm Surge Triage for Roofers',
        heroSubtitle:
            'When severe storms strike, capture hundreds of urgent roof tarping, leak repair, and insurance claim inspection calls without putting a single homeowner on hold.',
        metaTitle: 'AI Answering Service for Roofing Contractors | JustMascot',
        metaDescription:
            'Storm surge AI voice dispatcher for roofing companies. Handle 100+ simultaneous storm damage and emergency tarping calls with instant calendar booking.',
        keywords: [
            'roofing answering service',
            'AI voice receptionist for roofers',
            'storm damage call handling',
            'emergency roof tarping dispatch',
            'roofing contractor lead capture',
        ],
        initialScenarioId: 'emergency',
        badgeText: 'Built For Storm Restoration & Roofers',
        gradient: 'from-emerald-500 to-teal-600',
        icon: 'Home',
        stats: [
            { value: '500+', label: 'Concurrent Storm Calls Handled' },
            { value: '$8,500', label: 'Average Replacement Job Value' },
            { value: '0 Min', label: 'Caller Wait Time' },
            { value: 'Instant', label: 'Insurance Scope Notes Generated' },
        ],
        painPoints: [
            {
                title: 'Storm Surge Call Flooding',
                description:
                    'Hailstorms and hurricanes generate 200+ calls in 3 hours. Standard front desks collapse, leaving thousands in reroofing revenue on the table.',
            },
            {
                title: 'Missing Insurance Claim Windows',
                description:
                    'Homeowners file insurance claims quickly with the first contractor who schedules an on-site adjuster inspection.',
            },
            {
                title: 'Wasted Fuel on Out-of-Territory Leads',
                description:
                    'AI checks zip codes against your precise service radius before confirming appointments.',
            },
        ],
        keyFeatures: [
            {
                title: 'High-Volume Storm Concurrency',
                description:
                    'Answer hundreds of callers simultaneously during severe weather events with zero busy tones.',
            },
            {
                title: 'Insurance Claim & Tarping Triage',
                description:
                    'Categorizes active interior water leaks for immediate emergency tarping vs post-storm shingle inspections.',
            },
            {
                title: 'Zip-Code & Radius Filtering',
                description:
                    'Ensures only qualified homeowners within your licensed county or territory are booked.',
            },
        ],
        faqs: [
            {
                question: 'Can the AI schedule drone and estimator inspections?',
                answer: 'Yes, it schedules estimator appointments based on roof square footage and inspection type.',
            },
            {
                question: 'How quickly does it activate during storm warnings?',
                answer: 'It is always active 24/7/365, instantly scaling up capacity the second storm-related call surges begin.',
            },
            {
                question: 'Does the voice sound robotic to upset homeowners?',
                answer: 'No, it uses high-empathy, ultra-realistic voice models designed specifically to calm distressed homeowners.',
            },
        ],
    },
    'appliance-repair-scheduling': {
        slug: 'appliance-repair-scheduling',
        name: 'Appliance Repair Services',
        tradeName: 'Appliance Repair Specialists',
        heroTitle: 'Automated AI Voice Receptionist for Appliance Repair Pros',
        heroSubtitle:
            'Book refrigerator, washer, dryer, and oven repairs while you are in the field. Our voice AI captures model numbers, error codes, and authorizes diagnostic call-out fees automatically.',
        metaTitle: 'AI Answering Service for Appliance Repair | JustMascot',
        metaDescription:
            '24/7 AI receptionist for appliance repair businesses. Collect brand/model information, quote trip fees, and schedule repairs without answering the phone.',
        keywords: [
            'appliance repair answering service',
            'AI receptionist for appliance repair',
            'automated appliance scheduling software',
            'appliance technician call dispatch',
            'refrigerator repair answering service',
        ],
        initialScenarioId: 'hvac',
        badgeText: 'Tailored For Appliance Technicians',
        gradient: 'from-purple-500 to-violet-600',
        icon: 'WashingMachine',
        stats: [
            { value: '100%', label: 'Brand & Model Info Collected' },
            { value: '$120+', label: 'Trip Fee Terms Pre-Authorized' },
            { value: '3x', label: 'More Bookings While on Jobs' },
            { value: '0', label: 'Spam & Robocalls Passed Through' },
        ],
        painPoints: [
            {
                title: 'Hands Stuck in a Refrigerator While Phone Rings',
                description:
                    'You cannot answer customer calls while diagnosing sealed refrigeration systems. Every unanswered call is a lost $250 repair job.',
            },
            {
                title: 'Arriving Without Part Model Numbers',
                description:
                    'Without knowing whether the unit is Sub-Zero, Bosch, or Samsung, technicians waste trips returning to parts suppliers.',
            },
            {
                title: 'Customers Shocked by Service Call Fees',
                description:
                    'AI clearly explains trip and diagnostic fee structures so customers agree before scheduling.',
            },
        ],
        keyFeatures: [
            {
                title: 'Model & Symptom Intake',
                description:
                    'Prompts callers for appliance brand, approximate age, and specific symptoms (not cooling, error code E15, loud spin cycle).',
            },
            {
                title: 'Brand Certification Routing',
                description:
                    'Routes high-end luxury appliances (Sub-Zero, Wolf, Viking) only to certified factory-trained technicians.',
            },
            {
                title: 'Calendar & Travel Buffer Scheduling',
                description:
                    'Clusters daily jobs geographically to minimize drive time between diagnostic calls.',
            },
        ],
        faqs: [
            {
                question: 'Can the AI collect customer appliance brand details?',
                answer: 'Yes, it actively asks for the appliance brand and type (e.g. French-door refrigerator, front-load washer) and notes it in the booking.',
            },
            {
                question: 'Can we set custom diagnostic fees?',
                answer: 'Yes, you can configure standard residential and luxury brand diagnostic fees that the AI articulates during the call.',
            },
            {
                question: 'Can it send technician arrival updates to customers?',
                answer: 'Yes, customers receive automated tracking SMS with real-time technician status.',
            },
        ],
    },
    'pest-control-answering-service': {
        slug: 'pest-control-answering-service',
        name: 'Pest Control & Extermination',
        tradeName: 'Pest Control Operators',
        heroTitle: '24/7 AI Phone Booking & Triage for Pest Control Companies',
        heroSubtitle:
            'Book termite inspections, emergency rodent exclusions, and recurring perimeter spray plans on autopilot. Voice AI qualifies pest types and locks in routes effortlessly.',
        metaTitle: 'AI Answering Service for Pest Control | JustMascot',
        metaDescription:
            'Automated AI voice receptionist for pest control businesses. Triage termites, bed bugs, and rodent emergencies with instant scheduling.',
        keywords: [
            'pest control answering service',
            'AI voice receptionist for exterminators',
            'pest control booking software',
            'termite inspection scheduling AI',
            'after hours exterminator phone service',
        ],
        initialScenarioId: 'emergency',
        badgeText: 'Designed For Licensed Exterminators',
        gradient: 'from-rose-500 to-pink-600',
        icon: 'Bug',
        stats: [
            { value: '24/7', label: 'Emergency Rodent & Wasp Booking' },
            { value: '+40%', label: 'Recurring Plan Up-Sells' },
            { value: '< 1s', label: 'Live Phone Pick-up' },
            { value: '100%', label: 'Lead Contact Details Verified' },
        ],
        painPoints: [
            {
                title: 'Frantic Homeowners Demand Instant Reassurance',
                description:
                    'Homeowners seeing bed bugs or rodents will call down the list until they speak to someone who schedules an immediate inspection.',
            },
            {
                title: 'Wasted Time on Out-of-Scope Wildlife',
                description:
                    'If you do not handle raccoons or venomous snakes, the AI politely clarifies service boundaries and refers callers elsewhere.',
            },
            {
                title: 'Missed Recurring Subscription Opportunities',
                description:
                    'AI introduces seasonal perimeter maintenance plans during the initial inquiry to maximize customer lifetime value.',
            },
        ],
        keyFeatures: [
            {
                title: 'Pest Species Triage',
                description:
                    'Identifies termites, carpenter ants, German roaches, rodents, and wasps to assign the right chemical rigs.',
            },
            {
                title: 'Square Footage & Property Qualification',
                description:
                    'Captures lot size, foundation type (slab vs crawlspace), and pet safety considerations.',
            },
            {
                title: 'Route Density Optimization',
                description:
                    'Groups neighboring pest control service stops on dedicated zone days.',
            },
        ],
        faqs: [
            {
                question: 'Can the AI ask if pets or children are present on property?',
                answer: 'Yes, it gathers environmental safety details to prepare technician application methods.',
            },
            {
                question: 'Does it support recurring quarterly plan sign-ups?',
                answer: 'Yes, it can explain one-time eradication vs quarterly barrier protection options.',
            },
            {
                question: 'Can we listen to call recordings?',
                answer: 'Yes, every call includes full audio playback, transcripts, and AI-generated sentiment analysis.',
            },
        ],
    },
    'garage-door-emergency-dispatch': {
        slug: 'garage-door-emergency-dispatch',
        name: 'Garage Doors & Overhead Gates',
        tradeName: 'Garage Door Contractors',
        heroTitle: '24/7 AI Voice Dispatch for Garage Door Repair Contractors',
        heroSubtitle:
            'Capture every trapped car emergency and snapped torsion spring call. Voice AI qualifies door dimensions, spring types, and dispatches on-call technicians immediately.',
        metaTitle: 'AI Answering Service for Garage Door Contractors | JustMascot',
        metaDescription:
            '24/7 emergency voice AI receptionist for garage door repair businesses. Fast triage for broken springs, off-track doors, and stuck openers.',
        keywords: [
            'garage door answering service',
            'AI voice receptionist for garage door repair',
            'garage door emergency dispatch',
            'overhead door phone answering',
            'contractor answering service',
        ],
        initialScenarioId: 'emergency',
        badgeText: 'Built For Overhead Door & Gate Pros',
        gradient: 'from-orange-500 to-amber-600',
        icon: 'ShieldCheck',
        stats: [
            { value: '< 1s', label: 'Call Answer Speed' },
            { value: '$380', label: 'Average Spring Ticket Saved' },
            { value: '24/7', label: 'Trapped Vehicle Emergency Priority' },
            { value: '0', label: 'Missed Weekend Jobs' },
        ],
        painPoints: [
            {
                title: 'Trapped Cars Require Immediate Emergency Response',
                description:
                    'When a spring snaps at 6:30 AM before work, callers book the very first company that answers their call.',
            },
            {
                title: 'High Weekend & Evening Emergency Volume',
                description:
                    'Overhead doors break at random times. Missing evening calls means losing profitable emergency fee premiums.',
            },
            {
                title: 'Commercial Roll-up vs Residential Confusion',
                description:
                    'AI distinguishes standard residential sectional doors from commercial high-lift roll-up doors before dispatch.',
            },
        ],
        keyFeatures: [
            {
                title: 'Emergency Urgency Tagging',
                description:
                    'Flags trapped vehicles inside garages as top-priority dispatches to your on-call mobile technician.',
            },
            {
                title: 'Safety Warning Protocol',
                description:
                    'Warns callers not to pull emergency release cords on snapped cables to prevent heavy door collapse injuries.',
            },
            {
                title: 'Standard Spring & Opener Quoting',
                description:
                    'Quotes diagnostic and baseline single vs double spring replacement ranges according to your pricing table.',
            },
        ],
        faqs: [
            {
                question: 'Can the AI explain single vs double spring replacements?',
                answer: 'Yes, it informs customers why replacing both springs simultaneously is recommended for balanced tension.',
            },
            {
                question: 'Can technicians receive instant SMS dispatches on the road?',
                answer: 'Yes, SMS alerts with address, customer phone number, and door issue are sent instantly.',
            },
            {
                question: 'Does it handle gate automation and commercial doors?',
                answer: 'Yes, it routes gate access and commercial roll-up doors to designated commercial technicians.',
            },
        ],
    },
    'locksmith-call-answering': {
        slug: 'locksmith-call-answering',
        name: 'Locksmith & Access Security',
        tradeName: 'Emergency Locksmiths',
        heroTitle: '24/7 AI Voice Answering & Rapid Dispatch for Locksmiths',
        heroSubtitle:
            'Never lose a locked-out customer to competitor dispatchers. Voice AI gathers address coordinates, vehicle/door lock specs, and dispatches the nearest mobile technician in seconds.',
        metaTitle: 'AI Answering Service for Locksmiths | JustMascot',
        metaDescription:
            '24/7 voice AI receptionist for locksmith companies. Instant qualification for automotive lockouts, rekeying, and commercial access hardware.',
        keywords: [
            'locksmith answering service',
            'AI voice receptionist for locksmiths',
            'emergency lockout dispatch software',
            '24/7 locksmith call center',
            'automotive locksmith answering',
        ],
        initialScenarioId: 'emergency',
        badgeText: 'Optimized For Mobile Locksmith Pros',
        gradient: 'from-cyan-500 to-blue-600',
        icon: 'Lock',
        stats: [
            { value: '100%', label: 'Automotive & Residential Triage' },
            { value: '< 1s', label: 'Immediate Call Connection' },
            { value: '$150+', label: 'Lockout Dispatch Secured' },
            { value: 'GPS', label: 'Location & Cross-Street Intake' },
        ],
        painPoints: [
            {
                title: 'Lockouts Choose the First Person to Answer',
                description:
                    'A stranded customer standing outside in the rain will not leave a voicemail. They dial the next listing within 15 seconds.',
            },
            {
                title: 'Scam Impressions & Trust Issues',
                description:
                    'AI delivers a polished, professional, branded greeting with transparent pricing that builds instant customer trust.',
            },
            {
                title: 'Incomplete Automotive Key Details',
                description:
                    'AI collects vehicle year, make, model, and push-to-start smart key specs so technicians bring the correct programmer.',
            },
        ],
        keyFeatures: [
            {
                title: 'Precise Location & Cross-Street Capture',
                description:
                    'Captures exact parking lot or residential location details for accurate mobile navigation.',
            },
            {
                title: 'Key Programming vs Rekey Triage',
                description:
                    'Categorizes transponder keys, high-security deadbolts, and master key system requests accurately.',
            },
            {
                title: 'ID Verification Notice',
                description:
                    'Reminds callers that valid government ID and proof of ownership are required on technician arrival.',
            },
        ],
        faqs: [
            {
                question: 'Can the AI collect vehicle year, make, and model?',
                answer: 'Yes, it collects full vehicle info to ensure the dispatched tech has the appropriate laser cut or EEPROM programmer.',
            },
            {
                question: 'How are after-hours emergency rates communicated?',
                answer: 'The AI clearly presents emergency trip charges and gets caller confirmation before dispatching.',
            },
            {
                question: 'Can the caller speak with a live supervisor if needed?',
                answer: 'Yes, the AI can seamlessly bridge high-priority calls to an escalation phone number.',
            },
        ],
    },
};
