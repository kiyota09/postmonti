<script setup>
import { ref, computed, reactive, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ClipboardList, FileText, Package, CreditCard, Receipt,
    CheckCircle, XCircle, ChevronDown, ChevronRight, Plus,
    Send, Eye, DollarSign, Truck, AlertTriangle, Users,
    ShoppingCart, Boxes, RefreshCw, Search, X, Building2,
    Star, Clock, BadgeCheck, Ban, Printer, Download,
    ArrowRight, MoreHorizontal, CircleDollarSign, Warehouse
} from 'lucide-vue-next';

// ─────────────────────────────────────────────────────────
// PROPS (from Inertia controller)
// ─────────────────────────────────────────────────────────
const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            pendingMaterialRequests: 0,
            activeRFQs: 0,
            pendingPOs: 0,
            unpaidInvoices: 0
        })
    },
    warehouses: { // Dynamically populated for Delivery Address
        type: Array,
        default: () => []
    },
    materialRequests: {
        type: Array,
        default: () => []
    },
    // Dynamically populated from the database containing ONLY approved/official vendors
    suppliers: {
        type: Array,
        default: () => []
    },
    rfqs: {
        type: Array,
        default: () => []
    },
    purchaseOrders: {
        type: Array,
        default: () => []
    },
    invoices: {
        type: Array,
        default: () => []
    },
    payments: {
        type: Array,
        default: () => []
    }
});

// ─────────────────────────────────────────────────────────
// NAVIGATION STATE
// ─────────────────────────────────────────────────────────
const activeSection = ref('material_request');
const orderingExpanded = ref(false);
const paymentExpanded = ref(false);

const setSection = (section) => {
    activeSection.value = section;
    if (['purchase_order', 'invoice', 'make_payment'].includes(section)) {
        orderingExpanded.value = true;
    }
    if (['invoice', 'make_payment'].includes(section)) {
        paymentExpanded.value = true;
    }
};

const toggleOrdering = () => {
    orderingExpanded.value = !orderingExpanded.value;
    if (!orderingExpanded.value) paymentExpanded.value = false;
};

const togglePayment = () => {
    paymentExpanded.value = !paymentExpanded.value;
    if (paymentExpanded.value && activeSection.value === 'ordering') {
        activeSection.value = 'invoice';
    }
};

// ─────────────────────────────────────────────────────────
// LOCAL REACTIVE DATA (mirrors props, allows optimistic UI)
// ─────────────────────────────────────────────────────────
const localMaterialRequests = ref([...props.materialRequests]);
const localRFQs = ref([...props.rfqs]);
const localPOs = ref([...props.purchaseOrders]);
const localInvoices = ref([...props.invoices]);
const localPayments = ref([...props.payments]);

// Keep data synced if Inertia pushes new props via polling or reload
watch(() => props.rfqs, v => (localRFQs.value = v), { deep: true });
watch(() => props.purchaseOrders, v => (localPOs.value = v), { deep: true });
watch(() => props.invoices, v => (localInvoices.value = v), { deep: true });
watch(() => props.payments, v => (localPayments.value = v), { deep: true });
watch(() => props.materialRequests, v => (localMaterialRequests.value = v), { deep: true });

// ─────────────────────────────────────────────────────────
// MATERIAL REQUESTS TABS LOGIC
// ─────────────────────────────────────────────────────────
const mrTab = ref('pending');

const displayedMaterialRequests = computed(() => {
    return localMaterialRequests.value.filter(req => {
        if (mrTab.value === 'pending') return req.status === 'pending';
        if (mrTab.value === 'processing') return !['pending', 'fulfilled', 'received', 'completed', 'cancelled'].includes(req.status);
        if (mrTab.value === 'completed') return ['fulfilled', 'received', 'completed', 'cancelled'].includes(req.status);
        return true;
    });
});

const countMR = (type) => {
    if (type === 'pending') return localMaterialRequests.value.filter(r => r.status === 'pending').length;
    if (type === 'processing') return localMaterialRequests.value.filter(r => !['pending', 'fulfilled', 'received', 'completed', 'cancelled'].includes(r.status)).length;
    if (type === 'completed') return localMaterialRequests.value.filter(r => ['fulfilled', 'received', 'completed', 'cancelled'].includes(r.status)).length;
    return 0;
};

// ─────────────────────────────────────────────────────────
// MODALS STATE
// ─────────────────────────────────────────────────────────
// RFQ Creation Modal
const showRFQModal = ref(false);
const rfqTargetRequest = ref(null);
const rfqForm = reactive({
    mr_id: null,
    material_name: '',
    required_qty: 0,
    unit: '',
    deadline: '',
    delivery_address: '', // Cleared to force selection
    payment_terms: 'Net 30',
    notes: '',
    selected_suppliers: []
});

// Supplier Selection Modal
const showSupplierModal = ref(false);
const supplierSelectionStep = ref(false);

// Always return all approved suppliers, removing the strict category filter
const filteredSuppliers = computed(() => {
    return props.suppliers;
});

// View RFQ Detail Modal
const showRFQDetailModal = ref(false);
const selectedRFQ = ref(null);

// Accept Quotation Modal
const showAcceptModal = ref(false);
const selectedQuotationToAccept = ref(null);
const acceptingRFQ = ref(null);

// Decline Quotation Modal
const showDeclineModal = ref(false);
const selectedQuotationToDecline = ref(null);
const declineReason = ref('');

// PO Detail Modal
const showPODetailModal = ref(false);
const selectedPO = ref(null);

// Invoice Detail Modal
const showInvoiceModal = ref(false);
const selectedInvoice = ref(null);

// Payment Modal
const showPaymentModal = ref(false);
const paymentTargetInvoice = ref(null);
const paymentForm = reactive({
    invoice_id: null,
    invoice_number: '',
    supplier_name: '',
    amount: 0,
    method: 'Bank Transfer',
    bank_reference: '',
    payment_date: new Date().toISOString().split('T')[0],
    remarks: ''
});
const paymentMethods = ['Bank Transfer', 'Check', 'Online Payment', 'Cash'];

// Notification
const notification = reactive({ show: false, type: 'success', message: '' });

// ─────────────────────────────────────────────────────────
// LOADING STATE
// ─────────────────────────────────────────────────────────
const isLoading = ref(false);

// ─────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────
const formatCurrency = (val) => {
    if (!val && val !== 0) return '₱0.00';
    return '₱' + Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });
};

const statusBadge = (status) => {
    const map = {
        pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        rfq_sent: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        po_created: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
        responded: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        pending_review: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        accepted: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        declined: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        draft: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        sent: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',

        production: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        shipping: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
        delivered: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',

        confirmed: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
        partially_received: 'bg-lime-100 text-lime-700 dark:bg-lime-900/30 dark:text-lime-400',
        received: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        unpaid: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        High: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        Medium: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        Low: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    };
    return map[status] || 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300';
};

const statusLabel = (status) => {
    if (!status) return '';
    if (status === 'production') return 'In Production';
    if (status === 'shipping') return 'Shipping';
    if (status === 'delivered') return 'Delivered';
    return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const showNotif = (type, message) => {
    notification.show = true;
    notification.type = type;
    notification.message = message;
    setTimeout(() => { notification.show = false; }, 3500);
};

const rfqResponseCount = computed(() =>
    localRFQs.value.reduce((acc, rfq) => acc + (rfq.responses?.filter(r => r.status === 'pending_review').length || 0), 0)
);

const pendingInvoiceCount = computed(() =>
    localInvoices.value.filter(inv => inv.status === 'unpaid').length
);

// ─────────────────────────────────────────────────────────
// ACTIONS — RFQ FLOW
// ─────────────────────────────────────────────────────────
const openCreateRFQ = (request) => {
    rfqTargetRequest.value = request;
    rfqForm.mr_id = request.id;
    rfqForm.material_name = request.material_name;
    rfqForm.required_qty = request.required_qty;
    rfqForm.unit = request.unit;
    rfqForm.deadline = '';
    rfqForm.delivery_address = ''; // Cleared to force selection
    rfqForm.payment_terms = 'Net 30';
    rfqForm.notes = request.notes || '';
    rfqForm.selected_suppliers = [];
    showRFQModal.value = true;
    supplierSelectionStep.value = false;
};

const proceedToSupplierSelection = () => {
    if (!rfqForm.deadline || !rfqForm.delivery_address) {
        showNotif('error', 'Please fill all required fields.');
        return;
    }
    supplierSelectionStep.value = true;
};

const toggleSupplier = (supplierId) => {
    const idx = rfqForm.selected_suppliers.indexOf(supplierId);
    if (idx === -1) rfqForm.selected_suppliers.push(supplierId);
    else rfqForm.selected_suppliers.splice(idx, 1);
};

const submitRFQ = () => {
    if (rfqForm.selected_suppliers.length === 0) {
        showNotif('error', 'Please select at least one supplier.');
        return;
    }
    isLoading.value = true;
    router.post('/dashboard/scm/procurement/rfq', rfqForm, {
        preserveScroll: true,
        onSuccess: () => {
            showRFQModal.value = false;
            showNotif('success', `RFQ Sent to selected vendors.`);
            isLoading.value = false;
        },
        onError: (errors) => {
            isLoading.value = false;
            const errorMsg = Object.values(errors)[0] || 'Failed to send RFQ.';
            showNotif('error', errorMsg);
        }
    });
};

// ─────────────────────────────────────────────────────────
// ACTIONS — QUOTATION RESPONSE HANDLING
// ─────────────────────────────────────────────────────────
const openRFQDetail = (rfq) => {
    selectedRFQ.value = rfq;
    showRFQDetailModal.value = true;
};

const openAcceptModal = (rfq, response) => {
    acceptingRFQ.value = rfq;
    selectedQuotationToAccept.value = response;
    showRFQDetailModal.value = false;
    showAcceptModal.value = true;
};

const openDeclineModal = (rfq, response) => {
    acceptingRFQ.value = rfq;
    selectedQuotationToDecline.value = response;
    declineReason.value = '';
    showDeclineModal.value = true;
};

const acceptQuotation = () => {
    isLoading.value = true;
    router.post(`/dashboard/scm/procurement/quotations/${selectedQuotationToAccept.value.id}/accept`, {
        rfq_id: acceptingRFQ.value.id
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showAcceptModal.value = false;
            showNotif('success', `Purchase Order created successfully.`);
            setSection('purchase_order');
            isLoading.value = false;
        },
        onError: (errors) => {
            isLoading.value = false;
            const errorMsg = Object.values(errors)[0] || 'Failed to accept quotation.';
            showNotif('error', errorMsg);
        }
    });
};

