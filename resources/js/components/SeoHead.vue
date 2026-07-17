<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        title: string;
        description: string;
        keywords?: string;
        ogType?: string;
        ogImage?: string;
        canonical?: string;
        jsonLd?: Record<string, any>;
    }>(),
    {
        keywords:
            'AI receptionist, contractor answering service, HVAC dispatch, plumber scheduling, voice AI dispatch, trade contractors',
        ogType: 'website',
        ogImage: '/apple-touch-icon.png', // Fallback to apple touch icon or standard logo image
    },
);

const page = usePage();

// Safely construct absolute URLs
const siteUrl = computed(() => {
    const sharedUrl = page.props.app_url as string;

    if (sharedUrl) {
        return sharedUrl.replace(/\/$/, '');
    }

    if (typeof window !== 'undefined') {
        return window.location.origin;
    }

    return 'http://localhost:8000'; // fallback
});

const canonicalUrl = computed(() => {
    if (props.canonical) {
        return props.canonical;
    }

    const path = page.url || '';

    return `${siteUrl.value}${path}`;
});

const ogImageUrl = computed(() => {
    if (props.ogImage?.startsWith('http')) {
        return props.ogImage;
    }

    return `${siteUrl.value}${props.ogImage}`;
});

// Format JSON-LD script content
const jsonLdScript = computed(() => {
    if (!props.jsonLd) {
        return null;
    }

    return JSON.stringify(props.jsonLd);
});
</script>

<template>
    <Head>
        <title>{{ title }}</title>
        <meta name="description" :content="description" />
        <meta name="keywords" :content="keywords" />

        <!-- Open Graph / Facebook -->
        <meta property="og:type" :content="ogType" />
        <meta property="og:title" :content="title" />
        <meta property="og:description" :content="description" />
        <meta property="og:url" :content="canonicalUrl" />
        <meta property="og:image" :content="ogImageUrl" />
        <meta property="og:site_name" content="JustMascot" />

        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image" />
        <meta property="twitter:title" :content="title" />
        <meta property="twitter:description" :content="description" />
        <meta property="twitter:image" :content="ogImageUrl" />
        <meta property="twitter:url" :content="canonicalUrl" />

        <!-- Canonical URL -->
        <link rel="canonical" :href="canonicalUrl" />

        <!-- JSON-LD Structured Data -->
        <component
            :is="'script'"
            v-if="jsonLdScript"
            type="application/ld+json"
            v-html="jsonLdScript"
        />
    </Head>
</template>
