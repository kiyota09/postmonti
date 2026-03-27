<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    Receipt,
    Download,
    Search,
    Clock,
    AlertCircle,
    ArrowUpRight,
    FileText,
    ShieldCheck,
    Tag,
    Wallet,
    Calendar,
    ArrowDownToLine
} from 'lucide-vue-next';

const props = defineProps({
    invoices: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({
            totalOutstanding: '0.00',
            utilization: 0,
            pendingCount: 0,
            lastPaymentDate: 'No records'
        })
    }
});

const page = usePage();
const client = computed(() => page.props.auth?.client);

const getStatusClass = (status) => {
    switch (status.toLowerCase()) {
        case 'approved': return 'bg-green-50 text-green-600 dark:bg-green-900/20 border-green-100';
        case 'pending': return 'bg-orange-50 text-orange-600 dark:bg-orange-900/20 border-orange-100';
        default: return 'bg-gray-50 text-gray-600 border-gray-100';
    }
};

// Helper for date formatting
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>

    <Head title="Financial Statements - Partner Portal" />

    <AuthenticatedLayout>
        <div class="max-w-[1400px] mx-auto space-y-10 p-4 lg:p-8">

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div
                        class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Receipt class="h-3.5 w-3.5" />
                        Accounts Receivable
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Invoice <span class="text-indigo-600">Ledger</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">Financial transparency for {{
                        client?.company_name }}</p>
                </div>

                <button
                    class="flex items-center gap-2 px-6 py-3 rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm text-[10px] font-black uppercase tracking-widest text-gray-600 dark:text-gray-300 hover:bg-gray-50 transition-all">
                    <ArrowDownToLine class="h-4 w-4" />
                    Export Statement
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div
                    class="p-8 rounded-[2.5rem] bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Statement Balance</p>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white tracking-tighter">₱{{
                        stats.totalOutstanding }}</h3>
                    <div
                        class="mt-4 flex items-center gap-2 text-[10px] font-bold text-orange-600 bg-orange-50 px-2.5 py-1.5 rounded-xl w-fit">
                        <Clock class="h-3.5 w-3.5" /> {{ stats.pendingCount }} Invoices Unpaid
                    </div>
                </div>

                <div
                    class="p-8 rounded-[2.5rem] bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Credit Utilization
                    </p>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white tracking-tighter">{{ stats.utilization
                        }}%</h3>
                    <div class="mt-5 w-full bg-gray-100 dark:bg-gray-800 h-2 rounded-full overflow-hidden">
                        <div class="bg-indigo-600 h-full transition-all duration-1000"
                            :style="{ width: stats.utilization + '%' }"></div>
                    </div>
                </div>

                <div class="p-8 rounded-[2.5rem] bg-indigo-600 shadow-2xl shadow-indigo-500/30 text-white">
                    <p class="text-[10px] font-black uppercase tracking-widest opacity-70 mb-1">Account Activity</p>
                    <h3 class="text-2xl font-black tracking-tighter">{{ stats.lastPaymentDate }}</h3>
                    <p class="mt-4 text-[10px] font-bold uppercase opacity-90 italic flex items-center gap-2">
                        <ShieldCheck class="h-4 w-4" /> Secure Ledger Sync Active
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div v-for="invoice in invoices" :key="invoice.id"
                    class="relative bg-white dark:bg-gray-900 rounded-[3rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden flex flex-col">

                    <div
                        class="p-8 border-b border-dashed border-gray-100 dark:border-gray-800 flex justify-between items-start bg-gray-50/30 dark:bg-gray-800/20">
                        <div class="space-y-1">
                            <h4
                                class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter italic">
                                Statement #{{ invoice.po_number }}</h4>
                            <p
                                class="text-[10px] font-bold text-gray-400 uppercase tracking-widest italic flex items-center gap-2">
                                <Calendar class="h-3 w-3" /> Issued: {{ formatDate(invoice.updated_at) }}
                            </p>
                        </div>
                        <span :class="getStatusClass(invoice.status)"
                            class="px-4 py-2 rounded-2xl text-[10px] font-black uppercase tracking-widest border">
                            {{ invoice.status }}
                        </span>
                    </div>

                    <div class="p-8 space-y-6 flex-grow">
                        <div class="flex justify-between items-center group">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                    <FileText class="h-5 w-5 text-gray-400" />
                                </div>
                                <span class="text-xs font-black text-gray-400 uppercase tracking-widest">Base
                                    Subtotal</span>
                            </div>
                            <span class="text-sm font-black text-gray-900 dark:text-white">₱{{
                                parseFloat(invoice.subtotal).toLocaleString() }}</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                                    <Tag class="h-5 w-5 text-emerald-600" />
                                </div>
                                <span class="text-xs font-black text-emerald-600 uppercase tracking-widest">{{
                                    invoice.tier_level }} Discount</span>
                            </div>
                            <span class="text-sm font-black text-emerald-600">-₱{{
                                parseFloat(invoice.discount_amount).toLocaleString() }}</span>
                        </div>

                        <div
                            class="pt-6 border-t border-gray-50 dark:border-gray-800 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl">
                                    <Wallet class="h-5 w-5 text-indigo-600" />
                                </div>
                                <span class="text-xs font-black text-indigo-600 uppercase tracking-widest">Amount
                                    Payable</span>
                            </div>
                            <span class="text-2xl font-black text-gray-900 dark:text-white italic tracking-tighter">₱{{
                                parseFloat(invoice.total_amount).toLocaleString() }}</span>
                        </div>
                    </div>

                    <div class="bg-gray-50/50 dark:bg-gray-800/20 p-8 flex gap-3">
                        <button
                            class="flex-1 py-4 rounded-2xl bg-white dark:bg-gray-950 border border-gray-100 dark:border-gray-800 text-[10px] font-black uppercase tracking-widest hover:bg-gray-100 transition-all flex items-center justify-center gap-2">
                            <Download class="h-4 w-4" /> Save Receipt
                        </button>
                        <button
                            class="px-6 py-4 rounded-2xl bg-indigo-600 text-white shadow-lg text-[10px] font-black uppercase tracking-widest hover:brightness-110 transition-all">
                            Pay via Credit Line
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="!invoices.length"
                class="text-center py-20 bg-white dark:bg-gray-900 rounded-[3rem] border border-dashed border-gray-200">
                <AlertCircle class="h-12 w-12 text-gray-200 mx-auto mb-4" />
                <p class="text-xs font-black text-gray-300 uppercase tracking-widest italic tracking-[0.2em]">No
                    official invoices found in ledger</p>
            </div>

            <div
                class="flex items-center justify-between p-8 rounded-[3rem] bg-slate-50 dark:bg-gray-900/50 border border-dashed border-gray-200 dark:border-gray-800 mt-12">
                <div class="flex items-center gap-6">
                    <div
                        class="h-16 w-16 rounded-3xl bg-white dark:bg-gray-800 flex items-center justify-center text-indigo-600 shadow-sm border border-indigo-50">
                        <AlertCircle class="h-8 w-8" />
                    </div>
                    <div>
                        <h4
                            class="text-md font-black uppercase tracking-tight text-gray-900 dark:text-white italic underline decoration-indigo-200">
                            Billing Inquiry?</h4>
                        <p class="text-sm font-medium text-gray-500">Our financial audit team is available for credit
                            limit reconsiderations or payment term adjustments.</p>
                    </div>
                </div>
                <Link :href="route('client.support')"
                    class="flex items-center gap-2 text-xs font-black text-indigo-600 uppercase tracking-widest hover:underline px-6 py-4 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-indigo-50">
                    Open Support Ticket
                    <ArrowUpRight class="h-4 w-4" />
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.tracking-tighter {
    letter-spacing: -0.05em;
}
</style>