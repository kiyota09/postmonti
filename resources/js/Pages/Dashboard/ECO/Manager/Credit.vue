<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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
    AlertTriangle,
    Building2,
    History,
    Receipt,
    FileText,
    RefreshCw
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
const showHistoryModal = ref(false);
const showConfirmModal = ref(false);
const selectedAccount = ref(null);
const clientHistory = ref(null);
const isLoadingHistory = ref(false);
const search = ref(props.filters.search);

// --- Confirmation Modal Config ---
const confirmConfig = ref({
    title: '',
    message: '',
    confirmText: '',
    confirmColor: '',
    icon: null,
    action: () => { }
});

const openConfirmation = (config) => {
    confirmConfig.value = config;
    showConfirmModal.value = true;
};

// --- Search Logic ---
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

// --- Workflow Actions ---
const handleApproveCredit = (order) => {
    openConfirmation({
        title: 'Approve Credit Verification',
        message: `Authorize credit for ${order.client?.company_name}? This advances the order to Tier Management.`,
        confirmText: 'Authorize & Proceed',
        confirmColor: 'bg-green-600',
        icon: CheckCircle,
        action: () => router.post(route('eco.manager.credit.verify', order.id), { action: 'approve' }, {
            onSuccess: () => {
                showConfirmModal.value = false;
                refreshData();
            }
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
            onSuccess: () => {
                showConfirmModal.value = false;
                refreshData();
            }
        })
    });
};

// --- Client History ---
const openClientHistory = async (account) => {
    selectedAccount.value = account;
    isLoadingHistory.value = true;
    showHistoryModal.value = true;

    try {
        const response = await axios.get(route('eco.manager.credit.client-history', account.client_id));
        clientHistory.value = response.data;
    } catch (error) {
        console.error('Failed to load client history', error);
        clientHistory.value = null;
    } finally {
        isLoadingHistory.value = false;
    }
};

// --- Delete Credit Account ---
const handleConfirmDelete = (account) => {
    openConfirmation({
        title: 'Delete Credit Account',
        message: `Remove credit account for ${account.client?.company_name || account.customer_name}? This action cannot be undone.`,
        confirmText: 'Delete Permanently',
        confirmColor: 'bg-red-600',
        icon: Trash2,
        action: () => {
            router.delete(route('eco.manager.credit.destroy', account.id), {
                preserveScroll: true,
                onSuccess: () => {
                    showConfirmModal.value = false;
                    refreshData();
                }
            });
        }
    });
};

// --- LEDGER FORM LOGIC (kept for compatibility, though hidden) ---
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
            refreshData();
        }
    });
};

const toggleStatus = (account) => {
    router.patch(route('eco.manager.credit.toggle-status', account.id), {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => refreshData()
    });
};

// --- Data Refresh ---
const refreshData = () => {
    router.reload({ only: ['creditAccounts', 'pendingOrders'] });
};

// --- UI Helpers ---
const remainingBalance = (account) => {
    const total = parseFloat(account.outstanding_balance) || 0;
    return total.toLocaleString(undefined, { minimumFractionDigits: 2 });
};

const stats = computed(() => {
    const accounts = props.creditAccounts?.data || [];
    const totalOutstanding = accounts.reduce((acc, a) => acc + (parseFloat(a.outstanding_balance) || 0), 0);
    const activeLines = accounts.filter(a => a.is_good_payer === 1).length;
    const totalPartners = accounts.length;
    return [
        { label: 'Pending Reviews', value: props.pendingOrders.length, icon: ClipboardList, color: 'text-orange-600', bg: 'bg-orange-50' },
        { label: 'Good Payers', value: activeLines, icon: ShieldCheck, color: 'text-emerald-600', bg: 'bg-emerald-50' },
        { label: 'System Debt', value: `₱${totalOutstanding.toLocaleString()}`, icon: Wallet, color: 'text-purple-600', bg: 'bg-purple-50' },
        { label: 'Partners', value: totalPartners, icon: Users, color: 'text-blue-600', bg: 'bg-blue-50' },
    ];
});

// Format date helper
const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatCurrency = (val) => {
    if (!val && val !== 0) return '₱0.00';
    return '₱' + Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });
};
</script>

