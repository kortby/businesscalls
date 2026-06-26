<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import {
    Map,
    Phone,
    User,
    Calendar,
    Activity,
    Play,
    RefreshCw,
    Check,
    Navigation,
    UserCheck,
    AlertCircle,
    Info,
} from '@lucide/vue';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';
import { callStore } from '@/lib/store';

const props = defineProps<{
    bookings: any[];
    calls: any[];
    technicians: any[];
}>();

const page = usePage();
const tenantId = page.props.auth?.user?.tenant_id;

// Reactive local copies of data to allow instant simulated updates
const localBookings = ref([...props.bookings]);
const localCalls = ref([...props.calls]);
const localTechnicians = ref([...props.technicians]);

const mascotState = ref(0); // 0 = Idle, 1 = Scanning, 2 = Victory, 3 = Error
const selectedNode = ref<{ type: string; data: any } | null>(null);
const logs = ref<
    Array<{
        id: string;
        time: string;
        msg: string;
        type: 'info' | 'success' | 'warning';
    }>
>([]);
let simulationInterval: any = null;

const map = ref<any>(null);
const markers = ref<any[]>([]);
const routes = ref<any[]>([]);

// Add log entry helper
const addLog = (msg: string, type: 'info' | 'success' | 'warning' = 'info') => {
    const time = new Date().toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
    logs.value.unshift({
        id: Math.random().toString(36).substring(2, 9),
        time,
        msg,
        type,
    });
};

// Dynamic Leaflet Loading and Mounting
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
    if (!L || !document.getElementById('live-dispatch-map')) return;

    if (map.value) {
        map.value.remove();
        map.value = null;
    }

    map.value = L.map('live-dispatch-map', {
        zoomControl: false,
        attributionControl: false,
    }).setView([37.7749, -122.4194], 13);

    // Highly saturated voyager tiles styling
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        maxZoom: 19,
    }).addTo(map.value);

    L.control.zoom({ position: 'bottomright' }).addTo(map.value);

    refreshMapMarkers();
};

const refreshMapMarkers = () => {
    const L = (window as any).L;
    if (!L || !map.value) return;

    markers.value.forEach(m => m.remove());
    markers.value = [];
    routes.value.forEach(r => r.remove());
    routes.value = [];

    // Draw Bookings Pins
    localBookings.value.forEach(booking => {
        const lat = booking.latitude ?? (37.7749 + (booking.id % 20) * 0.0015 - 0.015);
        const lng = booking.longitude ?? (-122.4194 + (booking.id % 15) * 0.0015 - 0.012);

        const color = booking.priority_state === 'emergency' 
            ? '#EF4444' 
            : (booking.status === 'completed' ? '#10B981' : '#3B82F6');

        const bookingIcon = L.divIcon({
            html: `<div class="w-8 h-8 rounded-full border-4 border-slate-900 bg-white flex items-center justify-center font-black text-[10px]" style="color: ${color}; box-shadow: 2px 2px 0px #0f172a;">#${booking.id}</div>`,
            className: 'custom-booking-marker',
            iconSize: [32, 32]
        });

        const marker = L.marker([lat, lng], { icon: bookingIcon })
            .addTo(map.value)
            .on('click', () => {
                selectedNode.value = { type: 'booking', data: booking };
            });

        markers.value.push(marker);
    });

    // Draw Technicians & Route polylines
    localTechnicians.value.forEach(tech => {
        const tLat = tech.latitude ?? (37.7749 + (tech.id % 10) * 0.002 - 0.01);
        const tLng = tech.longitude ?? (-122.4194 + (tech.id % 8) * 0.002 - 0.008);

        const statusColor = tech.status === 'en_route' 
            ? '#F59E0B' 
            : (tech.status === 'on_site' ? '#EF4444' : '#10B981');

        const initials = `${tech.first_name[0]}${tech.last_name[0]}`;

        const techIcon = L.divIcon({
            html: `<div class="w-10 h-10 rounded-full border-4 border-slate-900 flex items-center justify-center font-black text-white text-xs shadow-md" style="background-color: ${statusColor}; box-shadow: 3px 3px 0px #0f172a;">${initials}</div>`,
            className: 'custom-tech-marker',
            iconSize: [40, 40]
        });

        const marker = L.marker([tLat, tLng], { icon: techIcon })
            .addTo(map.value)
            .on('click', () => {
                selectedNode.value = { type: 'tech', data: tech };
            });

        markers.value.push(marker);

        // Find active bookings for this tech and connect route polylines
        const techBookings = localBookings.value.filter(b => b.employee_id === tech.id && b.status === 'booked');
        if (techBookings.length > 0) {
            const routePoints = [[tLat, tLng]];
            techBookings.forEach(tb => {
                const bLat = tb.latitude ?? (37.7749 + (tb.id % 20) * 0.0015 - 0.015);
                const bLng = tb.longitude ?? (-122.4194 + (tb.id % 15) * 0.0015 - 0.012);
                routePoints.push([bLat, bLng]);
            });

            const polyline = L.polyline(routePoints, {
                color: techBookings.some(b => b.priority_state === 'emergency') ? '#EF4444' : '#3B82F6',
                weight: 4,
                dashArray: '8, 6',
                opacity: 0.85
            }).addTo(map.value);

            routes.value.push(polyline);
        }
    });
};

