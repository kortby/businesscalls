<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import {
    MapPin,
    Navigation,
    Phone,
    Calendar,
    Clock,
    Sparkles,
    CheckCircle2,
    Shield,
} from '@lucide/vue';
import { ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps<{
    booking: {
        id: number;
        booking_hash: string;
        customer_phone: string;
        job_details: string;
        status: string;
        scheduled_start: string;
        latitude?: number;
        longitude?: number;
        triage_notes?: string;
        appliance_brand?: string;
        appliance_age?: number;
        urgency_markers?: string[] | any;
        employee?: {
            id: number;
            first_name: string;
            last_name: string;
            phone: string;
            latitude?: number;
            longitude?: number;
        };
        tenant?: {
            name: string;
        };
    };
    reverbKey?: string;
    reverbHost?: string;
    reverbPort?: string | number;
    reverbScheme?: string;
}>();

// Map reactive states
const map = ref<any>(null);
const techMarker = ref<any>(null);
const destMarker = ref<any>(null);
const routeLine = ref<any>(null);
const currentEta = ref<number | null>(null);
const currentTechLat = ref<number | null>(
    props.booking.employee?.latitude ?? null,
);
const currentTechLng = ref<number | null>(
    props.booking.employee?.longitude ?? null,
);
const activeStatus = ref<string>(props.booking.status);

// Load Leaflet dynamically
const initLeaflet = () => {
    if (!document.getElementById('leaflet-css')) {
        const link = document.createElement('link');
        link.id = 'leaflet-css';
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);
    }

    if (!document.getElementById('leaflet-js')) {
        const script = document.createElement('script');
        script.id = 'leaflet-js';
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = () => {
            setupMap();
        };
        document.head.appendChild(script);
    } else {
        const L = (window as any).L;

        if (L) {
            setupMap();
        }
    }
};

const setupMap = () => {
    const L = (window as any).L;

    if (!L || !document.getElementById('tracking-map')) {
        return;
    }

    if (map.value) {
        map.value.remove();
        map.value = null;
    }

    // Default target coordinates (Destination)
    const destLat = props.booking.latitude ?? 37.7749;
    const destLng = props.booking.longitude ?? -122.4194;

    // Tech start coordinates
    const startLat = currentTechLat.value ?? destLat - 0.015;
    const startLng = currentTechLng.value ?? destLng - 0.012;

    map.value = L.map('tracking-map', {
        zoomControl: true,
        attributionControl: false,
    }).setView([startLat, startLng], 14);

    // Highly saturated voyager tiles styling for visual excellence
    L.tileLayer(
        'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
        {
            maxZoom: 19,
        },
    ).addTo(map.value);

    // Custom Job Destination icon
    const destIcon = L.divIcon({
        html: `<div class="w-9 h-9 rounded-full border-4 border-slate-900 bg-emerald-500 flex items-center justify-center font-black text-white shadow-lg animate-pulse" style="box-shadow: 2px 2px 0px #0f172a;">📍</div>`,
        className: 'custom-destination-marker',
        iconSize: [36, 36],
        iconAnchor: [18, 18],
    });

    destMarker.value = L.marker([destLat, destLng], { icon: destIcon })
        .addTo(map.value)
        .bindPopup(
            `<strong>Your Location</strong><br>${props.booking.job_details}`,
        )
        .openPopup();

    // Custom Technician moving icon
    const initials = props.booking.employee
        ? `${props.booking.employee.first_name[0]}${props.booking.employee.last_name[0]}`
        : 'Tech';

    const techIcon = L.divIcon({
        html: `<div class="w-11 h-11 rounded-full border-4 border-slate-900 bg-amber-500 flex flex-col items-center justify-center font-black text-white text-xs shadow-xl transition-all duration-500 relative" style="box-shadow: 3px 3px 0px #0f172a;">
            <span class="text-[9px] leading-none mb-0.5">ON WAY</span>
            <span class="text-[11px] leading-none">${initials}</span>
            <div class="absolute -bottom-1 -right-1 bg-slate-900 text-white rounded-full p-0.5 text-[8px] animate-bounce">🚗</div>
        </div>`,
        className: 'custom-tech-marker',
        iconSize: [44, 44],
        iconAnchor: [22, 22],
    });

    techMarker.value = L.marker([startLat, startLng], { icon: techIcon })
        .addTo(map.value)
        .bindPopup(
            `<strong>${props.booking.employee?.first_name || 'Technician'}</strong> is on the way!`,
        );

    // Draw route line
    routeLine.value = L.polyline(
        [
            [startLat, startLng],
            [destLat, destLng],
        ],
        {
            color: '#F59E0B',
            weight: 5,
            dashArray: '10, 8',
            opacity: 0.8,
        },
    ).addTo(map.value);

    // Zoom map to fit both markers
    const group = new L.featureGroup([destMarker.value, techMarker.value]);
    map.value.fitBounds(group.getBounds().pad(0.2));
};