<template>

    <Head title="Credit Management - ECO Manager" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
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
                <div class="flex items-center gap-2">
                    <button @click="refreshData"
                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <RefreshCw class="h-5 w-5 text-gray-500" />
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
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

            <!-- Pending Credit Verifications -->
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
                                    {{ order.client?.company_name }}
                                </h4>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                    PO: {{ order.po_number }} • ₱{{ parseFloat(order.total_amount).toLocaleString() }}
                                </p>
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

            <!-- Credit Accounts Table -->
            <div
                class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-gray-50 dark:border-gray-800 flex justify-between items-center">
                    <div class="relative flex-1 lg:w-96 group">
                        <Search
                            class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 group-focus-within:text-indigo-600 transition-colors" />
                        <input v-model="search" @input="updateSearch" type="text"
                            placeholder="Search by company name..."
                            class="w-full pl-11 pr-4 py-3.5 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase tracking-widest">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                                <th class="px-8 py-5">Corporate Account</th>
                                <th class="px-8 py-5 text-right">Outstanding Balance</th>
                                <th class="px-8 py-5 text-center">Payment Standing</th>
                                <th class="px-8 py-5 text-right px-10">Operations</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="account in creditAccounts.data" :key="account.id"
                                class="group hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6 flex items-center gap-4">
                                    <div
                                        class="h-10 w-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                        <Building2 class="h-5 w-5" />
                                    </div>
                                    <span class="text-sm font-black text-gray-900 uppercase italic">
                                        {{ account.client?.company_name || 'Unknown' }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right font-black text-rose-500 italic">
                                    ₱{{ remainingBalance(account) }}
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <button @click="toggleStatus(account)"
                                        :class="account.is_good_payer ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'"
                                        class="px-3 py-1.5 rounded-xl text-[9px] font-black uppercase">
                                        {{ account.is_good_payer ? 'Good Payer' : 'High Risk' }}
                                    </button>
                                </td>
                                <td class="px-8 py-6 text-right px-10">
                                    <div class="flex justify-end gap-2">
                                        <button @click="openClientHistory(account)"
                                            class="p-2 rounded-xl bg-gray-50 text-gray-400 hover:text-indigo-600">
                                            <History class="h-4 w-4" />
                                        </button>
                                        <button @click="handleConfirmDelete(account)"
                                            class="p-2 rounded-xl bg-gray-50 text-gray-400 hover:text-red-500">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="creditAccounts.data.length === 0">
                                <td colspan="4"
                                    class="px-8 py-20 text-center text-gray-400 uppercase font-black italic">
                                    No credit accounts found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="creditAccounts.meta?.last_page > 1"
                    class="px-8 py-5 border-t border-gray-50 dark:border-gray-800 flex items-center justify-between">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest italic">
                        Showing {{ creditAccounts.meta.from }}–{{ creditAccounts.meta.to }} of {{
                        creditAccounts.meta.total }} accounts
                    </p>
                    <div class="flex gap-2">
                        <component v-for="link in creditAccounts.links" :key="link.label" :is="link.url ? 'a' : 'span'"
                            :href="link.url ?? undefined" v-html="link.label" :class="[
                                'px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all',
                                link.active ? 'bg-indigo-600 text-white shadow-sm' : link.url ? 'text-gray-400 hover:text-indigo-600 hover:bg-indigo-50' : 'text-gray-200 cursor-default'
                            ]" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Client History Modal -->
        <Teleport to="body">
            <div v-if="showHistoryModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="showHistoryModal = false">
                <div
                    class="bg-white dark:bg-gray-900 w-full max-w-4xl rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-8 py-6 bg-indigo-600 text-white flex justify-between items-center flex-shrink-0">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 rounded-2xl bg-white/10 flex items-center justify-center">
                                <History class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-black uppercase tracking-tighter italic">Client Financial
                                    Profile</h3>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80">
                                    {{ selectedAccount?.client?.company_name }}
                                </p>
                            </div>
                        </div>
                        <button @click="showHistoryModal = false"
                            class="p-2 bg-white/10 rounded-xl hover:bg-white/20 transition-all">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="p-8 overflow-y-auto flex-1">
                        <div v-if="isLoadingHistory" class="flex justify-center py-12">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                        </div>
                        <div v-else-if="!clientHistory" class="text-center py-12 text-gray-400">
                            <AlertCircle class="h-12 w-12 mx-auto mb-4 opacity-30" />
                            <p class="font-bold">Failed to load client data.</p>
                        </div>
                        <div v-else class="space-y-8">
                            <!-- Credit Summary -->
                            <div
                                class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                        Outstanding Balance</p>
                                    <p class="text-2xl font-black text-rose-600">{{
                                        formatCurrency(clientHistory.credit_account?.outstanding_balance) }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Payment
                                        Status</p>
                                    <p class="text-xl font-black"
                                        :class="clientHistory.credit_account?.is_good_payer ? 'text-emerald-600' : 'text-red-600'">
                                        {{ clientHistory.credit_account?.is_good_payer ? 'Good Payer' : 'High Risk' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Credit
                                        Limit</p>
                                    <p class="text-xl font-black text-gray-900 dark:text-white">{{
                                        formatCurrency(selectedAccount?.client?.credit_limit) }}</p>
                                </div>
                            </div>

                            <!-- Purchase Orders -->
                            <div>
                                <h4
                                    class="text-xs font-black uppercase tracking-widest text-gray-500 mb-4 flex items-center gap-2">
                                    <Receipt class="h-4 w-4" /> Purchase Orders
                                </h4>
                                <div class="space-y-3">
                                    <div v-for="order in clientHistory.orders" :key="order.id"
                                        class="p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <p class="font-mono text-xs font-bold text-gray-900 dark:text-white">{{
                                                    order.po_number }}</p>
                                                <p class="text-[10px] text-gray-500">Created: {{
                                                    formatDate(order.created_at) }}</p>
                                            </div>
                                            <span :class="{
                                                'bg-green-100 text-green-700': order.status === 'approved',
                                                'bg-orange-100 text-orange-700': order.status === 'credit_review',
                                                'bg-blue-100 text-blue-700': order.status === 'tier_assignment',
                                                'bg-red-100 text-red-700': order.status === 'rejected'
                                            }" class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase">
                                                {{ order.status.replace('_', ' ') }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-end">
                                            <div>
                                                <p class="text-[10px] text-gray-400">Total Amount</p>
                                                <p class="text-sm font-black text-gray-900 dark:text-white">{{
                                                    formatCurrency(order.total_amount) }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-[10px] text-gray-400">Tier</p>
                                                <p class="text-xs font-black text-indigo-600">{{ order.tier_level ||
                                                    'Normal' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="!clientHistory.orders?.length"
                                        class="text-center py-6 text-gray-400 text-sm">
                                        No purchase orders found.
                                    </div>
                                </div>
                            </div>

                            <!-- Quotations -->
                            <div>
                                <h4
                                    class="text-xs font-black uppercase tracking-widest text-gray-500 mb-4 flex items-center gap-2">
                                    <FileText class="h-4 w-4" /> Quotations
                                </h4>
                                <div class="space-y-3">
                                    <div v-for="quote in clientHistory.quotations" :key="quote.id"
                                        class="p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <p class="font-mono text-xs font-bold text-gray-900 dark:text-white">{{
                                                    quote.quotation_number }}</p>
                                                <p class="text-[10px] text-gray-500">Valid until: {{
                                                    formatDate(quote.valid_until) }}</p>
                                            </div>
                                            <span :class="{
                                                'bg-green-100 text-green-700': quote.status === 'accepted',
                                                'bg-amber-100 text-amber-700': quote.status === 'under_review',
                                                'bg-red-100 text-red-700': quote.status === 'rejected',
                                                'bg-gray-100 text-gray-700': quote.status === 'sent'
                                            }" class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase">
                                                {{ quote.status }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-end">
                                            <div>
                                                <p class="text-[10px] text-gray-400">Grand Total</p>
                                                <p class="text-sm font-black text-gray-900 dark:text-white">{{
                                                    formatCurrency(quote.grand_total) }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-[10px] text-gray-400">Issued</p>
                                                <p class="text-xs font-black text-gray-500">{{
                                                    formatDate(quote.issue_date) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="!clientHistory.quotations?.length"
                                        class="text-center py-6 text-gray-400 text-sm">
                                        No quotations found.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="px-8 py-6 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 flex justify-end flex-shrink-0">
                        <button @click="showHistoryModal = false"
                            class="px-8 py-3 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-[10px] font-black uppercase tracking-widest text-gray-600 hover:bg-gray-50 transition-all">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Confirmation Modal (for approve/reject/delete) -->
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