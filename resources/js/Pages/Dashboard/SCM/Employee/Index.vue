<script setup>
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatsCard from '@/Components/dashboard/StatsCard.vue';
import {
    Truck,
    PackageSearch,
    ClipboardCheck,
    FilePlus,
    Send,
    Building2,
    Clock
} from 'lucide-vue-next';

// 1. Hardcoded Dummy Props for testing
const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            pendingPickups: 4,
            incomingShipments: 12,
            inventoryChecks: 3
        })
    },
    user: {
        type: Object,
        default: () => ({ name: 'SCM Staff' })
    }
});

// 2. Dummy Data for Approved Requirements (Waiting for PO)
const approvedRequirements = ref([
    { id: 'REQ-001', material: '200GSM Combed Cotton', qty: 1750, unit: 'm', vendor: 'Heritage Textile Mills', price: 8.45 },
    { id: 'REQ-002', material: 'Reactive Dye - Navy', qty: 50, unit: 'L', vendor: 'Skyline Fabric Ltd', price: 9.10 },
    { id: 'REQ-003', material: 'Branded Resin Buttons', qty: 15000, unit: 'pcs', vendor: 'Heritage Textile Mills', price: 0.12 }
]);

const selectedReq = ref(null);
const poSuccess = ref(false);

// 3. Methods
const generatePO = (req) => {
    selectedReq.value = req;
};

const sendPO = () => {
    // Simulate sending PO to vendor
    poSuccess.value = true;
    setTimeout(() => {
        approvedRequirements.value = approvedRequirements.value.filter(r => r.id !== selectedReq.value.id);
        selectedReq.value = null;
        poSuccess.value = false;
    }, 2000);
};

onMounted(() => {
    // Auto-select first req to show the UI
    if (approvedRequirements.value.length > 0) {
        selectedReq.value = approvedRequirements.value[0];
    }
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">SCM Operations Portal</h2>
            <p class="text-gray-500 dark:text-gray-400">Welcome, {{ user.name }}. Manage your purchasing tasks here.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <StatsCard title="Pending Pickups" :value="stats.pendingPickups" :icon="Truck"
                colorClass="text-blue-600 bg-blue-50 dark:bg-blue-900/20" />
            <StatsCard title="Shipments Due" :value="stats.incomingShipments" :icon="PackageSearch"
                colorClass="text-orange-600 bg-orange-50 dark:bg-orange-900/20" />
            <StatsCard title="Inventory Checks" :value="stats.inventoryChecks" :icon="ClipboardCheck"
                colorClass="text-cyan-600 bg-cyan-50 dark:bg-cyan-900/20" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <div
                class="lg:col-span-5 bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <h3 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-4 flex items-center">
                    <FilePlus class="w-4 h-4 mr-2" /> Approved Req List
                </h3>
                <div class="space-y-3">
                    <div v-for="req in approvedRequirements" :key="req.id" @click="generatePO(req)"
                        :class="selectedReq?.id === req.id ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20' : 'border-gray-100 hover:bg-gray-50'"
                        class="p-4 border rounded-xl cursor-pointer transition relative overflow-hidden">

                        <div v-if="selectedReq?.id === req.id" class="absolute left-0 top-0 h-full w-1 bg-indigo-500">
                        </div>

                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-bold text-gray-800 dark:text-gray-100 text-sm">{{ req.material }}</h4>
                                <div class="flex items-center text-[10px] text-gray-500 mt-1">
                                    <Building2 class="w-3 h-3 mr-1" /> {{ req.vendor }}
                                </div>
                            </div>
                            <span class="text-xs font-mono font-black text-indigo-600">{{ req.qty }} {{ req.unit
                                }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="lg:col-span-7 bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 min-h-[400px]">
                <div v-if="selectedReq" class="animate-in fade-in slide-in-from-right-4 duration-300">
                    <div class="flex justify-between items-start border-b pb-6 mb-6">
                        <div>
                            <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">
                                Purchase Order Draft</h3>
                            <p class="text-xs text-gray-400 mt-1">Ref: PO-GEN-{{ selectedReq.id }}</p>
                        </div>
                        <div class="text-right">
                            <span
                                class="bg-amber-100 text-amber-700 text-[10px] font-black px-3 py-1 rounded-full uppercase">Draft
                                Mode</span>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase">Supplier</label>
                                <p class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ selectedReq.vendor }}
                                </p>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase">Unit Price</label>
                                <p class="text-sm font-bold text-gray-800 dark:text-gray-200">${{ selectedReq.price }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Total Purchase Value:</span>
                                <span class="text-lg font-black text-gray-900 dark:text-white">${{ (selectedReq.qty *
                                    selectedReq.price).toLocaleString() }}</span>
                            </div>
                        </div>

                        <button @click="sendPO" :disabled="poSuccess"
                            class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 disabled:bg-emerald-500 text-white rounded-xl font-bold transition-all shadow-lg flex items-center justify-center">
                            <template v-if="!poSuccess">
                                <Send class="w-4 h-4 mr-2" /> DISPATCH PO TO SUPPLIER
                            </template>
                            <template v-else>
                                <CheckCircle class="w-4 h-4 mr-2" /> PO DISPATCHED SUCCESSFULLY
                            </template>
                        </button>
                    </div>
                </div>

                <div v-else class="h-full flex flex-col items-center justify-center text-center py-20">
                    <div
                        class="w-16 h-16 bg-gray-50 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                        <Clock class="w-8 h-8 text-gray-300" />
                    </div>
                    <p class="text-gray-400 text-sm">Select a requirement from the queue to generate a formal PO.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>