watch([localBookings, localTechnicians], () => {
    refreshMapMarkers();
}, { deep: true });

// WebSocket integration using useEcho
onMounted(() => {
    addLog('Live Dispatch Map system initialized.', 'success');
    initLeaflet();

    if (tenantId) {
        // 1. Listening to dispatch/booking updates
        useEcho(`tenant.${tenantId}`, 'dispatch.updated', (payload: any) => {
            addLog(
                `Websocket update: ${payload.message || 'Dispatch status changed'}`,
                payload.type === 'error' ? 'warning' : 'info',
            );

            // Mascot state transitions based on dispatch payload
            if (payload.type === 'route_rebalanced' || payload.booking?.priority_state === 'emergency') {
                mascotState.value = 1; // Scanning Radar
            } else if (payload.type === 'success' || payload.type === 'booking_confirmed') {
                mascotState.value = 2; // Victory
                setTimeout(() => {
                    if (mascotState.value === 2) mascotState.value = 0;
                }, 4000);
            } else if (payload.type === 'error' || payload.type === 'route_failed') {
                mascotState.value = 3; // Sad Error
            }

            if (payload.booking) {
                const idx = localBookings.value.findIndex(
                    (b) => b.id === payload.booking.id,
                );
                if (idx !== -1) {
                    localBookings.value[idx] = payload.booking;
                } else {
                    localBookings.value.unshift(payload.booking);
                }
            }
        });

        // 2. Listening to live call started event
        useEcho(`tenant.${tenantId}`, 'CallStarted', (payload: any) => {
            addLog(
                `Incoming call started from ${payload.customer_phone || 'Customer'}`,
                'warning',
            );
            mascotState.value = 1; // Scanning Radar

            const call = {
                id: payload.id || Math.floor(Math.random() * 10000),
                call_id: payload.call_id || 'call_live',
                customer_phone: payload.customer_phone || 'Unknown',
                status: 'ongoing',
                created_at: new Date().toISOString(),
            };

            if (!localCalls.value.some((c) => c.call_id === call.call_id)) {
                localCalls.value.unshift(call);
            }
        });

        // 3. Listening to live call ended event
        useEcho(`tenant.${tenantId}`, 'CallEnded', (payload: any) => {
            addLog(`Call ${payload.call_id || 'active'} ended`, 'info');
            mascotState.value = 0;

            const existing = localCalls.value.find(
                (c) => c.call_id === payload.call_id,
            );
            if (existing) {
                existing.status = 'completed';
            }
        });
    }
});

onUnmounted(() => {
    stopCallSimulation();
    if (map.value) {
        map.value.remove();
    }
});

// Simulation Helpers
const startCallSimulation = () => {
    stopCallSimulation();
    mascotState.value = 1; // Scanning radar animation
    callStore.isSpeaking = true;
    addLog('Simulation: Incoming Call webhook triggered.', 'warning');

    const simulatedCallId = `call_sim_${Math.floor(Math.random() * 9000) + 1000}`;
    const randomPhone = `+1 (555) 902-${Math.floor(Math.random() * 9000) + 1000}`;

    const newCall = {
        id: Math.floor(Math.random() * 10000),
        call_id: simulatedCallId,
        customer_phone: randomPhone,
        status: 'ongoing',
        created_at: new Date().toISOString(),
    };

    localCalls.value.unshift(newCall);
    selectedNode.value = { type: 'call', data: newCall };

    simulationInterval = setInterval(() => {
        callStore.amplitude = 0.1 + Math.random() * 0.75;
    }, 150);
};

