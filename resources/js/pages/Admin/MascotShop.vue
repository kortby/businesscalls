<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    Award,
    Lock,
    Check,
    Sparkles,
    RefreshCw,
    AlertCircle,
} from '@lucide/vue';
import { ref } from 'vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Mascot Shop',
                href: '/admin/mascot-shop',
            },
        ],
    },
});

const props = defineProps<{
    totalPoints: number;
    activeSkin: string;
    purchasedSkins: string[];
}>();

const previewState = ref<number>(0);
const selectedPreviewSkin = ref<string>(props.activeSkin);

const shopItems = [
    {
        id: 'standard',
        name: 'Standard Owl',
        description: 'The classic dispatcher mascot owl with emerald styling.',
        cost: 0,
        color: 'bg-emerald-500 dark:bg-emerald-600',
        borderColor: 'border-emerald-600 dark:border-emerald-700',
        textColor: 'text-emerald-700 dark:text-emerald-300',
        skinName: 'standard',
    },
    {
        id: 'robot',
        name: 'Robotic Helper',
        description:
            'A cybernetic dispatcher with cyan visor and steel-gray alloy casing.',
        cost: 250,
        color: 'bg-slate-500 dark:bg-slate-600',
        borderColor: 'border-slate-600 dark:border-slate-700',
        textColor: 'text-slate-700 dark:text-slate-300',
        skinName: 'robot',
    },
    {
        id: 'gold',
        name: 'Golden Dispatcher',
        description:
            'A high-achieving dispatcher crafted in pure gold, wearing a crown.',
        cost: 500,
        color: 'bg-amber-500 dark:bg-amber-600',
        borderColor: 'border-amber-600 dark:border-amber-700',
        textColor: 'text-amber-700 dark:text-amber-300',
        skinName: 'gold',
    },
];

const purchaseForm = useForm({
    skin: '',
    cost: 0,
});

const handleAction = (item: (typeof shopItems)[0]) => {
    purchaseForm.skin = item.id;
    purchaseForm.cost = props.purchasedSkins.includes(item.id) ? 0 : item.cost;
    purchaseForm.post('/admin/mascot-shop/purchase', {
        preserveScroll: true,
        onSuccess: () => {
            selectedPreviewSkin.value = item.id;
        },
    });
};

const setPreviewState = (stateNum: number) => {
    previewState.value = stateNum;
};
</script>

