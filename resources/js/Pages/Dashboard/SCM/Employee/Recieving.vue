<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatsCard from '@/Components/dashboard/StatsCard.vue';
import {
    Truck,
    PackageSearch,
    ClipboardCheck,
    MapPin,
    Search,
    FileCheck,
    AlertCircle
} from 'lucide-vue-next';

// --- Phase State Management ---
const currentPhase = ref('inbound'); // 'inbound' or 'receiving'

// --- Phase 4 Dummy Data: Inbound Tracking ---
const inboundShipments = ref([
    { id: 'SHP-7701', material: '200GSM Combed Cotton', origin: 'Heritage Textile Mills', status: 'In Transit', progress: 65, location: 'Port of Manila' },
    { id: 'SHP-7702', material: 'Reactive Dye - Navy', origin: 'Skyline Fabric Ltd', status: 'Arrived', progress: 100, location: 'Warehouse Gate' },
]);

// --- Phase 5 Dummy Data: Receiving Queue ---
const receivingQueue = ref([
    { poId: 'PO-2026-045', material: 'Branded Resin Buttons', expectedQty: 15000, unit: 'pcs', vendor: 'Heritage Textile Mills' },
    { poId: 'PO-2026-048', material: 'Recycled Poly Thread', expectedQty: 4500, unit: 'spools', vendor: 'Eastern Yarn Co.' }
]);

const selectedPO = ref(null);
const receivedQty = ref(0);

// --- Methods ---
const startReceiving = (order) => {
    selectedPO.value = order;
    receivedQty.value = order.expectedQty; // Default to expected
};

const submitGRN = () => {
    alert(`Goods Received Note (GRN) generated for ${selectedPO.value.poId}. Inventory levels updated.`);
    receivingQueue.value = receivingQueue.value.filter(item => item.poId !== selectedPO.value.poId);
    selectedPO.value = null;
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="mb-6 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">SCM Operations Portal</h2>
                <p class="text-sm text-gray-500">Managing Logistics & Inventory Entry</p>
            </div>
            <div class="flex bg-gray-100 dark:bg-gray-700 p-1 rounded-xl">
                <button @click="currentPhase = 'inbound'"
                    :class="currentPhase === 'inbound' ? 'bg-white shadow text-blue-600' : 'text-gray-500'"
                    class="px-4 py-1.5 rounded-md text-sm font-bold transition-all">Inbound Tracking</button>
                <button @click="currentPhase = 'receiving'"
                    :class="currentPhase === 'receiving' ? 'bg-white shadow text-blue-600' : 'text-gray-500'"
                    class="px-4 py-1.5 rounded-md text-sm font-bold transition-all">Receiving (GRN)</button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <StatsCard title="Inbound Shipments" value="3 Active" :icon="Truck" colorClass="text-blue-600 bg-blue-50" />
            <StatsCard title="Awaiting Inspection" :value="receivingQueue.length" :icon="PackageSearch"
                colorClass="text-orange-600 bg-orange-50" />
            <StatsCard title="GRNs Today" value="8 Issues" :icon="ClipboardCheck"
                colorClass="text-emerald-600 bg-emerald-50" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <div class="lg:col-span-5 space-y-4">
                <div
                    class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center">
                        <Search class="w-4 h-4 mr-2" />
                        {{ currentPhase === 'inbound' ? 'Shipment Tracking Log' : 'Receiving Queue (Pending POs)' }}
                    </h3>

                    <div class="space-y-3">
                        <template v-if="currentPhase === 'inbound'">
                            <div v-for="shipment in inboundShipments" :key="shipment.id"
                                class="p-4 border rounded-xl border-gray-50 bg-gray-50/30 dark:bg-gray-700/30">
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="font-bold text-gray-800 dark:text-gray-100 text-sm">{{ shipment.material
                                    }}</h4>
                                    <span
                                        class="text-[10px] font-black uppercase px-2 py-1 rounded bg-blue-100 text-blue-600">{{
                                            shipment.status }}</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-600 h-1 rounded-full overflow-hidden">
                                    <div class="bg-blue-500 h-full" :style="{ width: shipment.progress + '%' }"></div>
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div v-for="order in receivingQueue" :key="order.poId" @click="startReceiving(order)"
                                :class="selectedPO?.poId === order.poId ? 'border-blue-500 bg-blue-50' : 'border-gray-50'"
                                class="p-4 border rounded-xl cursor-pointer hover:bg-gray-50 transition">
                                <h4 class="font-bold text-gray-800 dark:text-gray-100 text-sm">{{ order.material }}</h4>
                                <p class="text-[10px] text-gray-500 uppercase font-bold">{{ order.poId }} | {{
                                    order.vendor }}</p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div
                class="lg:col-span-7 bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div v-if="currentPhase === 'receiving' && selectedPO">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">
                            Inspection Terminal
                        </h3>
                        <span class="text-[10px] font-bold text-gray-400">VERIFYING AGAINST PO</span>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-8">
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <label class="text-[10px] font-black text-gray-400 uppercase">Expected Quantity</label>
                            <p class="text-lg font-bold text-gray-900">{{ selectedPO.expectedQty }} {{ selectedPO.unit
                            }}</p>
                        </div>
                        <div class="p-4 bg-blue-50 border border-blue-100 rounded-xl">
                            <label class="text-[10px] font-black text-blue-600 uppercase">Actual Received</label>
                            <input type="number" v-model="receivedQty"
                                class="w-full bg-transparent border-none p-0 text-lg font-bold text-blue-700 focus:ring-0">
                        </div>
                    </div>

                    <div v-if="receivedQty !== selectedPO.expectedQty"
                        class="mb-8 p-4 bg-rose-50 border border-rose-100 rounded-xl flex items-center">
                        <AlertCircle class="w-5 h-5 text-rose-500 mr-3" />
                        <p class="text-xs text-rose-700 font-bold">Quantity discrepancy detected. Variance will be
                            logged in SCM
                            Close.</p>
                    </div>

                    <button @click="submitGRN"
                        class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold shadow-lg transition flex items-center justify-center">
                        <FileCheck class="w-5 h-5 mr-2" /> GENERATE GOODS RECEIVED NOTE (GRN)
                    </button>
                </div>

                <div v-else class="h-full flex flex-col items-center justify-center py-24 text-gray-300">
                    <PackageSearch class="w-16 h-16 mb-4 opacity-20" />
                    <p class="text-sm">Select a task from the left to begin processing.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>