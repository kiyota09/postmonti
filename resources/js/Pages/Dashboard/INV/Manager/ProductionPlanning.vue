<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    Package,
    CheckCircle,
    AlertTriangle,
    Clock,
    Calendar,
    DollarSign,
    Eye,
    Layers,
    Building2,
    X,
    RefreshCw,
    TrendingUp,
    Factory
} from 'lucide-vue-next';

const props = defineProps({
    orders: Array,
});

const showDetailModal = ref(false);
const selectedOrder = ref(null);
const isLoading = ref(false);
const insufficientMaterials = ref(null);
const showInsufficientModal = ref(false);
const processingOrderId = ref(null);

const checkAvailability = (orderId) => {
    if (confirm('Check material availability for this order? This will automatically create material requests if needed.')) {
        processingOrderId.value = orderId;
        isLoading.value = true;
        router.post(route('inv.manager.production-planning.check', orderId), {}, {
            preserveScroll: true,
            onSuccess: (page) => {
                // If the response contains insufficient materials, show modal
                if (page.props.flash?.insufficient) {
                    insufficientMaterials.value = page.props.flash.insufficient;
                    showInsufficientModal.value = true;
                }
                // Refresh the page to update the list
                router.reload({ only: ['orders'] });
            },
            onError: (errors) => {
                alert('Error: ' + Object.values(errors)[0]);
            },
            onFinish: () => {
                isLoading.value = false;
                processingOrderId.value = null;
            }
        });
    }
};

const openDetail = (order) => {
    selectedOrder.value = order;
    showDetailModal.value = true;
};

const closeModal = () => {
    showDetailModal.value = false;
    selectedOrder.value = null;
};

const closeInsufficientModal = () => {
    showInsufficientModal.value = false;
    insufficientMaterials.value = null;
};

const formatCurrency = (val) => '₱' + Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });
const formatDate = (date) => date ? new Date(date).toLocaleDateString('en-PH') : '—';

const getQueueStageBadge = (stage) => {
    const map = {
        inv_check: 'bg-amber-100 text-amber-700',
        man_production: 'bg-emerald-100 text-emerald-700',
        waiting_materials: 'bg-red-100 text-red-700',
        eco_approved: 'bg-blue-100 text-blue-700',
        scm_received: 'bg-purple-100 text-purple-700',
    };
    return map[stage] || 'bg-gray-100 text-gray-700';
};

const getQueueStageLabel = (stage) => {
    const map = {
        inv_check: 'Awaiting Check',
        man_production: 'Ready for Production',
        waiting_materials: 'Waiting for Materials',
        eco_approved: 'Approved by ECO',
        scm_received: 'Received by SCM',
    };
    return map[stage] || stage;
};
</script>

