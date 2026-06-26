<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useEcho } from '@laravel/echo-vue';
import { MapPin, Navigation, Phone, Calendar, Clock, Sparkles, CheckCircle2, Shield } from '@lucide/vue';

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
const currentTechLat = ref<number | null>(props.booking.employee?.latitude ?? null);
const currentTechLng = ref<number | null>(props.booking.employee?.longitude ?? null);
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
    if (!L || !document.getElementById('tracking-map')) return;

    if (map.value) {
        map.value.remove();
        map.value = null;
    }

    // Default target coordinates (Destination)
    const destLat = props.booking.latitude ?? 37.7749;
    const destLng = props.booking.longitude ?? -122.4194;

    // Tech start coordinates
    const startLat = currentTechLat.value ?? (destLat - 0.015);
    const startLng = currentTechLng.value ?? (destLng - 0.012);

    map.value = L.map('tracking-map', {
        zoomControl: true,
        attributionControl: false,
    }).setView([startLat, startLng], 14);

    // Highly saturated voyager tiles styling for visual excellence
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        maxZoom: 19,
    }).addTo(map.value);

    // Custom Job Destination icon
    const destIcon = L.divIcon({
        html: `<div class="w-9 h-9 rounded-full border-4 border-slate-900 bg-emerald-500 flex items-center justify-center font-black text-white shadow-lg animate-pulse" style="box-shadow: 2px 2px 0px #0f172a;">📍</div>`,
        className: 'custom-destination-marker',
        iconSize: [36, 36],
        iconAnchor: [18, 18]
    });

    destMarker.value = L.marker([destLat, destLng], { icon: destIcon })
        .addTo(map.value)
        .bindPopup(`<strong>Your Location</strong><br>${props.booking.job_details}`)
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
        iconAnchor: [22, 22]
    });

    techMarker.value = L.marker([startLat, startLng], { icon: techIcon })
        .addTo(map.value)
        .bindPopup(`<strong>${props.booking.employee?.first_name || 'Technician'}</strong> is on the way!`);

    // Draw route line
    routeLine.value = L.polyline([[startLat, startLng], [destLat, destLng]], {
        color: '#F59E0B',
        weight: 5,
        dashArray: '10, 8',
        opacity: 0.8
    }).addTo(map.value);

    // Zoom map to fit both markers
    const group = new L.featureGroup([destMarker.value, techMarker.value]);
    map.value.fitBounds(group.getBounds().pad(0.2));
};

const updateLocationOnMap = (lat: number, lng: number, eta: number) => {
    const L = (window as any).L;
    if (!L || !map.value) return;

    currentTechLat.value = lat;
    currentTechLng.value = lng;
    currentEta.value = eta;

    if (techMarker.value) {
        techMarker.value.setLatLng([lat, lng]);
    }

    if (routeLine.value) {
        const destLat = props.booking.latitude ?? 37.7749;
        const destLng = props.booking.longitude ?? -122.4194;
        routeLine.value.setLatLngs([[lat, lng], [destLat, destLng]]);
    }

    // Smoothly pan map to center the tech
    map.value.panTo([lat, lng]);
};

// Formats scheduled start nicely
const formatTime = (timeStr: string) => {
    try {
        const date = new Date(timeStr);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } catch (e) {
        return timeStr;
    }
};

const formatUrgencyMarker = (marker: string) => {
    return marker.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
};

