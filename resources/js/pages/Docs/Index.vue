<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Search,
    BookOpen,
    FileText,
    Terminal,
    Lock,
    Clock,
    X,
    ChevronRight,
    Info,
    Sparkles,
    ShieldCheck,
    Wrench,
    ArrowLeft,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Documentation',
                href: '/docs',
            },
        ],
    },
});

interface Article {
    id: string;
    category: string;
    title: string;
    icon: any;
    summary: string;
    content: string;
    tags: string[];
    codeBlock?: {
        language: string;
        code: string;
    };
}

const articles: Article[] = [
    {
        id: 'getting-started',
        category: 'Getting Started',
        title: 'Introduction & Quick Start',
        icon: BookOpen,
        summary:
            'Learn how businesscalls schedules technician appointments and automates client voice dispatch.',
        content: `Welcome to **businesscalls**, the production-grade AI-driven voice receptionist and scheduling coordinator for trade contractors (HVAC, plumbing, electrical, and locksmiths). businesscalls bridges the gap between incoming client calls and real-time dispatcher scheduling.

### Step-by-Step Setup:
1. **Technician Setup**: Head to the **Employees** portal and add your technicians with their specific trade skills (e.g. plumbing, HVAC).
2. **Configure Availability**: Set active weekly work shifts (days and hours) for each technician.
3. **Telephony Integration**: Integrate your voice provider (Vapi or Retell) with your company's webhook URL to route incoming calls.
4. **Deploy & Monitor**: The AI receptionist answers calls, checks skills, confirms shift availabilities, enforces travel buffers, and logs confirmed bookings automatically.`,
        tags: ['quick start', 'introduction', 'setup', 'onboarding'],
    },
    {
        id: 'ai-receptionist',
        category: 'Core Features',
        title: 'AI Receptionist & Mascot Broadcasts',
        icon: Sparkles,
        summary:
            'Understanding the AI agent workflow, live speech processing, and the mascot statuses.',
        content: `The AI Voice Receptionist utilizes state-of-the-art LLMs, speech-to-text, and conversational AI to answer incoming customer calls. The system parses customer requirements, locates the appropriate technician, and books the appointment in real-time.

### Interactive Mascot States:
* **Idle**: The mascot monitors channels and remains ready to pick up incoming calls.
* **Scanning**: The agent is parsing customer request details, checking skill requirements, and validating technician schedules.
* **Confirmed (Victory)**: A qualified technician has been located, travel buffers verified, and the booking is logged.
* **Conflict (Error)**: The requested slot overlaps with an existing booking or falls outside of shift limits.

Broadcasts are sent via **Pusher Reverb** websocket channels to synchronize the dispatcher dashboard instantly without manual page refreshes.`,
        tags: [
            'receptionist',
            'mascot',
            'live call',
            'speech',
            'reverb',
            'websocket',
        ],
    },
    {
        id: 'travel-buffer',
        category: 'Scheduling Engine',
        title: '1.5-Hour Overlap Buffer Engine',
        icon: Clock,
        summary:
            'How businesscalls protects travel margins and prevents technician double-bookings.',
        content: `To ensure technicians have sufficient travel time between appointments, the scheduling engine automatically enforces a **1.5-hour buffer** between appointments.

### How the Buffer Works:
1. When a call comes in requesting a specific appointment slot, the system queries all active bookings for the selected technician on that day.
2. The engine verifies that:
   * **Start buffer**: The new booking starts at least 1.5 hours after any prior appointment ends.
   * **End buffer**: The new booking ends at least 1.5 hours before any subsequent appointment begins.
3. If the slot falls inside this 1.5-hour travel window, the scheduling engine blocks the request and prompts the AI receptionist to suggest alternate slots.`,
        tags: ['buffer', 'overlap', 'travel time', 'scheduling', 'conflict'],
        codeBlock: {
            language: 'php',
            code: `// Enforce travel buffer logic
$overlapExists = Booking::where('employee_id', $employeeId)
    ->where('status', 'booked')
    ->where(function ($query) use ($startTime, $endTime) {
        $query->whereBetween('scheduled_start', [
            $startTime->copy()->subMinutes(90),
            $endTime->copy()->addMinutes(90)
        ]);
    })->exists();`,
        },
    },
    {
        id: 'tenant-isolation',
        category: 'Security & Architecture',
        title: 'Multi-Tenant Database Isolation',
        icon: Lock,
        summary:
            'Secure database architecture isolating query scopes to ensure tenant data privacy.',
        content: `businesscalls is a multi-tenant platform, meaning that multiple contracting companies run on the same application server but their data remains strictly isolated.

### Global Query Scoping:
* **TenantScope**: Every query to model tables (employees, customers, bookings, etc.) automatically appends an active \`tenant_id\` condition.
* **Security Validation**: Cross-company access is validated at the Eloquent model lifecycle layer, making data leaks physically impossible.
* **Simulated Test Mode**: You can toggle "Test Mode" in your settings. This creates virtual employees (Alice and Bob) and mock bookings so you can safely test telephony flows and dashboard dispatchers without affecting live calendars.`,
        tags: [
            'multi-tenant',
            'isolation',
            'database',
            'tenantscope',
            'security',
            'test mode',
        ],
    },
    {
        id: 'hmac-security',
        category: 'Security & Architecture',
        title: 'HMAC Webhook Security',
        icon: ShieldCheck,
        summary:
            'Validate incoming telephony requests using HMAC-SHA256 headers.',
        content: `Your API endpoints are protected against spoofed telephony webhooks using **HMAC (Hash-based Message Authentication Code)** signature verification.

### Handshake Protocol:
1. Every telephony request payload is signed using your unique **Tenant Secret Key**.
2. When the webhook reaches businesscalls, the \`EnsureRegulatoryCompliance\` middleware recomputes the HMAC-SHA256 hash using the raw request body.
3. If the recomputed hash matches the header signature, the request is processed; otherwise, it is blocked with an HTTP 403 Forbidden error. This secures technician schedules from malicious external API triggers.`,
        tags: [
            'hmac',
            'webhook',
            'security',
            'signature',
            'sha256',
            'middleware',
        ],
        codeBlock: {
            language: 'php',
            code: `// HMAC signature validation middleware
$secret = $tenant->webhook_secret;
$computed = hash_hmac('sha256', $request->getContent(), $secret);

if (!hash_equals($computed, $request->header('X-Webhook-Signature'))) {
    abort(403, 'Invalid Webhook Signature');
}`,
        },
    },
    {
        id: 'crm-sync',
        category: 'Core Features',
        title: 'Technician Portals & CQS Analytics',
        icon: Wrench,
        summary:
            'Manage dispatcher operations, client lists, and CQS (Call Quality Score) metrics.',
        content: `The dashboard aggregates real-time metrics so you can optimize receptionist dispatch efficiency and client experiences.

### Key Metrics:
* **Call Quality Score (CQS)**: Evaluates conversational accuracy, customer sentiment, and scheduling success rate.
* **Booking Streak**: Tracks consecutive days with successful scheduling bookings.
* **Customer List**: Imports and syncs customer phone logs.
* **Technician Dashboard**: Specialized portals let technicians view their work shifts, scheduled routes, and service job specifics.`,
        tags: [
            'analytics',
            'cqs',
            'technician portal',
            'crm',
            'booking statistics',
        ],
    },
];

