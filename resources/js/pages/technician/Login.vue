<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import { Wrench, ArrowRight } from '@lucide/vue';

defineOptions({
    layout: null,
});
</script>

<template>
    <Head title="Technician Portal - Log In" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center items-center p-4 font-sans selection:bg-indigo-500 selection:text-white">
        <!-- Background decorative gradients -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -left-40 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl"></div>
        </div>

        <div class="relative w-full max-w-md bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-8 shadow-2xl flex flex-col items-center text-center gap-6">
            <!-- Brand Icon -->
            <div class="flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-600/10 border border-indigo-500/30 text-indigo-400 shadow-inner animate-pulse">
                <Wrench class="h-8 w-8" />
            </div>

            <!-- Header -->
            <div class="space-y-2">
                <h1 class="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-indigo-200 via-slate-100 to-emerald-200 bg-clip-text text-transparent">
                    Technician Portal
                </h1>
                <p class="text-sm text-slate-400">
                    Use your biometrics or passkey to securely access your route and assignments.
                </p>
            </div>

            <!-- Passkey Login Element -->
            <div class="w-full mt-4">
                <PasskeyVerify 
                    label="Identify via Biometrics"
                    loading-label="Verifying passkey..."
                    separator="Looking for password login?"
                />
            </div>

            <!-- Fallback Links -->
            <div class="w-full pt-2 flex flex-col items-center gap-4">
                <Link
                    href="/login"
                    class="group inline-flex items-center justify-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 font-medium transition-colors duration-200"
                >
                    Standard Office Portal Login
                    <ArrowRight class="h-3 w-3 transition-transform duration-200 group-hover:translate-x-0.5" />
                </Link>
                <p class="text-[10px] text-slate-500">
                    Laravel BusinessCalls &bull; Secured with WebAuthn Passkeys
                </p>
            </div>
        </div>
    </div>
</template>