const declineQuotation = () => {
    isLoading.value = true;
    router.post(`/dashboard/scm/procurement/quotations/${selectedQuotationToDecline.value.id}/decline`, {
        reason: declineReason.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showDeclineModal.value = false;
            showNotif('success', 'Quotation declined and supplier notified.');
            isLoading.value = false;
        },
        onError: (errors) => {
            isLoading.value = false;
            const errorMsg = Object.values(errors)[0] || 'Failed to decline quotation.';
            showNotif('error', errorMsg);
        }
    });
};

// ─────────────────────────────────────────────────────────
// ACTIONS — PURCHASE ORDER
// ─────────────────────────────────────────────────────────
const openPODetail = (po) => {
    selectedPO.value = po;
    showPODetailModal.value = true;
};

const sendPO = (po) => {
    isLoading.value = true;
    router.post(`/dashboard/scm/procurement/purchase-orders/${po.id}/send`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showNotif('success', `PO sent to ${po.supplier_name}.`);
            isLoading.value = false;
        },
        onError: (errors) => {
            isLoading.value = false;
            const errorMsg = Object.values(errors)[0] || 'Failed to send PO.';
            showNotif('error', errorMsg);
        }
    });
};

// ─────────────────────────────────────────────────────────
// ACTIONS — PAYMENT FLOW
// ─────────────────────────────────────────────────────────
const openPaymentModal = (invoice) => {
    paymentTargetInvoice.value = invoice;
    paymentForm.invoice_id = invoice.id;
    paymentForm.invoice_number = invoice.invoice_number;
    paymentForm.supplier_name = invoice.supplier_name;
    paymentForm.amount = invoice.amount;
    paymentForm.method = 'Bank Transfer';
    paymentForm.bank_reference = '';
    paymentForm.payment_date = new Date().toISOString().split('T')[0];
    paymentForm.remarks = '';
    showPaymentModal.value = true;
};

const submitPayment = () => {
    if (!paymentForm.bank_reference) {
        showNotif('error', 'Please provide a reference number.');
        return;
    }
    isLoading.value = true;
    router.post('/dashboard/scm/procurement/payments', paymentForm, {
        preserveScroll: true,
        onSuccess: () => {
            showPaymentModal.value = false;
            showNotif('success', `Payment processed successfully.`);
            isLoading.value = false;
        },
        onError: (errors) => {
            isLoading.value = false;
            const errorMsg = Object.values(errors)[0] || 'Failed to process payment.';
            showNotif('error', errorMsg);
        }
    });
};
</script>

