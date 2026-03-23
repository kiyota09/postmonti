<script setup>
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatsCard from '@/Components/dashboard/StatsCard.vue';
import {
    Truck,
    PackageSearch,
    ClipboardCheck,
    FileSearch,
    CheckCircle,
    ArrowRightLeft,
    ShieldCheck,
    Receipt
} from 'lucide-vue-next';

// --- State Management ---
const currentPhase = ref('inbound'); // inbound, receiving, inventory, verification
const selectedTask = ref(null);

// --- Dummy Data Based on Uploaded Phases ---

// Phase 4: Inbound Tracking
const shipments = ref([
    { id: 'SHP-99', item: 'Egyptian Cotton', mill: 'Textile Mill A', eta: '2026-02-15', status: 'En Route', progress: 70 },
    { id: 'SHP-100', item: 'Indigo Dye', mill: 'Chemical Corp', eta: '2026-02-11', status: 'Near Port', progress: 95 }
]);

// Phase 5: Receiving (Inspection)
const pendingReceipts = ref([
    { id: 'PO-882', item: 'Recycled Polyester', expected: 5000, unit: 'm', vendor: 'EcoFab Co.' }
]);

// Phase 6: Inventory (Ledger Updates)
const inventoryLedger = ref([
    { id: 'SKU-77', name: 'Zippers (Silver)', bin: 'B-10', currentQty: 2500, unit: 'pcs' }
]);

// Phase 7: Verification (3-Way Match)
const verificationQueue = ref([
    { id: 'REC-55', po: 'PO-701', grn: 'GRN-402', invoice: 'INV-991', total: '$12,400', status: 'Awaiting Match' }
]);

// --- Methods ---
const selectTask = (task, phase) => {
    selectedTask.value = task;
    currentPhase.value = phase;
};

const completeVerification = () => {
    alert("3-Way Match Successful: PO, GRN, and Invoice amounts reconciled.");
    verificationQueue.value = [];
    selectedTask.value = null;
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="mb-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black text-gray-800 dark:text-white uppercase tracking-tighter">SCM Operations
                    Portal</h2>
                <div class="flex gap-4 mt-2">
                    <button @click="currentPhase = 'inbound'"
                        :class="currentPhase === 'inbound' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-400'"
                        class="pb-1 text-[10px] font-black uppercase">4. Inbound</button>
                    <button @click="currentPhase = 'receiving'"
                        :class="currentPhase === 'receiving' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-400'"
                        class="pb-1 text-[10px] font-black uppercase">5. Receiving</button>
                    <button @click="currentPhase = 'inventory'"
                        :class="currentPhase === 'inventory' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-400'"
                        class="pb-1 text-[10px] font-black uppercase">6. Inventory</button>
                    <button @click="currentPhase = 'verification'"
                        :class="currentPhase === 'verification' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-400'"
                        class="pb-1 text-[10px] font-black uppercase">7. Verification</button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-4 space-y-4">
                <div
                    class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-4">Operations Queue
                    </h3>

                    <div v-if="currentPhase === 'inbound'" v-for="s in shipments" :key="s.id" @click="selectedTask = s"
                        class="p-4 border rounded-xl cursor-pointer transition mb-2"
                        :class="selectedTask?.id === s.id ? 'border-blue-500 bg-blue-50' : 'border-gray-50'">
                        <p class="text-[10px] font-black text-blue-600 uppercase">{{ s.id }}</p>
                        <h4 class="font-bold text-sm">{{ s.item }}</h4>
                        <p class="text-[10px] text-gray-400">Mill: {{ s.mill }}</p>
                    </div>

                    <div v-if="currentPhase === 'verification'" v-for="v in verificationQueue" :key="v.id"
                        @click="selectedTask = v" class="p-4 border rounded-xl cursor-pointer transition"
                        :class="selectedTask?.id === v.id ? 'border-indigo-500 bg-indigo-50' : 'border-gray-50'">
                        <p class="text-[10px] font-black text-indigo-600 uppercase">Match ID: {{ v.id }}</p>
                        <h4 class="font-bold text-sm">{{ v.grn }} Match</h4>
                        <p class="text-[10px] text-gray-400">Invoice: {{ v.invoice }}</p>
                    </div>

                    <div v-else-if="!shipments.length && !verificationQueue.length"
                        class="text-center py-10 text-gray-300 italic text-sm">No pending tasks.</div>
                </div>
            </div>

            <div
                class="lg:col-span-8 bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div v-if="selectedTask">

                    <div v-if="currentPhase === 'inbound'">
                        <h3 class="text-xl font-black mb-4 flex items-center">
                            <Truck class="mr-2 text-blue-500" /> Track Shipment
                        </h3>
                        <div class="bg-gray-50 p-6 rounded-2xl border mb-6">
                            <p class="text-xs font-bold text-gray-400 uppercase mb-2">Lead Time Tracker</p>
                            <div class="w-full bg-gray-200 h-2 rounded-full mb-4">
                                <div class="bg-blue-600 h-full rounded-full transition-all"
                                    :style="{ width: selectedTask.progress + '%' }"></div>
                            </div>
                            <p class="text-sm font-bold">Estimated Arrival: <span class="text-blue-600">{{
                                    selectedTask.eta }}</span></p>
                        </div>
                        <button class="w-full py-3 bg-gray-900 text-white rounded-xl font-bold">Log Shipping Advice
                            Update</button>
                    </div>

                    <div v-if="currentPhase === 'verification'">
                        <h3 class="text-xl font-black mb-6 flex items-center">
                            <ShieldCheck class="mr-2 text-indigo-500" /> 3-Way Reconciliation
                        </h3>
                        <div class="grid grid-cols-3 gap-4 mb-8">
                            <div class="p-4 border rounded-xl text-center">
                                <Receipt class="w-4 h-4 mx-auto mb-2 text-gray-400" />
                                <p class="text-[10px] uppercase font-bold text-gray-400">PO</p>
                                <p class="text-sm font-black">{{ selectedTask.po }}</p>
                            </div>
                            <div class="p-4 border rounded-xl text-center">
                                <PackageSearch class="w-4 h-4 mx-auto mb-2 text-gray-400" />
                                <p class="text-[10px] uppercase font-bold text-gray-400">GRN</p>
                                <p class="text-sm font-black">{{ selectedTask.grn }}</p>
                            </div>
                            <div class="p-4 border rounded-xl text-center">
                                <Receipt class="w-4 h-4 mx-auto mb-2 text-gray-400" />
                                <p class="text-[10px] uppercase font-bold text-gray-400">Invoice</p>
                                <p class="text-sm font-black">{{ selectedTask.invoice }}</p>
                            </div>
                        </div>
                        <div class="bg-indigo-50 p-4 rounded-xl mb-6 flex justify-between items-center">
                            <span class="text-xs font-bold text-indigo-600 uppercase">Total Amount Matched:</span>
                            <span class="text-xl font-black text-indigo-700">{{ selectedTask.total }}</span>
                        </div>
                        <button @click="completeVerification"
                            class="w-full py-4 bg-indigo-600 text-white rounded-xl font-black shadow-lg">APPROVE 3-WAY
                            MATCH</button>
                    </div>

                </div>
                <div v-else class="h-full flex items-center justify-center text-gray-300 italic">Select a task from the
                    sidebar.</div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>