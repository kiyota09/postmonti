<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ClipboardList, DollarSign, ArrowRight, Clock, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    materialRequests: Array,
    invoices: Array,
});

const forwardRequest = (id) => {
    router.post(route('scm.manager.material-request.forward', id), {}, {
        preserveScroll: true,
    });
};
</script>

<template>

    <Head title="SCM Manager Dashboard" />
    <AuthenticatedLayout>
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">SCM Command Center</h2>
            <p class="text-sm text-gray-500">Central coordination hub – forward requests to Procurement, approve
                payments.</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Pending Material Requests
                        </p>
                        <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{
                            stats.pendingMaterialRequests }}</p>
                    </div>
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                        <ClipboardList class="w-6 h-6 text-blue-600" />
                    </div>
                </div>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Pending Payments</p>
                        <p class="text-3xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ stats.pendingPayments
                            }}</p>
                    </div>
                    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                        <DollarSign class="w-6 h-6 text-amber-600" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Material Requests Section -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 mb-8 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <h3 class="font-bold text-gray-800 dark:text-white">Material Requests from INV</h3>
                <Link :href="route('scm.manager.payments')"
                    class="text-xs font-semibold text-blue-600 hover:underline flex items-center gap-1">
                Go to Payments
                <ArrowRight class="w-3 h-3" />
                </Link>
            </div>
            <div class="p-6">
                <div v-if="materialRequests.length === 0" class="text-center py-8 text-gray-400">
                    No pending material requests.
                </div>
                <div v-else class="space-y-3">
                    <div v-for="req in materialRequests" :key="req.id"
                        class="flex items-center justify-between p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                        <div>
                            <p class="font-semibold text-gray-800 dark:text-white">{{ req.material_name }}</p>
                            <p class="text-xs text-gray-500">Qty: {{ req.required_qty }} {{ req.unit }} | Urgency: {{
                                req.urgency }}</p>
                        </div>
                        <button @click="forwardRequest(req.id)"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg text-xs font-semibold hover:bg-blue-700 transition">
                            Forward to PRO
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Invoices Preview -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-bold text-gray-800 dark:text-white">Pending Invoices</h3>
            </div>
            <div class="p-6">
                <div v-if="invoices.length === 0" class="text-center py-8 text-gray-400">
                    No pending invoices.
                </div>
                <div v-else class="space-y-2">
                    <div v-for="inv in invoices" :key="inv.id" class="flex justify-between items-center">
                        <div>
                            <p class="font-mono text-xs">{{ inv.invoice_number }}</p>
                            <p class="text-sm font-medium">{{ inv.supplier_name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-amber-600">₱{{ inv.amount.toLocaleString() }}</p>
                            <p class="text-xs text-gray-400">Due: {{ inv.due_date }}</p>
                        </div>
                    </div>
                </div>
                <div class="mt-4 text-right">
                    <Link :href="route('scm.manager.payments')" class="text-sm text-blue-600 hover:underline">View all →
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>