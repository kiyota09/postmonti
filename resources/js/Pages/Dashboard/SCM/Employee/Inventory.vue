<script setup>
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatsCard from '@/Components/dashboard/StatsCard.vue';
import {
    Truck,
    PackageSearch,
    ClipboardCheck,
    MapPin,
    FileCheck,
    Navigation,
    Boxes,
    ArrowRightLeft
} from 'lucide-vue-next';

// --- Global State ---
const activePhase = ref('inbound'); // 'inbound', 'receiving', or 'inventory'

// --- Phase 4: Inbound Dummy Data ---
const shipments = ref([
    { id: 'SHP-101', item: 'Cotton Yarn', mill: 'Mill Alpha', status: 'In Transit', location: 'Customs', progress: 75 },
    { id: 'SHP-102', item: 'Synthetic Dye', mill: 'Mill Beta', status: 'At Port', location: 'Batangas Port', progress: 90 }
]);

// --- Phase 5: Receiving Dummy Data ---
const pendingReceipts = ref([
    { id: 'PO-500', item: 'Denim Fabric', expected: 2000, unit: 'm', vendor: 'Heritage Mills' }
]);

// --- Phase 6: Inventory Dummy Data ---
const stockLedger = ref([
    { id: 'SKU-001', item: 'Combed Cotton Yarn', bin: 'A-12', qty: 850, unit: 'kg' },
    { id: 'SKU-002', item: 'Indigo Dye', bin: 'B-04', qty: 15, unit: 'L' }
]);

const selectedTask = ref(null);

// --- Methods ---
const selectTask = (task, phase) => {
    selectedTask.value = task;
    activePhase.value = phase;
};