<template>

    <Head title="Procurement | SCM" />
    <AuthenticatedLayout>
        <transition name="slide-toast">
            <div v-if="notification.show" :class="[
                'fixed top-5 right-5 z-[9999] flex items-center gap-3 px-5 py-3.5 rounded-xl shadow-xl border text-sm font-medium',
                notification.type === 'success' ? 'bg-white dark:bg-slate-800 border-emerald-300 text-emerald-700 dark:text-emerald-400' : 'bg-white dark:bg-slate-800 border-red-300 text-red-700 dark:text-red-400'
            ]">
                <component :is="notification.type === 'success' ? CheckCircle : XCircle"
                    class="w-5 h-5 flex-shrink-0" />
                {{ notification.message }}
                <button @click="notification.show = false" class="ml-2 opacity-60 hover:opacity-100">
                    <X class="w-4 h-4" />
                </button>
            </div>
        </transition>

        <div class="mb-6 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Procurement Management</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">SCM · Full Procurement Workflow</p>
            </div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span v-if="localMaterialRequests.filter(r => r.status === 'pending').length > 0"
                    class="flex items-center gap-1 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 px-3 py-1.5 rounded-full font-medium border border-amber-100 dark:border-amber-800/30">
                    <AlertTriangle class="w-3.5 h-3.5" /> {{localMaterialRequests.filter(r => r.status ===
                        'pending').length}} Pending Requests
                </span>
                <span v-if="rfqResponseCount > 0"
                    class="flex items-center gap-1 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 px-3 py-1.5 rounded-full font-medium border border-blue-100 dark:border-blue-800/30">
                    <FileText class="w-3.5 h-3.5" /> {{ rfqResponseCount }} Quotations to Review
                </span>
                <span v-if="pendingInvoiceCount > 0"
                    class="flex items-center gap-1 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 px-3 py-1.5 rounded-full font-medium border border-red-100 dark:border-red-800/30">
                    <Receipt class="w-3.5 h-3.5" /> {{ pendingInvoiceCount }} Unpaid Invoices
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div
                class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-lg bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center flex-shrink-0">
                    <ClipboardList class="w-5 h-5 text-orange-500" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800 dark:text-white">{{localMaterialRequests.filter(r =>
                        r.status === 'pending').length}}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Material Requests</p>
                </div>
            </div>
            <div
                class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center flex-shrink-0">
                    <Send class="w-5 h-5 text-blue-500" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ localRFQs.length }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Active RFQs</p>
                </div>
            </div>
            <div
                class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center flex-shrink-0">
                    <ShoppingCart class="w-5 h-5 text-indigo-500" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ localPOs.length }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Purchase Orders</p>
                </div>
            </div>
            <div
                class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center flex-shrink-0">
                    <CircleDollarSign class="w-5 h-5 text-red-500" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800 dark:text-white">{{localInvoices.filter(i => i.status
                        === 'unpaid').length}}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Unpaid Invoices</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

            <div class="lg:col-span-3">
                <div
                    class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm sticky top-4">
                    <div class="px-4 pt-4 pb-2">
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                            Workflow</p>
                    </div>
                    <nav class="px-2 pb-3 space-y-0.5">
                        <button @click="setSection('material_request')" :class="[
                            'w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150',
                            activeSection === 'material_request' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'
                        ]">
                            <div class="flex items-center gap-2.5">
                                <ClipboardList class="w-4 h-4 flex-shrink-0" />
                                <span>Material Request</span>
                            </div>
                            <span v-if="localMaterialRequests.filter(r => r.status === 'pending').length > 0" :class="[
                                'text-[10px] font-bold px-1.5 py-0.5 rounded-full',
                                activeSection === 'material_request' ? 'bg-white/20 text-white' : 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400'
                            ]">{{localMaterialRequests.filter(r => r.status === 'pending').length}}</span>
                        </button>

                        <button @click="setSection('supplier_quotation')" :class="[
                            'w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150',
                            activeSection === 'supplier_quotation' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'
                        ]">
                            <div class="flex items-center gap-2.5">
                                <FileText class="w-4 h-4 flex-shrink-0" />
                                <span>Supplier Quotation</span>
                            </div>
                            <span v-if="rfqResponseCount > 0" :class="[
                                'text-[10px] font-bold px-1.5 py-0.5 rounded-full',
                                activeSection === 'supplier_quotation' ? 'bg-white/20 text-white' : 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
                            ]">{{ rfqResponseCount }}</span>
                        </button>

                        <div>
                            <button @click="toggleOrdering" :class="[
                                'w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150',
                                ['purchase_order', 'invoice', 'make_payment'].includes(activeSection) ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/10' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'
                            ]">
                                <div class="flex items-center gap-2.5">
                                    <Package class="w-4 h-4 flex-shrink-0" />
                                    <span>Ordering &amp; Receipt</span>
                                </div>
                                <component :is="orderingExpanded ? ChevronDown : ChevronRight"
                                    class="w-4 h-4 flex-shrink-0 transition-transform duration-200" />
                            </button>

                            <transition name="accordion">
                                <div v-show="orderingExpanded"
                                    class="mt-0.5 ml-4 space-y-0.5 pl-3 border-l-2 border-slate-200 dark:border-slate-600">
                                    <button @click="setSection('purchase_order')" :class="[
                                        'w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150',
                                        activeSection === 'purchase_order' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'
                                    ]">
                                        <div class="flex items-center gap-2">
                                            <ShoppingCart class="w-3.5 h-3.5 flex-shrink-0" />
                                            <span>Purchase Order</span>
                                        </div>
                                    </button>

                                    <div>
                                        <button @click="togglePayment" :class="[
                                            'w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150',
                                            ['invoice', 'make_payment'].includes(activeSection) ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'
                                        ]">
                                            <div class="flex items-center gap-2">
                                                <CreditCard class="w-3.5 h-3.5 flex-shrink-0" />
                                                <span>Payment</span>
                                            </div>
                                            <component :is="paymentExpanded ? ChevronDown : ChevronRight"
                                                class="w-3.5 h-3.5 flex-shrink-0 transition-transform duration-200" />
                                        </button>

                                        <transition name="accordion">
                                            <div v-show="paymentExpanded"
                                                class="mt-0.5 ml-3 space-y-0.5 pl-3 border-l-2 border-slate-200 dark:border-slate-600">
                                                <button @click="setSection('invoice')" :class="[
                                                    'w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150',
                                                    activeSection === 'invoice' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'
                                                ]">
                                                    <div class="flex items-center gap-2">
                                                        <Receipt class="w-3.5 h-3.5 flex-shrink-0" />
                                                        <span>Invoice</span>
                                                    </div>
                                                    <span v-if="pendingInvoiceCount > 0" :class="[
                                                        'text-[10px] font-bold px-1.5 py-0.5 rounded-full',
                                                        activeSection === 'invoice' ? 'bg-white/20 text-white' : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                                                    ]">{{ pendingInvoiceCount }}</span>
                                                </button>

                                                <button @click="setSection('make_payment')" :class="[
                                                    'w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150',
                                                    activeSection === 'make_payment' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'
                                                ]">
                                                    <DollarSign class="w-3.5 h-3.5 flex-shrink-0" />
                                                    <span>Make Payment</span>
                                                </button>
                                            </div>
                                        </transition>
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </nav>

                    <div class="border-t border-slate-100 dark:border-slate-700 px-4 py-3 mt-1">
                        <p
                            class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">
                            Flow Status</p>
                        <div class="flex flex-col gap-1.5">
                            <div v-for="(step, i) in [
                                { label: 'Material Request', key: 'material_request', done: localMaterialRequests.some(r => r.status !== 'pending') },
                                { label: 'Supplier Quotation', key: 'supplier_quotation', done: localRFQs.some(r => r.responses?.some(q => q.status === 'accepted')) },
                                { label: 'Purchase Order', key: 'purchase_order', done: localPOs.length > 0 },
                                { label: 'Invoice & Payment', key: 'invoice', done: localPayments.length > 0 }
                            ]" :key="i" class="flex items-center gap-2 text-xs">
                                <div
                                    :class="['w-4 h-4 rounded-full flex items-center justify-center flex-shrink-0', step.done ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-600']">
                                    <CheckCircle v-if="step.done" class="w-3 h-3 text-white" />
                                </div>
                                <span
                                    :class="step.done ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400'">{{
                                        step.label }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-9">
                <div
                    class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm min-h-[500px]">

                    <div v-if="activeSection === 'material_request'">
                        <div
                            class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-t-xl">
                            <div>
                                <h3 class="text-base font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                    <ClipboardList class="w-5 h-5 text-blue-500" />
                                    Material Requests
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Materials flagged for
                                    restock by the Inventory module.</p>
                            </div>
                        </div>

                        <div
                            class="px-6 pt-3 flex gap-6 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30 overflow-x-auto no-scrollbar">
                            <button @click="mrTab = 'pending'"
                                :class="['pb-3 text-sm font-black border-b-2 transition-all whitespace-nowrap', mrTab === 'pending' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300']">
                                Pending Actions
                                <span
                                    :class="['ml-1.5 px-2 py-0.5 rounded-full text-[10px] transition-colors', mrTab === 'pending' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-400']">{{
                                        countMR('pending') }}</span>
                            </button>
                            <button @click="mrTab = 'processing'"
                                :class="['pb-3 text-sm font-black border-b-2 transition-all whitespace-nowrap', mrTab === 'processing' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300']">
                                On Process
                                <span
                                    :class="['ml-1.5 px-2 py-0.5 rounded-full text-[10px] transition-colors', mrTab === 'processing' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-400']">{{
                                        countMR('processing') }}</span>
                            </button>
                            <button @click="mrTab = 'completed'"
                                :class="['pb-3 text-sm font-black border-b-2 transition-all whitespace-nowrap', mrTab === 'completed' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300']">
                                Completed
                                <span
                                    :class="['ml-1.5 px-2 py-0.5 rounded-full text-[10px] transition-colors', mrTab === 'completed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-400']">{{
                                        countMR('completed') }}</span>
                            </button>
                        </div>

                        <div class="p-5 space-y-3 bg-white dark:bg-slate-800 rounded-b-xl">
                            <div v-if="displayedMaterialRequests.length === 0" class="text-center py-16 text-slate-400">
                                <Boxes class="w-12 h-12 mx-auto mb-3 opacity-30" />
                                <p class="text-sm font-bold">No {{ mrTab === 'pending' ? 'pending' : mrTab ===
                                    'processing' ? 'processing' : 'completed' }} material requests.</p>
                            </div>
                            <div v-for="req in displayedMaterialRequests" :key="req.id"
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-700 transition-all shadow-sm">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <span class="text-xs font-mono text-slate-500 dark:text-slate-400">{{
                                            req.req_number }}</span>
                                        <span :class="statusBadge(req.status)"
                                            class="text-[10px] font-bold px-2 py-0.5 rounded-full">{{
                                                statusLabel(req.status) }}</span>
                                    </div>
                                    <p class="font-semibold text-slate-800 dark:text-white text-sm">{{ req.material_name
                                        }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ req.category }}
                                        &nbsp;·&nbsp; Requested: <strong>{{ Number(req.required_qty).toLocaleString() }}
                                            {{ req.unit }}</strong> &nbsp;·&nbsp; In Stock: <strong>{{
                                                Number(req.current_stock).toLocaleString() }} {{ req.unit }}</strong></p>
                                    <div class="mt-2 bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 w-full max-w-xs">
                                        <div :style="{ width: Math.min((req.current_stock / req.reorder_point) * 100, 100) + '%' }"
                                            :class="['h-1.5 rounded-full', req.current_stock < req.reorder_point ? 'bg-red-500' : 'bg-emerald-500']">
                                        </div>
                                    </div>
                                    <p v-if="req.notes" class="text-xs text-slate-400 dark:text-slate-500 mt-1 italic">
                                        {{
                                            req.notes }}</p>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0 mt-2 sm:mt-0">
                                    <!-- <div class="text-left sm:text-right mr-2 sm:block flex-1 sm:flex-none">
                                        <p class="text-xs text-slate-400 font-medium">Reorder Gap</p>
                                        <p
                                            :class="['text-sm font-bold', req.current_stock < req.required_qty ? 'text-red-600' : 'text-emerald-600']">
                                            {{ req.current_stock < req.required_qty ? '-' + (req.required_qty -
                                                req.current_stock).toLocaleString() + ' ' + req.unit : 'Sufficient' }}
                                                </p>
                                    </div> -->
                                    <button v-if="req.status === 'pending'" @click="openCreateRFQ(req)"
                                        class="flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-xs font-semibold transition-all shadow-sm flex-1 sm:flex-none">
                                        <Send class="w-3.5 h-3.5" /> Create RFQ
                                    </button>
                                    <span v-else-if="req.status === 'rfq_sent'"
                                        class="text-xs text-blue-600 dark:text-blue-400 font-medium flex items-center justify-center gap-1 flex-1 sm:flex-none bg-blue-50 dark:bg-blue-900/20 px-3 py-1.5 rounded-lg border border-blue-100 dark:border-blue-800/30">
                                        <CheckCircle class="w-3.5 h-3.5" /> RFQ Sent
                                    </span>
                                    <span v-else
                                        class="text-xs text-emerald-600 dark:text-emerald-400 font-medium flex items-center justify-center gap-1 flex-1 sm:flex-none bg-emerald-50 dark:bg-emerald-900/20 px-3 py-1.5 rounded-lg border border-emerald-100 dark:border-emerald-800/30">
                                        <BadgeCheck class="w-3.5 h-3.5" /> {{ statusLabel(req.status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="activeSection === 'supplier_quotation'">
                        <div
                            class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-700">
                            <div>
                                <h3 class="text-base font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                    <FileText class="w-5 h-5 text-blue-500" />
                                    Supplier Quotations
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Review quotation responses
                                    from suppliers. Accept to generate a Purchase Order.</p>
                            </div>
                        </div>
                        <div class="p-5 space-y-4">
                            <div v-if="localRFQs.length === 0" class="text-center py-16 text-slate-400">
                                <FileText class="w-12 h-12 mx-auto mb-3 opacity-30" />
                                <p class="text-sm">No RFQs sent yet. Create one from Material Requests.</p>
                            </div>
                            <div v-for="rfq in localRFQs" :key="rfq.id"
                                class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden shadow-sm">
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-4 py-3 bg-slate-50 dark:bg-slate-800/50">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span
                                                    class="text-xs font-mono font-bold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-700 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-600">{{
                                                        rfq.rfq_number }}</span>
                                                <span class="text-[10px] text-slate-400 dark:text-slate-500">← {{
                                                    rfq.mr_ref }}</span>
                                                <span :class="statusBadge(rfq.status)"
                                                    class="text-[10px] font-bold px-2 py-0.5 rounded-full">{{
                                                        statusLabel(rfq.status) }}</span>
                                            </div>
                                            <p class="text-sm font-semibold text-slate-800 dark:text-white mt-1">{{
                                                rfq.material_name }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{
                                                Number(rfq.required_qty).toLocaleString() }} {{ rfq.unit }}
                                                &nbsp;·&nbsp; Deadline: {{ rfq.deadline }}</p>
                                        </div>
                                    </div>
                                    <div
                                        class="flex items-center justify-between sm:justify-end gap-3 w-full sm:w-auto mt-2 sm:mt-0">
                                        <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{
                                            rfq.responses?.length
                                            || 0 }} response(s)</span>
                                        <button @click="openRFQDetail(rfq)"
                                            class="flex items-center justify-center gap-1 text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 bg-blue-50 dark:bg-blue-900/20 px-3 py-1.5 rounded-lg border border-blue-100 dark:border-blue-800/30 transition-colors font-semibold">
                                            <Eye class="w-3.5 h-3.5" /> View RFQ
                                        </button>
                                    </div>
                                </div>

                                <div v-if="rfq.responses && rfq.responses.length > 0"
                                    class="divide-y divide-slate-100 dark:divide-slate-700">
                                    <div v-for="res in rfq.responses" :key="res.id"
                                        :class="['flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-4 py-3 transition-all', res.status === 'accepted' ? 'bg-emerald-50/50 dark:bg-emerald-900/10' : res.status === 'declined' ? 'bg-red-50/30 dark:bg-red-900/5 opacity-60' : 'bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/80']">
                                        <div class="flex items-start sm:items-center gap-3 min-w-0">
                                            <div
                                                class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center flex-shrink-0 shadow-sm mt-1 sm:mt-0">
                                                <span class="text-white text-xs font-bold">{{
                                                    res.supplier_name.charAt(0) }}</span>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-sm text-slate-800 dark:text-white">{{
                                                    res.supplier_name }}</p>
                                                <div
                                                    class="flex items-center gap-x-3 gap-y-1 text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex-wrap">
                                                    <span>Unit: <strong class="text-slate-700 dark:text-slate-200">{{
                                                        formatCurrency(res.unit_price) }}/{{ rfq.unit
                                                            }}</strong></span>
                                                    <span>Total: <strong class="text-slate-700 dark:text-slate-200">{{
                                                        formatCurrency(res.total_price) }}</strong></span>
                                                    <span class="flex items-center">
                                                        <Clock class="w-3 h-3 mr-1" />{{ res.lead_time }}
                                                    </span>
                                                    <span>Terms: {{ res.payment_terms }}</span>
                                                </div>
                                                <p v-if="res.notes"
                                                    class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 italic border-l-2 border-slate-200 dark:border-slate-600 pl-2">
                                                    {{
                                                        res.notes }}</p>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 mt-2 sm:mt-0 w-full sm:w-auto">
                                            <span :class="statusBadge(res.status)"
                                                class="text-[10px] font-bold px-2 py-1 rounded-md sm:rounded-full hidden sm:inline-block">{{
                                                    statusLabel(res.status) }}</span>
                                            <template v-if="res.status === 'pending_review'">
                                                <button @click="openAcceptModal(rfq, res)"
                                                    class="flex-1 sm:flex-none flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 sm:py-1.5 rounded-lg text-xs font-semibold transition-all shadow-sm">
                                                    <CheckCircle class="w-3.5 h-3.5" /> Accept
                                                </button>
                                                <button @click="openDeclineModal(rfq, res)"
                                                    class="flex-1 sm:flex-none flex items-center justify-center gap-1.5 bg-white dark:bg-slate-800 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 px-3 py-2 sm:py-1.5 rounded-lg text-xs font-semibold transition-all border border-red-200 dark:border-red-800/50 shadow-sm">
                                                    <XCircle class="w-3.5 h-3.5" /> Decline
                                                </button>
                                            </template>
                                            <span v-else-if="res.status === 'accepted'"
                                                class="w-full sm:w-auto text-xs text-emerald-600 dark:text-emerald-400 font-semibold flex items-center justify-center gap-1 bg-emerald-50 dark:bg-emerald-900/20 px-3 py-2 sm:py-1.5 rounded-lg border border-emerald-100 dark:border-emerald-800/30">
                                                <BadgeCheck class="w-4 h-4" /> PO Created
                                            </span>
                                            <span v-else-if="res.status === 'declined'"
                                                class="w-full sm:w-auto text-xs text-red-500 dark:text-red-400 font-semibold flex items-center justify-center gap-1 bg-red-50 dark:bg-red-900/10 px-3 py-2 sm:py-1.5 rounded-lg border border-red-100 dark:border-red-800/30">
                                                <Ban class="w-3.5 h-3.5" /> Declined
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="px-4 py-3 bg-white dark:bg-slate-800 text-xs text-slate-400 italic">
                                    Awaiting supplier responses...
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="activeSection === 'purchase_order'">
                        <div
                            class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-700">
                            <div>
                                <h3 class="text-base font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                    <ShoppingCart class="w-5 h-5 text-blue-500" />
                                    Purchase Orders
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">SCM-issued POs sent to
                                    suppliers. Click a row to
                                    expand details.</p>
                            </div>
                        </div>
                        <div class="p-5 space-y-3">
                            <div v-if="localPOs.length === 0" class="text-center py-16 text-slate-400">
                                <ShoppingCart class="w-12 h-12 mx-auto mb-3 opacity-30" />
                                <p class="text-sm">No purchase orders yet. Accept a supplier quotation first.</p>
                            </div>
                            <div v-for="po in localPOs" :key="po.id"
                                class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden shadow-sm hover:border-blue-300 dark:hover:border-blue-700 transition-colors">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-4 py-3 bg-white dark:bg-slate-800 cursor-pointer"
                                    @click="openPODetail(po)">
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span
                                                class="text-xs font-mono font-bold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-600">{{
                                                    po.po_number
                                                }}</span>
                                            <span :class="statusBadge(po.status)"
                                                class="text-[10px] font-bold px-2 py-0.5 rounded-full">{{
                                                    statusLabel(po.status) }}</span>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-800 dark:text-white mt-1">{{
                                            po.supplier_name }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                            Issued: {{ po.issued_date }} &nbsp;·&nbsp; Delivery: {{
                                                po.expected_delivery }}
                                            <template v-if="po.rfq_ref"> &nbsp;·&nbsp; Ref: {{ po.rfq_ref }}</template>
                                        </p>
                                    </div>
                                    <div
                                        class="flex items-center justify-between sm:justify-end gap-4 w-full sm:w-auto mt-2 sm:mt-0 border-t border-slate-100 dark:border-slate-700 pt-3 sm:border-0 sm:pt-0">
                                        <div class="text-left sm:text-right">
                                            <p
                                                class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-0.5">
                                                Grand Total</p>
                                            <p class="font-black text-slate-800 dark:text-white text-base leading-none">
                                                {{
                                                    formatCurrency(po.grand_total) }}</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button v-if="po.status === 'draft'" @click.stop="sendPO(po)"
                                                class="flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 sm:py-1.5 rounded-lg text-xs font-semibold transition-all shadow-sm">
                                                <Send class="w-3.5 h-3.5" /> Send PO
                                            </button>
                                            <button @click.stop="openPODetail(po)"
                                                class="p-2 sm:p-1.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-500 dark:text-slate-400 rounded-lg hover:text-blue-600 dark:hover:text-blue-400 transition-colors shadow-sm">
                                                <Eye class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="px-4 py-2.5 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-700">
                                    <div class="flex flex-wrap gap-4 text-xs text-slate-500 dark:text-slate-400">
                                        <span v-for="item in po.items" :key="item.id"
                                            class="flex items-center gap-1.5 font-medium">
                                            <Package class="w-3 h-3 text-slate-400" /> {{ item.material_name }}: {{
                                                item.qty }} {{
                                                item.unit }} @ {{
                                                formatCurrency(item.unit_price) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="activeSection === 'invoice'">
                        <div
                            class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-700">
                            <div>
                                <h3 class="text-base font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                    <Receipt class="w-5 h-5 text-blue-500" />
                                    Purchase Invoices
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Invoices received from
                                    suppliers. Process payment
                                    on unpaid invoices.</p>
                            </div>
                        </div>
                        <div class="p-5">
                            <div v-if="localInvoices.length === 0" class="text-center py-16 text-slate-400">
                                <Receipt class="w-12 h-12 mx-auto mb-3 opacity-30" />
                                <p class="text-sm">No invoices yet.</p>
                            </div>
                            <div class="space-y-3">
                                <div v-for="inv in localInvoices" :key="inv.id"
                                    :class="['flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl border transition-all shadow-sm', inv.status === 'unpaid' ? 'border-red-200 dark:border-red-800/50 bg-red-50/40 dark:bg-red-900/10' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800']">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap mb-1">
                                            <span
                                                class="text-xs font-mono font-bold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-700 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-600">{{
                                                    inv.invoice_number
                                                }}</span>
                                            <span
                                                class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider">←
                                                {{ inv.po_number
                                                }}</span>
                                            <span :class="statusBadge(inv.status)"
                                                class="text-[10px] font-bold px-2 py-0.5 rounded-full">{{
                                                    statusLabel(inv.status) }}</span>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-800 dark:text-white mt-1 truncate">{{
                                            inv.supplier_name }}</p>
                                        <div
                                            class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex-wrap">
                                            <span>Date: {{ inv.invoice_date }}</span>
                                            <span
                                                :class="inv.status === 'unpaid' ? 'text-red-600 dark:text-red-400 font-bold' : ''">Due:
                                                {{ inv.due_date }}</span>
                                            <span>Terms: {{ inv.payment_terms }}</span>
                                        </div>
                                    </div>
                                    <div
                                        class="flex items-center justify-between sm:justify-end gap-4 w-full sm:w-auto border-t border-slate-100 dark:border-slate-700/50 pt-3 sm:border-0 sm:pt-0">
                                        <div class="text-left sm:text-right">
                                            <p
                                                class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-0.5">
                                                Amount Due</p>
                                            <p
                                                :class="['font-black text-lg leading-none', inv.status === 'unpaid' ? 'text-red-600 dark:text-red-400' : 'text-slate-800 dark:text-white']">
                                                {{ formatCurrency(inv.amount) }}
                                            </p>
                                        </div>
                                        <div class="flex gap-2">
                                            <button @click="selectedInvoice = inv; showInvoiceModal = true"
                                                class="p-2 sm:p-2 bg-white dark:bg-slate-700 text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors border border-slate-200 dark:border-slate-600 rounded-lg shadow-sm">
                                                <Eye class="w-4 h-4" />
                                            </button>
                                            <button v-if="inv.status === 'unpaid'" @click="openPaymentModal(inv)"
                                                class="flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 sm:py-1.5 rounded-lg text-xs font-bold transition-all shadow-sm shadow-blue-500/20">
                                                <DollarSign class="w-3.5 h-3.5" /> Pay Now
                                            </button>
                                            <span v-else
                                                class="flex items-center justify-center gap-1 text-xs text-emerald-600 dark:text-emerald-400 font-bold px-3 py-2 sm:py-1.5 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/30 rounded-lg">
                                                <BadgeCheck class="w-4 h-4" /> Paid
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="activeSection === 'make_payment'">
                        <div
                            class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-700">
                            <div>
                                <h3 class="text-base font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                    <DollarSign class="w-5 h-5 text-blue-500" />
                                    Payment History
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">All processed supplier
                                    payments and transaction records.</p>
                            </div>
                        </div>
                        <div class="p-5">
                            <div v-if="localInvoices.some(i => i.status === 'unpaid')" class="mb-6">
                                <h4
                                    class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <AlertTriangle class="w-4 h-4 text-red-500" /> Outstanding Payments Required
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <div v-for="inv in localInvoices.filter(i => i.status === 'unpaid')" :key="inv.id"
                                        class="flex flex-col justify-between p-4 bg-red-50/50 dark:bg-red-900/10 border border-red-200 dark:border-red-800/50 rounded-xl shadow-sm">
                                        <div class="mb-3">
                                            <p class="text-sm font-bold text-slate-800 dark:text-white truncate"
                                                :title="inv.supplier_name">
                                                {{
                                                    inv.supplier_name }}</p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span
                                                    class="text-xs font-mono font-semibold text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-800 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700">{{
                                                        inv.invoice_number }}</span>
                                            </div>
                                            <p
                                                class="text-[10px] text-red-600 dark:text-red-400 font-semibold uppercase tracking-wider mt-2">
                                                Due: {{ inv.due_date }}
                                            </p>
                                        </div>
                                        <div class="flex items-end justify-between mt-auto">
                                            <p class="font-black text-red-600 dark:text-red-400 text-lg leading-none">{{
                                                formatCurrency(inv.amount) }}</p>
                                            <button @click="openPaymentModal(inv)"
                                                class="flex items-center justify-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all shadow-sm">
                                                <DollarSign class="w-3.5 h-3.5" /> Pay
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h4
                                class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <CheckCircle class="w-4 h-4 text-emerald-500" /> Completed Transactions
                            </h4>
                            <div v-if="localPayments.length === 0"
                                class="text-center py-10 text-slate-400 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
                                <CircleDollarSign class="w-10 h-10 mx-auto mb-2 opacity-30" />
                                <p class="text-sm font-medium">No payments processed yet.</p>
                            </div>

                            <div class="md:hidden space-y-3" v-else>
                                <div v-for="pay in localPayments" :key="'mob-' + pay.id"
                                    class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-sm">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <span
                                                class="font-mono text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-2 py-0.5 rounded">{{
                                                    pay.payment_number }}</span>
                                            <p class="font-bold text-sm text-slate-800 dark:text-white mt-1">{{
                                                pay.supplier_name }}</p>
                                        </div>
                                        <span
                                            :class="['text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-md', statusBadge(pay.status)]">{{
                                                pay.status }}</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-xs mb-3">
                                        <div>
                                            <p class="text-[9px] text-slate-400 uppercase font-bold">Invoice</p>
                                            <p class="font-mono text-slate-600 dark:text-slate-300">{{
                                                pay.invoice_number }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[9px] text-slate-400 uppercase font-bold">Date</p>
                                            <p class="text-slate-600 dark:text-slate-300 font-medium">{{ pay.paid_date
                                            }}</p>
                                        </div>
                                        <div class="col-span-2">
                                            <p class="text-[9px] text-slate-400 uppercase font-bold">Method / Ref</p>
                                            <p class="text-slate-600 dark:text-slate-300 font-medium">{{ pay.method }}
                                                <span class="text-slate-400 mx-1">•</span> <span class="font-mono">{{
                                                    pay.bank_reference
                                                }}</span>
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        class="pt-3 border-t border-slate-100 dark:border-slate-700 flex justify-between items-center">
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Amount
                                            Paid</p>
                                        <p class="font-black text-emerald-600 dark:text-emerald-400 text-base">{{
                                            formatCurrency(pay.amount)
                                        }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="hidden md:block overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700"
                                v-if="localPayments.length > 0">
                                <table class="w-full text-sm text-left">
                                    <thead
                                        class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700">
                                        <tr>
                                            <th class="px-5 py-3.5">Payment #</th>
                                            <th class="px-5 py-3.5">Invoice</th>
                                            <th class="px-5 py-3.5">Supplier</th>
                                            <th class="px-5 py-3.5">Date</th>
                                            <th class="px-5 py-3.5">Method</th>
                                            <th class="px-5 py-3.5">Reference</th>
                                            <th class="px-5 py-3.5 text-right">Amount</th>
                                            <th class="px-5 py-3.5 text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <tr v-for="pay in localPayments" :key="pay.id"
                                            class="bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                            <td
                                                class="px-5 py-3 font-mono text-xs font-semibold text-slate-600 dark:text-slate-300">
                                                {{
                                                    pay.payment_number
                                                }}</td>
                                            <td class="px-5 py-3 font-mono text-xs text-slate-500 dark:text-slate-400">
                                                {{
                                                    pay.invoice_number }}</td>
                                            <td class="px-5 py-3 font-bold text-slate-800 dark:text-white">{{
                                                pay.supplier_name }}</td>
                                            <td
                                                class="px-5 py-3 text-xs text-slate-500 dark:text-slate-400 font-medium">
                                                {{
                                                    pay.paid_date }}</td>
                                            <td
                                                class="px-5 py-3 text-xs text-slate-600 dark:text-slate-300 font-medium">
                                                {{ pay.method
                                                }}</td>
                                            <td class="px-5 py-3 font-mono text-xs text-slate-500 dark:text-slate-400">
                                                {{
                                                    pay.bank_reference
                                                }}</td>
                                            <td class="px-5 py-3 text-right font-black text-slate-800 dark:text-white">
                                                {{
                                                    formatCurrency(pay.amount) }}</td>
                                            <td class="px-5 py-3 text-center">
                                                <span :class="statusBadge(pay.status)"
                                                    class="text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">{{
                                                        pay.status
                                                    }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot
                                        class="bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200 dark:border-slate-700">
                                        <tr>
                                            <td colspan="6"
                                                class="px-5 py-3.5 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest text-right">
                                                Total Paid:</td>
                                            <td
                                                class="px-5 py-3.5 text-right font-black text-emerald-600 dark:text-emerald-400 text-base">
                                                {{formatCurrency(localPayments.reduce((s, p) => s + Number(p.amount),
                                                    0))}}
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <transition name="modal-fade">
            <div v-if="showRFQModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="showRFQModal = false">
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col border border-slate-200 dark:border-slate-700">
                    <div
                        class="flex items-center justify-between px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 flex-shrink-0">
                        <div>
                            <h3
                                class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <FileText class="w-5 h-5 text-blue-500" />
                                <span v-if="supplierSelectionStep">Select Approved Vendors</span>
                                <span v-else>Create Request for Quotation</span>
                            </h3>
                            <p class="text-[10px] sm:text-xs font-bold text-slate-500 mt-1 uppercase tracking-widest">{{
                                rfqTargetRequest?.req_number }} <span class="text-slate-300 mx-1">•</span> {{
                                    rfqTargetRequest?.material_name }}</p>
                        </div>
                        <button @click="showRFQModal = false"
                            class="p-2 bg-white dark:bg-slate-700 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 border border-slate-200 dark:border-slate-600 shadow-sm transition-all">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <div v-if="!supplierSelectionStep" class="p-5 sm:p-6 overflow-y-auto space-y-5 flex-1">
                        <div
                            class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800/50 p-4 rounded-xl">
                            <p class="text-sm font-bold text-blue-800 dark:text-blue-300 mb-1">Target Material: <span
                                    class="font-black">{{ rfqForm.material_name }}</span></p>
                            <p class="text-xs font-medium text-blue-600 dark:text-blue-400">Please define the delivery
                                parameters before selecting suppliers.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-[10px] font-black text-slate-400 mb-1.5 uppercase tracking-widest">Required
                                    Quantity *</label>
                                <div class="flex relative">
                                    <input type="number" v-model="rfqForm.required_qty"
                                        class="w-full pl-3 pr-12 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500/20 font-bold dark:text-white" />
                                    <span
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">{{
                                            rfqForm.unit }}</span>
                                </div>
                            </div>
                            <div>
                                <label
                                    class="block text-[10px] font-black text-slate-400 mb-1.5 uppercase tracking-widest">Response
                                    Deadline *</label>
                                <input type="date" v-model="rfqForm.deadline"
                                    :min="new Date().toISOString().split('T')[0]"
                                    class="w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500/20 font-bold dark:text-white" />
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-[10px] font-black text-slate-400 mb-1.5 uppercase tracking-widest">Delivery
                                Address / Warehouse *</label>
                            <div class="relative">
                                <select v-model="rfqForm.delivery_address"
                                    class="w-full appearance-none px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500/20 font-bold text-slate-700 dark:text-slate-200">
                                    <option value="" disabled>Select Destination Warehouse...</option>
                                    <option v-for="wh in warehouses" :key="wh.id"
                                        :value="wh.name + ' - ' + wh.location">
                                        {{ wh.name }} ({{ wh.location }})
                                    </option>
                                </select>
                                <ChevronDown
                                    class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label
                                    class="block text-[10px] font-black text-slate-400 mb-1.5 uppercase tracking-widest">Payment
                                    Terms *</label>
                                <div class="relative">
                                    <select v-model="rfqForm.payment_terms"
                                        class="w-full appearance-none px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500/20 font-bold text-slate-700 dark:text-slate-200">
                                        <option>Net 30</option>
                                        <option>Net 45</option>
                                        <option>Cash on Delivery</option>
                                        <option>50% DP, 50% on Delivery</option>
                                    </select>
                                    <ChevronDown
                                        class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                                </div>
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-[10px] font-black text-slate-400 mb-1.5 uppercase tracking-widest">Additional
                                Notes</label>
                            <textarea v-model="rfqForm.notes" rows="2"
                                placeholder="Material specs, certifications required, etc."
                                class="w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500/20 font-medium dark:text-white resize-none"></textarea>
                        </div>
                    </div>

                    <div v-if="!supplierSelectionStep"
                        class="px-5 sm:px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row gap-3 bg-slate-50 dark:bg-slate-800 flex-shrink-0">
                        <button @click="showRFQModal = false"
                            class="w-full sm:w-auto px-6 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors shadow-sm">Cancel</button>
                        <button @click="proceedToSupplierSelection"
                            class="w-full sm:flex-1 bg-blue-600 text-white px-6 py-3 rounded-xl text-sm font-black shadow-lg shadow-blue-500/20 flex justify-center items-center gap-2 hover:bg-blue-700 transition-colors">
                            Next: Select Vendors
                            <ArrowRight class="w-4 h-4" />
                        </button>
                    </div>

                    <div v-else class="p-5 sm:p-6 flex-1 overflow-y-auto space-y-4">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Select which official vendors
                            to send
                            this RFQ to. Only approved vendors from your database are shown here.</p>

                        <div v-if="filteredSuppliers.length === 0"
                            class="text-center p-10 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
                            <Users class="h-10 w-10 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
                            <p class="text-sm text-slate-500 font-bold uppercase tracking-widest">No Official Vendors
                                Available
                            </p>
                            <p class="text-xs text-slate-400 mt-1">Approve vendor registrations in the Vendor Registry
                                first.
                            </p>
                        </div>

                        <div class="space-y-3">
                            <div v-for="sup in filteredSuppliers" :key="sup.id" @click="toggleSupplier(sup.id)"
                                :class="['flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl border-2 cursor-pointer transition-all', rfqForm.selected_suppliers.includes(sup.id) ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/20 shadow-sm' : 'border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-700 bg-white dark:bg-slate-800']">
                                <div class="flex items-center gap-3.5 mb-3 sm:mb-0">
                                    <div
                                        :class="['w-5 h-5 rounded flex items-center justify-center flex-shrink-0 transition-colors', rfqForm.selected_suppliers.includes(sup.id) ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600']">
                                        <CheckCircle v-if="rfqForm.selected_suppliers.includes(sup.id)"
                                            class="w-3.5 h-3.5" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-black text-sm text-slate-900 dark:text-white truncate">{{
                                            sup.business_name }}</p>
                                        <p
                                            class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                                            {{
                                                sup.representative_name }} <span class="mx-1">•</span> {{ sup.email }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 pl-8 sm:pl-0 flex-shrink-0">
                                    <span v-if="sup.requirements?.length"
                                        class="text-[9px] font-black uppercase tracking-widest text-slate-500 bg-slate-100 dark:bg-slate-700 px-2.5 py-1 rounded-md border border-slate-200 dark:border-slate-600 flex items-center gap-1.5">
                                        <ClipboardList class="w-3 h-3" /> {{ sup.requirements.length }} Req.
                                    </span>
                                    <span
                                        class="text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-md bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 flex items-center gap-1.5 border border-emerald-200 dark:border-emerald-800/50">
                                        <BadgeCheck class="w-3 h-3" /> Official
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="supplierSelectionStep"
                        class="px-5 sm:px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row gap-3 bg-slate-50 dark:bg-slate-800 flex-shrink-0">
                        <button @click="supplierSelectionStep = false"
                            class="w-full sm:w-auto px-6 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors shadow-sm flex justify-center items-center gap-2">
                            <ChevronRight class="w-4 h-4 rotate-180" /> Back
                        </button>
                        <button @click="submitRFQ" :disabled="isLoading || rfqForm.selected_suppliers.length === 0"
                            :class="['w-full sm:flex-1 py-3 rounded-xl text-sm font-black flex justify-center items-center gap-2 transition-all', rfqForm.selected_suppliers.length > 0 ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-lg shadow-blue-500/20' : 'bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500 cursor-not-allowed']">
                            <Send class="w-4 h-4" />
                            <span>{{ isLoading ? 'Sending...' : `Send RFQ (${rfqForm.selected_suppliers.length})`
                            }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <transition name="modal-fade">
            <div v-if="showRFQDetailModal && selectedRFQ"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="showRFQDetailModal = false">
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl w-full max-w-lg border border-slate-200 dark:border-slate-700 overflow-hidden flex flex-col max-h-[90vh]">
                    <div
                        class="flex items-center justify-between px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 flex-shrink-0">
                        <div>
                            <h3
                                class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <Eye class="w-5 h-5 text-blue-500" /> RFQ Details
                            </h3>
                            <p
                                class="text-[10px] sm:text-xs font-bold text-slate-500 mt-1 uppercase tracking-widest font-mono">
                                {{ selectedRFQ.rfq_number }}</p>
                        </div>
                        <button @click="showRFQDetailModal = false"
                            class="p-2 bg-white dark:bg-slate-700 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 border border-slate-200 dark:border-slate-600 shadow-sm transition-all">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="p-5 sm:p-6 space-y-5 overflow-y-auto flex-1">
                        <div
                            class="grid grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-100 dark:border-slate-700">
                            <div class="col-span-2">
                                <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-1">Target
                                    Material
                                </p>
                                <p class="text-sm font-black text-slate-800 dark:text-white">{{
                                    selectedRFQ.material_name }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-1">Required
                                    Qty</p>
                                <p class="text-sm font-bold text-slate-800 dark:text-white">{{
                                    Number(selectedRFQ.required_qty).toLocaleString() }} <span
                                        class="text-xs text-slate-500">{{
                                            selectedRFQ.unit }}</span></p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-1">Deadline
                                </p>
                                <p class="text-sm font-bold text-slate-800 dark:text-white">{{ selectedRFQ.deadline }}
                                </p>
                            </div>
                            <div class="col-span-2" v-if="selectedRFQ.notes">
                                <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-1">Notes</p>
                                <p
                                    class="text-xs font-medium text-slate-600 dark:text-slate-400 italic border-l-2 border-slate-200 dark:border-slate-600 pl-2">
                                    {{ selectedRFQ.notes }}</p>
                            </div>
                        </div>

                        <div>
                            <p
                                class="text-xs font-black text-slate-800 dark:text-white flex items-center justify-between mb-3 border-b border-slate-100 dark:border-slate-800 pb-2">
                                <span>Supplier Responses</span>
                                <span
                                    class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 px-2 py-0.5 rounded-md text-[10px] uppercase tracking-widest">{{
                                        selectedRFQ.responses?.length || 0 }} Total</span>
                            </p>

                            <div v-if="!selectedRFQ.responses?.length"
                                class="text-sm font-medium text-slate-400 italic text-center py-6 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-dashed border-slate-200 dark:border-slate-700">
                                Awaiting vendor responses...</div>

                            <div v-else class="space-y-3">
                                <div v-for="res in selectedRFQ.responses" :key="res.id"
                                    :class="['p-3 sm:p-4 rounded-xl border transition-colors', res.status === 'accepted' ? 'border-emerald-200 bg-emerald-50/50 dark:bg-emerald-900/10' : res.status === 'declined' ? 'border-red-200 bg-red-50/30 dark:bg-red-900/5 opacity-70' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800']">
                                    <div class="flex justify-between items-start mb-2">
                                        <p class="font-black text-sm text-slate-800 dark:text-white truncate pr-2">{{
                                            res.supplier_name }}</p>
                                        <span
                                            :class="['text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-md whitespace-nowrap', statusBadge(res.status)]">{{
                                                statusLabel(res.status) }}</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 mt-3 text-xs">
                                        <div>
                                            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">
                                                Unit Price
                                            </p>
                                            <p class="font-bold text-slate-700 dark:text-slate-300">{{
                                                formatCurrency(res.unit_price) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">
                                                Lead Time
                                            </p>
                                            <p class="font-bold text-slate-700 dark:text-slate-300">{{ res.lead_time }}
                                            </p>
                                        </div>
                                        <div
                                            class="col-span-2 pt-2 border-t border-slate-100 dark:border-slate-700/50 mt-1 flex justify-between items-center">
                                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">
                                                Total
                                                Value</p>
                                            <p class="font-black text-sm text-slate-900 dark:text-white">{{
                                                formatCurrency(res.total_price) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <transition name="modal-fade">
            <div v-if="showAcceptModal && selectedQuotationToAccept"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="showAcceptModal = false">
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl w-full max-w-md border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div
                        class="flex items-center justify-between px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800">
                        <h3
                            class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <CheckCircle class="w-5 h-5 text-emerald-500" /> Accept Quotation
                        </h3>
                        <button @click="showAcceptModal = false"
                            class="p-2 bg-white dark:bg-slate-700 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 border border-slate-200 dark:border-slate-600 shadow-sm transition-all">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="p-5 sm:p-6 space-y-5">
                        <div
                            class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50 rounded-xl p-4 sm:p-5">
                            <p
                                class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-3">
                                Generating Purchase Order for:</p>
                            <div class="space-y-2 text-sm text-emerald-800 dark:text-emerald-300">
                                <p class="flex justify-between items-center"><span
                                        class="font-semibold text-emerald-600 dark:text-emerald-500 text-xs">Supplier</span>
                                    <strong class="font-black truncate max-w-[180px]">{{
                                        selectedQuotationToAccept.supplier_name
                                    }}</strong>
                                </p>
                                <p class="flex justify-between items-center"><span
                                        class="font-semibold text-emerald-600 dark:text-emerald-500 text-xs">Material</span>
                                    <strong class="font-black truncate max-w-[180px]">{{ acceptingRFQ?.material_name
                                    }}</strong>
                                </p>
                                <p class="flex justify-between items-center"><span
                                        class="font-semibold text-emerald-600 dark:text-emerald-500 text-xs">Quantity</span>
                                    <strong class="font-black">{{ Number(acceptingRFQ?.required_qty).toLocaleString() }}
                                        <span class="text-[10px]">{{ acceptingRFQ?.unit }}</span></strong>
                                </p>
                                <div class="pt-3 pb-1 mt-3 border-t border-emerald-200 dark:border-emerald-800/50">
                                    <p class="flex justify-between items-center"><span
                                            class="font-black uppercase tracking-widest text-[10px] text-emerald-600 dark:text-emerald-500">Subtotal</span>
                                        <strong class="font-bold">{{
                                            formatCurrency(selectedQuotationToAccept.total_price)
                                        }}</strong>
                                    </p>
                                    <p class="flex justify-between items-center mt-1"><span
                                            class="font-black uppercase tracking-widest text-[10px] text-emerald-600 dark:text-emerald-500">Tax
                                            (10%)</span> <strong class="font-bold">{{
                                                formatCurrency(selectedQuotationToAccept.total_price * 0.1) }}</strong></p>
                                </div>
                                <div
                                    class="pt-3 mt-1 border-t-2 border-emerald-300 dark:border-emerald-700 border-dashed">
                                    <p class="flex justify-between items-end"><span
                                            class="font-black uppercase tracking-widest text-xs text-emerald-700 dark:text-emerald-400 mb-0.5">Grand
                                            Total</span> <strong class="text-xl font-black">{{
                                                formatCurrency(selectedQuotationToAccept.total_price * 1.1) }}</strong></p>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 font-medium text-center">Accepting this quotation will
                            automatically
                            decline all others and generate a draft PO.</p>
                    </div>
                    <div
                        class="px-5 sm:px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row gap-3 bg-slate-50 dark:bg-slate-800">
                        <button @click="showAcceptModal = false"
                            class="w-full sm:w-auto px-6 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors shadow-sm">Cancel</button>
                        <button @click="acceptQuotation" :disabled="isLoading"
                            class="w-full sm:flex-1 bg-emerald-600 text-white px-6 py-3 rounded-xl text-sm font-black shadow-lg shadow-emerald-500/20 flex justify-center items-center gap-2 hover:bg-emerald-700 transition-colors disabled:opacity-50">
                            <CheckCircle class="w-4 h-4" />
                            <span>{{ isLoading ? 'Processing...' : 'Confirm & Create PO' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <transition name="modal-fade">
            <div v-if="showDeclineModal && selectedQuotationToDecline"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="showDeclineModal = false">
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl w-full max-w-md border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div
                        class="flex items-center justify-between px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800">
                        <h3
                            class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <XCircle class="w-5 h-5 text-red-500" /> Decline Quotation
                        </h3>
                        <button @click="showDeclineModal = false"
                            class="p-2 bg-white dark:bg-slate-700 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 border border-slate-200 dark:border-slate-600 shadow-sm transition-all">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="p-5 sm:p-6 space-y-5">
                        <div
                            class="bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-800/30 p-4 rounded-xl flex items-start gap-3">
                            <AlertTriangle class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
                            <p class="text-sm font-medium text-red-800 dark:text-red-300 leading-snug">Declining quote
                                from
                                <strong class="font-black">{{ selectedQuotationToDecline.supplier_name }}</strong>. This
                                action
                                is final and the supplier will be notified.
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-black text-slate-400 mb-1.5 uppercase tracking-widest">Reason
                                for Declining <span class="text-red-500">*</span></label>
                            <textarea v-model="declineReason" rows="3"
                                placeholder="e.g. Price too high, better offer found..."
                                class="w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-red-500/20 font-medium dark:text-white resize-none"></textarea>
                        </div>
                    </div>
                    <div
                        class="px-5 sm:px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row gap-3 bg-slate-50 dark:bg-slate-800">
                        <button @click="showDeclineModal = false"
                            class="w-full sm:w-auto px-6 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors shadow-sm">Cancel</button>
                        <button @click="declineQuotation" :disabled="isLoading || !declineReason.trim()"
                            class="w-full sm:flex-1 bg-red-600 text-white px-6 py-3 rounded-xl text-sm font-black shadow-lg shadow-red-500/20 flex justify-center items-center gap-2 hover:bg-red-700 transition-colors disabled:opacity-50">
                            <Ban class="w-4 h-4" />
                            <span>{{ isLoading ? 'Processing...' : 'Confirm Decline' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <transition name="modal-fade">
            <div v-if="showPODetailModal && selectedPO"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="showPODetailModal = false">
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl w-full max-w-xl border border-slate-200 dark:border-slate-700 overflow-hidden flex flex-col max-h-[90vh]">
                    <div
                        class="flex items-center justify-between px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 flex-shrink-0">
                        <div>
                            <h3
                                class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <ShoppingCart class="w-5 h-5 text-blue-500" /> Purchase Order
                            </h3>
                            <div class="flex items-center gap-2 mt-1">
                                <p
                                    class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-widest font-mono">
                                    {{ selectedPO.po_number }}</p>
                                <span
                                    :class="['text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-md whitespace-nowrap', statusBadge(selectedPO.status)]">{{
                                        statusLabel(selectedPO.status) }}</span>
                            </div>
                        </div>
                        <button @click="showPODetailModal = false"
                            class="p-2 bg-white dark:bg-slate-700 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 border border-slate-200 dark:border-slate-600 shadow-sm transition-all">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="p-5 sm:p-6 space-y-5 overflow-y-auto flex-1">
                        <div
                            class="grid grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-100 dark:border-slate-700">
                            <div class="col-span-2">
                                <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-1">Supplier
                                </p>
                                <p class="text-sm font-black text-slate-800 dark:text-white">{{ selectedPO.supplier_name
                                }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-1">Issue
                                    Date</p>
                                <p class="text-sm font-bold text-slate-800 dark:text-white">{{ selectedPO.issued_date }}
                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-1">Delivery
                                    Date</p>
                                <p class="text-sm font-bold text-slate-800 dark:text-white">{{
                                    selectedPO.expected_delivery }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden bg-white dark:bg-slate-900 shadow-sm">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead
                                        class="bg-slate-50 dark:bg-slate-800 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-200 dark:border-slate-700">
                                        <tr>
                                            <th class="px-4 py-3 text-left">Item Details</th>
                                            <th class="px-4 py-3 text-right">Qty</th>
                                            <th class="px-4 py-3 text-right hidden sm:table-cell">Unit Px</th>
                                            <th class="px-4 py-3 text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <tr v-for="item in selectedPO.items" :key="item.id">
                                            <td class="px-4 py-3 font-bold text-slate-800 dark:text-white">
                                                {{ item.material_name }}
                                                <span
                                                    class="block sm:hidden text-[10px] text-slate-400 font-medium mt-0.5">@
                                                    {{
                                                        formatCurrency(item.unit_price) }}</span>
                                            </td>
                                            <td
                                                class="px-4 py-3 text-right font-medium text-slate-600 dark:text-slate-300">
                                                {{
                                                    item.qty }} <span class="text-[10px] text-slate-400">{{ item.unit
                                                }}</span></td>
                                            <td
                                                class="px-4 py-3 text-right font-medium text-slate-600 dark:text-slate-300 hidden sm:table-cell">
                                                {{ formatCurrency(item.unit_price) }}</td>
                                            <td class="px-4 py-3 text-right font-black text-slate-800 dark:text-white">
                                                {{
                                                    formatCurrency(item.total) }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-slate-50 dark:bg-slate-800/50">
                                        <tr>
                                            <td colspan="2" class="hidden sm:table-cell"></td>
                                            <td colspan="1"
                                                class="sm:hidden text-right px-4 py-2 text-[10px] font-bold text-slate-400 uppercase">
                                                Subtotal</td>
                                            <td
                                                class="hidden sm:table-cell px-4 py-2 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest border-t border-slate-200 dark:border-slate-700">
                                                Subtotal</td>
                                            <td
                                                class="px-4 py-2 text-right font-bold text-slate-800 dark:text-white border-t border-slate-200 dark:border-slate-700">
                                                {{ formatCurrency(selectedPO.subtotal) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="hidden sm:table-cell"></td>
                                            <td colspan="1"
                                                class="sm:hidden text-right px-4 py-2 text-[10px] font-bold text-slate-400 uppercase">
                                                Tax ({{ selectedPO.tax_rate }}%)</td>
                                            <td
                                                class="hidden sm:table-cell px-4 py-2 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                                Tax ({{ selectedPO.tax_rate }}%)</td>
                                            <td class="px-4 py-2 text-right font-bold text-slate-800 dark:text-white">{{
                                                formatCurrency(selectedPO.tax_amount) }}</td>
                                        </tr>
                                        <tr class="bg-blue-50/50 dark:bg-blue-900/10">
                                            <td colspan="2"
                                                class="hidden sm:table-cell border-t border-slate-200 dark:border-slate-700">
                                            </td>
                                            <td colspan="1"
                                                class="sm:hidden text-right px-4 py-3 text-xs font-black text-blue-600 dark:text-blue-500 uppercase tracking-widest border-t border-slate-200 dark:border-slate-700">
                                                Grand Total</td>
                                            <td
                                                class="hidden sm:table-cell px-4 py-3 text-right text-[10px] font-black text-blue-600 dark:text-blue-500 uppercase tracking-widest border-t border-slate-200 dark:border-slate-700">
                                                Grand Total</td>
                                            <td
                                                class="px-4 py-3 text-right font-black text-lg text-blue-700 dark:text-blue-400 border-t border-slate-200 dark:border-slate-700">
                                                {{ formatCurrency(selectedPO.grand_total) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div
                        class="px-5 sm:px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row justify-between gap-3 bg-slate-50 dark:bg-slate-800 flex-shrink-0">
                        <button
                            class="w-full sm:w-auto px-6 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors shadow-sm flex items-center justify-center gap-2">
                            <Printer class="w-4 h-4" /> Print Document
                        </button>
                        <button v-if="selectedPO.status === 'draft'"
                            @click="sendPO(selectedPO); showPODetailModal = false" :disabled="isLoading"
                            class="w-full sm:w-auto bg-blue-600 text-white px-6 py-3 rounded-xl text-sm font-black shadow-lg shadow-blue-500/20 flex justify-center items-center gap-2 hover:bg-blue-700 transition-colors disabled:opacity-50">
                            <Send class="w-4 h-4" />
                            <span>{{ isLoading ? 'Sending...' : 'Send to Supplier' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <transition name="modal-fade">
            <div v-if="showPaymentModal && paymentTargetInvoice"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="showPaymentModal = false">
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl w-full max-w-md border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div
                        class="flex items-center justify-between px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800">
                        <h3
                            class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <DollarSign class="w-5 h-5 text-blue-500" /> Process Payment
                        </h3>
                        <button @click="showPaymentModal = false"
                            class="p-2 bg-white dark:bg-slate-700 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 border border-slate-200 dark:border-slate-600 shadow-sm transition-all">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="p-5 sm:p-6 space-y-5">
                        <div
                            class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-black text-blue-800 dark:text-blue-300 text-sm truncate">{{
                                    paymentForm.supplier_name }}</p>
                                <p class="text-blue-600 dark:text-blue-400 text-xs font-mono font-bold mt-0.5">INV: {{
                                    paymentForm.invoice_number }}</p>
                            </div>
                            <div class="sm:text-right flex-shrink-0">
                                <p class="text-[10px] font-black uppercase tracking-widest text-blue-500 mb-0.5">Amount
                                    Due</p>
                                <p class="text-2xl font-black text-blue-700 dark:text-blue-400 leading-none">{{
                                    formatCurrency(paymentForm.amount) }}</p>
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-[10px] font-black text-slate-400 mb-1.5 uppercase tracking-widest">Payment
                                Method *</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button v-for="method in paymentMethods" :key="method"
                                    @click="paymentForm.method = method"
                                    :class="['px-3 py-2.5 rounded-xl text-xs font-bold border-2 transition-all', paymentForm.method === method ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 shadow-sm' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-600 bg-white dark:bg-slate-800']">
                                    {{ method }}
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-[10px] font-black text-slate-400 mb-1.5 uppercase tracking-widest">Payment
                                    Date *</label>
                                <input type="date" v-model="paymentForm.payment_date"
                                    class="w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500/20 font-bold dark:text-white" />
                            </div>
                            <div>
                                <label
                                    class="block text-[10px] font-black text-slate-400 mb-1.5 uppercase tracking-widest">Reference
                                    / TXN # <span class="text-red-500">*</span></label>
                                <input type="text" v-model="paymentForm.bank_reference" placeholder="e.g. TXN-1234"
                                    class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500/20 font-bold dark:text-white" />
                            </div>
                            <div class="sm:col-span-2">
                                <label
                                    class="block text-[10px] font-black text-slate-400 mb-1.5 uppercase tracking-widest">Remarks
                                    <span class="normal-case font-medium text-slate-400">(optional)</span></label>
                                <input type="text" v-model="paymentForm.remarks"
                                    placeholder="Any additional payment notes..."
                                    class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500/20 font-medium dark:text-white" />
                            </div>
                        </div>
                    </div>
                    <div
                        class="px-5 sm:px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row gap-3 bg-slate-50 dark:bg-slate-800">
                        <button @click="showPaymentModal = false"
                            class="w-full sm:w-auto px-6 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors shadow-sm">Cancel</button>
                        <button @click="submitPayment" :disabled="isLoading || !paymentForm.bank_reference.trim()"
                            class="w-full sm:flex-1 bg-blue-600 text-white px-6 py-3 rounded-xl text-sm font-black shadow-lg shadow-blue-500/20 flex justify-center items-center gap-2 hover:bg-blue-700 transition-colors disabled:opacity-50">
                            <CheckCircle class="w-4 h-4" />
                            <span>{{ isLoading ? 'Processing...' : 'Confirm Payment' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

    </AuthenticatedLayout>
</template>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.accordion-enter-active,
.accordion-leave-active {
    transition: all 0.25s ease;
    overflow: hidden;
}

.accordion-enter-from,
.accordion-leave-to {
    max-height: 0;
    opacity: 0;
}

.accordion-enter-to,
.accordion-leave-from {
    max-height: 500px;
    opacity: 1;
}

.slide-toast-enter-active,
.slide-toast-leave-active {
    transition: all 0.3s ease;
}

.slide-toast-enter-from,
.slide-toast-leave-to {
    opacity: 0;
    transform: translateX(20px);
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>