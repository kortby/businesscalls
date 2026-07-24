<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Sun, Moon, Menu, X, PhoneCall, ChevronRight } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useAppearance } from '@/composables/useAppearance';
import {
    home,
    about,
    pricing,
    contact,
    dashboard,
    login,
    register,
} from '@/routes';

type Props = {
    activePage?: 'home' | 'about' | 'pricing' | 'contact';
};

defineProps<Props>();

const { appearance, updateAppearance } = useAppearance();
const isMobileMenuOpen = ref(false);

const toggleAppearance = () => {
    updateAppearance(appearance.value === 'dark' ? 'light' : 'dark');
};

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};
</script>

<template>
    <header class="sticky top-0 z-50 w-full border-b bg-background/95 shadow-xs backdrop-blur-md transition-all duration-300 supports-[backdrop-filter]:bg-background/60">
        <div class="container mx-auto flex h-16 items-center justify-between px-4 sm:px-6">
            <!-- Brand Logo -->
            <Link :href="home()" class="group flex items-center gap-2">
                <AppLogoIcon class="h-9 w-9 shrink-0 transition-transform duration-300 group-hover:scale-105" />
                <span class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Just<span class="text-emerald-500 dark:text-emerald-400">Mascot</span>
                </span>
            </Link>

            <!-- Desktop Navigation Links -->
            <nav class="hidden items-center gap-6 md:flex">
                <Link
                    :href="home()"
                    class="relative text-sm font-semibold transition-colors hover:text-foreground"
                    :class="activePage === 'home' ? 'text-foreground font-bold' : 'text-muted-foreground'"
                >
                    Home
                </Link>
                <Link
                    :href="about()"
                    class="relative text-sm font-semibold transition-colors hover:text-foreground"
                    :class="activePage === 'about' ? 'text-foreground font-bold' : 'text-muted-foreground'"
                >
                    About
                </Link>
                <Link
                    :href="pricing()"
                    class="relative text-sm font-semibold transition-colors hover:text-foreground"
                    :class="activePage === 'pricing' ? 'text-foreground font-bold' : 'text-muted-foreground'"
                >
                    Pricing
                </Link>
                <Link
                    :href="contact()"
                    class="relative text-sm font-semibold transition-colors hover:text-foreground"
                    :class="activePage === 'contact' ? 'text-foreground font-bold' : 'text-muted-foreground'"
                >
                    Contact
                </Link>
            </nav>

            <!-- Desktop Right Actions -->
            <div class="hidden items-center gap-3 sm:flex">
                <!-- Phone Call Quick Action -->
                <a
                    href="tel:+16196390411"
                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 text-xs font-bold text-emerald-600 shadow-xs transition-all hover:bg-emerald-500/20 dark:bg-emerald-500/20 dark:text-emerald-400"
                    title="Call Test Line"
                >
                    <PhoneCall class="h-3.5 w-3.5 animate-pulse text-emerald-500" />
                    <span>+1 (619) 639-0411</span>
                </a>

                <!-- Theme Toggle Button -->
                <button
                    @click="toggleAppearance"
                    class="flex h-9 w-9 items-center justify-center rounded-md border border-input bg-background text-sm font-medium shadow-xs transition-colors hover:bg-accent hover:text-accent-foreground"
                    title="Toggle theme"
                >
                    <Sun v-if="appearance === 'dark'" class="h-4 w-4 text-yellow-500" />
                    <Moon v-else class="h-4 w-4 text-slate-700 dark:text-slate-300" />
                </button>

                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-all hover:bg-primary/90 focus-visible:ring-1 active:scale-95"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="inline-flex h-9 items-center justify-center rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground active:scale-95"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="register()"
                        class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-all hover:bg-primary/90 active:scale-95"
                    >
                        Get Started
                    </Link>
                </template>
            </div>

            <!-- Mobile Controls (Hamburger + Theme Toggle) -->
            <div class="flex items-center gap-2 sm:hidden">
                <button
                    @click="toggleAppearance"
                    class="flex h-9 w-9 items-center justify-center rounded-md border border-input bg-background text-sm font-medium shadow-xs"
                    title="Toggle theme"
                >
                    <Sun v-if="appearance === 'dark'" class="h-4 w-4 text-yellow-500" />
                    <Moon v-else class="h-4 w-4 text-slate-700 dark:text-slate-300" />
                </button>

                <button
                    @click="toggleMobileMenu"
                    class="flex h-9 w-9 items-center justify-center rounded-md border border-input bg-background text-foreground shadow-xs hover:bg-accent"
                    :aria-expanded="isMobileMenuOpen"
                    aria-label="Toggle Navigation Menu"
                >
                    <X v-if="isMobileMenuOpen" class="h-5 w-5" />
                    <Menu v-else class="h-5 w-5" />
                </button>
            </div>
        </div>

        <!-- Mobile Navigation Slide-Down Drawer -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="transform -translate-y-4 opacity-0"
            enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="transform translate-y-0 opacity-100"
            leave-to-class="transform -translate-y-4 opacity-0"
        >
            <div
                v-if="isMobileMenuOpen"
                class="border-b bg-background/95 px-4 pt-3 pb-6 shadow-xl backdrop-blur-md md:hidden"
            >
                <div class="space-y-3">
                    <!-- Nav Links -->
                    <div class="flex flex-col space-y-1">
                        <Link
                            :href="home()"
                            @click="isMobileMenuOpen = false"
                            class="flex items-center justify-between rounded-lg px-3 py-2.5 text-base font-semibold transition-colors hover:bg-accent"
                            :class="activePage === 'home' ? 'bg-primary/10 text-primary font-bold' : 'text-foreground'"
                        >
                            <span>Home</span>
                            <ChevronRight class="h-4 w-4 text-muted-foreground" />
                        </Link>

                        <Link
                            :href="about()"
                            @click="isMobileMenuOpen = false"
                            class="flex items-center justify-between rounded-lg px-3 py-2.5 text-base font-semibold transition-colors hover:bg-accent"
                            :class="activePage === 'about' ? 'bg-primary/10 text-primary font-bold' : 'text-foreground'"
                        >
                            <span>About</span>
                            <ChevronRight class="h-4 w-4 text-muted-foreground" />
                        </Link>

                        <Link
                            :href="pricing()"
                            @click="isMobileMenuOpen = false"
                            class="flex items-center justify-between rounded-lg px-3 py-2.5 text-base font-semibold transition-colors hover:bg-accent"
                            :class="activePage === 'pricing' ? 'bg-primary/10 text-primary font-bold' : 'text-foreground'"
                        >
                            <span>Pricing</span>
                            <ChevronRight class="h-4 w-4 text-muted-foreground" />
                        </Link>

                        <Link
                            :href="contact()"
                            @click="isMobileMenuOpen = false"
                            class="flex items-center justify-between rounded-lg px-3 py-2.5 text-base font-semibold transition-colors hover:bg-accent"
                            :class="activePage === 'contact' ? 'bg-primary/10 text-primary font-bold' : 'text-foreground'"
                        >
                            <span>Contact</span>
                            <ChevronRight class="h-4 w-4 text-muted-foreground" />
                        </Link>
                    </div>

                    <!-- Call AI Action Card -->
                    <a
                        href="tel:+16196390411"
                        class="flex items-center justify-between rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3.5 text-sm font-bold text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400"
                    >
                        <div class="flex items-center gap-2.5">
                            <PhoneCall class="h-5 w-5 animate-pulse text-emerald-500" />
                            <span>Call AI Assistant: +1 (619) 639-0411</span>
                        </div>
                        <ChevronRight class="h-4 w-4 text-emerald-500" />
                    </a>

                    <!-- Auth Action Buttons -->
                    <div class="pt-2">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            @click="isMobileMenuOpen = false"
                            class="flex w-full items-center justify-center rounded-xl bg-primary py-3 text-base font-bold text-primary-foreground shadow-md"
                        >
                            Go to Dashboard
                        </Link>
                        <div v-else class="grid grid-cols-2 gap-2">
                            <Link
                                :href="login()"
                                @click="isMobileMenuOpen = false"
                                class="flex items-center justify-center rounded-xl border bg-background py-3 text-sm font-semibold text-foreground shadow-xs"
                            >
                                Log in
                            </Link>
                            <Link
                                :href="register()"
                                @click="isMobileMenuOpen = false"
                                class="flex items-center justify-center rounded-xl bg-primary py-3 text-sm font-bold text-primary-foreground shadow-md"
                            >
                                Get Started
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </header>
</template>
