<script setup>
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatsCard from '@/Components/dashboard/StatsCard.vue';
import {
    Truck,
    PackageSearch,
    ClipboardCheck,
    MapPin,
    Calendar,
    Search,
    ArrowRight
} from 'lucide-vue-next';

// 1. Local Dummy Data for Inbound Tracking
const inboundShipments = ref([
    {
        id: 'SHP-7701',
        material: '200GSM Combed Cotton',
        origin: 'Heritage Textile Mills',
        status: 'In Transit',
        eta: '2026-02-12',
        progress: 65,
        location: 'Port of Manila'
    },
    {
        id: 'SHP-7702',
        material: 'Reactive Dye - Navy',
        origin: 'Skyline Fabric Ltd',
        status: 'Departed',
        eta: '2026-02-10',
        progress: 90,
        location: 'Customs Clearance'
    },
    {
        id: 'SHP-7703',
        material: 'Branded Resin Buttons',
        origin: 'Heritage Textile Mills',
        status: 'Processing',
        eta: '2026-02-18',
        progress: 15,
        location: 'Factory Floor'
    }
]);

const selectedShipment = ref(null);

// 2. Methods
const selectShipment = (shipment) => {
    selectedShipment.value = shipment;
};

onMounted(() => {
    // Auto-select the most urgent shipment
    if (inboundShipments.value.length > 0) {
        selectedShipment.value = inboundShipments.value[1]; // Selecting the 90% progress one
    }
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">SCM Operations Portal</h2>
            <p class="text-sm text-gray-500">Phase 4: Inbound Logistics & Shipment Tracking (SCM Staff)</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <StatsCard title="Inbound Shipments" value="3 Active" :icon="Truck"
                colorClass="text-blue-600 bg-blue-50 dark:bg-blue-900/20" />
            <StatsCard title="Pending Clearance" value="1 Shipment" :icon="PackageSearch"
                colorClass="text-orange-600 bg-orange-50 dark:bg-orange-900/20" />
            <StatsCard title="On-Time Rate" value="94%" :icon="ClipboardCheck"
                colorClass="text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <div
                class="lg:col-span-5 bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest flex items-center">
                        <Search class="w-4 h-4 mr-2" /> Inbound Tracking Log
                    </h3>
                </div>

                <div class="space-y-3">
                    <div v-for="shipment in inboundShipments" :key="shipment.id" @click="selectShipment(shipment)"
                        :class="selectedShipment?.id === shipment.id ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/20' : 'border-gray-50 hover:bg-gray-50'"
                        class="p-4 border rounded-xl cursor-pointer transition relative">

                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="text-[10px] font-bold text-blue-600 uppercase tracking-tighter">{{ shipment.id
                                    }}</p>
                                <h4 class="font-bold text-gray-800 dark:text-gray-100 text-sm">{{ shipment.material }}
                                </h4>
                            </div>
                            <span
                                class="text-[10px] font-black uppercase px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-500">
                                {{ shipment.status }}
                            </span>
                        </div>

                        <div class="w-full bg-gray-100 dark:bg-gray-700 h-1 rounded-full mt-3 overflow-hidden">
                            <div class="bg-blue-500 h-full transition-all duration-500"
                                :style="{ width: shipment.progress + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="lg:col-span-7 bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div v-if="selectedShipment">
                    <div class="flex justify-between items-center mb-8">
                        <h3 class="text-xl font-black text-gray-900 dark:text-white flex items-center">
                            <Truck class="w-6 h-6 mr-3 text-blue-600" />
                            Shipping Advice Detail
                        </h3>
                        <div class="flex items-center text-xs font-bold text-gray-400">
                            <Calendar class="w-4 h-4 mr-1" /> ETA: {{ selectedShipment.eta }}
                        </div>
                    </div>

                    <div
                        class="relative pl-8 space-y-8 before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-0.5 before:bg-gray-100 dark:before:bg-gray-700">
                        <div class="relative">
                            <div
                                class="absolute -left-10 w-6 h-6 rounded-full bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-600 flex items-center justify-center">
                                <div class="w-2 h-2 rounded-full bg-gray-300"></div>
                            </div>
                            <p class="text-[10px] font-black text-gray-400 uppercase">Origin</p>
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ selectedShipment.origin }}
                            </p>
                        </div>

                        <div class="relative">
                            <div
                                class="absolute -left-10 w-6 h-6 rounded-full bg-blue-100 border-2 border-blue-500 flex items-center justify-center animate-pulse">
                                <MapPin class="w-3 h-3 text-blue-600" />
                            </div>
                            <p class="text-[10px] font-black text-blue-600 uppercase">Current Location</p>
                            <p class="text-sm font-black text-gray-900 dark:text-white">{{ selectedShipment.location }}
                            </p>
                        </div>

                        <div class="relative">
                            <div
                                class="absolute -left-10 w-6 h-6 rounded-full bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-600 flex items-center justify-center">
                                <div class="w-2 h-2 rounded-full bg-gray-300"></div>
                            </div>
                            <p class="text-[10px] font-black text-gray-400 uppercase">Destination</p>
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-200">Main Textile Warehouse (Gen.
                                Trias)</p>
                        </div>
                    </div>

                    <button
                        class="w-full mt-10 py-4 bg-gray-900 dark:bg-blue-600 text-white rounded-xl font-bold flex items-center justify-center hover:bg-black transition">
                        Update Tracking Status
                        <ArrowRight class="w-4 h-4 ml-2" />
                    </button>
                </div>

                <div v-else class="h-full flex flex-col items-center justify-center py-20 text-gray-400">
                    <PackageSearch class="w-12 h-12 mb-4 opacity-20" />
                    <p>Select a shipment from the log to view live inbound details.</p>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>