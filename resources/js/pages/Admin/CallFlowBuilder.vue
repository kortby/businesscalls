<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Activity,
    Plus,
    Trash2,
    Settings,
    CornerRightDown,
} from '@lucide/vue';
import { ref, computed } from 'vue';

interface Connection {
    condition: string;
    targetId: string;
}

interface Node {
    id: string;
    title: string;
    type: 'ivr' | 'handover' | 'tool';
    x: number;
    y: number;
    connections: Connection[];
    config: Record<string, any>;
}

const props = defineProps<{
    callFlowTree: Node[];
}>();

const nodes = ref<Node[]>(
    props.callFlowTree && props.callFlowTree.length > 0
        ? JSON.parse(JSON.stringify(props.callFlowTree))
        : [
              {
                  id: 'node_1',
                  title: 'Inbound Call Start',
                  type: 'ivr',
                  x: 50,
                  y: 50,
                  connections: [{ condition: 'Any', targetId: 'node_2' }],
                  config: { prompt: 'Welcome to HVAC Dispatch' },
              },
              {
                  id: 'node_2',
                  title: 'Route to Agent',
                  type: 'handover',
                  x: 350,
                  y: 50,
                  connections: [],
                  config: { agentId: 'agent-primary-vapi' },
              },
          ]
);

const isSaving = ref(false);
const activeNodeId = ref<string | null>(null);
const draggingNodeId = ref<string | null>(null);
const dragOffset = ref({ x: 0, y: 0 });

const activeNode = computed(() => nodes.value.find((n) => n.id === activeNodeId.value) || null);

// Add a new node to the canvas
const addNode = (type: 'ivr' | 'handover' | 'tool') => {
    const id = `node_${Date.now()}`;
    const titles = {
        ivr: 'IVR Menu (Press Digit)',
        handover: 'Subagent Handover',
        tool: 'MCP Tool / Webhook',
    };
    nodes.value.push({
        id,
        title: titles[type],
        type,
        x: 100 + Math.random() * 100,
        y: 100 + Math.random() * 100,
        connections: [],
        config: type === 'ivr' ? { digits: '1' } : type === 'handover' ? { agentId: '' } : { toolName: '' },
    });
    activeNodeId.value = id;
};

// Delete a node and references to it
const deleteNode = (id: string) => {
    nodes.value = nodes.value.filter((n) => n.id !== id);
    nodes.value.forEach((n) => {
        n.connections = n.connections.filter((c) => c.targetId !== id);
    });
    if (activeNodeId.value === id) {
        activeNodeId.value = null;
    }
};

// Mouse Drag event handlers
const startDrag = (event: MouseEvent, node: Node) => {
    draggingNodeId.value = node.id;
    dragOffset.value = {
        x: event.clientX - node.x,
        y: event.clientY - node.y,
    };
    activeNodeId.value = node.id;
};

const handleDrag = (event: MouseEvent) => {
    if (!draggingNodeId.value) return;
    const node = nodes.value.find((n) => n.id === draggingNodeId.value);
    if (node) {
        node.x = Math.max(0, event.clientX - dragOffset.value.x);
        node.y = Math.max(0, event.clientY - dragOffset.value.y);
    }
};

const endDrag = () => {
    draggingNodeId.value = null;
};

// Add connection from active node
const addConnection = () => {
    if (!activeNode.value) return;
    activeNode.value.connections.push({
        condition: '1',
        targetId: '',
    });
};

// Save call flow tree
const saveFlow = async () => {
    isSaving.value = true;
    try {
        const response = await fetch('/api/settings/call-flow', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
            body: JSON.stringify({ call_flow_tree: nodes.value }),
        });
        if (response.ok) {
            router.reload();
        }
    } catch (e) {
        console.error(e);
    } finally {
        isSaving.value = false;
    }
};
</script>

