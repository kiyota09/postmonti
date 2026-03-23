<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
// FIXED: Removed ExclamationTriangle and ensured all icons are standard Lucide names
import {
    Plus,
    Search,
    Pencil,
    Trash2,
    Users,
    CreditCard,
    Calendar,
    DollarSign,
    Check,
    X,
    Eye,
    Clock,
    Filter,
    ArrowDownToLine,
    TrendingUp,
    ShieldCheck,
    ChevronRight,
    Wallet,
    AlertCircle,
    ClipboardList,
    CheckCircle,
    XCircle,
    AlertTriangle // Replaced ExclamationTriangle
} from 'lucide-vue-next';

const props = defineProps({
    creditAccounts: {
        type: Object,
        default: () => ({ data: [], links: {}, meta: {} }),
    },
    pendingOrders: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
});

// --- Modal & Search State ---
const showCreateModal = ref(false);
const showScheduleModal = ref(false);
const showConfirmModal = ref(false);
const selectedCreditAccount = ref(null);
const search = ref(props.filters.search);

// --- Confirmation Modal Config ---
const confirmConfig = ref({
    title: '',
    message: '',
    confirmText: '',
    confirmColor: '',
    icon: null,
    action: () => { } // Initialize as empty function to avoid "not a function" errors
});

const openConfirmation = (config) => {
    confirmConfig.value = config;
    showConfirmModal.value = true;
};

// --- SEARCH LOGIC ---
let searchTimeout;
const updateSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('eco.manager.credit'),
            { search: search.value },
            { preserveState: true, replace: true }
        );
    }, 300);
};

// --- WORKFLOW ACTIONS ---
const handleApproveCredit = (order) => {
    openConfirmation({
        title: 'Approve Credit Verification',
        message: `Authorize credit for ${order.client?.company_name}? This advances the order to Tier Management.`,
        confirmText: 'Authorize & Proceed',
        confirmColor: 'bg-green-600',
        icon: CheckCircle,
        action: () => router.post(route('eco.manager.credit.verify', order.id), { action: 'approve' }, {
            onSuccess: () => showConfirmModal.value = false
        })
    });
};

const handleRejectCredit = (order) => {
    openConfirmation({
        title: 'Reject Purchase Order',
        message: `Flagging ${order.po_number} as a credit risk will permanently cancel the order.`,
        confirmText: 'Cancel Order',
        confirmColor: 'bg-red-600',
        icon: XCircle,
        action: () => router.post(route('eco.manager.credit.verify', order.id), { action: 'reject' }, {
            onSuccess: () => showConfirmModal.value = false
        })
    });
};

// --- LEDGER FORM LOGIC ---
const createForm = useForm({
    customer_name: '',
    total_amount: '',
    term_type: 'one-time',
    installment_months: 1,
    status: 'active',
});

const submitCreate = () => {
    createForm.post(route('eco.manager.credit.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        }
    });
};

const toggleStatus = (account) => {
    router.patch(route('eco.manager.credit.toggle-status', account.id), {}, {
        preserveState: true,
        preserveScroll: true
    });
};

// --- UI HELPERS ---
const viewSchedule = (account) => {
    selectedCreditAccount.value = account;
    showScheduleModal.value = true;
};

const remainingBalance = (account) => {
    const total = parseFloat(account.total_amount) || 0;
    const paid = parseFloat(account.paid_amount) || 0;
    return (total - paid).toLocaleString(undefined, { minimumFractionDigits: 2 });
};

const stats = computed(() => {
    const accounts = props.creditAccounts?.data || [];
    const totalOutstanding = accounts.reduce((acc, a) => acc + (parseFloat(a.total_amount) - parseFloat(a.paid_amount || 0)), 0);
    return [
        { label: 'Pending Reviews', value: props.pendingOrders.length, icon: ClipboardList, color: 'text-orange-600', bg: 'bg-orange-50' },
        { label: 'Active Lines', value: accounts.filter(a => a.status === 'active').length, icon: ShieldCheck, color: 'text-emerald-600', bg: 'bg-emerald-50' },
        { label: 'System Debt', value: `₱${totalOutstanding.toLocaleString()}`, icon: Wallet, color: 'text-purple-600', bg: 'bg-purple-50' },
        { label: 'Partners', value: accounts.length, icon: Users, color: 'text-blue-600', bg: 'bg-blue-50' },
    ];
});
</script>