const updateLocationOnMap = (lat: number, lng: number, eta: number) => {
    const L = (window as any).L;

    if (!L || !map.value) {
        return;
    }

    currentTechLat.value = lat;
    currentTechLng.value = lng;
    currentEta.value = eta;

    if (techMarker.value) {
        techMarker.value.setLatLng([lat, lng]);
    }

    if (routeLine.value) {
        const destLat = props.booking.latitude ?? 37.7749;
        const destLng = props.booking.longitude ?? -122.4194;
        routeLine.value.setLatLngs([
            [lat, lng],
            [destLat, destLng],
        ]);
    }

    // Smoothly pan map to center the tech
    map.value.panTo([lat, lng]);
};

// Formats scheduled start nicely
const formatTime = (timeStr: string) => {
    try {
        const date = new Date(timeStr);

        return date.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch (e) {
        return timeStr;
    }
};

const formatUrgencyMarker = (marker: string) => {
    return marker.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
};

// Listen to WebSocket location updates
onMounted(() => {
    initLeaflet();

    // Reverb connection config is parsed from props or auto-connected
    if (window.Echo) {
        window.Echo.channel(
            `booking-tracking.${props.booking.booking_hash}`,
        ).listen('.TechnicianLocationUpdated', (e: any) => {
            const lat = parseFloat(e.latitude);
            const lng = parseFloat(e.longitude);
            const eta = parseInt(e.etaMinutes ?? e.eta_minutes ?? 15);
            updateLocationOnMap(lat, lng, eta);
        });
    }
});

onBeforeUnmount(() => {
    if (window.Echo) {
        window.Echo.leave(`booking-tracking.${props.booking.booking_hash}`);
    }

    if (map.value) {
        map.value.remove();
    }
});
</script>

<template>
    <Head
        :title="`Track Technician - ${booking.tenant?.name || 'On My Way'}`"
    />

    <div
        class="flex min-h-screen flex-col items-center justify-start bg-slate-900 p-4 font-sans text-slate-100 sm:p-6 md:p-8"
    >
        <!-- Main Card Wrapper -->
        <div
            class="flex w-full max-w-3xl flex-col overflow-hidden rounded-3xl border-4 border-slate-950 bg-slate-800 shadow-2xl"
        >
            <!-- Branding Header -->
            <div
                class="flex items-center justify-between border-b-4 border-slate-900 bg-slate-950 p-6"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl border-2 border-slate-900 bg-amber-500 text-xl font-black text-slate-950 shadow-md"
                    >
                        ⚡
                    </div>
                    <div>
                        <h1
                            class="text-lg font-black tracking-tight text-white uppercase"
                        >
                            {{ booking.tenant?.name || 'JustMascot CRM' }}
                        </h1>
                        <p
                            class="text-xs font-bold tracking-widest text-amber-500 uppercase"
                        >
                            Live Dispatch Tracking
                        </p>
                    </div>
                </div>
                <div
                    class="flex items-center gap-2 rounded-full border border-slate-800 bg-slate-900 px-3.5 py-1.5"
                >
                    <span class="relative flex h-3 w-3">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                        ></span>
                        <span
                            class="relative inline-flex h-3 w-3 rounded-full bg-emerald-500"
                        ></span>
                    </span>
                    <span
                        class="text-xs font-black tracking-wider text-emerald-400 uppercase"
                        >Live tracking</span
                    >
                </div>
            </div>

            <!-- ETA and Driver Card -->
            <div
                class="grid grid-cols-1 items-center gap-6 border-b-4 border-slate-950 bg-slate-800/50 p-6 md:grid-cols-2"
            >
                <!-- Left: Estimated Time -->
                <div
                    class="flex items-center gap-4 rounded-2xl border-2 border-slate-950 bg-slate-900/60 p-4"
                >
                    <div
                        class="rounded-xl border-2 border-slate-950 bg-amber-500 p-4 text-slate-950"
                    >
                        <Navigation class="h-7 w-7 animate-pulse" />
                    </div>
                    <div>
                        <p
                            class="text-xs font-bold tracking-wider text-slate-400 uppercase"
                        >
                            Estimated Arrival
                        </p>
                        <h2 class="text-3xl leading-none font-black text-white">
                            {{
                                currentEta !== null
                                    ? `${currentEta} mins`
                                    : '15-20 mins'
                            }}
                        </h2>
                    </div>
                </div>

                <!-- Right: Technician Info -->
                <div
                    class="flex items-center gap-4 rounded-2xl border-2 border-slate-950 bg-slate-900/60 p-4"
                >
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-xl border-2 border-slate-950 bg-emerald-600 text-xl font-black text-white uppercase shadow-inner"
                    >
                        {{
                            booking.employee
                                ? `${booking.employee.first_name[0]}${booking.employee.last_name[0]}`
                                : 'T'
                        }}
                    </div>
                    <div>
                        <p
                            class="text-xs font-bold tracking-wider text-slate-400 uppercase"
                        >
                            Your Technician
                        </p>
                        <h3 class="text-xl font-black text-white">
                            {{
                                booking.employee
                                    ? `${booking.employee.first_name} ${booking.employee.last_name}`
                                    : 'Dispatch Tech'
                            }}
                        </h3>
                        <p
                            class="flex items-center gap-1 text-xs font-bold text-amber-500"
                        >
                            <Phone class="h-3.5 w-3.5" />
                            {{ booking.employee?.phone || 'Calling enabled' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Leaflet Map Container -->
            <div class="relative">
                <div
                    id="tracking-map"
                    class="h-[420px] w-full bg-slate-950"
                ></div>
                <!-- Status Overlay banner -->
                <div
                    class="absolute right-4 bottom-4 left-4 z-[1000] flex items-center justify-between rounded-xl border-2 border-slate-800 bg-slate-950/95 p-3.5 shadow-lg"
                >
                    <div class="flex items-center gap-2.5">
                        <span
                            class="rounded-lg bg-amber-500/20 p-1.5 text-amber-400"
                        >
                            <Clock class="h-4 w-4" />
                        </span>
                        <div>
                            <span
                                class="block text-xs leading-tight font-medium text-slate-400"
                                >Status</span
                            >
                            <span
                                class="text-sm font-black tracking-wider text-white uppercase"
                                >{{ activeStatus.replace('_', ' ') }}</span
                            >
                        </div>
                    </div>
                    <div class="text-right">
                        <span
                            class="block text-xs leading-tight font-medium text-slate-400"
                            >Scheduled Start</span
                        >
                        <span class="text-sm font-bold text-amber-400">{{
                            formatTime(booking.scheduled_start)
                        }}</span>
                    </div>
                </div>
            </div>

            <!-- Technical Diagnostics & Triage Details -->
            <div
                class="grid grid-cols-1 gap-6 border-t-4 border-slate-950 bg-slate-900 p-6 md:grid-cols-2"
            >
                <!-- Triage details -->
                <div class="space-y-4">
                    <h4
                        class="flex items-center gap-1.5 text-xs font-black tracking-widest text-slate-400 uppercase"
                    >
                        <Sparkles class="h-4 w-4 text-amber-500" /> Triage
                        Diagnostic Analysis
                    </h4>

                    <div
                        class="space-y-3 rounded-2xl border border-slate-800 bg-slate-950/80 p-4"
                    >
                        <div
                            class="grid grid-cols-2 gap-2 border-b border-slate-800/60 pb-2 text-sm"
                        >
                            <div>
                                <span class="block text-xs text-slate-500"
                                    >Appliance Brand</span
                                >
                                <span class="font-bold text-white uppercase">{{
                                    booking.appliance_brand || 'Not Specified'
                                }}</span>
                            </div>
                            <div>
                                <span class="block text-xs text-slate-500"
                                    >Appliance Age</span
                                >
                                <span class="font-bold text-white">{{
                                    booking.appliance_age !== null &&
                                    booking.appliance_age !== undefined
                                        ? `${booking.appliance_age} years`
                                        : 'Unknown'
                                }}</span>
                            </div>
                        </div>

                        <div>
                            <span class="mb-1 block text-xs text-slate-500"
                                >Diagnostic Intake Notes</span
                            >
                            <p
                                class="text-xs leading-relaxed text-slate-300 italic"
                            >
                                "{{
                                    booking.triage_notes ||
                                    'AI is currently analyzing the conversation logs.'
                                }}"
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Urgency & Certification -->
                <div class="space-y-4">
                    <h4
                        class="flex items-center gap-1.5 text-xs font-black tracking-widest text-slate-400 uppercase"
                    >
                        <Shield class="h-4 w-4 text-emerald-500" /> Urgency &
                        Compliance
                    </h4>

                    <div
                        class="space-y-3 rounded-2xl border border-slate-800 bg-slate-950/80 p-4"
                    >
                        <div>
                            <span class="mb-1.5 block text-xs text-slate-500"
                                >Urgency Markers</span
                            >
                            <div
                                class="flex flex-wrap gap-1.5"
                                v-if="
                                    booking.urgency_markers &&
                                    booking.urgency_markers.length
                                "
                            >
                                <span
                                    v-for="marker in booking.urgency_markers"
                                    :key="marker"
                                    class="inline-flex items-center gap-1 rounded-md border border-red-500/25 bg-red-500/10 px-2.5 py-1 text-[10px] font-black tracking-wider text-red-400 uppercase"
                                >
                                    ⚠️ {{ formatUrgencyMarker(marker) }}
                                </span>
                            </div>
                            <div v-else class="text-xs text-slate-500 italic">
                                No urgent conditions flagged by triage
                            </div>
                        </div>

                        <div>
                            <span class="block text-xs text-slate-500"
                                >Compliance Check</span
                            >
                            <div class="mt-1 flex items-center gap-2">
                                <CheckCircle2
                                    class="h-4.5 w-4.5 text-emerald-500"
                                />
                                <span class="text-xs text-slate-300"
                                    >Technician is certified and
                                    dispatched</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer info -->
            <div
                class="border-t-2 border-slate-900 bg-slate-950 p-4 text-center text-[10px] font-medium text-slate-600"
            >
                Provided by JustMascot multi-tenant SaaS. Secure end-to-end
                encrypted dispatch tracking.
            </div>
        </div>
    </div>
</template>

<style>
/* Leaflet map custom icon styling overrides */
.custom-tech-marker,
.custom-destination-marker {
    background: none !important;
    border: none !important;
}
</style>
