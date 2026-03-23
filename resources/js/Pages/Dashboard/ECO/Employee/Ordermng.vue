<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import {
    Search, ArrowDownToLine, ShieldCheck, ChevronRight,
    PackageCheck, Clock, User, ExternalLink, Printer,
    ShoppingBag, AlertCircle, ClipboardList, X,
    Building2, FileText, Tag, Wallet, Info, Zap,
} from 'lucide-vue-next';

const props = defineProps({
    orders: { type: Object, default: () => ({ data: [], links: [], meta: {} }) },
    filters: { type: Object, default: () => ({ search: '' }) },
});

// ── State ─────────────────────────────────────────────────────────────────────
const search = ref(props.filters.search);
const activeTab = ref('all');
const showMonitorModal = ref(false);
const selectedOrder = ref(null);

// ── Tabs / filter ─────────────────────────────────────────────────────────────
const TABS = [
    { key: 'all', label: 'All Orders' },
    { key: 'credit_review', label: 'Credit Review' },
    { key: 'tier_assignment', label: 'Tier Assignment' },
    { key: 'pending_client_approval', label: 'Pending Approval' },
    { key: 'approved', label: 'Approved' },
];

const filteredOrders = computed(() => {
    if (activeTab.value === 'all') return props.orders.data;
    return props.orders.data.filter(o => o.status === activeTab.value);
});

// ── Stats ─────────────────────────────────────────────────────────────────────
const stats = computed(() => [
    { label: 'In Review', value: props.orders.data.filter(o => o.status === 'credit_review').length, icon: Clock, color: 'text-orange-500', bg: 'bg-orange-50 dark:bg-orange-900/20' },
    { label: 'Tiering Phase', value: props.orders.data.filter(o => o.status === 'tier_assignment').length, icon: ClipboardList, color: 'text-blue-600', bg: 'bg-blue-50 dark:bg-blue-900/20' },
    { label: 'Awaiting Client', value: props.orders.data.filter(o => o.status === 'pending_client_approval').length, icon: User, color: 'text-purple-600', bg: 'bg-purple-50 dark:bg-purple-900/20' },
    { label: 'Live Approvals', value: props.orders.data.filter(o => o.status === 'approved').length, icon: ShieldCheck, color: 'text-emerald-600', bg: 'bg-emerald-50 dark:bg-emerald-900/20' },
]);

// ── Helpers ───────────────────────────────────────────────────────────────────
const getStatusStyles = (status) => ({
    credit_review: 'bg-orange-50 text-orange-600 border-orange-100',
    tier_assignment: 'bg-blue-50 text-blue-600 border-blue-100',
    pending_client_approval: 'bg-purple-50 text-purple-600 border-purple-100',
    approved: 'bg-emerald-50 text-emerald-600 border-emerald-100',
    rejected: 'bg-red-50 text-red-600 border-red-100',
}[status] ?? 'bg-gray-50 text-gray-400 border-gray-100');

const getStatusDot = (status) => ({
    credit_review: 'bg-orange-400',
    tier_assignment: 'bg-blue-500',
    pending_client_approval: 'bg-purple-500',
    approved: 'bg-emerald-500',
    rejected: 'bg-red-500',
}[status] ?? 'bg-gray-300');

const fmt = (n) => Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// ── Monitor modal ─────────────────────────────────────────────────────────────
const openMonitor = (order) => {
    selectedOrder.value = order;
    showMonitorModal.value = true;
};

// ── Search debounce ───────────────────────────────────────────────────────────
watch(search, (value) => {
    router.get(route('eco.employee.ordermng'), { search: value }, { preserveState: true, replace: true });
});
</script>

