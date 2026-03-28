<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Eye, Check, X, Send } from 'lucide-vue-next';

const props = defineProps({
    orders: Object,
});

const approveOrder = (order) => {
    router.post(route('eco.manager.order.approve', order.id));
};
const rejectOrder = (order) => {
    const reason = prompt('Rejection reason:');
    if (reason) router.post(route('eco.manager.order.reject', order.id), { reason });
};
const sendToSCM = (order) => {
    router.post(route('eco.manager.order.send-to-scm', order.id));
};

const getStatusBadge = (status) => {
    const map = {
        credit_review: 'bg-orange-100 text-orange-700',
        tier_assignment: 'bg-blue-100 text-blue-700',
        pending_client_approval: 'bg-purple-100 text-purple-700',
        approved: 'bg-green-100 text-green-700',
        rejected: 'bg-red-100 text-red-700',
    };
    return map[status] || 'bg-gray-100 text-gray-700';
};
</script>

<template>

    <Head title="Order Management" />
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto p-4">
            <h1 class="text-2xl font-bold mb-6">Order Management</h1>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left">PO #</th>
                            <th class="px-6 py-3 text-left">Client</th>
                            <th class="px-6 py-3 text-right">Total</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3 text-center">Queue Stage</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="order in orders.data" :key="order.id">
                            <td class="px-6 py-4">{{ order.po_number }}</td>
                            <td class="px-6 py-4">{{ order.client?.company_name }}</td>
                            <td class="px-6 py-4 text-right">₱{{ order.total_amount.toLocaleString() }}</td>
                            <td class="px-6 py-4 text-center">
                                <span :class="getStatusBadge(order.status)" class="px-2 py-1 rounded-full text-xs">
                                    {{ order.status.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span v-if="order.queue_stage" class="text-xs">{{ order.queue_stage }}</span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button v-if="order.status === 'credit_review'" @click="approveOrder(order)"
                                    class="text-green-600 hover:text-green-800">
                                    <Check class="h-5 w-5" />
                                </button>
                                <button v-if="order.status === 'credit_review'" @click="rejectOrder(order)"
                                    class="text-red-600 hover:text-red-800">
                                    <X class="h-5 w-5" />
                                </button>
                                <button v-if="order.queue_stage === 'eco_approved'" @click="sendToSCM(order)"
                                    class="text-blue-600 hover:text-blue-800">
                                    <Send class="h-5 w-5" />
                                </button>
                                <button @click="router.get(route('eco.manager.order.show', order.id))"
                                    class="text-gray-600 hover:text-gray-800">
                                    <Eye class="h-5 w-5" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <Link :href="orders.links.prev" v-if="orders.links.prev" class="mr-2">Previous</Link>
                <Link :href="orders.links.next" v-if="orders.links.next">Next</Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>