<template>
    <Head title="Visual Call Flow Builder" />

    <div
        class="min-h-screen bg-[#F0FDF4] p-6 text-slate-900 dark:bg-slate-950 dark:text-slate-100"
        @mousemove="handleDrag"
        @mouseup="endDrag"
    >
        <!-- Header -->
        <header
            class="mb-8 flex flex-col gap-4 rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)] sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-4">
                <div class="rounded-2xl border-4 border-slate-900 bg-amber-400 p-3 text-slate-900 dark:border-slate-100">
                    <Activity class="h-8 w-8 stroke-[3]" />
                </div>
                <div>
                    <h1 class="text-2xl font-black tracking-tight uppercase sm:text-3xl">Visual Call Router</h1>
                    <p class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">
                        Drag-and-Drop Call Flow & Dynamic IVR Designer
                    </p>
                </div>
            </div>
            <button
                :disabled="isSaving"
                class="rounded-2xl border-4 border-slate-900 bg-emerald-500 px-6 py-3 text-sm font-black uppercase text-white shadow-[2px_2px_0px_0px_rgba(15,23,42,1)] transition-transform hover:-translate-y-0.5 active:translate-y-0 dark:border-slate-100 dark:shadow-[2px_2px_0px_0px_rgba(255,255,255,1)]"
                @click="saveFlow"
            >
                {{ isSaving ? 'Saving...' : 'Save Flow Tree' }}
            </button>
        </header>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
            <!-- Left Side: Nodes Toolbox & Config -->
            <div class="flex flex-col gap-6 lg:col-span-1">
                <!-- Add Nodes Card -->
                <div
                    class="rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                >
                    <h3 class="mb-4 text-xs font-black tracking-widest text-slate-500 uppercase dark:text-slate-400">
                        Add Action Nodes
                    </h3>
                    <div class="flex flex-col gap-3">
                        <button
                            class="flex items-center justify-between rounded-xl border-4 border-slate-900 bg-emerald-100 p-3 text-left font-black text-emerald-800 transition-transform hover:-translate-y-0.5"
                            @click="addNode('ivr')"
                        >
                            <span>IVR Press Digit</span>
                            <Plus class="h-5 w-5 stroke-[3]" />
                        </button>
                        <button
                            class="flex items-center justify-between rounded-xl border-4 border-slate-900 bg-yellow-100 p-3 text-left font-black text-yellow-800 transition-transform hover:-translate-y-0.5"
                            @click="addNode('handover')"
                        >
                            <span>Agent Handover</span>
                            <Plus class="h-5 w-5 stroke-[3]" />
                        </button>
                        <button
                            class="flex items-center justify-between rounded-xl border-4 border-slate-900 bg-indigo-100 p-3 text-left font-black text-indigo-800 transition-transform hover:-translate-y-0.5"
                            @click="addNode('tool')"
                        >
                            <span>Tool/Webhook</span>
                            <Plus class="h-5 w-5 stroke-[3]" />
                        </button>
                    </div>
                </div>

                <!-- Config Editor Panel -->
                <div
                    v-if="activeNode"
                    class="rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-xs font-black tracking-widest text-slate-500 uppercase dark:text-slate-400">
                            Node Configuration
                        </h3>
                        <button class="text-rose-500 hover:text-rose-700" @click="deleteNode(activeNode.id)">
                            <Trash2 class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-black uppercase text-slate-500 dark:text-slate-400">Node Title</label>
                            <input
                                v-model="activeNode.title"
                                type="text"
                                class="mt-1 w-full rounded-xl border-4 border-slate-900 p-2 font-black dark:border-slate-100 dark:bg-slate-800"
                            />
                        </div>

                        <!-- Type Specific Settings -->
                        <div v-if="activeNode.type === 'ivr'">
                            <label class="block text-xs font-black uppercase text-slate-500 dark:text-slate-400">Welcome Speak Prompt</label>
                            <textarea
                                v-model="activeNode.config.prompt"
                                class="mt-1 w-full rounded-xl border-4 border-slate-900 p-2 font-black dark:border-slate-100 dark:bg-slate-800"
                                rows="3"
                            ></textarea>
                        </div>

                        <div v-if="activeNode.type === 'handover'">
                            <label class="block text-xs font-black uppercase text-slate-500 dark:text-slate-400">Target Assistant ID</label>
                            <input
                                v-model="activeNode.config.agentId"
                                type="text"
                                class="mt-1 w-full rounded-xl border-4 border-slate-900 p-2 font-mono font-black dark:border-slate-100 dark:bg-slate-800"
                            />
                        </div>

                        <div v-if="activeNode.type === 'tool'">
                            <label class="block text-xs font-black uppercase text-slate-500 dark:text-slate-400">MCP Tool Binding</label>
                            <input
                                v-model="activeNode.config.toolName"
                                type="text"
                                class="mt-1 w-full rounded-xl border-4 border-slate-900 p-2 font-mono font-black dark:border-slate-100 dark:bg-slate-800"
                            />
                        </div>

                        <!-- Connections list -->
                        <div class="border-t-4 border-slate-900 pt-4 dark:border-slate-100">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-xs font-black uppercase text-slate-500 dark:text-slate-400">Routing Paths</span>
                                <button
                                    class="rounded-lg border-2 border-slate-900 bg-amber-400 px-2 py-0.5 text-xs font-black dark:border-slate-100"
                                    @click="addConnection"
                                >
                                    + Path
                                </button>
                            </div>
                            <div class="space-y-3">
                                <div
                                    v-for="(conn, idx) in activeNode.connections"
                                    :key="idx"
                                    class="flex gap-2 rounded-xl border-2 border-slate-900 bg-slate-50 p-2 dark:border-slate-100 dark:bg-slate-800"
                                >
                                    <input
                                        v-model="conn.condition"
                                        type="text"
                                        placeholder="Key/Rule"
                                        class="w-1/3 rounded-lg border-2 border-slate-900 p-1 text-center font-black dark:border-slate-100 dark:bg-slate-900"
                                    />
                                    <select
                                        v-model="conn.targetId"
                                        class="w-2/3 rounded-lg border-2 border-slate-900 p-1 font-black dark:border-slate-100 dark:bg-slate-900"
                                    >
                                        <option value="">Select Target</option>
                                        <option v-for="n in nodes" :key="n.id" :value="n.id" v-show="n.id !== activeNode.id">
                                            {{ n.title }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Canvas Editor Workspace -->
            <div class="relative lg:col-span-3">
                <div
                    class="relative h-[680px] w-full overflow-hidden rounded-3xl border-4 border-slate-900 bg-slate-50 dark:border-slate-100 dark:bg-slate-900/60"
                    style="background-image: radial-gradient(#94a3b8 1px, transparent 1px); background-size: 20px 20px"
                >
                    <!-- Visual Nodes -->
                    <div
                        v-for="node in nodes"
                        :key="node.id"
                        class="absolute cursor-move select-none rounded-2xl border-4 border-slate-900 bg-white p-4 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                        :class="{
                            'ring-4 ring-indigo-500': activeNodeId === node.id,
                        }"
                        :style="{
                            left: `${node.x}px`,
                            top: `${node.y}px`,
                            width: '240px',
                        }"
                        @mousedown="startDrag($event, node)"
                    >
                        <div class="mb-2 flex items-center justify-between">
                            <span
                                class="inline-flex rounded-lg px-2 py-0.5 text-xs font-black uppercase text-white"
                                :class="[
                                    node.type === 'ivr' ? 'bg-emerald-500' : node.type === 'handover' ? 'bg-amber-500' : 'bg-indigo-500',
                                ]"
                            >
                                {{ node.type }}
                            </span>
                            <Settings class="h-4 w-4 text-slate-400" />
                        </div>
                        <h4 class="text-sm font-black tracking-tight">{{ node.title }}</h4>

                        <!-- Show child routing path count -->
                        <div
                            v-if="node.connections.length > 0"
                            class="mt-3 flex items-center gap-1 text-xs font-bold text-slate-500 dark:text-slate-400"
                        >
                            <CornerRightDown class="h-4 w-4" />
                            {{ node.connections.length }} Routing target(s)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
