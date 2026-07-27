<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import {
    Mail,
    Phone,
    MapPin,
    Send,
    PhoneCall,
    Clock,
    ShieldCheck,
    Sparkles,
    Headphones,
    MessageSquare,
    Building2,
    CheckCircle2,
    HelpCircle,
    ChevronDown,
    Zap,
    Radio,
} from '@lucide/vue';
import { ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    dashboard,
    login,
    register,
    home,
    about,
    pricing,
    contact,
    privacy,
    terms,
} from '@/routes';

const form = useForm({
    name: '',
    company_name: '',
    email: '',
    phone: '',
    trade_category: 'hvac',
    technician_count: '1-5',
    preferred_contact: 'email',
    message: '',
});

const isSent = ref(false);
const activeFaq = ref<number | null>(null);

const toggleFaq = (index: number) => {
    activeFaq.value = activeFaq.value === index ? null : index;
};

const submit = () => {
    isSent.value = true;
    setTimeout(() => {
        isSent.value = false;
        form.reset();
    }, 4500);
};

const contactFaqs = [
    {
        question: 'How quickly can our business set up the AI Voice Receptionist?',
        answer: 'You can be up and running in under 10 minutes. Simply configure your technician shift schedules, select your trade prompt presets, and forward your existing business calls or provision a dedicated number.',
    },
    {
        question: 'Can we test our custom IVR and voice routing before going live?',
        answer: 'Yes! You can use our site’s interactive Sandbox Mode or dial our live demonstration receptionist line at +1 (619) 639-0411 right now to experience real-time scheduling and diagnostic triage.',
    },
    {
        question: 'Which trade management systems and CRMs do you integrate with?',
        answer: 'JustMascot supports direct webhook integrations, HMAC payload signing, ServiceTitan, Housecall Pro, Jobber, Zapier, and custom REST API endpoints for enterprise franchises.',
    },
    {
        question: 'What support options are available for active trade contractors?',
        answer: 'We offer 24/7 priority telephony channel monitoring, dedicated email support (info@shieldsuite.co), direct phone support (+1 619 742-7188), and dedicated account managers on Enterprise plans.',
    },
];

const supportPillars = [
    {
        title: 'Dedicated Integration Onboarding',
        description: 'Our engineers assist you with setting up technician shift availabilities, custom voice prompts, and telephony forwarding rules.',
        icon: Headphones,
        color: 'text-indigo-500 bg-indigo-500/10 border-indigo-500/20',
        response: '< 1 Hour Response',
    },
    {
        title: '24/7 Priority Emergency Triage',
        description: 'Instant monitoring for high-priority dispatch issues, emergency call rerouting, and travel buffer conflict handling.',
        icon: ShieldCheck,
        color: 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20',
        response: '24/7 Live Monitoring',
    },
    {
        title: 'Custom Voice & Telephony Setup',
        description: 'Need branded custom voice clones or specialized HMAC webhook authentication? Our team is available for custom builds.',
        icon: Sparkles,
        color: 'text-amber-500 bg-amber-500/10 border-amber-500/20',
        response: 'Dedicated Telephony Engineers',
    },
];
</script>

