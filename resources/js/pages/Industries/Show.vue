<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    Droplets,
    Wind,
    Zap,
    Home,
    WashingMachine,
    Bug,
    ShieldCheck,
    Lock,
    PhoneCall,
    CheckCircle2,
    ArrowRight,
    Sparkles,
    ChevronDown,
    Clock,
    UserCheck,
    AlertCircle,
    Star,
} from '@lucide/vue';
import PublicHeader from '@/components/PublicHeader.vue';
import SeoHead from '@/components/SeoHead.vue';
import PublicSandboxLeadMagnet from '@/components/PublicSandboxLeadMagnet.vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';
import { Badge } from '@/components/ui/badge';
import { type IndustryData, industriesData } from '@/data/industries';
import { register, home } from '@/routes';

const props = defineProps<{
    industry: IndustryData;
    slug: string;
}>();

const openFaqIndex = ref<number | null>(0);

const toggleFaq = (index: number) => {
    openFaqIndex.value = openFaqIndex.value === index ? null : index;
};

// Map string icon names to Lucide components
const iconMap: Record<string, any> = {
    Droplets,
    Wind,
    Zap,
    Home,
    WashingMachine,
    Bug,
    ShieldCheck,
    Lock,
};

const currentIcon = computed(() => iconMap[props.industry.icon] || Sparkles);

// List of all other industries for internal cross-linking
const otherIndustries = computed(() =>
    Object.values(industriesData).filter((ind) => ind.slug !== props.slug),
);