<template>

    <Head title="Order Architect - Staff Monitoring" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <!-- ── Header ──────────────────────────────────────────────────── -->
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                <div class="space-y-1">
                    <div
                        class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <PackageCheck class="h-3 w-3" /> Fulfillment Monitoring
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Order <span class="text-indigo-600">Architect</span>
                    </h1>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        class="px-6 py-3 rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 text-[11px] font-black uppercase tracking-widest text-gray-600 hover:bg-gray-50 transition-all flex items-center gap-2">
                        <ArrowDownToLine class="h-4 w-4" /> Export Pipeline
                    </button>
                </div>
            </div>

            <!-- ── Stats ───────────────────────────────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="stat in stats" :key="stat.label"
                    class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm flex items-center justify-between hover:shadow-md transition-all">
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ stat.label }}</p>
                        <p class="text-3xl font-black text-gray-900 dark:text-white mt-1 tracking-tighter italic">{{
                            stat.value }}</p>
                    </div>
                    <div :class="[stat.bg, stat.color]" class="h-14 w-14 rounded-2xl flex items-center justify-center">
                        <component :is="stat.icon" class="h-7 w-7" />
                    </div>
                </div>
            </div>

            <!-- ── Orders Panel ─────────────────────────────────────────────── -->
            <div
                class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">

                <!-- Toolbar -->
                <div
                    class="p-8 border-b border-gray-50 dark:border-gray-800 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                    <!-- Tabs -->
                    <div class="flex p-1.5 bg-gray-50 dark:bg-gray-950 rounded-2xl overflow-x-auto no-scrollbar gap-1">
                        <button v-for="tab in TABS" :key="tab.key" @click="activeTab = tab.key" :class="activeTab === tab.key
                            ? 'bg-white dark:bg-gray-800 shadow-sm text-indigo-600'
                            : 'text-gray-400 hover:text-gray-600'"
                            class="px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest whitespace-nowrap transition-all">
                            {{ tab.label }}
                        </button>
                    </div>

                    <!-- Search -->
                    <div class="relative w-full lg:w-72 group flex-shrink-0">
                        <Search
                            class="absolute left-4 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400 group-focus-within:text-indigo-600 transition-colors" />
                        <input v-model="search" type="text" placeholder="Search PO # or Client..."
                            class="w-full pl-11 pr-4 py-3 rounded-2xl bg-gray-50 dark:bg-gray-950 border border-gray-100 dark:border-gray-800 text-[10px] font-black uppercase tracking-widest text-gray-700 dark:text-gray-300 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition-all" />
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead
                            class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-8 py-5 italic">Purchase Order & Client</th>
                                <th class="px-8 py-5">Workflow Valuation</th>
                                <th class="px-8 py-5 text-center">Status Index</th>
                                <th class="px-8 py-5 text-right">Operations</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="order in filteredOrders" :key="order.id"
                                class="group hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-all">

                                <!-- PO + Client -->
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="h-11 w-11 rounded-[1rem] bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 border border-indigo-100 dark:border-indigo-800 shadow-sm flex-shrink-0">
                                            <ShoppingBag class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p
                                                class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-tighter italic">
                                                {{ order.po_number }}</p>
                                            <p
                                                class="text-[10px] font-bold text-gray-400 uppercase tracking-widest italic">
                                                {{ order.client?.company_name }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Valuation -->
                                <td class="px-8 py-6">
                                    <p class="text-sm font-black text-gray-900 dark:text-white italic tracking-tighter">
                                        ₱{{ fmt(order.total_amount) }}</p>
                                    <p class="text-[9px] font-bold text-indigo-500 uppercase italic mt-0.5">Tier: {{
                                        order.tier_level || 'Calculating...' }}</p>
                                </td>

                                <!-- Status -->
                                <td class="px-8 py-6 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <span :class="getStatusDot(order.status)"
                                            class="w-1.5 h-1.5 rounded-full flex-shrink-0"></span>
                                        <span :class="getStatusStyles(order.status)"
                                            class="px-3 py-1 rounded-xl text-[9px] font-black uppercase tracking-widest border">
                                            {{ order.status.replace(/_/g, ' ') }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Action -->
                                <td class="px-8 py-6 text-right">
                                    <button @click="openMonitor(order)"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-950 dark:bg-white text-white dark:text-gray-900 rounded-xl text-[9px] font-black uppercase tracking-widest hover:scale-105 transition-all active:scale-95 shadow-lg shadow-black/10">
                                        Monitor
                                        <ExternalLink class="h-3.5 w-3.5" />
                                    </button>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="filteredOrders.length === 0">
                                <td colspan="4" class="px-8 py-20 text-center">
                                    <ShoppingBag class="h-10 w-10 text-gray-200 mx-auto mb-3" />
                                    <p class="text-[10px] font-black text-gray-300 uppercase tracking-widest italic">No
                                        orders found in this pipeline stage</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="orders.meta?.last_page > 1"
                    class="px-8 py-5 border-t border-gray-50 dark:border-gray-800 flex items-center justify-between">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest italic">
                        Showing {{ orders.meta.from }}–{{ orders.meta.to }} of {{ orders.meta.total }} orders
                    </p>
                    <div class="flex gap-2">
                        <component v-for="link in orders.links" :key="link.label" :is="link.url ? 'a' : 'span'"
                            :href="link.url ?? undefined" v-html="link.label" :class="[
                                'px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all',
                                link.active ? 'bg-indigo-600 text-white shadow-sm' : link.url ? 'text-gray-400 hover:text-indigo-600 hover:bg-indigo-50' : 'text-gray-200 cursor-default'
                            ]" />
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ MONITOR MODAL ══════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="showMonitorModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/60 backdrop-blur-md"
                @click.self="showMonitorModal = false">
                <div
                    class="bg-white dark:bg-gray-900 w-full max-w-4xl rounded-[3rem] border border-gray-100 dark:border-gray-800 shadow-2xl overflow-hidden">

                    <!-- Modal header -->
                    <div class="px-10 py-8 bg-indigo-600 text-white flex justify-between items-center">
                        <div class="flex items-center gap-4">
                            <div
                                class="h-12 w-12 rounded-2xl bg-white/10 flex items-center justify-center backdrop-blur-sm">
                                <Info class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-2xl font-black uppercase tracking-tighter italic">Order Breakdown</h3>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80">Monitoring PO
                                    #{{ selectedOrder.po_number }}</p>
                            </div>
                        </div>
                        <button @click="showMonitorModal = false"
                            class="p-3 bg-white/10 rounded-2xl hover:bg-white/20 transition-all">
                            <X class="h-6 w-6" />
                        </button>
                    </div>

                    <!-- Modal body -->
                    <div class="p-10 grid grid-cols-1 lg:grid-cols-12 gap-10 max-h-[70vh] overflow-y-auto no-scrollbar">

                        <!-- Left: partner + manifest -->
                        <div class="lg:col-span-7 space-y-8">

                            <!-- Partner info -->
                            <div class="space-y-3">
                                <div
                                    class="flex items-center gap-2 text-indigo-600 text-[10px] font-black uppercase tracking-widest">
                                    <Building2 class="h-3.5 w-3.5" /> Partner Information
                                </div>
                                <div
                                    class="p-6 bg-gray-50 dark:bg-gray-800 rounded-[2rem] border border-gray-100 dark:border-gray-700/50">
                                    <h4 class="text-lg font-black text-gray-900 dark:text-white uppercase italic">{{
                                        selectedOrder.client?.company_name }}</h4>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mt-1">
                                        Status: {{ selectedOrder.client?.status || 'Active' }} Partner
                                    </p>
                                </div>
                            </div>

                            <!-- Manifest -->
                            <div class="space-y-3">
                                <div
                                    class="flex items-center gap-2 text-indigo-600 text-[10px] font-black uppercase tracking-widest">
                                    <FileText class="h-3.5 w-3.5" /> Manifest Breakdown
                                </div>
                                <div class="space-y-3 max-h-[280px] overflow-y-auto no-scrollbar pr-1">
                                    <div v-for="item in selectedOrder.items" :key="item.id"
                                        class="flex items-center justify-between p-4 bg-white dark:bg-gray-850 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="h-10 w-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-400 flex-shrink-0">
                                                <PackageCheck class="h-5 w-5" />
                                            </div>
                                            <div>
                                                <p
                                                    class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-tighter">
                                                    {{ item.product?.name || 'Textile SKU' }}</p>
                                                <p
                                                    class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">
                                                    Qty: {{ item.quantity }} × ₱{{ fmt(item.unit_price) }}
                                                </p>
                                            </div>
                                        </div>
                                        <p class="text-xs font-black text-gray-900 dark:text-white italic">₱{{
                                            fmt(item.quantity * item.unit_price) }}</p>
                                    </div>

                                    <div v-if="!selectedOrder.items?.length"
                                        class="py-10 text-center bg-gray-50 dark:bg-gray-800 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700">
                                        <p
                                            class="text-[10px] font-black text-gray-300 uppercase tracking-widest italic">
                                            No line items recorded</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: financial summary -->
                        <div class="lg:col-span-5">
                            <div
                                class="bg-gray-50 dark:bg-gray-800 rounded-[3rem] p-8 border border-indigo-50 dark:border-indigo-900/20 flex flex-col h-full">
                                <h5
                                    class="text-[10px] font-black uppercase tracking-widest text-indigo-600 mb-8 border-b border-indigo-100 dark:border-indigo-900/40 pb-4">
                                    Financial Finalization
                                </h5>

                                <div class="space-y-6 flex-grow">
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center gap-2">
                                            <div class="p-1.5 bg-white dark:bg-gray-700 rounded-lg shadow-sm">
                                                <Wallet class="h-4 w-4 text-gray-400" />
                                            </div>
                                            <span class="text-[10px] font-black text-gray-500 uppercase">Subtotal</span>
                                        </div>
                                        <span class="text-sm font-black text-gray-900 dark:text-white italic">₱{{
                                            fmt(selectedOrder.subtotal) }}</span>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="p-1.5 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg shadow-sm">
                                                <Tag class="h-4 w-4 text-emerald-600" />
                                            </div>
                                            <span class="text-[10px] font-black text-emerald-600 uppercase">{{
                                                selectedOrder.tier_level || 'Normal' }} Tier Save</span>
                                        </div>
                                        <span class="text-sm font-black text-emerald-600 italic">-₱{{
                                            fmt(selectedOrder.discount_amount) }}</span>
                                    </div>

                                    <div
                                        class="pt-6 border-t border-dashed border-gray-200 dark:border-gray-700 flex justify-between items-end">
                                        <div>
                                            <span
                                                class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.2em] block mb-1">Final
                                                Payable</span>
                                            <p
                                                class="text-3xl font-black text-gray-900 dark:text-white tracking-tighter italic">
                                                ₱{{ fmt(selectedOrder.total_amount) }}</p>
                                        </div>
                                        <div
                                            class="h-10 w-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white rotate-12 shadow-lg shadow-indigo-500/30">
                                            <ShieldCheck class="h-6 w-6" />
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-10 pt-8 border-t border-indigo-100 dark:border-indigo-900/50">
                                    <p class="text-[9px] font-bold text-gray-400 uppercase italic leading-relaxed">
                                        Authorized ERP Verification: This monitoring log represents a live database
                                        snapshot of the B2B fulfillment workflow.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal footer -->
                    <div
                        class="px-10 py-6 bg-gray-50/50 dark:bg-gray-800/30 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                        <button
                            class="px-8 py-3 rounded-2xl bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-[10px] font-black uppercase tracking-widest text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-900 transition-all flex items-center gap-2">
                            <Printer class="h-4 w-4" /> Print Manifest
                        </button>
                        <button @click="showMonitorModal = false"
                            class="px-10 py-3 rounded-2xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-[10px] font-black uppercase tracking-widest shadow-xl hover:scale-105 active:scale-95 transition-all">
                            Close Log
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>