const stopCallSimulation = () => {
    if (simulationInterval) {
        clearInterval(simulationInterval);
        simulationInterval = null;
    }
    callStore.isSpeaking = false;
    callStore.amplitude = 0;
};

const simulateBookingConfirmation = () => {
    mascotState.value = 2; // Celebratory victory dance
    addLog('Simulation: New job dispatch booking confirmed!', 'success');

    const randomTech =
        localTechnicians.value[
            Math.floor(Math.random() * localTechnicians.value.length)
        ] || null;
    const randomPhone = `+1 (555) 303-${Math.floor(Math.random() * 9000) + 1000}`;

    const newBooking = {
        id: Math.floor(Math.random() * 10000),
        customer_phone: randomPhone,
        job_details: 'Routine Maintenance Agreement',
        status: 'booked',
        priority_state: 'routine_maintenance',
        scheduled_start: new Date().toISOString(),
        employee_id: randomTech ? randomTech.id : null,
        employee: randomTech,
        created_at: new Date().toISOString(),
        latitude: 37.7749 + (Math.random() * 0.02 - 0.01),
        longitude: -122.4194 + (Math.random() * 0.02 - 0.01),
    };

    localBookings.value.unshift(newBooking);
    selectedNode.value = { type: 'booking', data: newBooking };

    if (randomTech) {
        const tIdx = localTechnicians.value.findIndex(
            (t) => t.id === randomTech.id,
        );
        if (tIdx !== -1) {
            localTechnicians.value[tIdx].status = 'en_route';
        }
    }

    setTimeout(() => {
        if (mascotState.value === 2) {
            mascotState.value = 0;
        }
    }, 4000);
};

// Simulate dynamic emergency insertion and schedule shift re-balancing
const simulateEmergencyInsertion = () => {
    mascotState.value = 1; // Scanning Radar
    addLog('Simulation: Emergency Call Triage matching unassigned HVAC problem...', 'warning');

    const randomTech =
        localTechnicians.value[
            Math.floor(Math.random() * localTechnicians.value.length)
        ] || null;

    if (!randomTech) return;

    const emergencyBooking = {
        id: Math.floor(Math.random() * 10000),
        customer_phone: `+1 (555) 911-${Math.floor(Math.random() * 9000) + 1000}`,
        job_details: 'AC Refrigerant Leak & Blowout (Requires EPA_608)',
        status: 'booked',
        priority_state: 'emergency',
        required_certification: 'EPA_608',
        scheduled_start: new Date().toISOString(),
        employee_id: randomTech.id,
        employee: randomTech,
        created_at: new Date().toISOString(),
        latitude: 37.7749 + (Math.random() * 0.01 - 0.005),
        longitude: -122.4194 + (Math.random() * 0.01 - 0.005),
    };

    // Find and push back any subsequent bookings for this technician
    localBookings.value.forEach(b => {
        if (b.employee_id === randomTech.id && b.status === 'booked' && b.id !== emergencyBooking.id) {
            const oldDate = new Date(b.scheduled_start);
            b.scheduled_start = new Date(oldDate.getTime() + 120 * 60000).toISOString();
            addLog(`Simulation: Pushed back Job #${b.id} by 120 minutes due to Emergency Insertion.`, 'info');
        }
    });

    localBookings.value.unshift(emergencyBooking);
    selectedNode.value = { type: 'booking', data: emergencyBooking };

    randomTech.status = 'en_route';

    addLog(`Simulation: Emergency inserted at front. Technician ${randomTech.first_name} routed.`, 'success');
};

const simulateTechnicianStatusUpdate = () => {
    if (localTechnicians.value.length === 0) return;

    const statuses = ['idle', 'en_route', 'on_site', 'completed'];
    const randomTech =
        localTechnicians.value[
            Math.floor(Math.random() * localTechnicians.value.length)
        ];
    const nextStatus = statuses[Math.floor(Math.random() * statuses.length)];

    randomTech.status = nextStatus;

    if (nextStatus === 'on_site' || nextStatus === 'completed') {
        mascotState.value = 2; // Victory confetti
        setTimeout(() => {
            if (mascotState.value === 2) mascotState.value = 0;
        }, 4000);
    }

    addLog(
        `Simulation: Technician ${randomTech.first_name} is now ${nextStatus.replace('_', ' ')}.`,
        'info',
    );
    selectedNode.value = { type: 'tech', data: randomTech };
};

