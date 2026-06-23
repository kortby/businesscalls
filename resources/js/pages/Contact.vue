<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { dashboard, login, register, home, about, pricing, contact } from '@/routes';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Mail, Phone, MapPin, Send } from '@lucide/vue';

const form = useForm({
    name: '',
    email: '',
    message: ''
});

const isSent = ref(false);

const submit = () => {
    isSent.value = true;
    setTimeout(() => {
        isSent.value = false;
        form.reset();
    }, 4000);
};
</script>

<template>
    <Head title="Contact - businesscalls" />

    <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 font-sans">
        
        <!-- Header -->
        <header class="sticky top-0 z-40 w-full border-b bg-background/95 backdrop-blur-md supports-[backdrop-filter]:bg-background/60">
            <div class="container mx-auto flex h-16 items-center justify-between px-4 sm:px-6">
                <Link :href="home()" class="flex items-center gap-2">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-sm">
                        <AppLogoIcon class="h-5 w-5 text-primary-foreground fill-current" />
                    </div>
                    <span class="text-xl font-bold tracking-tight">businesscalls</span>
                </Link>

                <nav class="hidden md:flex items-center gap-6">
                    <Link :href="home()" class="text-sm font-semibold text-muted-foreground hover:text-foreground">Home</Link>
                    <Link :href="about()" class="text-sm font-semibold text-muted-foreground hover:text-foreground">About</Link>
                    <Link :href="pricing()" class="text-sm font-semibold text-muted-foreground hover:text-foreground">Pricing</Link>
                    <Link :href="contact()" class="text-sm font-semibold text-foreground hover:text-foreground">Contact</Link>
                </nav>

                <div class="flex items-center gap-4">
                    <Link v-if="$page.props.auth.user" :href="dashboard()" class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs hover:bg-primary/90">
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link :href="login()" class="text-sm font-medium text-muted-foreground hover:text-foreground">Log in</Link>
                        <Link :href="register()" class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs hover:bg-primary/90">Get Started</Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Contact Content (Duolingo Style Border & Box) -->
        <section class="py-16 md:py-24">
            <div class="container mx-auto px-4 sm:px-6 grid grid-cols-1 md:grid-cols-12 gap-12 items-start max-w-[1000px]">
                
                <!-- Left Details Column -->
                <div class="md:col-span-5 space-y-6">
                    <h1 class="text-4xl font-extrabold tracking-tight text-foreground">
                        Get in Touch with Us
                    </h1>
                    <p class="text-sm text-muted-foreground leading-relaxed">
                        Have questions about dynamic Vapi assistants, custom telephony setups, or multi-tenant scope isolation? Drop us a note!
                    </p>

                    <div class="space-y-4 pt-4">
                        <div class="flex items-center gap-3 text-xs font-semibold">
                            <div class="h-9 w-9 rounded-lg bg-indigo-500/10 text-indigo-500 flex items-center justify-center border border-indigo-500/20">
                                <Mail class="h-4 w-4" />
                            </div>
                            <span>support@businesscalls.com</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs font-semibold">
                            <div class="h-9 w-9 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center border border-emerald-500/20">
                                <Phone class="h-4 w-4" />
                            </div>
                            <span>+1 (800) 555-0199</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs font-semibold">
                            <div class="h-9 w-9 rounded-lg bg-amber-500/10 text-amber-500 flex items-center justify-center border border-amber-500/20">
                                <MapPin class="h-4 w-4" />
                            </div>
                            <span>Silicon Valley, CA</span>
                        </div>
                    </div>
                </div>

                <!-- Right Form Column (Duolingo Style 3D Container) -->
                <div class="md:col-span-7 border-4 border-b-12 border-slate-300 dark:border-slate-800 rounded-3xl p-6 bg-card">
                    <h3 class="text-lg font-black uppercase tracking-wider text-foreground mb-4">Send a Message</h3>
                    
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name" class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Your Name</Label>
                            <Input id="name" type="text" v-model="form.name" class="w-full" placeholder="John Doe" required />
                        </div>
                        <div class="space-y-2">
                            <Label for="email" class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Email Address</Label>
                            <Input id="email" type="email" v-model="form.email" class="w-full" placeholder="john@example.com" required />
                        </div>
                        <div class="space-y-2">
                            <Label for="message" class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Message</Label>
                            <textarea 
                                id="message" 
                                v-model="form.message" 
                                class="w-full min-h-[100px] border-2 border-slate-300 dark:border-slate-800 rounded-xl p-3 text-xs focus:ring-0 focus:outline-hidden dark:bg-slate-900/30 transition-all resize-y" 
                                placeholder="How can we help your business?" 
                                required
                            ></textarea>
                        </div>

                        <div class="pt-2 flex items-center justify-between">
                            <span v-if="isSent" class="text-emerald-600 dark:text-emerald-400 font-bold text-xs flex items-center gap-1">
                                <Send class="h-3.5 w-3.5" /> Message sent successfully!
                            </span>
                            <span v-else></span>

                            <button
                                type="submit"
                                class="bg-indigo-500 hover:bg-indigo-400 text-white font-black tracking-wide uppercase px-6 py-2.5 rounded-xl border-2 border-indigo-500 border-b-6 border-indigo-700 active:border-b-2 active:translate-y-1 transition-all cursor-pointer shadow-md text-xs"
                            >
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </section>

        <!-- Footer -->
        <footer class="py-8 bg-background border-t">
            <div class="container mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-semibold text-muted-foreground">
                <p>© 2026 businesscalls Inc. All rights reserved.</p>
                <div class="flex items-center gap-6">
                    <Link :href="home()" class="hover:text-foreground">Home</Link>
                    <Link :href="about()" class="hover:text-foreground">About</Link>
                    <Link :href="pricing()" class="hover:text-foreground">Pricing</Link>
                </div>
            </div>
        </footer>

    </div>
</template>