onMounted(() => {
    // Default selection
    selectTask(shipments.value[0], 'inbound');
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">SCM Operations Portal</h2>
            <div class="flex gap-4 mt-2">
                <button @click="activePhase = 'inbound'"
                    :class="activePhase === 'inbound' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-400'"
                    class="pb-1 text-xs font-bold uppercase tracking-widest">4. Inbound</button>
                <button @click="activePhase = 'receiving'"
                    :class="activePhase === 'receiving' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-400'"
                    class="pb-1 text-xs font-bold uppercase tracking-widest">5. Receiving</button>
                <button @click="activePhase = 'inventory'"
                    :class="activePhase === 'inventory' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-400'"
                    class="pb-1 text-xs font-bold uppercase tracking-widest">6. Inventory</button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <StatsCard title="Shipments En Route" :value="shipments.length" :icon="Truck"
                colorClass="text-blue-600 bg-blue-50" />
            <StatsCard title="Unprocessed GRNs" :value="pendingReceipts.length" :icon="PackageSearch"
                colorClass="text-orange-600 bg-orange-50" />
            <StatsCard title="Ledger Updates" value="12 Today" :icon="ClipboardCheck"
                colorClass="text-emerald-600 bg-emerald-50" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <div
                class="lg:col-span-4 bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4">Daily Operations Log
                </h3>

                <div class="space-y-3">
                    <div v-if="activePhase === 'inbound'" v-for="s in shipments" :key="s.id" @click="selectedTask = s"
                        class="p-4 border rounded-xl cursor-pointer transition"
                        :class="selectedTask?.id === s.id ? 'border-blue-500 bg-blue-50' : 'border-gray-50'">
                        <p class="text-xs font-bold text-blue-600">{{ s.id }}</p>
                        <h4 class="font-bold text-sm text-gray-800">{{ s.item }}</h4>
                        <div class="w-full bg-gray-200 h-1 rounded-full mt-2">
                            <div class="bg-blue-500 h-full" :style="{ width: s.progress + '%' }"></div>
                        </div>
                    </div>

                    <div v-if="activePhase === 'receiving'" v-for="r in pendingReceipts" :key="r.id"
                        @click="selectedTask = r" class="p-4 border rounded-xl cursor-pointer transition"
                        :class="selectedTask?.id === r.id ? 'border-orange-500 bg-orange-50' : 'border-gray-50'">
                        <p class="text-xs font-bold text-orange-600">{{ r.id }}</p>
                        <h4 class="font-bold text-sm text-gray-800">{{ r.item }}</h4>
                        <p class="text-[10px] text-gray-400">From: {{ r.vendor }}</p>
                    </div>

                    <div v-if="activePhase === 'inventory'" v-for="i in stockLedger" :key="i.id"
                        @click="selectedTask = i" class="p-4 border rounded-xl cursor-pointer transition"
                        :class="selectedTask?.id === i.id ? 'border-emerald-500 bg-emerald-50' : 'border-gray-50'">
                        <p class="text-xs font-bold text-emerald-600">BIN: {{ i.bin }}</p>
                        <h4 class="font-bold text-sm text-gray-800">{{ i.item }}</h4>
                        <p class="text-[10px] text-gray-400">Current: {{ i.qty }} {{ i.unit }}</p>
                    </div>
                </div>
            </div>

            <div
                class="lg:col-span-8 bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 min-h-[450px]">
                <div v-if="selectedTask">

                    <div v-if="activePhase === 'inbound'">
                        <div class="flex items-center gap-3 mb-8">
                            <Navigation class="text-blue-500" />
                            <h3 class="text-xl font-black text-gray-800 uppercase tracking-tighter">Inbound Logistics
                                Log</h3>
                        </div>
                        <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 space-y-4">
                            <div class="flex justify-between">
                                <span class="text-xs font-bold text-gray-400 uppercase">Current Tracking Status</span>
                                <span class="text-xs font-black text-blue-600 uppercase">{{ selectedTask.status
                                }}</span>
                            </div>
                            <p class="text-lg font-bold">Last Scanned: <span class="text-blue-600">{{
                                selectedTask.location }}</span></p>
                            <button class="w-full py-3 bg-gray-900 text-white rounded-xl text-sm font-bold">Request Lead
                                Time Update from Mill</button>
                        </div>
                    </div>

                    <div v-if="activePhase === 'receiving'">
                        <div class="flex items-center gap-3 mb-8">
                            <FileCheck class="text-orange-500" />
                            <h3 class="text-xl font-black text-gray-800 uppercase tracking-tighter">Goods Received Note
                                (GRN) Entry</h3>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="p-4 bg-gray-50 rounded-xl"><label class="text-[10px] font-bold text-gray-400">PO
                                    Quantity</label>
                                <p class="font-bold">{{ selectedTask.expected }} {{ selectedTask.unit }}</p>
                            </div>
                            <div class="p-4 bg-orange-50 border border-orange-100 rounded-xl"><label
                                    class="text-[10px] font-bold text-orange-600">Actual Count</label><input
                                    type="number" class="w-full bg-transparent border-none p-0 font-bold focus:ring-0">
                            </div>
                        </div>
                        <button class="w-full py-4 bg-orange-600 text-white rounded-xl font-bold shadow-lg">Verify
                            Inspection & Issue GRN</button>
                    </div>

                    <div v-if="activePhase === 'inventory'">
                        <div class="flex items-center gap-3 mb-8">
                            <Boxes class="text-emerald-500" />
                            <h3 class="text-xl font-black text-gray-800 uppercase tracking-tighter">Stock Ledger Update
                            </h3>
                        </div>
                        <div class="space-y-6">
                            <div
                                class="flex items-center justify-between p-6 border-2 border-dashed border-gray-100 rounded-2xl text-center">
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase">Warehouse Bin</p>
                                    <p class="text-2xl font-black text-emerald-600">{{ selectedTask.bin }}</p>
                                </div>
                                <ArrowRightLeft class="text-gray-200" />
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase">Movement To</p>
                                    <p class="text-sm font-bold italic">Production Bin P-01</p>
                                </div>
                            </div>
                            <button class="w-full py-4 bg-emerald-600 text-white rounded-xl font-bold shadow-lg">Commit
                                Inventory Movement</button>
                        </div>
                    </div>

                </div>
                <div v-else class="h-full flex items-center justify-center text-gray-300 italic">Select a task from the
                    daily log to proceed.</div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>