<template>

    <Head title="Production Planning - INV Manager" />
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-8 p-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Production Planning</h1>
                    <p class="text-sm text-gray-500">Check material availability for orders from SCM</p>
                </div>
                <button @click="router.reload()" class="p-2 rounded-lg hover:bg-gray-100 transition">
                    <RefreshCw class="h-5 w-5 text-gray-500" />
                </button>
            </div>

            <div v-if="orders.length === 0" class="bg-white dark:bg-gray-800 rounded-lg border p-12 text-center">
                <Factory class="h-12 w-12 text-gray-300 mx-auto mb-4" />
                <p class="text-gray-500">No orders awaiting material check.</p>
                <p class="text-sm text-gray-400">Orders forwarded by SCM will appear here.</p>
            </div>

            <div v-else class="space-y-6">
                <div v-for="order in orders" :key="order.id"
                    class="bg-white dark:bg-gray-800 rounded-lg border shadow-sm hover:shadow-md transition">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2 flex-wrap">
                                    <span class="text-xs font-mono font-bold text-gray-500">#{{ order.po_number
                                    }}</span>
                                    <span :class="getQueueStageBadge(order.queue?.stage)"
                                        class="text-xs px-2 py-0.5 rounded-full">
                                        {{ getQueueStageLabel(order.queue?.stage) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Building2 class="h-4 w-4 text-gray-400" />
                                    <span class="font-medium text-gray-900 dark:text-white">{{
                                        order.client?.company_name }}</span>
                                </div>
                                <div class="flex items-center gap-4 mt-1 text-sm text-gray-500">
                                    <span class="flex items-center gap-1">
                                        <Calendar class="h-3.5 w-3.5" /> {{ formatDate(order.created_at) }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <DollarSign class="h-3.5 w-3.5" /> {{ formatCurrency(order.total_amount) }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <Package class="h-3.5 w-3.5" /> {{ order.items.length }} item(s)
                                    </span>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button @click="openDetail(order)"
                                    class="px-4 py-2 border rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-1">
                                    <Eye class="h-4 w-4" /> Details
                                </button>
                                <button @click="checkAvailability(order.id)"
                                    :disabled="isLoading && processingOrderId === order.id"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 flex items-center gap-1 disabled:opacity-50">
                                    <CheckCircle v-if="!isLoading || processingOrderId !== order.id" class="h-4 w-4" />
                                    <RefreshCw v-else class="h-4 w-4 animate-spin" />
                                    {{ isLoading && processingOrderId === order.id ? 'Checking...' : 'CheckAvailability'
                                    }}
                                </button>
                            </div>
                        </div>

                        <!-- Material Requirements Summary -->
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <h4 class="text-xs font-bold uppercase text-gray-500 mb-2 flex items-center gap-1">
                                <Layers class="h-3.5 w-3.5" /> Required Materials (from BOM)
                            </h4>
                            <div class="flex flex-wrap gap-2">
                                <div v-for="(need, materialId) in order.material_needs" :key="materialId"
                                    class="bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full text-xs">
                                    Material #{{ materialId }}: {{ need.toLocaleString() }} units
                                </div>
                                <div v-if="Object.keys(order.material_needs).length === 0"
                                    class="text-xs text-gray-400 italic">
                                    No BOM defined for this order.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Material Requirements Modal -->
        <Teleport to="body">
            <div v-if="showDetailModal && selectedOrder"
                class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeModal">
                <div class="bg-white dark:bg-gray-900 rounded-xl max-w-2xl w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h2 class="text-xl font-bold">Material Requirements</h2>
                        <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                    <div class="p-6">
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Order #: <strong>{{
                                selectedOrder.po_number }}</strong></p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Client: <strong>{{
                                selectedOrder.client?.company_name }}</strong></p>
                        </div>

                        <div class="space-y-4">
                            <div v-for="(need, materialId) in selectedOrder.material_needs" :key="materialId"
                                class="border rounded-lg p-3">
                                <p class="font-semibold">Material ID: {{ materialId }}</p>
                                <p class="text-sm">Required Quantity: {{ need.toLocaleString() }} units</p>
                            </div>
                            <div v-if="Object.keys(selectedOrder.material_needs).length === 0"
                                class="text-center py-8 text-gray-500">
                                No BOM defined. Material requirements cannot be calculated.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Insufficient Materials Modal -->
        <Teleport to="body">
            <div v-if="showInsufficientModal && insufficientMaterials"
                class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
                @click.self="closeInsufficientModal">
                <div class="bg-white dark:bg-gray-900 rounded-xl max-w-2xl w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h2 class="text-xl font-bold text-red-600 flex items-center gap-2">
                            <AlertTriangle class="h-5 w-5" /> Insufficient Materials
                        </h2>
                        <button @click="closeInsufficientModal" class="text-gray-500 hover:text-gray-700">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                    <div class="p-6">
                        <p class="mb-4 text-sm text-gray-600">The following materials are insufficient. Material
                            requests have been created and forwarded to SCM for procurement.</p>
                        <div class="space-y-3">
                            <div v-for="mat in insufficientMaterials" :key="mat.material_id"
                                class="border border-red-200 rounded-lg p-3 bg-red-50 dark:bg-red-900/10">
                                <p class="font-semibold">{{ mat.material }}</p>
                                <div class="grid grid-cols-2 gap-2 text-sm mt-2">
                                    <span>Stock: {{ mat.stock.toLocaleString() }} {{ mat.unit }}</span>
                                    <span>Required: {{ mat.need.toLocaleString() }} {{ mat.unit }}</span>
                                    <span class="text-red-600 font-bold">Deficit: {{ mat.deficit.toLocaleString() }} {{
                                        mat.unit }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 text-center">
                            <button @click="closeInsufficientModal"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>