<template>

    <Head title="Credit Management - ECO Manager" />

    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div
                        class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <ShieldCheck class="h-3.5 w-3.5" />
                        Risk & Financial Control
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Credit <span class="text-indigo-600">Architect</span>
                    </h1>
                </div>

                <!-- <button @click="showCreateModal = true"
                    class="flex items-center gap-2 px-6 py-3 rounded-[1.5rem] bg-indigo-600 text-white shadow-lg shadow-indigo-500/20 text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition-all">
                    <Plus class="h-4 w-4" />
                    New Account
                </button> -->
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div v-for="stat in stats" :key="stat.label"
                    class="p-7 rounded-[2.5rem] bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm transition-all">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ stat.label }}</p>
                    <div class="flex items-center justify-between">
                        <h3 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">{{
                            stat.value }}</h3>
                        <div :class="stat.bg" class="p-2.5 rounded-xl">
                            <component :is="stat.icon" :class="stat.color" class="h-6 w-6" />
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="pendingOrders.length > 0"
                class="space-y-6 bg-orange-50/30 dark:bg-orange-950/10 p-8 rounded-[3rem] border border-orange-100 dark:border-orange-900/30">
                <h2 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter italic">Pending
                    Credit Verifications</h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div v-for="order in pendingOrders" :key="order.id"
                        class="flex items-center justify-between p-6 bg-white dark:bg-gray-950 rounded-[2rem] shadow-sm border border-gray-100 dark:border-gray-800 transition-all">
                        <div class="flex items-center gap-5">
                            <div
                                class="h-12 w-12 rounded-2xl bg-orange-50 flex items-center justify-center text-orange-600">
                                <ClipboardList class="h-6 w-6" />
                            </div>
                            <div>
                                <h4
                                    class="text-sm font-black text-gray-900 dark:text-white uppercase italic tracking-tighter">
                                    {{ order.client?.company_name }}</h4>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">PO: {{
                                    order.po_number }} • ₱{{ parseFloat(order.total_amount).toLocaleString() }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="handleApproveCredit(order)"
                                class="p-3 bg-green-50 text-green-600 rounded-xl hover:bg-green-600 hover:text-white transition-all">
                                <CheckCircle class="h-5 w-5" />
                            </button>
                            <button @click="handleRejectCredit(order)"
                                class="p-3 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all">
                                <XCircle class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-gray-50 dark:border-gray-800 flex justify-between items-center">
                    <div class="relative flex-1 lg:w-96 group">
                        <Search
                            class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 group-focus-within:text-indigo-600 transition-colors" />
                        <input v-model="search" @input="updateSearch" type="text"
                            placeholder="Search customer records..."
                            class="w-full pl-11 pr-4 py-3.5 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase tracking-widest">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                                <th class="px-8 py-5">Corporate Account</th>
                                <th class="px-8 py-5 text-right">Remaining Balance</th>
                                <th class="px-8 py-5 text-center">Status</th>
                                <th class="px-8 py-5 text-right px-10">Operations</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="account in creditAccounts.data" :key="account.id"
                                class="group hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6 flex items-center gap-4">
                                    <div
                                        class="h-10 w-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                        <Users class="h-5 w-5" />
                                    </div>
                                    <span class="text-sm font-black text-gray-900 uppercase italic">{{
                                        account.customer_name }}</span>
                                </td>
                                <td class="px-8 py-6 text-right font-black text-rose-500 italic">₱{{
                                    remainingBalance(account) }}</td>
                                <td class="px-8 py-6 text-center">
                                    <button @click="toggleStatus(account)"
                                        :class="account.status === 'active' ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-400'"
                                        class="px-3 py-1.5 rounded-xl text-[9px] font-black uppercase">
                                        {{ account.status }}
                                    </button>
                                </td>
                                <td class="px-8 py-6 text-right px-10">
                                    <div class="flex justify-end gap-2">
                                        <button @click="viewSchedule(account)"
                                            class="p-2 rounded-xl bg-gray-50 text-gray-400 hover:text-indigo-600">
                                            <Eye class="h-4 w-4" />
                                        </button>
                                        <button @click="handleConfirmDelete(account)"
                                            class="p-2 rounded-xl bg-gray-50 text-gray-400 hover:text-red-500">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="showConfirmModal"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div
                class="bg-white dark:bg-gray-900 w-full max-w-md rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-2xl overflow-hidden p-8">
                <div class="flex items-center gap-4 mb-6">
                    <div class="h-12 w-12 rounded-2xl bg-orange-50 flex items-center justify-center text-orange-500">
                        <component :is="confirmConfig.icon || AlertCircle" class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">{{
                            confirmConfig.title }}</h3>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">ECO System Confirmation
                        </p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-8 leading-relaxed font-medium">{{
                    confirmConfig.message }}</p>
                <div class="flex gap-3">
                    <button @click="showConfirmModal = false"
                        class="flex-1 px-6 py-4 rounded-2xl bg-gray-50 text-[10px] font-black uppercase text-gray-500">Cancel</button>
                    <button @click="confirmConfig.action" :class="confirmConfig.confirmColor"
                        class="flex-1 px-6 py-4 rounded-2xl text-white text-[10px] font-black uppercase tracking-widest shadow-lg hover:brightness-110 transition-all">
                        {{ confirmConfig.confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.tracking-tighter {
    letter-spacing: -0.05em;
}

.tracking-tight {
    letter-spacing: -0.02em;
}
</style>