// Listen to WebSocket location updates
onMounted(() => {
    initLeaflet();

    // Reverb connection config is parsed from props or auto-connected
    if (window.Echo) {
        window.Echo.channel(`booking-tracking.${props.booking.booking_hash}`)
            .listen('.TechnicianLocationUpdated', (e: any) => {
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
    <Head :title="`Track Technician - ${booking.tenant?.name || 'On My Way'}`" />

    <div class="min-h-screen bg-slate-900 text-slate-100 font-sans flex flex-col items-center justify-start p-4 sm:p-6 md:p-8">
        <!-- Main Card Wrapper -->
        <div class="w-full max-w-3xl bg-slate-800 rounded-3xl border-4 border-slate-950 overflow-hidden shadow-2xl flex flex-col">
            
            <!-- Branding Header -->
            <div class="bg-slate-950 p-6 border-b-4 border-slate-900 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-amber-500 rounded-xl flex items-center justify-center font-black text-slate-950 text-xl shadow-md border-2 border-slate-900">
                        ⚡
                    </div>
                    <div>
                        <h1 class="text-lg font-black tracking-tight text-white uppercase">{{ booking.tenant?.name || 'BusinessCalls CRM' }}</h1>
                        <p class="text-xs text-amber-500 font-bold tracking-widest uppercase">Live Dispatch Tracking</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 bg-slate-900 px-3.5 py-1.5 rounded-full border border-slate-800">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <span class="text-xs font-black text-emerald-400 uppercase tracking-wider">Live tracking</span>
                </div>
            </div>

            <!-- ETA and Driver Card -->
            <div class="p-6 bg-slate-800/50 border-b-4 border-slate-950 grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                <!-- Left: Estimated Time -->
                <div class="flex items-center gap-4 bg-slate-900/60 p-4 rounded-2xl border-2 border-slate-950">
                    <div class="p-4 bg-amber-500 rounded-xl border-2 border-slate-950 text-slate-950">
                        <Navigation class="w-7 h-7 animate-pulse" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Estimated Arrival</p>
                        <h2 class="text-3xl font-black text-white leading-none">
                            {{ currentEta !== null ? `${currentEta} mins` : '15-20 mins' }}
                        </h2>
                    </div>
                </div>

                <!-- Right: Technician Info -->
                <div class="flex items-center gap-4 bg-slate-900/60 p-4 rounded-2xl border-2 border-slate-950">
                    <div class="h-14 w-14 bg-emerald-600 rounded-xl border-2 border-slate-950 flex items-center justify-center font-black text-white text-xl uppercase shadow-inner">
                        {{ booking.employee ? `${booking.employee.first_name[0]}${booking.employee.last_name[0]}` : 'T' }}
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Your Technician</p>
                        <h3 class="text-xl font-black text-white">{{ booking.employee ? `${booking.employee.first_name} ${booking.employee.last_name}` : 'Dispatch Tech' }}</h3>
                        <p class="text-xs text-amber-500 font-bold flex items-center gap-1">
                            <Phone class="w-3.5 h-3.5" /> {{ booking.employee?.phone || 'Calling enabled' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Leaflet Map Container -->
            <div class="relative">
                <div id="tracking-map" class="h-[420px] w-full bg-slate-950"></div>
                <!-- Status Overlay banner -->
                <div class="absolute bottom-4 left-4 right-4 bg-slate-950/95 border-2 border-slate-800 rounded-xl p-3.5 shadow-lg flex items-center justify-between z-[1000]">
                    <div class="flex items-center gap-2.5">
                        <span class="p-1.5 bg-amber-500/20 text-amber-400 rounded-lg">
                            <Clock class="w-4 h-4" />
                        </span>
                        <div>
                            <span class="text-xs text-slate-400 block leading-tight font-medium">Status</span>
                            <span class="text-sm font-black text-white uppercase tracking-wider">{{ activeStatus.replace('_', ' ') }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-slate-400 block leading-tight font-medium">Scheduled Start</span>
                        <span class="text-sm font-bold text-amber-400">{{ formatTime(booking.scheduled_start) }}</span>
                    </div>
                </div>
            </div>

            <!-- Technical Diagnostics & Triage Details -->
            <div class="p-6 bg-slate-900 border-t-4 border-slate-950 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Triage details -->
                <div class="space-y-4">
                    <h4 class="text-xs font-black tracking-widest text-slate-400 uppercase flex items-center gap-1.5">
                        <Sparkles class="w-4 h-4 text-amber-500" /> Triage Diagnostic Analysis
                    </h4>
                    
                    <div class="bg-slate-950/80 p-4 rounded-2xl border border-slate-800 space-y-3">
                        <div class="grid grid-cols-2 gap-2 text-sm border-b border-slate-800/60 pb-2">
                            <div>
                                <span class="text-xs text-slate-500 block">Appliance Brand</span>
                                <span class="font-bold text-white uppercase">{{ booking.appliance_brand || 'Not Specified' }}</span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500 block">Appliance Age</span>
                                <span class="font-bold text-white">{{ booking.appliance_age !== null && booking.appliance_age !== undefined ? `${booking.appliance_age} years` : 'Unknown' }}</span>
                            </div>
                        </div>

                        <div>
                            <span class="text-xs text-slate-500 block mb-1">Diagnostic Intake Notes</span>
                            <p class="text-xs text-slate-300 italic leading-relaxed">
                                "{{ booking.triage_notes || 'AI is currently analyzing the conversation logs.' }}"
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Urgency & Certification -->
                <div class="space-y-4">
                    <h4 class="text-xs font-black tracking-widest text-slate-400 uppercase flex items-center gap-1.5">
                        <Shield class="w-4 h-4 text-emerald-500" /> Urgency & Compliance
                    </h4>

                    <div class="bg-slate-950/80 p-4 rounded-2xl border border-slate-800 space-y-3">
                        <div>
                            <span class="text-xs text-slate-500 block mb-1.5">Urgency Markers</span>
                            <div class="flex flex-wrap gap-1.5" v-if="booking.urgency_markers && booking.urgency_markers.length">
                                <span 
                                    v-for="marker in booking.urgency_markers" 
                                    :key="marker"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-500/10 border border-red-500/25 rounded-md text-[10px] font-black text-red-400 uppercase tracking-wider"
                                >
                                    ⚠️ {{ formatUrgencyMarker(marker) }}
                                </span>
                            </div>
                            <div v-else class="text-xs text-slate-500 italic">No urgent conditions flagged by triage</div>
                        </div>

                        <div>
                            <span class="text-xs text-slate-500 block">Compliance Check</span>
                            <div class="flex items-center gap-2 mt-1">
                                <CheckCircle2 class="w-4.5 h-4.5 text-emerald-500" />
                                <span class="text-xs text-slate-300">Technician is certified and dispatched</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer info -->
            <div class="bg-slate-950 p-4 text-center border-t-2 border-slate-900 text-[10px] text-slate-600 font-medium">
                Provided by BusinessCalls multi-tenant SaaS. Secure end-to-end encrypted dispatch tracking.
            </div>
        </div>
    </div>
</template>

<style>
/* Leaflet map custom icon styling overrides */
.custom-tech-marker, .custom-destination-marker {
    background: none !important;
    border: none !important;
}
</style>