const searchQuery = ref('');
const activeArticleId = ref('getting-started');

// Group articles by category for standard navigation tree
const categories = computed(() => {
    const map: Record<string, Article[]> = {};
    articles.forEach((art) => {
        if (!map[art.category]) {
            map[art.category] = [];
        }
        map[art.category].push(art);
    });
    return map;
});

// Search matches filtering query
const filteredArticles = computed(() => {
    if (!searchQuery.value.trim()) {
        return articles;
    }
    const q = searchQuery.value.toLowerCase().trim();
    return articles.filter((art) => {
        return (
            art.title.toLowerCase().includes(q) ||
            art.summary.toLowerCase().includes(q) ||
            art.content.toLowerCase().includes(q) ||
            art.tags.some((t) => t.toLowerCase().includes(q)) ||
            art.category.toLowerCase().includes(q)
        );
    });
});

const currentArticle = computed(() => {
    return (
        articles.find((art) => art.id === activeArticleId.value) || articles[0]
    );
});

const selectArticle = (id: string) => {
    activeArticleId.value = id;
};

// Text highlighting filter for matches
const highlightText = (text: string, query: string) => {
    if (!query.trim()) return text;
    const escapedQuery = query.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
    const regex = new RegExp(`(${escapedQuery})`, 'gi');
    return text.replace(
        regex,
        '<mark class="bg-amber-100 text-amber-900 rounded-sm px-0.5 dark:bg-amber-950/80 dark:text-amber-200">$1</mark>',
    );
};
</script>

