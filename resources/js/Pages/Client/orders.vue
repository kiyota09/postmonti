<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ShoppingBag,
    FileText,
    TrendingUp,
    Clock,
    CheckCircle2,
    CreditCard,
    ArrowUpRight,
    Plus
} from 'lucide-vue-next';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            pending_orders: 0,
            completed_orders: 0,
            recent_orders: []
        })
    }
});

const page = usePage();
const client = computed(() => page.props.auth?.client);
</script>

<template>

    <Head title="Orders - Partner Portal" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-8">

            <div
                class="relative overflow-hidden rounded-[2rem] bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 p-8 shadow-sm">
                <div class="absolute -right-20 -top-20 h-64 w-64 bg-blue-500/5 rounded-full blur-3xl"></div>

                <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <div
                            class="flex items-center gap-2 text-blue-600 font-black text-[10px] uppercase tracking-widest mb-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                            B2B Partner Portal
                        </div>
                        <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight uppercase">
                            Welcome, <span class="text-blue-600">{{ client?.company_name || 'Partner' }}</span>
                        </h1>
                        <p class="text-sm font-medium text-gray-500 mt-1">
                            Managing orders for {{ client?.contact_person ?? 'Main Representative' }}
                        </p>
                    </div>

                    <div v-if="client"
                        class="flex items-center gap-3 bg-slate-50 dark:bg-gray-800/50 p-2 pr-4 rounded-2xl border border-gray-100 dark:border-gray-700">
                        <div
                            class="h-10 w-10 rounded-xl bg-green-500/10 flex items-center justify-center text-green-600">
                            <CheckCircle2 class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter leading-none">
                                Account Status</p>
                            <p class="text-xs font-bold text-gray-900 dark:text-white uppercase">{{ client.status }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div
                    class="group relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-blue-600 to-indigo-700 p-6 text-white shadow-lg shadow-blue-500/20">
                    <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4">
                        <CreditCard class="h-32 w-32" />
                    </div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80">Available Credit Limit
                        </p>
                        <h3 class="mt-2 text-3xl font-black tracking-tight">
                            ₱{{ client?.credit_limit ? parseFloat(client.credit_limit).toLocaleString() : '0' }}
                        </h3>
                        <div class="mt-4 flex items-center gap-2">
                            <span class="text-[10px] font-bold bg-white/20 px-2 py-1 rounded-lg">
                                Terms: {{ client?.payment_terms_days ?? 30 }} Days Net
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-[2rem] bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div
                            class="h-12 w-12 rounded-2xl bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center text-orange-600">
                            <Clock class="h-6 w-6" />
                        </div>
                        <span
                            class="text-[10px] font-black text-orange-600 bg-orange-50 dark:bg-orange-900/30 px-2 py-1 rounded-lg uppercase">In
                            Progress</span>
                    </div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Pending Orders</p>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ stats.pending_orders }}</h3>
                    <p class="text-xs text-gray-500 mt-1 font-medium italic">Awaiting Manufacturing</p>
                </div>

                <div
                    class="rounded-[2rem] bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div
                            class="h-12 w-12 rounded-2xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600">
                            <TrendingUp class="h-6 w-6" />
                        </div>
                        <span
                            class="text-[10px] font-black text-blue-600 bg-blue-50 dark:bg-blue-900/30 px-2 py-1 rounded-lg uppercase">{{
                                client?.business_type || 'B2B' }}</span>
                    </div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Tax Information</p>
                    <h3 class="text-xl font-black text-gray-900 dark:text-white mt-1 truncate">TIN: {{
                        client?.tin_number || 'N/A' }}</h3>
                    <p class="text-xs text-gray-500 mt-1 font-medium">Verified Business Account</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <Link href="#"
                    class="group flex items-center justify-center gap-3 rounded-2xl bg-gray-900 dark:bg-white px-8 py-5 text-white dark:text-gray-900 shadow-xl transition-all hover:scale-[1.02] active:scale-95">
                    <Plus class="h-5 w-5" />
                    <span class="font-black uppercase text-sm tracking-widest">New Wholesale Order</span>
                </Link>

                <Link href="#"
                    class="group flex items-center justify-center gap-3 rounded-2xl bg-white dark:bg-gray-900 border-2 border-gray-900 dark:border-white px-8 py-5 text-gray-900 dark:text-white transition-all hover:bg-gray-50 dark:hover:bg-gray-800">
                    <FileText class="h-5 w-5" />
                    <span class="font-black uppercase text-sm tracking-widest">View Invoices</span>
                    <ArrowUpRight class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-all" />
                </Link>
            </div>

            <div
                class="overflow-hidden rounded-[2rem] bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm">
                <div class="flex items-center justify-between px-8 py-6 border-b border-gray-50 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div
                            class="h-8 w-8 rounded-lg bg-slate-100 dark:bg-gray-800 flex items-center justify-center text-gray-500">
                            <ShoppingBag class="h-4 w-4" />
                        </div>
                        <h3 class="font-black text-gray-900 dark:text-white uppercase text-sm tracking-tighter">Recent
                            Order History</h3>
                    </div>
                    <Link href="#" class="text-[10px] font-black text-blue-600 uppercase hover:underline">View All
                        Orders</Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead
                            class="bg-slate-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-widest">
                            <tr>
                                <th class="px-8 py-4">Order Reference</th>
                                <th class="px-8 py-4">Placement Date</th>
                                <th class="px-8 py-4 text-right">Total Amount</th>
                                <th class="px-8 py-4 text-center">Current Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-if="stats.recent_orders.length === 0">
                                <td colspan="4" class="px-8 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center opacity-40">
                                        <ShoppingBag class="h-12 w-12 mb-4" />
                                        <p class="text-sm font-bold text-gray-500 italic uppercase">No orders found in
                                            history</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="order in stats.recent_orders" :key="order.id"
                                class="group hover:bg-slate-50/50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-8 py-5">
                                    <span
                                        class="font-black text-gray-900 dark:text-white tracking-tighter uppercase text-sm">#ORD-{{
                                        order.id }}</span>
                                </td>
                                <td class="px-8 py-5 text-sm font-bold text-gray-500 tracking-tight">{{ order.date }}
                                </td>
                                <td class="px-8 py-5 text-right font-black text-gray-900 dark:text-white text-sm">₱{{
                                    order.total }}</td>
                                <td class="px-8 py-5 text-center">
                                    <span
                                        class="inline-flex items-center rounded-lg bg-orange-50 dark:bg-orange-900/20 px-3 py-1.5 text-[10px] font-black text-orange-600 uppercase">
                                        Processing
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.tracking-tighter {
    letter-spacing: -0.05em;
}
</style>