<template>
    <SeoHead
        title="Contact Us | Dedicated Support & Setup Assistance | JustMascot"
        description="Connect with our trade answering and dispatch experts. Get live assistance with AI receptionist setup, custom voice prompts, travel buffers, and CRM integrations."
        keywords="contact JustMascot, contractor dispatcher support, AI receptionist setup help, voice dispatch integrations, ShieldSuite support"
        :jsonLd="{
            '@context': 'https://schema.org',
            '@type': 'ContactPage',
            name: 'Contact JustMascot',
            description:
                'Contact page for JustMascot, providing 24/7 support, demo assistance, and custom integration setup for trade contractors.',
            url: 'https://justmascot.io/contact',
            mainEntity: {
                '@type': 'Organization',
                name: 'JustMascot / ShieldSuite Inc.',
                telephone: '+1-619-742-7188',
                email: 'info@shieldsuite.co',
                address: {
                    '@type': 'PostalAddress',
                    addressLocality: 'Houston',
                    addressRegion: 'TX',
                    addressCountry: 'US'
                }
            }
        }"
    />

    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-primary selection:text-primary-foreground dark:bg-slate-950 dark:text-slate-100">
        <!-- Header -->
        <PublicHeader activePage="contact" />

        <!-- Hero Banner Section -->
        <section class="relative overflow-hidden border-b bg-gradient-to-b from-indigo-500/10 via-transparent to-transparent py-16 md:py-24 dark:from-indigo-950/20">
            <div class="absolute inset-0 -z-20 bg-[linear-gradient(to_right,#8080800a_1px,transparent_1px),linear-gradient(to_bottom,#8080800a_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)] bg-[size:14px_24px]"></div>
            <div class="animate-float-slow absolute top-10 left-10 -z-10 h-72 w-72 rounded-full bg-indigo-500/15 blur-3xl"></div>

            <div class="container mx-auto max-w-[900px] space-y-4 px-4 text-center sm:px-6">
                <div class="mx-auto inline-flex items-center gap-2 rounded-full border border-indigo-500/30 bg-indigo-500/10 px-4 py-1.5 text-xs font-bold text-indigo-600 uppercase tracking-wider dark:text-indigo-400">
                    <MessageSquare class="h-3.5 w-3.5 text-indigo-500" />
                    <span>We are here to help 24/7</span>
                </div>
                <h1 class="text-4xl font-extrabold tracking-tight text-foreground sm:text-5xl md:text-6xl">
                    Contact Our <span class="bg-gradient-to-r from-indigo-600 via-purple-600 to-emerald-500 bg-clip-text text-transparent dark:from-indigo-400 dark:via-purple-400 dark:to-emerald-400">Trade Answering</span> Experts
                </h1>
                <p class="text-base leading-relaxed text-muted-foreground sm:text-lg">
                    Have questions about setting up your AI voice receptionist, dynamic travel buffers, custom telephony webhooks, or multi-technician schedules? Our support team is ready to help.
                </p>
            </div>
        </section>

        <!-- Main Form & Contact Info Section -->
        <section class="py-16 md:py-24">
            <div class="container mx-auto grid max-w-[1100px] grid-cols-1 items-start gap-12 px-4 sm:px-6 lg:grid-cols-12">
                <!-- Left Details Column -->
                <div class="space-y-8 lg:col-span-5">
                    <div>
                        <h2 class="text-2xl font-extrabold tracking-tight text-foreground">Get In Touch Directly</h2>
                        <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                            Reach out to our customer success team for setup assistance, live demonstrations, or technical custom integration inquiries.
                        </p>
                    </div>

                    <!-- Direct Contact Cards -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-4 rounded-2xl border bg-card p-4 shadow-xs backdrop-blur-md transition-all hover:border-indigo-500/30 dark:border-slate-800">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-indigo-500/20 bg-indigo-500/10 text-indigo-500">
                                <Mail class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Email Support</p>
                                <a href="mailto:info@shieldsuite.co" class="text-sm font-extrabold text-foreground hover:text-indigo-600 dark:hover:text-indigo-400">
                                    info@shieldsuite.co
                                </a>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 rounded-2xl border bg-card p-4 shadow-xs backdrop-blur-md transition-all hover:border-emerald-500/30 dark:border-slate-800">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-emerald-500/20 bg-emerald-500/10 text-emerald-500">
                                <Phone class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Direct Phone Line</p>
                                <a href="tel:+16197427188" class="text-sm font-extrabold text-foreground hover:text-emerald-600 dark:hover:text-emerald-400">
                                    +1 (619) 742-7188
                                </a>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 rounded-2xl border bg-card p-4 shadow-xs backdrop-blur-md transition-all hover:border-amber-500/30 dark:border-slate-800">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-amber-500/20 bg-amber-500/10 text-amber-500">
                                <MapPin class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Headquarters</p>
                                <p class="text-sm font-extrabold text-foreground">Houston, Texas</p>
                            </div>
                        </div>
                    </div>

                    <!-- Live AI Test Line Promo Box -->
                    <div class="rounded-2xl border border-emerald-500/30 bg-gradient-to-br from-emerald-500/10 via-slate-900 to-indigo-950 p-6 text-white shadow-xl">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-slate-950 shadow-md">
                                <PhoneCall class="h-5 w-5 animate-pulse" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold">Test Live AI Assistant Line</h3>
                                <p class="text-xs text-slate-300">Dial to test live appointment booking</p>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-2 rounded-xl border border-emerald-500/30 bg-slate-950/80 p-3">
                            <span class="font-mono text-sm font-bold text-emerald-400">+1 (619) 639-0411</span>
                            <a
                                href="tel:+16196390411"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-bold text-slate-950 shadow-md transition-all hover:bg-emerald-400 active:scale-95"
                            >
                                <PhoneCall class="h-3.5 w-3.5" />
                                <span>Call Line</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Right Form Column (3D Container) -->
                <div class="rounded-3xl border-4 border-b-12 border-slate-300 bg-card p-6 shadow-xl lg:col-span-7 dark:border-slate-800 sm:p-8">
                    <div class="mb-6 flex items-center justify-between border-b pb-4">
                        <div>
                            <h3 class="text-xl font-black text-foreground uppercase tracking-wide">Send Us A Message</h3>
                            <p class="text-xs text-muted-foreground">Fill in your information and our team will get back to you promptly.</p>
                        </div>
                        <Badge variant="outline" class="border-indigo-500/30 bg-indigo-500/10 text-xs font-bold text-indigo-600 dark:text-indigo-400">
                            Fast Response
                        </Badge>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="name" class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Your Name</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    v-model="form.name"
                                    class="w-full"
                                    placeholder="John Doe"
                                    required
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="company_name" class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Company / Business Name</Label>
                                <Input
                                    id="company_name"
                                    type="text"
                                    v-model="form.company_name"
                                    class="w-full"
                                    placeholder="Apex Plumbing & Heating"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="email" class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Email Address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    class="w-full"
                                    placeholder="john@apexplumbing.com"
                                    required
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="phone" class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Phone Number</Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    v-model="form.phone"
                                    class="w-full"
                                    placeholder="+1 (555) 000-0000"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="trade_category" class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Trade Category</Label>
                                <select
                                    id="trade_category"
                                    v-model="form.trade_category"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 py-2 text-xs font-semibold focus:outline-hidden"
                                >
                                    <option value="hvac">HVAC & Climate Control</option>
                                    <option value="plumbing">Plumbing & Drain Services</option>
                                    <option value="electrical">Electrical & Power</option>
                                    <option value="roofing">Roofing & Storm Repair</option>
                                    <option value="locksmith">Locksmith & Security</option>
                                    <option value="pest">Pest Control</option>
                                    <option value="appliance">Appliance Repair</option>
                                    <option value="other">General Trade / Other</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <Label for="technician_count" class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Active Technicians</Label>
                                <select
                                    id="technician_count"
                                    v-model="form.technician_count"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 py-2 text-xs font-semibold focus:outline-hidden"
                                >
                                    <option value="1">Solo Contractor (1 Tech)</option>
                                    <option value="2-5">Small Team (2-5 Techs)</option>
                                    <option value="6-15">Growing Fleet (6-15 Techs)</option>
                                    <option value="16+">Enterprise Franchise (16+ Techs)</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="message" class="text-xs font-bold uppercase tracking-wider text-muted-foreground">How can we help?</Label>
                            <textarea
                                id="message"
                                v-model="form.message"
                                class="min-h-[110px] w-full resize-y rounded-xl border-2 border-slate-300 p-3 text-xs transition-all focus:ring-0 focus:outline-hidden dark:border-slate-800 dark:bg-slate-900/40"
                                placeholder="Tell us about your dispatching needs, CRM integration, or questions..."
                                required
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <span v-if="isSent" class="flex items-center gap-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                <CheckCircle2 class="h-4 w-4" /> Message sent successfully! Our team will contact you shortly.
                            </span>
                            <span v-else></span>

                            <button
                                type="submit"
                                class="cursor-pointer rounded-xl border-2 border-b-6 border-indigo-700 bg-indigo-500 px-6 py-3 text-xs font-black tracking-wide text-white uppercase shadow-md transition-all hover:bg-indigo-400 active:translate-y-1 active:border-b-2"
                            >
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <!-- Support Pillars Showcase Section -->
        <section class="border-y bg-card/60 py-16 dark:bg-slate-900/30 md:py-24">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="mx-auto mb-14 max-w-[800px] text-center">
                    <h2 class="text-xs font-black uppercase tracking-widest text-primary">Service Level Standards</h2>
                    <h3 class="mt-2 text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl">Why Contractors Choose Our Support</h3>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div
                        v-for="pillar in supportPillars"
                        :key="pillar.title"
                        class="flex flex-col justify-between rounded-2xl border bg-background p-6 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl dark:border-slate-800"
                    >
                        <div>
                            <div class="mb-4 flex items-center justify-between">
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl border p-2.5" :class="pillar.color">
                                    <component :is="pillar.icon" class="h-6 w-6" />
                                </div>
                                <Badge variant="outline" class="border-emerald-500/30 bg-emerald-500/10 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ pillar.response }}
                                </Badge>
                            </div>
                            <h4 class="mb-2 text-lg font-bold text-foreground">{{ pillar.title }}</h4>
                            <p class="text-xs leading-relaxed text-muted-foreground">{{ pillar.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact FAQs Section -->
        <section class="border-b bg-background py-16 md:py-24">
            <div class="container mx-auto max-w-[850px] px-4 sm:px-6">
                <div class="mb-12 text-center">
                    <div class="mx-auto inline-flex items-center gap-1.5 rounded-full border bg-accent/60 px-3 py-1 text-xs font-bold text-muted-foreground uppercase">
                        <HelpCircle class="h-3.5 w-3.5 text-primary" />
                        <span>Got Questions?</span>
                    </div>
                    <h3 class="mt-3 text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                        Common Contact & Setup Questions
                    </h3>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(faq, index) in contactFaqs"
                        :key="index"
                        class="overflow-hidden rounded-2xl border bg-card transition-all duration-300 hover:border-indigo-500/30 dark:border-slate-800"
                    >
                        <button
                            @click="toggleFaq(index)"
                            class="flex w-full cursor-pointer items-center justify-between p-5 text-left font-bold text-foreground focus:outline-hidden"
                        >
                            <span class="text-base sm:text-lg">{{ faq.question }}</span>
                            <span
                                class="ml-4 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground transition-all duration-300"
                                :class="{ 'rotate-180 bg-primary text-primary-foreground': activeFaq === index }"
                            >
                                <ChevronDown class="h-4 w-4" />
                            </span>
                        </button>

                        <Transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="max-h-0 opacity-0 transform -translate-y-2"
                            enter-to-class="max-h-96 opacity-100 transform translate-y-0"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="max-h-96 opacity-100 transform translate-y-0"
                            leave-to-class="max-h-0 opacity-0 transform -translate-y-2"
                        >
                            <div v-show="activeFaq === index" class="overflow-hidden">
                                <div class="border-t border-border/60 px-5 pt-3 pb-5 text-sm leading-relaxed text-muted-foreground">
                                    {{ faq.answer }}
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t bg-background py-8">
            <div class="container mx-auto flex flex-col items-center justify-between gap-4 px-4 text-xs font-semibold text-muted-foreground sm:flex-row sm:px-6">
                <p>© 2026 ShieldSuite Inc. All rights reserved.</p>
                <div class="flex items-center gap-6">
                    <Link :href="home()" class="hover:text-foreground">Home</Link>
                    <Link :href="about()" class="hover:text-foreground">About</Link>
                    <Link :href="pricing()" class="hover:text-foreground">Pricing</Link>
                    <Link :href="privacy()" class="hover:text-foreground">Privacy</Link>
                    <Link :href="terms()" class="hover:text-foreground">Terms</Link>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@keyframes float-slow {
    0%, 100% { transform: translateY(0px) scale(1); }
    50% { transform: translateY(-15px) scale(1.05); }
}
.animate-float-slow {
    animation: float-slow 8s ease-in-out infinite;
}
</style>