<template>
    <Head title="Documentation - businesscalls" />

    <div class="mx-auto flex max-w-7xl flex-col gap-6 p-4 sm:p-6 md:p-8">
        <!-- Documentation Header Page Title and Search -->
        <div
            class="flex flex-col gap-4 border-b pb-6 md:flex-row md:items-center md:justify-between"
        >
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">
                    Documentation
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Everything you need to know about setting up and using
                    businesscalls.
                </p>
            </div>

            <!-- Real-Time Search Box -->
            <div class="relative w-full md:w-80">
                <Search
                    class="absolute top-2.5 left-3 h-4.5 w-4.5 text-muted-foreground"
                />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search guides (e.g. HMAC, buffer)..."
                    class="h-10 w-full rounded-md border border-input bg-background pr-8 pl-9 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-hidden disabled:cursor-not-allowed disabled:opacity-50"
                />
                <button
                    v-if="searchQuery"
                    @click="searchQuery = ''"
                    class="absolute top-2.5 right-3 text-muted-foreground hover:text-foreground"
                >
                    <X class="h-4.5 w-4.5" />
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 items-start gap-8 md:grid-cols-12">
            <!-- Sidebar Navigation -->
            <div class="space-y-6 md:col-span-4 lg:col-span-3">
                <!-- If Search is Active -->
                <div v-if="searchQuery.trim()" class="space-y-2">
                    <h3
                        class="px-3 text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Search Results ({{ filteredArticles.length }})
                    </h3>
                    <div class="space-y-1">
                        <button
                            v-for="art in filteredArticles"
                            :key="art.id"
                            @click="selectArticle(art.id)"
                            class="flex w-full cursor-pointer items-center justify-between rounded-lg px-3 py-2 text-left text-sm transition-colors"
                            :class="[
                                activeArticleId === art.id
                                    ? 'bg-primary/5 font-semibold text-primary'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                            ]"
                        >
                            <span class="truncate">{{ art.title }}</span>
                            <ChevronRight class="h-3.5 w-3.5 shrink-0" />
                        </button>
                    </div>

                    <div
                        v-if="filteredArticles.length === 0"
                        class="space-y-2 rounded-xl border border-dashed bg-muted/20 p-4 text-center text-xs text-muted-foreground"
                    >
                        <Info class="mx-auto h-6 w-6 text-muted-foreground" />
                        <p>No guides match your search.</p>
                        <button
                            @click="searchQuery = ''"
                            class="font-semibold text-primary hover:underline"
                        >
                            Reset Search
                        </button>
                    </div>
                </div>

                <!-- Default Category tree view -->
                <div
                    v-else
                    v-for="(arts, catName) in categories"
                    :key="catName"
                    class="space-y-2"
                >
                    <h3
                        class="px-3 text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        {{ catName }}
                    </h3>
                    <div class="space-y-1">
                        <button
                            v-for="art in arts"
                            :key="art.id"
                            @click="selectArticle(art.id)"
                            class="flex w-full cursor-pointer items-center justify-between rounded-lg px-3 py-2 text-left text-sm transition-colors"
                            :class="[
                                activeArticleId === art.id
                                    ? 'rounded-l-none border-l-2 border-primary bg-primary/5 font-semibold text-primary'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                            ]"
                        >
                            <span class="truncate">{{ art.title }}</span>
                            <ChevronRight class="h-3.5 w-3.5 shrink-0" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Content Panel -->
            <div
                class="rounded-2xl border bg-card p-6 shadow-xs md:col-span-8 md:p-8 lg:col-span-9"
            >
                <!-- Info Header Badge and Summary -->
                <div class="space-y-4 border-b pb-6">
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-indigo-500/20 bg-indigo-500/5 px-2.5 py-0.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400"
                        >
                            <component
                                :is="currentArticle.icon"
                                class="h-3.5 w-3.5"
                            />
                            {{ currentArticle.category }}
                        </span>
                    </div>

                    <h2
                        class="text-2xl font-extrabold tracking-tight sm:text-3xl"
                    >
                        {{ currentArticle.title }}
                    </h2>

                    <p
                        class="text-sm leading-relaxed text-muted-foreground sm:text-base"
                    >
                        {{ currentArticle.summary }}
                    </p>
                </div>

                <!-- Markdown Content body -->
                <div
                    class="prose prose-slate dark:prose-invert mt-6 max-w-none"
                >
                    <!-- Renders markdown paragraphs -->
                    <div class="space-y-6">
                        <!-- Custom parser for hardcoded markdown formatting: headers, bold, bullet points -->
                        <div
                            v-for="(para, pIdx) in currentArticle.content.split(
                                '\n\n',
                            )"
                            :key="pIdx"
                            class="text-sm leading-relaxed text-foreground/90 sm:text-base"
                        >
                            <!-- Headers ### -->
                            <h3
                                v-if="para.startsWith('### ')"
                                class="mt-6 mb-3 flex items-center gap-2 text-lg font-bold text-foreground"
                                v-html="
                                    highlightText(
                                        para.replace('### ', ''),
                                        searchQuery,
                                    )
                                "
                            ></h3>

                            <!-- Bullet points * -->
                            <ul
                                v-else-if="
                                    para.startsWith('* ') ||
                                    para.startsWith('- ')
                                "
                                class="list-disc space-y-2.5 pl-6"
                            >
                                <li
                                    v-for="(bullet, bIdx) in para.split('\n')"
                                    :key="bIdx"
                                    class="leading-relaxed"
                                    v-html="
                                        highlightText(
                                            bullet.replace(/^[*-\s]+/, ''),
                                            searchQuery,
                                        )
                                    "
                                ></li>
                            </ul>

                            <!-- Numbered points 1. -->
                            <ol
                                v-else-if="para.match(/^\d+\./m)"
                                class="list-decimal space-y-2.5 pl-6"
                            >
                                <li
                                    v-for="(numPoint, nIdx) in para.split('\n')"
                                    :key="nIdx"
                                    class="leading-relaxed"
                                    v-html="
                                        highlightText(
                                            numPoint.replace(/^\d+\.\s+/, ''),
                                            searchQuery,
                                        )
                                    "
                                ></li>
                            </ol>

                            <!-- Paragraph text -->
                            <p
                                v-else
                                v-html="highlightText(para, searchQuery)"
                                class="whitespace-pre-line"
                            ></p>
                        </div>
                    </div>
                </div>

                <!-- Technical Code Block Section -->
                <div v-if="currentArticle.codeBlock" class="mt-8 space-y-3">
                    <div
                        class="flex items-center gap-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        <Terminal class="h-4 w-4" />
                        <span
                            >Example Code Implementation ({{
                                currentArticle.codeBlock.language
                            }})</span
                        >
                    </div>
                    <div
                        class="relative overflow-hidden rounded-xl border bg-slate-950 p-4 font-mono text-xs leading-normal text-slate-100 shadow-inner sm:text-sm"
                    >
                        <pre
                            class="overflow-x-auto whitespace-pre"
                        ><code>{{ currentArticle.codeBlock.code }}</code></pre>
                    </div>
                </div>

                <!-- Technical Note box -->
                <div
                    class="mt-8 flex gap-4 rounded-xl border border-border/80 bg-muted/40 p-4"
                >
                    <Info class="mt-0.5 h-5 w-5 shrink-0 text-primary" />
                    <div
                        class="text-xs leading-relaxed text-muted-foreground sm:text-sm"
                    >
                        <span class="font-bold text-foreground"
                            >Technical Reference:</span
                        >
                        This guide represents the canonical implementation
                        configured for our Laravel and Inertia v3 application
                        layer. For programmatic adjustments, inspect the
                        relevant controller and middleware hooks.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Smooth custom styles for documentation animations */
.bg-card {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
