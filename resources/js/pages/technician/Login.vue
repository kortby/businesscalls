<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import { Wrench, ArrowRight } from '@lucide/vue';
</script>

<template>
    <Head title="Technician Portal - Log In" />

    <div
        class="flex min-h-screen flex-col items-center justify-center bg-slate-950 p-4 font-sans text-slate-100 selection:bg-indigo-500 selection:text-white"
    >
        <!-- Background decorative gradients -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div
                class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-indigo-500/10 blur-3xl"
            ></div>
            <div
                class="absolute -right-40 -bottom-40 h-96 w-96 rounded-full bg-emerald-500/10 blur-3xl"
            ></div>
        </div>

        <div
            class="relative flex w-full max-w-md flex-col items-center gap-6 rounded-3xl border border-slate-800/80 bg-slate-900/80 p-8 text-center shadow-2xl backdrop-blur-xl"
        >
            <!-- Brand Icon -->
            <div
                class="flex h-16 w-16 animate-pulse items-center justify-center rounded-2xl border border-indigo-500/30 bg-indigo-600/10 text-indigo-400 shadow-inner"
            >
                <Wrench class="h-8 w-8" />
            </div>

            <!-- Header -->
            <div class="space-y-2">
                <h1
                    class="bg-gradient-to-r from-indigo-200 via-slate-100 to-emerald-200 bg-clip-text text-3xl font-extrabold tracking-tight text-transparent"
                >
                    Technician Portal
                </h1>
                <p class="text-sm text-slate-400">
                    Use your biometrics or passkey to securely access your route
                    and assignments.
                </p>
            </div>

            <!-- Passkey Login Element -->
            <div class="mt-4 w-full">
                <PasskeyVerify
                    label="Identify via Biometrics"
                    loading-label="Verifying passkey..."
                    separator="Looking for password login?"
                />
            </div>

            <!-- Fallback Links -->
            <div class="flex w-full flex-col items-center gap-4 pt-2">
                <Link
                    href="/login"
                    class="group inline-flex items-center justify-center gap-1.5 text-xs font-medium text-indigo-400 transition-colors duration-200 hover:text-indigo-300"
                >
                    Standard Office Portal Login
                    <ArrowRight
                        class="h-3 w-3 transition-transform duration-200 group-hover:translate-x-0.5"
                    />
                </Link>
                <p class="text-[10px] text-slate-500">
                    Laravel BusinessCalls &bull; Secured with WebAuthn Passkeys
                </p>
            </div>
        </div>
    </div>
</template>