// Simulate Route Deviation & Uncertified matches
const simulateRouteDeviationError = () => {
    mascotState.value = 3; // Sad Error Mascot State
    addLog('Simulation Error: Uncertified technician mismatch or route deviation detected!', 'warning');
};

const resetSimulation = () => {
    stopCallSimulation();
    mascotState.value = 0;
    localBookings.value = [...props.bookings];
    localCalls.value = [...props.calls];
    localTechnicians.value = [...props.technicians];
    selectedNode.value = null;
    logs.value = [];
    addLog('Simulation state reset to default database records.', 'info');
};
</script>

<template>
    <Head title="Live Dispatch Map" />

    <div
        class="min-h-screen bg-[#F0FDF4] p-6 text-slate-900 dark:bg-slate-950 dark:text-slate-100"
    >
        <!-- Duolingo Styled Header -->
        <header
            class="mb-8 flex flex-col gap-4 rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] sm:flex-row sm:items-center sm:justify-between dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
        >
            <div class="flex items-center gap-4">
                <div
                    class="rounded-2xl border-4 border-slate-900 bg-amber-400 p-3 text-slate-950 dark:border-slate-100"
                >
                    <Map class="h-8 w-8 stroke-[3]" />
                </div>
                <div>
                    <h1
                        class="text-2xl font-black tracking-tight uppercase sm:text-3xl"
                    >
                        Live Dispatch Board
                    </h1>
                    <p
                        class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                    >
                        Geo-Tracking, Reverb Listeners & Mascot States
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                <div
                    class="inline-flex items-center rounded-2xl border-4 border-slate-900 bg-emerald-500 px-4 py-2 text-sm font-black text-white uppercase dark:border-slate-100"
                >
                    <span
                        class="mr-2 h-2.5 w-2.5 animate-ping rounded-full bg-white"
                    ></span>
                    Live Streaming
                </div>
            </div>
        </header>

        <!-- Quick Stats Banner -->
        <div class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-3">
            <div
                class="flex items-center gap-4 rounded-2xl border-4 border-slate-900 bg-white p-4 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
            >
                <div
                    class="rounded-xl border-4 border-slate-900 bg-blue-400 p-2.5 text-slate-950"
                >
                    <Calendar class="h-6 w-6 stroke-[3]" />
                </div>
                <div>
                    <div class="text-2xl font-black">
                        {{ localBookings.length }}
                    </div>
                    <div
                        class="text-xs font-bold text-slate-500 uppercase dark:text-slate-400"
                    >
                        Booked Jobs
                    </div>
                </div>
            </div>

            <div
                class="flex items-center gap-4 rounded-2xl border-4 border-slate-900 bg-white p-4 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
            >
                <div
                    class="rounded-xl border-4 border-slate-900 bg-emerald-400 p-2.5 text-slate-950"
                >
                    <Activity class="h-6 w-6 stroke-[3]" />
                </div>
                <div>
                    <div class="text-2xl font-black">
                        {{
                            localCalls.filter((c) => c.status === 'ongoing')
                                .length
                        }}
                    </div>
                    <div
                        class="text-xs font-bold text-slate-500 uppercase dark:text-slate-400"
                    >
                        Ongoing Calls
                    </div>
                </div>
            </div>

            <div
                class="flex items-center gap-4 rounded-2xl border-4 border-slate-900 bg-white p-4 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
            >
                <div
                    class="rounded-xl border-4 border-slate-900 bg-yellow-400 p-2.5 text-slate-950"
                >
                    <User class="h-6 w-6 stroke-[3]" />
                </div>
                <div>
                    <div class="text-2xl font-black">
                        {{ localTechnicians.length }}
                    </div>
                    <div
                        class="text-xs font-bold text-slate-500 uppercase dark:text-slate-400"
                    >
                        Technicians
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Layout Grid -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- Left & Middle: Leaflet Map & Dispatch Status -->
            <div class="flex flex-col gap-8 lg:col-span-2">
                <!-- Live Leaflet Map Container -->
                <div
                    class="relative flex flex-col rounded-3xl border-4 border-slate-900 bg-white p-4 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-black tracking-tight uppercase">
                            Interactive Dispatch Radar
                        </h2>
                        <span class="text-xs font-bold text-slate-400 uppercase"
                            >Saturated Geographic HUD</span
                        >
                    </div>

                    <!-- Map container element -->
                    <div
                        class="relative overflow-hidden rounded-2xl border-4 border-slate-900 bg-emerald-50/50 dark:bg-slate-950 z-0"
                    >
                        <div id="live-dispatch-map"></div>
                    </div>

                    <!-- Coordinates Map Legend -->
                    <div
                        class="mt-4 flex flex-wrap gap-4 border-t-2 border-slate-900/10 pt-4 text-xs font-bold dark:border-slate-100/10"
                    >
                        <div class="flex items-center gap-1.5">
                            <span
                                class="h-3.5 w-3.5 rounded-full border-2 border-slate-900 bg-[#3B82F6]"
                            ></span>
                            <span>Pending Job</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span
                                class="h-3.5 w-3.5 rounded-full border-2 border-slate-900 bg-[#EF4444]"
                            ></span>
                            <span>Emergency Job</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span
                                class="h-3.5 w-3.5 rounded-full border-2 border-slate-900 bg-[#10B981]"
                            ></span>
                            <span>Completed Job</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span
                                class="h-3.5 w-3.5 rounded-full border-2 border-slate-900 bg-[#F59E0B]"
                            ></span>
                            <span>Tech: En Route</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span
                                class="h-3.5 w-3.5 rounded-full border-2 border-slate-900 bg-[#EF4444]"
                            ></span>
                            <span>Tech: On Site</span>
                        </div>
                    </div>
                </div>

                <!-- Selected Node Drawer/Panel -->
                <div
                    v-if="selectedNode"
                    class="animate-in rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] duration-200 fade-in slide-in-from-bottom-2 dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                >
                    <div
                        class="mb-4 flex items-center justify-between border-b-4 border-slate-900 pb-4 dark:border-slate-100"
                    >
                        <div class="flex items-center gap-2">
                            <Info class="h-5 w-5 stroke-[3] text-amber-500" />
                            <h3
                                class="text-md font-black tracking-tight uppercase"
                            >
                                {{
                                    selectedNode.type === 'tech'
                                        ? 'Technician Details'
                                        : selectedNode.type === 'booking'
                                          ? 'Booking Details'
                                          : 'Active Call Details'
                                }}
                            </h3>
                        </div>
                        <button
                            @click="selectedNode = null"
                            class="rounded-md border-2 border-slate-900 px-2.5 py-1 text-xs font-black uppercase hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            Close
                        </button>
                    </div>

                    <!-- Technician Information -->
                    <div
                        v-if="selectedNode.type === 'tech'"
                        class="grid grid-cols-1 gap-4 text-sm font-bold sm:grid-cols-2"
                    >
                        <div>
                            <div class="text-xs text-slate-400 uppercase">
                                Name
                            </div>
                            <div class="text-base font-black">
                                {{ selectedNode.data.first_name }}
                                {{ selectedNode.data.last_name }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400 uppercase">
                                Phone
                            </div>
                            <div class="text-base">
                                {{ selectedNode.data.phone }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400 uppercase">
                                Status
                            </div>
                            <div
                                class="inline-block rounded-lg border-2 border-slate-900 px-2.5 py-0.5 capitalize text-slate-950"
                                :class="[
                                    selectedNode.data.status === 'en_route'
                                        ? 'bg-amber-400'
                                        : selectedNode.data.status === 'on_site'
                                          ? 'bg-rose-400'
                                          : 'bg-emerald-400',
                                ]"
                            >
                                {{ selectedNode.data.status }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400 uppercase">
                                Skills & Licenses
                            </div>
                            <div class="mt-1 flex flex-wrap gap-1">
                                <span
                                    v-for="skill in selectedNode.data.skills"
                                    :key="skill"
                                    class="rounded border border-slate-900 px-1.5 py-0.5 text-xs bg-slate-50 dark:bg-slate-800"
                                >
                                    {{ skill }}
                                </span>
                                <span
                                    v-for="cert in (selectedNode.data.certifications || [])"
                                    :key="cert"
                                    class="rounded border-2 border-slate-950 bg-amber-200 text-slate-950 px-1.5 py-0.5 text-xs font-black uppercase"
                                >
                                    {{ cert }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Information -->
                    <div
                        v-if="selectedNode.type === 'booking'"
                        class="grid grid-cols-1 gap-4 text-sm font-bold sm:grid-cols-2"
                    >
                        <div>
                            <div class="text-xs text-slate-400 uppercase">
                                Job Details
                            </div>
                            <div class="text-base font-black">
                                {{ selectedNode.data.job_details }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400 uppercase">
                                Customer Phone
                            </div>
                            <div class="text-base">
                                {{ selectedNode.data.customer_phone }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400 uppercase">
                                Priority State
                            </div>
                            <div
                                class="inline-block rounded-lg border-2 border-slate-900 px-2.5 py-0.5 uppercase text-slate-950"
                                :class="[
                                    selectedNode.data.priority_state === 'emergency' ? 'bg-red-400' : 'bg-blue-300'
                                ]"
                            >
                                {{ selectedNode.data.priority_state || 'routine_maintenance' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400 uppercase">
                                Assigned Technician
                            </div>
                            <div class="text-base font-black text-amber-500">
                                {{
                                    selectedNode.data.employee
                                        ? `${selectedNode.data.employee.first_name} ${selectedNode.data.employee.last_name}`
                                        : 'Not Assigned'
                                }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Rive/Mascot feedback & Telemetry Logs -->
            <div class="flex flex-col gap-8">
                <!-- Rive Mascot Indicator Card -->
                <div
                    class="flex flex-col items-center justify-center rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                >
                    <h3
                        class="mb-4 self-start text-xs font-black tracking-widest text-slate-500 uppercase dark:text-slate-400"
                    >
                        Dispatch Assistant Mascot
                    </h3>

                    <!-- Render Mascot Component -->
                    <div class="mb-4 aspect-square w-full max-w-[240px]">
                        <DispatcherMascot
                            :state="mascotState"
                            :is-speaking="callStore.isSpeaking"
                            :amplitude="callStore.amplitude"
                        />
                    </div>

                    <div
                        class="w-full rounded-2xl border-4 border-slate-900 bg-slate-50 p-4 text-xs font-bold dark:border-slate-100 dark:bg-slate-950"
                    >
                        <div class="mb-1 flex justify-between">
                            <span>Mascot Speech:</span>
                            <span class="text-emerald-500">{{
                                callStore.isSpeaking
                                    ? 'ACTIVE SPEAKING'
                                    : 'SILENT'
                            }}</span>
                        </div>
                        <div class="mb-1 flex justify-between">
                            <span>RMS Amplitude:</span>
                            <span>{{ callStore.amplitude.toFixed(3) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Active State:</span>
                            <span class="font-black text-amber-500 uppercase"
                                >State {{ mascotState }}</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Walkthrough Simulation Panel (Duolingo Style Action Buttons) -->
                <div
                    class="rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                >
                    <h3
                        class="mb-4 text-xs font-black tracking-widest text-slate-500 uppercase dark:text-slate-400"
                    >
                        Simulation Walkthrough
                    </h3>

                    <div class="flex flex-col gap-3.5">
                        <button
                            @click="startCallSimulation"
                            class="duo-btn duo-btn-warning flex w-full items-center justify-center gap-2"
                        >
                            <Phone class="h-4 w-4 stroke-[3]" />
                            Trigger Webhook Call
                        </button>

                        <button
                            @click="simulateBookingConfirmation"
                            class="duo-btn duo-btn-success flex w-full items-center justify-center gap-2"
                        >
                            <UserCheck class="h-4 w-4 stroke-[3]" />
                            Simulate Book Confirmed
                        </button>

                        <button
                            @click="simulateEmergencyInsertion"
                            class="duo-btn duo-btn-info flex w-full items-center justify-center gap-2"
                            style="background-color: #ef4444; box-shadow: 0 4px 0 0 #b91c1c;"
                        >
                            <AlertCircle class="h-4 w-4 stroke-[3]" />
                            Simulate Emergency Insertion
                        </button>

                        <button
                            @click="simulateTechnicianStatusUpdate"
                            class="duo-btn duo-btn-info flex w-full items-center justify-center gap-2"
                        >
                            <Navigation class="h-4 w-4 stroke-[3]" />
                            Update Tech Location
                        </button>

                        <button
                            @click="simulateRouteDeviationError"
                            class="duo-btn duo-btn-muted flex w-full items-center justify-center gap-2"
                            style="background-color: #f87171; box-shadow: 0 4px 0 0 #dc2626;"
                        >
                            <AlertCircle class="h-4 w-4 stroke-[3]" />
                            Simulate Route Deviation
                        </button>

                        <button
                            @click="resetSimulation"
                            class="duo-btn duo-btn-muted flex w-full items-center justify-center gap-2"
                        >
                            <RefreshCw class="h-4 w-4 stroke-[3]" />
                            Reset Dispatch Map
                        </button>
                    </div>
                </div>

                <!-- Simulation Feed Logs -->
                <div
                    class="flex flex-col rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                >
                    <h3
                        class="mb-4 text-xs font-black tracking-widest text-slate-500 uppercase dark:text-slate-400"
                    >
                        Telemetry Activity Logs
                    </h3>

                    <div
                        class="flex max-h-[160px] flex-col gap-3 overflow-y-auto pr-1"
                    >
                        <div
                            v-for="log in logs"
                            :key="log.id"
                            class="flex items-start gap-2 border-b border-slate-900/10 pb-2 text-xs font-bold last:border-b-0 dark:border-slate-100/10"
                        >
                            <span class="text-slate-400">{{ log.time }}</span>
                            <span
                                :class="[
                                    log.type === 'success'
                                        ? 'text-emerald-500'
                                        : log.type === 'warning'
                                          ? 'text-amber-500'
                                          : 'text-blue-500',
                                ]"
                            >
                                {{ log.msg }}
                            </span>
                        </div>
                        <div
                            v-if="logs.length === 0"
                            class="py-4 text-center text-xs text-slate-400"
                        >
                            No telemetry logs generated yet.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Leaflet map container sizing & visual styling */
#live-dispatch-map {
    width: 100%;
    height: 420px;
    filter: saturate(1.8) contrast(1.15) brightness(0.95);
    background: #f0fdf4;
}

/* Duolingo Chunky Geometric Button Styling */
.duo-btn {
    border-radius: 1rem;
    border: 4px solid #0f172a;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    cursor: pointer;
}

.dark .duo-btn {
    border-color: #f1f5f9;
}

.duo-btn:hover {
    transform: translateY(2px);
}

.duo-btn:active {
    transform: translateY(4px);
}

.duo-btn-warning {
    background-color: #fbbf24;
    color: #0f172a;
    box-shadow: 0 4px 0 0 #d97706;
}
.duo-btn-warning:hover {
    box-shadow: 0 2px 0 0 #d97706;
}
.duo-btn-warning:active {
    box-shadow: 0 0px 0 0 #d97706;
}

.duo-btn-success {
    background-color: #10b981;
    color: #ffffff;
    box-shadow: 0 4px 0 0 #059669;
}
.duo-btn-success:hover {
    box-shadow: 0 2px 0 0 #059669;
}
.duo-btn-success:active {
    box-shadow: 0 0px 0 0 #059669;
}

.duo-btn-info {
    background-color: #3b82f6;
    color: #ffffff;
    box-shadow: 0 4px 0 0 #2563eb;
}
.duo-btn-info:hover {
    box-shadow: 0 2px 0 0 #2563eb;
}
.duo-btn-info:active {
    box-shadow: 0 0px 0 0 #2563eb;
}

.duo-btn-muted {
    background-color: #e2e8f0;
    color: #0f172a;
    box-shadow: 0 4px 0 0 #cbd5e1;
}
.dark .duo-btn-muted {
    background-color: #334155;
    color: #ffffff;
    box-shadow: 0 4px 0 0 #1e293b;
}
.duo-btn-muted:hover {
    box-shadow: 0 2px 0 0 #cbd5e1;
}
.dark .duo-btn-muted:hover {
    box-shadow: 0 2px 0 0 #1e293b;
}
.duo-btn-muted:active {
    box-shadow: 0 0px 0 0 #cbd5e1;
}
.dark .duo-btn-muted:active {
    box-shadow: 0 0px 0 0 #1e293b;
}
</style>
