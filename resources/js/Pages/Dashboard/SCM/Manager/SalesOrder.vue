<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    ShoppingCart,
    Package,
    Truck,
    Eye,
    Send,
    Clock,
    AlertCircle,
    ChevronRight,
    Building2,
    Calendar,
    DollarSign,
    FileText,
    X
} from 'lucide-vue-next';

const props = defineProps({
    orders: Array,
});

const isLoading = ref(false);
const selectedOrder = ref(null);
const showDetailModal = ref(false);

const forwardToINV = (orderId) => {
    if (confirm('Forward this order to Inventory for material check?')) {
        isLoading.value = true;
        router.post(route('scm.manager.sales-orders.forward', orderId), {}, {
            preserveScroll: true,
            onSuccess: () => {
                isLoading.value = false;
            },
            onError: () => {
                isLoading.value = false;
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

const formatCurrency = (val) => '₱' + Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });
const formatDate = (date) => date ? new Date(date).toLocaleDateString('en-PH') : '—';

const getStatusBadge = (stage) => {
    const map = {
        scm_received: 'bg-blue-100 text-blue-700',
        inv_check: 'bg-amber-100 text-amber-700',
        man_production: 'bg-purple-100 text-purple-700',
        completed: 'bg-emerald-100 text-emerald-700'
    };
    return map[stage] || 'bg-gray-100 text-gray-700';
};
</script>

<template>

    <Head title="Sales Orders - SCM Manager" />
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-8 p-4">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sales Orders from ECO</h1>
                    <p class="text-sm text-gray-500">Orders approved by E-Commerce and ready for material check</p>
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <span class="flex items-center gap-1">
                        <Clock class="h-3.5 w-3.5" /> First-in, first-out processing
                    </span>
                </div>
            </div>

            <!-- Orders List -->
            <div v-if="orders.length === 0" class="bg-white dark:bg-gray-800 rounded-lg border p-12 text-center">
                <Package class="h-12 w-12 text-gray-300 mx-auto mb-4" />
                <p class="text-gray-500">No sales orders pending.</p>
                <p class="text-sm text-gray-400">Orders approved by ECO will appear here.</p>
            </div>

            <div v-else class="space-y-4">
                <div v-for="order in orders" :key="order.id"
                    class="bg-white dark:bg-gray-800 rounded-lg border shadow-sm hover:shadow-md transition">
                    <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <!-- Order Info -->
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs font-mono font-bold text-gray-500">#{{ order.po_number }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                                    {{ order.queue?.stage?.replace('_', ' ') || 'SCM Received' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Building2 class="h-4 w-4 text-gray-400" />
                                <span class="font-medium text-gray-900 dark:text-white">{{ order.client?.company_name
                                    }}</span>
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

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <button @click="openDetail(order)"
                                class="px-4 py-2 border rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-1">
                                <Eye class="h-4 w-4" /> Details
                            </button>
                            <button @click="forwardToINV(order.id)" :disabled="isLoading"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 flex items-center gap-1">
                                <Send class="h-4 w-4" /> Forward to INV
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Order Details -->
        <Teleport to="body">
            <div v-if="showDetailModal && selectedOrder"
                class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeModal">
                <div class="bg-white dark:bg-gray-900 rounded-xl max-w-2xl w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h2 class="text-xl font-bold">Order Details</h2>
                        <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                    <div class="p-6 space-y-6">
                        <!-- Header -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">PO Number</p>
                                <p class="font-bold">{{ selectedOrder.po_number }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Date</p>
                                <p>{{ formatDate(selectedOrder.created_at) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Client</p>
                                <p class="font-medium">{{ selectedOrder.client?.company_name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Tier</p>
                                <p>{{ selectedOrder.tier_level || 'Normal' }}</p>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div>
                            <h3 class="font-semibold mb-2">Items</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th class="px-4 py-2 text-left">Product</th>
                                            <th class="px-4 py-2 text-right">Qty</th>
                                            <th class="px-4 py-2 text-right">Unit Price</th>
                                            <th class="px-4 py-2 text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <tr v-for="item in selectedOrder.items" :key="item.id">
                                            <td class="px-4 py-2">{{ item.product?.name || 'Product' }}</td>
                                            <td class="px-4 py-2 text-right">{{ item.quantity }}</td>
                                            <td class="px-4 py-2 text-right">{{ formatCurrency(item.unit_price) }}</td>
                                            <td class="px-4 py-2 text-right">{{ formatCurrency(item.quantity *
                                                item.unit_price) }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <td colspan="3" class="px-4 py-2 text-right font-semibold">Subtotal</td>
                                            <td class="px-4 py-2 text-right">{{ formatCurrency(selectedOrder.subtotal)
                                                }}</td>
                                        </tr>
                                        <tr v-if="selectedOrder.discount_amount > 0">
                                            <td colspan="3" class="px-4 py-2 text-right font-semibold">Discount</td>
                                            <td class="px-4 py-2 text-right">-{{
                                                formatCurrency(selectedOrder.discount_amount) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="px-4 py-2 text-right font-semibold">Total</td>
                                            <td class="px-4 py-2 text-right font-bold">{{
                                                formatCurrency(selectedOrder.total_amount) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2">
                        <button @click="closeModal" class="px-4 py-2 border rounded-lg">Close</button>
                        <button @click="forwardToINV(selectedOrder.id); closeModal()"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg">Forward to INV</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>