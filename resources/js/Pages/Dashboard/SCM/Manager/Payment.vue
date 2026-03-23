<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { DollarSign, CheckCircle, X, Receipt } from 'lucide-vue-next';

const props = defineProps({
    pendingInvoices: Array,
    paymentHistory: Array,
    stats: Object,
});

const showPaymentModal = ref(false);
const selectedInvoice = ref(null);
const paymentForm = useForm({
    invoice_id: null,
    invoice_number: '',
    supplier_name: '',
    amount: 0,
    method: 'Bank Transfer',
    bank_reference: '',
    payment_date: new Date().toISOString().slice(0, 10),
    remarks: '',
});

const openPayment = (invoice) => {
    selectedInvoice.value = invoice;
    paymentForm.invoice_id = invoice.id;
    paymentForm.invoice_number = invoice.invoice_number;
    paymentForm.supplier_name = invoice.supplier_name;
    paymentForm.amount = invoice.amount;
    paymentForm.method = 'Bank Transfer';
    paymentForm.bank_reference = '';
    paymentForm.payment_date = new Date().toISOString().slice(0, 10);
    paymentForm.remarks = '';
    showPaymentModal.value = true;
};

const submitPayment = () => {
    paymentForm.post(route('scm.manager.payments.process'), {
        preserveScroll: true,
        onSuccess: () => {
            showPaymentModal.value = false;
        },
    });
};

const formatCurrency = (val) => '₱' + Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });
</script>

<template>

    <Head title="SCM Payment Approval" />
    <AuthenticatedLayout>
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Payment Approval</h2>
            <p class="text-sm text-gray-500">Approve supplier invoices and record payments.</p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-xs font-bold text-gray-400 uppercase">Total Pending</p>
                <p class="text-3xl font-black text-amber-600">{{ formatCurrency(stats.totalPending) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-xs font-bold text-gray-400 uppercase">Invoices Pending</p>
                <p class="text-3xl font-black text-amber-600">{{ stats.countPending }}</p>
            </div>
        </div>

        <!-- Pending Invoices -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 mb-8">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-bold text-gray-800 dark:text-white">Pending Invoices</h3>
            </div>
            <div class="p-6">
                <div v-if="pendingInvoices.length === 0" class="text-center py-8 text-gray-400">
                    No invoices pending payment.
                </div>
                <div class="space-y-3">
                    <div v-for="inv in pendingInvoices" :key="inv.id"
                        class="flex justify-between items-center p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                        <div>
                            <p class="font-mono text-sm font-bold">{{ inv.invoice_number }}</p>
                            <p class="text-sm">{{ inv.supplier_name }}</p>
                            <p class="text-xs text-gray-500">Due: {{ inv.due_date }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-black text-amber-600">{{ formatCurrency(inv.amount) }}</p>
                            <button @click="openPayment(inv)"
                                class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-xs font-semibold">Approve
                                Payment</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-bold text-gray-800 dark:text-white">Payment History</h3>
            </div>
            <div class="p-6">
                <div v-if="paymentHistory.length === 0" class="text-center py-8 text-gray-400">
                    No payment history.
                </div>
                <div class="space-y-3">
                    <div v-for="pay in paymentHistory" :key="pay.id"
                        class="flex justify-between items-center p-4 rounded-xl border border-gray-100 dark:border-gray-700">
                        <div>
                            <p class="font-mono text-xs">{{ pay.payment_number }}</p>
                            <p class="font-bold">{{ pay.supplier_name }}</p>
                            <p class="text-xs text-gray-500">{{ pay.paid_date }} | {{ pay.method }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-emerald-600">{{ formatCurrency(pay.amount) }}</p>
                            <p class="text-xs text-gray-400">Ref: {{ pay.bank_reference }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <Teleport to="body">
            <div v-if="showPaymentModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="showPaymentModal = false">
                <div
                    class="bg-white dark:bg-gray-900 rounded-2xl w-full max-w-md border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                        <h3 class="text-lg font-black">Process Payment</h3>
                        <button @click="showPaymentModal = false"
                            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase">Invoice</label>
                            <p class="font-bold">{{ paymentForm.invoice_number }} - {{ paymentForm.supplier_name }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase">Amount</label>
                            <p class="text-2xl font-black text-amber-600">{{ formatCurrency(paymentForm.amount) }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase">Payment Method</label>
                            <select v-model="paymentForm.method" class="w-full mt-1 px-3 py-2 border rounded-xl">
                                <option>Bank Transfer</option>
                                <option>Check</option>
                                <option>Online Payment</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase">Reference Number *</label>
                            <input v-model="paymentForm.bank_reference" type="text"
                                class="w-full mt-1 px-3 py-2 border rounded-xl" />
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase">Payment Date</label>
                            <input v-model="paymentForm.payment_date" type="date"
                                class="w-full mt-1 px-3 py-2 border rounded-xl" />
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase">Remarks</label>
                            <textarea v-model="paymentForm.remarks" rows="2"
                                class="w-full mt-1 px-3 py-2 border rounded-xl"></textarea>
                        </div>
                    </div>
                    <div class="p-6 border-t border-gray-100 dark:border-gray-800 flex gap-3">
                        <button @click="showPaymentModal = false"
                            class="flex-1 py-2 border rounded-xl text-sm font-bold">Cancel</button>
                        <button @click="submitPayment" :disabled="paymentForm.processing"
                            class="flex-1 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold">Confirm
                            Payment</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>