<template>
    <Head title="Mascot Customization Shop" />

    <div class="mx-auto flex max-w-6xl flex-col gap-6 p-4 sm:p-6 md:p-8">
        <!-- Title Header (Duolingo visual guidelines) -->
        <div
            class="flex flex-col gap-4 border-b pb-6 md:flex-row md:items-center md:justify-between"
        >
            <div>
                <h1
                    class="text-3xl font-black tracking-tight text-foreground sm:text-4xl"
                >
                    Mascot Customization Shop
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Redeem your team's high performance points to unlock
                    dispatcher mascot themes.
                </p>
            </div>

            <!-- Performance Points Box (Duolingo Style: thick rounded yellow outline) -->
            <div
                class="inline-flex items-center gap-3 rounded-2xl border-4 border-amber-400 bg-amber-50 px-5 py-2.5 shadow-sm dark:bg-amber-950/20"
            >
                <Award class="h-6 w-6 animate-pulse text-amber-500" />
                <div>
                    <div
                        class="text-[10px] font-black tracking-wider text-amber-600 uppercase dark:text-amber-400"
                    >
                        Performance Balance
                    </div>
                    <div
                        class="text-xl font-black text-amber-700 dark:text-amber-300"
                    >
                        {{ totalPoints }} Points
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
            <!-- Left Side: Rive Live Preview Canvas Card -->
            <div class="flex flex-col gap-4 lg:col-span-5">
                <div
                    class="relative overflow-hidden rounded-3xl border-4 border-slate-200 bg-card p-6 shadow-md dark:border-slate-800"
                >
                    <h3
                        class="mb-4 text-xs font-black tracking-widest text-muted-foreground uppercase"
                    >
                        Live Preview (Active State Test)
                    </h3>

                    <!-- Canvas Frame -->
                    <div
                        class="mx-auto mb-6 aspect-square w-full max-w-[280px]"
                    >
                        <DispatcherMascot
                            :state="previewState"
                            :skin="selectedPreviewSkin"
                        />
                    </div>

                    <!-- State Test Toggles (Duolingo 3D Button styles) -->
                    <div class="space-y-3">
                        <span
                            class="block text-center text-xs font-black tracking-wider text-muted-foreground uppercase"
                        >
                            Test Dispatch States
                        </span>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                @click="setPreviewState(0)"
                                class="h-10 cursor-pointer rounded-xl border-2 border-b-4 border-slate-300 bg-slate-100 text-xs font-black text-slate-700 uppercase shadow-xs transition-all active:translate-y-[2px] active:border-b-2 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                :class="{
                                    'border-slate-500 bg-slate-200 dark:bg-slate-700':
                                        previewState === 0,
                                }"
                            >
                                Idle State
                            </button>
                            <button
                                @click="setPreviewState(1)"
                                class="h-10 cursor-pointer rounded-xl border-2 border-b-4 border-amber-300 bg-amber-100 text-xs font-black text-amber-700 uppercase shadow-xs transition-all active:translate-y-[2px] active:border-b-2 dark:border-amber-700 dark:bg-amber-950/20 dark:text-amber-400"
                                :class="{
                                    'border-amber-500 bg-amber-200 dark:bg-amber-900/40':
                                        previewState === 1,
                                }"
                            >
                                Scanning
                            </button>
                            <button
                                @click="setPreviewState(2)"
                                class="h-10 cursor-pointer rounded-xl border-2 border-b-4 border-emerald-300 bg-emerald-100 text-xs font-black text-emerald-700 uppercase shadow-xs transition-all active:translate-y-[2px] active:border-b-2 dark:border-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400"
                                :class="{
                                    'border-emerald-500 bg-emerald-200 dark:bg-emerald-900/40':
                                        previewState === 2,
                                }"
                            >
                                Booking Confirmed
                            </button>
                            <button
                                @click="setPreviewState(3)"
                                class="h-10 cursor-pointer rounded-xl border-2 border-b-4 border-rose-300 bg-rose-100 text-xs font-black text-rose-700 uppercase shadow-xs transition-all active:translate-y-[2px] active:border-b-2 dark:border-rose-700 dark:bg-rose-950/20 dark:text-rose-400"
                                :class="{
                                    'border-rose-500 bg-rose-200 dark:bg-rose-900/40':
                                        previewState === 3,
                                }"
                            >
                                Conflict Block
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Points info hint -->
                <div
                    class="flex gap-3 rounded-2xl border border-slate-200 bg-muted/30 p-4 dark:border-slate-800"
                >
                    <AlertCircle
                        class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground"
                    />
                    <p class="text-xs leading-relaxed text-muted-foreground">
                        Points are calculated automatically based on your
                        average **Call Quality Score (CQS)** and **CSAT
                        scores**. Deductions are applied when purchasing new
                        skins.
                    </p>
                </div>
            </div>

            <!-- Right Side: Customizer Shop Items Grid -->
            <div class="flex flex-col gap-6 lg:col-span-7">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div
                        v-for="item in shopItems"
                        :key="item.id"
                        class="relative flex flex-col justify-between rounded-3xl border-4 bg-card p-5 shadow-sm transition-all duration-300 dark:border-slate-800"
                        :class="[
                            activeSkin === item.id
                                ? 'border-indigo-400 shadow-md ring-2 ring-indigo-400/20 dark:border-indigo-600'
                                : 'border-slate-200 hover:border-slate-300 dark:hover:border-slate-700',
                        ]"
                    >
                        <!-- Active Skin Flag -->
                        <div
                            v-if="activeSkin === item.id"
                            class="absolute -top-3 right-6 rounded-full border border-indigo-600 bg-indigo-500 px-3 py-0.5 text-[10px] font-black tracking-wider text-white uppercase shadow-xs"
                        >
                            Active Skin
                        </div>

                        <div>
                            <!-- Miniature Theme Block representation -->
                            <div
                                class="relative mb-4 flex h-24 items-center justify-center overflow-hidden rounded-2xl border-2 transition-all duration-300"
                                :class="[item.color, item.borderColor]"
                            >
                                <Sparkles
                                    v-if="item.id === 'gold'"
                                    class="absolute top-2 right-2 h-5 w-5 animate-pulse text-yellow-300"
                                />
                                <span
                                    class="text-3xl font-black text-white capitalize opacity-90"
                                >
                                    {{ item.id[0] }}
                                </span>
                            </div>

                            <h3 class="text-lg font-black text-foreground">
                                {{ item.name }}
                            </h3>
                            <p
                                class="mt-1 text-xs leading-relaxed text-muted-foreground"
                            >
                                {{ item.description }}
                            </p>
                        </div>

                        <div class="mt-6 flex flex-col gap-2.5">
                            <hr
                                class="mb-1 border-slate-100 dark:border-slate-800/80"
                            />

                            <div
                                class="flex items-center justify-between text-xs font-black"
                            >
                                <span
                                    class="tracking-wider text-muted-foreground uppercase"
                                    >Redeem Cost</span
                                >
                                <span
                                    :class="
                                        item.cost === 0
                                            ? 'text-emerald-500'
                                            : 'text-slate-800 dark:text-slate-200'
                                    "
                                >
                                    {{
                                        item.cost === 0
                                            ? 'Free'
                                            : `${item.cost} Points`
                                    }}
                                </span>
                            </div>

                            <!-- Purchase / Equip Button (Duolingo 3D Button Style) -->
                            <button
                                @click="handleAction(item)"
                                :disabled="
                                    purchaseForm.processing ||
                                    (!purchasedSkins.includes(item.id) &&
                                        totalPoints < item.cost)
                                "
                                class="h-11 w-full cursor-pointer rounded-xl border-2 border-b-6 border-slate-700 bg-slate-500 text-xs font-black tracking-wider text-white uppercase shadow-sm transition-all duration-75 hover:bg-slate-400 active:mt-1 active:border-b-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="[
                                    activeSkin === item.id
                                        ? 'cursor-default border-slate-600 bg-slate-400 active:mt-0 active:border-b-6'
                                        : purchasedSkins.includes(item.id)
                                          ? 'border-indigo-700 bg-indigo-500 hover:bg-indigo-400'
                                          : 'border-amber-700 bg-amber-500 hover:bg-amber-400',
                                ]"
                            >
                                <span
                                    v-if="activeSkin === item.id"
                                    class="flex items-center justify-center gap-1"
                                >
                                    <Check class="h-4 w-4 stroke-[3]" /> Active
                                </span>
                                <span
                                    v-else-if="purchasedSkins.includes(item.id)"
                                >
                                    Equip Skin
                                </span>
                                <span
                                    v-else-if="totalPoints < item.cost"
                                    class="flex items-center justify-center gap-1"
                                >
                                    <Lock class="h-3.5 w-3.5" /> Locked
                                </span>
                                <span v-else> Unlock Skin </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Controller / testing debug panel preview -->
                <div
                    class="rounded-3xl border-4 border-dashed border-slate-200 bg-card p-6 text-center dark:border-slate-800"
                >
                    <h4
                        class="mb-2 text-sm font-black tracking-wider text-foreground uppercase"
                    >
                        Preview Skin Selector
                    </h4>
                    <p class="mb-4 text-xs text-muted-foreground">
                        Test skins locally in the canvas without redeeming
                        points.
                    </p>
                    <div class="flex flex-wrap justify-center gap-2">
                        <button
                            v-for="item in shopItems"
                            :key="'preview-' + item.id"
                            @click="selectedPreviewSkin = item.id"
                            class="cursor-pointer rounded-lg border px-4 py-2 text-xs font-black uppercase shadow-xs transition-all"
                            :class="[
                                selectedPreviewSkin === item.id
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'bg-background hover:bg-accent',
                            ]"
                        >
                            {{ item.name }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* 3D Button shadow dynamics */
button {
    transition: all 0.1s cubic-bezier(0.16, 1, 0.3, 1);
}
button:active:not(:disabled) {
    transform: translateY(4px);
    border-bottom-width: 2px;
}
</style>