// Comprehensive Schema.org JSON-LD Structured Data
const jsonLdData = computed(() => ({
    '@context': 'https://schema.org',
    '@graph': [
        {
            '@type': 'Service',
            'name': props.industry.heroTitle,
            'provider': {
                '@type': 'Organization',
                'name': 'JustMascot AI Voice Receptionist',
                'url': 'https://justmascot.com',
                'logo': 'https://justmascot.com/apple-touch-icon.png',
            },
            'serviceType': props.industry.name,
            'description': props.industry.metaDescription,
            'areaServed': 'US',
            'hasOfferCatalog': {
                '@type': 'OfferCatalog',
                'name': `${props.industry.tradeName} AI Answering Packages`,
                'itemListElement': [
                    {
                        '@type': 'Offer',
                        'itemOffered': {
                            '@type': 'Service',
                            'name': '24/7 AI Emergency Call Dispatch & Scheduling',
                        },
                        'price': '99.00',
                        'priceCurrency': 'USD',
                    },
                ],
            },
        },
        {
            '@type': 'FAQPage',
            'mainEntity': props.industry.faqs.map((faq) => ({
                '@type': 'Question',
                'name': faq.question,
                'acceptedAnswer': {
                    '@type': 'Answer',
                    'text': faq.answer,
                },
            })),
        },
    ],
}));
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 selection:bg-emerald-500 selection:text-slate-950 font-sans antialiased">
        <SeoHead
            :title="industry.metaTitle"
            :description="industry.metaDescription"
            :keywords="industry.keywords.join(', ')"
            :json-ld="jsonLdData"
        />

        <PublicHeader />

        <main>
            <!-- Hero Section -->
            <section class="relative overflow-hidden pt-12 pb-20 md:pt-20 md:pb-28 border-b border-slate-800/80">
                <!-- Background ambient glows -->
                <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 -z-10 h-[500px] w-[700px] rounded-full bg-emerald-500/10 blur-[130px]"></div>
                <div class="absolute -top-24 right-10 -z-10 h-80 w-80 rounded-full bg-indigo-500/10 blur-[100px]"></div>

                <div class="container mx-auto px-4 sm:px-6">
                    <!-- Breadcrumbs -->
                    <nav class="mb-6 flex items-center gap-2 text-xs text-slate-400 font-mono">
                        <Link :href="home()" class="hover:text-emerald-400 transition-colors">Home</Link>
                        <span>/</span>
                        <span class="text-slate-500">Industries</span>
                        <span>/</span>
                        <span class="text-emerald-400 font-semibold">{{ industry.name }}</span>
                    </nav>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                        <div class="lg:col-span-7 space-y-6">
                            <!-- Industry Badge -->
                            <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-emerald-400 backdrop-blur-md">
                                <component :is="currentIcon" class="h-4 w-4 text-emerald-400" />
                                <span>{{ industry.badgeText }}</span>
                            </div>

                            <!-- Main H1 Title -->
                            <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl md:text-5xl lg:text-6xl text-white leading-tight">
                                {{ industry.heroTitle }}
                            </h1>

                            <p class="text-base sm:text-lg md:text-xl text-slate-300 leading-relaxed max-w-2xl">
                                {{ industry.heroSubtitle }}
                            </p>

                            <!-- CTAs -->
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 pt-2">
                                <Link
                                    :href="register()"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-500 px-6 py-3.5 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all hover:bg-emerald-400 hover:scale-[1.02] active:scale-95"
                                >
                                    <span>Start 14-Day Free Trial</span>
                                    <ArrowRight class="h-4 w-4" />
                                </Link>

                                <a
                                    href="tel:+16196390411"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-900/80 px-6 py-3.5 text-sm font-semibold text-white transition-all hover:border-slate-600 hover:bg-slate-800"
                                >
                                    <PhoneCall class="h-4 w-4 text-emerald-400 animate-pulse" />
                                    <span>Test Live Call: (619) 639-0411</span>
                                </a>
                            </div>

                            <!-- Trust proof -->
                            <div class="flex items-center gap-4 pt-4 text-xs text-slate-400">
                                <div class="flex items-center gap-1 text-amber-400">
                                    <Star v-for="i in 5" :key="i" class="h-3.5 w-3.5 fill-current" />
                                </div>
                                <span>Rated 4.9/5 by 350+ Field Service Contractors</span>
                            </div>
                        </div>

                        <!-- Mascot & Live Dispatch Animation Column -->
                        <div class="lg:col-span-5 flex justify-center">
                            <div class="relative w-full max-w-md rounded-3xl border border-slate-800 bg-gradient-to-b from-slate-900/90 to-slate-950 p-6 shadow-2xl backdrop-blur-xl">
                                <div class="mb-4 flex items-center justify-between border-b border-slate-800 pb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="h-3 w-3 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span class="text-xs font-mono font-bold uppercase tracking-wider text-slate-300">Live Trade Dispatcher</span>
                                    </div>
                                    <Badge variant="outline" class="border-emerald-500/30 bg-emerald-500/10 text-[10px] text-emerald-400">
                                        24/7 Active
                                    </Badge>
                                </div>

                                <div class="flex flex-col items-center text-center py-4">
                                    <DispatcherMascot class="h-32 w-32 drop-shadow-[0_10px_20px_rgba(16,185,129,0.2)] mb-4" />
                                    <h3 class="text-lg font-bold text-white mb-1">{{ industry.tradeName }} Voice Node</h3>
                                    <p class="text-xs text-slate-400 max-w-xs">
                                        Sub-second voice response trained on trade terminology, emergency shutoff protocols, and technician schedule rules.
                                    </p>
                                </div>

                                <!-- Live Metrics Grid -->
                                <div class="grid grid-cols-2 gap-3 mt-4 border-t border-slate-800 pt-4">
                                    <div
                                        v-for="(stat, sIdx) in industry.stats"
                                        :key="sIdx"
                                        class="rounded-xl border border-slate-800/80 bg-slate-900/50 p-3 text-center"
                                    >
                                        <p class="text-lg font-extrabold text-emerald-400 font-mono">{{ stat.value }}</p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">{{ stat.label }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Embedded Interactive Sandbox Demo Pre-Selected For This Trade -->
            <PublicSandboxLeadMagnet
                :initial-scenario-id="industry.initialScenarioId"
                :custom-title="`Test Interactive AI Receptionist For ${industry.name}`"
            />

            <!-- Pain Points vs AI Solutions -->
            <section class="py-20 md:py-28 bg-slate-950 border-b border-slate-800">
                <div class="container mx-auto px-4 sm:px-6">
                    <div class="mx-auto mb-16 max-w-3xl text-center space-y-3">
                        <div class="inline-flex items-center gap-1.5 rounded-full border border-rose-500/30 bg-rose-500/10 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-rose-400">
                            <AlertCircle class="h-3.5 w-3.5" />
                            <span>The High Cost Of Missed Calls</span>
                        </div>
                        <h2 class="text-3xl font-extrabold text-white tracking-tight sm:text-4xl">
                            Why Traditional Answering Services Fail {{ industry.tradeName }}
                        </h2>
                        <p class="text-slate-400 text-sm sm:text-base">
                            Generic call centers don't know trade terms, can't schedule jobs without overlapping travel times, and put high-dollar emergencies on hold.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div
                            v-for="(point, pIdx) in industry.painPoints"
                            :key="pIdx"
                            class="relative rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-xl transition-all duration-300 hover:border-slate-700 hover:bg-slate-900"
                        >
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-500/10 text-rose-400 mb-4 font-mono font-bold text-sm">
                                0{{ pIdx + 1 }}
                            </div>
                            <h3 class="text-lg font-bold text-white mb-2">{{ point.title }}</h3>
                            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">{{ point.description }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Key Features & Guardrails -->
            <section class="py-20 md:py-28 bg-slate-900/40 border-b border-slate-800">
                <div class="container mx-auto px-4 sm:px-6">
                    <div class="mx-auto mb-16 max-w-3xl text-center space-y-3">
                        <div class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-emerald-400">
                            <Sparkles class="h-3.5 w-3.5" />
                            <span>Trade-Specific Intelligence</span>
                        </div>
                        <h2 class="text-3xl font-extrabold text-white tracking-tight sm:text-4xl">
                            Engineered Specifically For {{ industry.name }} Workflows
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div
                            v-for="(feat, fIdx) in industry.keyFeatures"
                            :key="fIdx"
                            class="rounded-2xl border border-emerald-500/20 bg-gradient-to-b from-slate-900 to-slate-950 p-6 shadow-xl"
                        >
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-400 mb-5">
                                <CheckCircle2 class="h-6 w-6" />
                            </div>
                            <h3 class="text-lg font-bold text-white mb-2">{{ feat.title }}</h3>
                            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">{{ feat.description }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Interactive FAQs with JSON-LD Schema -->
            <section class="py-20 md:py-28 bg-slate-950 border-b border-slate-800">
                <div class="container mx-auto px-4 sm:px-6 max-w-4xl">
                    <div class="text-center mb-12 space-y-3">
                        <h2 class="text-3xl font-extrabold text-white tracking-tight sm:text-4xl">
                            Frequently Asked Questions
                        </h2>
                        <p class="text-slate-400 text-sm sm:text-base">
                            Everything you need to know about setting up AI phone dispatch for {{ industry.name }}.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="(faq, fIndex) in industry.faqs"
                            :key="fIndex"
                            class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden transition-all duration-300"
                        >
                            <button
                                @click="toggleFaq(fIndex)"
                                class="w-full flex items-center justify-between p-5 sm:p-6 text-left font-bold text-white text-base sm:text-lg cursor-pointer"
                            >
                                <span>{{ faq.question }}</span>
                                <ChevronDown
                                    class="h-5 w-5 text-emerald-400 transition-transform duration-300 shrink-0 ml-4"
                                    :class="openFaqIndex === fIndex ? 'rotate-180' : ''"
                                />
                            </button>

                            <div
                                v-show="openFaqIndex === fIndex"
                                class="px-5 sm:px-6 pb-6 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-4"
                            >
                                {{ faq.answer }}
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Cross-Link Other Trades for SEO Crawlability -->
            <section class="py-16 bg-slate-900/20 border-b border-slate-800">
                <div class="container mx-auto px-4 sm:px-6">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 text-center mb-8">
                        Explore AI Voice Dispatch Solutions For Other Trades
                    </h3>

                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                        <Link
                            v-for="other in otherIndustries"
                            :key="other.slug"
                            :href="`/industries/${other.slug}`"
                            class="flex items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-950/70 p-3.5 text-xs font-semibold text-slate-300 transition-all hover:border-emerald-500/40 hover:bg-slate-900 hover:text-white"
                        >
                            <component :is="iconMap[other.icon] || Sparkles" class="h-4 w-4 text-emerald-400 shrink-0" />
                            <span class="truncate">{{ other.name }}</span>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Bottom CTA Banner -->
            <section class="py-20 bg-gradient-to-b from-slate-950 to-emerald-950/30 text-center">
                <div class="container mx-auto px-4 sm:px-6 max-w-3xl space-y-6">
                    <h2 class="text-3xl font-extrabold text-white sm:text-4xl">
                        Stop Losing High-Ticket {{ industry.tradeName }} Jobs to Missed Calls
                    </h2>
                    <p class="text-slate-300 text-base sm:text-lg">
                        Get started in 5 minutes. Zero contracts, instant call forwarding, and 14-day free trial with full phone simulator access.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link
                            :href="register()"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-500 px-8 py-4 text-sm font-bold text-slate-950 shadow-xl shadow-emerald-500/20 transition-all hover:bg-emerald-400 hover:scale-105"
                        >
                            <span>Claim 14-Day Free Trial</span>
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                        <a
                            href="tel:+16196390411"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-900 px-6 py-4 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            <PhoneCall class="h-4 w-4 text-emerald-400" />
                            <span>Dial Demo Line: (619) 639-0411</span>
                        </a>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
