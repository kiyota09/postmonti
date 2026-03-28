<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    FileText,
    Eye,
    CheckCircle,
    XCircle,
    Calendar,
    Building2,
    DollarSign,
    Package,
    Clock,
    MapPin,
    AlertCircle,
    ChevronRight
} from 'lucide-vue-next';

const props = defineProps({
    quotations: Array,
});

const showDetailModal = ref(false);
const selectedQuotation = ref(null);
const responseForm = ref({
    action: '',
    notes: '',
    grand_total: null,
    valid_until: '',
    lead_time: '',
    payment_terms: '',
    items: []
});

const openModal = (quotation, action) => {
    selectedQuotation.value = quotation;
    responseForm.value.action = action;
    responseForm.value.notes = '';
    responseForm.value.grand_total = quotation.grand_total;
    responseForm.value.valid_until = quotation.valid_until;
    responseForm.value.lead_time = quotation.lead_time;
    responseForm.value.payment_terms = quotation.payment_terms;
    // Copy line items for editing
    responseForm.value.items = quotation.items.map(item => ({
        id: item.id,
        quantity: item.quantity,
        unit_price: item.unit_price,
        discount: item.discount || 0,
        line_total: item.line_total
    }));
    showDetailModal.value = true;
};

const submitResponse = () => {
    router.post(route('eco.manager.quotations.respond', selectedQuotation.value.id), responseForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            showDetailModal.value = false;
        }
    });
};

const formatCurrency = (val) => '₱' + Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });
const formatDate = (date) => date ? new Date(date).toLocaleDateString('en-PH') : '—';

const getStatusBadge = (status) => {
    const map = {
        sent: 'bg-blue-100 text-blue-700',
        under_review: 'bg-amber-100 text-amber-700',
        accepted: 'bg-emerald-100 text-emerald-700',
        rejected: 'bg-red-100 text-red-700',
        expired: 'bg-gray-100 text-gray-700',
        converted: 'bg-purple-100 text-purple-700'
    };
    return map[status] || 'bg-gray-100 text-gray-700';
};
</script>

<template>

    <Head title="Client Quotations - ECO Manager" />
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-8 p-4">
            <div>
                <h1 class="text-2xl font-bold">Client Quotation Requests</h1>
                <p class="text-gray-500">Review and respond to incoming requests from partners</p>
            </div>

            <div v-if="quotations.length === 0" class="text-center py-12 bg-white rounded-lg border">
                <FileText class="h-12 w-12 text-gray-300 mx-auto mb-3" />
                <p class="text-gray-500">No pending quotation requests.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="quote in quotations" :key="quote.id"
                    class="bg-white rounded-lg border shadow-sm hover:shadow-md transition">
                    <div class="p-5">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <p class="text-xs font-mono font-bold text-gray-500">{{ quote.quotation_number }}</p>
                                <p class="font-semibold text-gray-900">{{ quote.client?.company_name }}</p>
                            </div>
                            <span :class="getStatusBadge(quote.status)" class="px-2 py-1 rounded text-xs font-bold">
                                {{ quote.status.replace('_', ' ') }}
                            </span>
                        </div>
                        <div class="space-y-1 text-sm text-gray-500 mb-4">
                            <p class="flex items-center gap-1">
                                <Calendar class="h-3 w-3" /> Valid until: {{ formatDate(quote.valid_until) }}
                            </p>
                            <p class="flex items-center gap-1">
                                <DollarSign class="h-3 w-3" /> Amount: {{ formatCurrency(quote.grand_total) }}
                            </p>
                            <p class="flex items-center gap-1">
                                <Package class="h-3 w-3" /> Items: {{ quote.items.length }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button @click="openModal(quote, 'approve')"
                                class="flex-1 bg-blue-600 text-white py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                                Review & Respond
                            </button>
                            <button @click="openModal(quote, 'reject')"
                                class="px-4 py-2 border border-red-200 text-red-600 rounded-lg text-sm font-semibold hover:bg-red-50 transition">
                                Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Response -->
        <Teleport to="body">
            <div v-if="showDetailModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
                @click.self="showDetailModal = false">
                <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">
                            {{ responseForm.action === 'approve' ? 'Review Quotation' : 'Reject Quotation' }}
                        </h2>
                        <button @click="showDetailModal = false">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p><strong>Company:</strong> {{ selectedQuotation?.client?.company_name }}</p>
                            <p><strong>Reference:</strong> {{ selectedQuotation?.quotation_number }}</p>
                            <p><strong>Requested on:</strong> {{ formatDate(selectedQuotation?.created_at) }}</p>
                        </div>

                        <div v-if="responseForm.action === 'approve'">
                            <h3 class="font-semibold mb-2">Items</h3>
                            <div v-for="(item, idx) in responseForm.items" :key="item.id"
                                class="border p-3 rounded mb-2">
                                <div class="font-medium">{{ item.product?.name || 'Product' }}</div>
                                <div class="grid grid-cols-3 gap-2 mt-2">
                                    <input v-model.number="item.quantity" type="number" placeholder="Qty"
                                        class="border rounded p-1 text-sm" />
                                    <input v-model.number="item.unit_price" type="number" placeholder="Unit Price"
                                        class="border rounded p-1 text-sm" />
                                    <input v-model.number="item.discount" type="number" placeholder="Discount"
                                        class="border rounded p-1 text-sm" />
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Line total: {{ formatCurrency((item.quantity *
                                    item.unit_price) - item.discount) }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label class="block text-sm font-medium">Valid Until</label>
                                    <input v-model="responseForm.valid_until" type="date"
                                        class="w-full border rounded p-2" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Lead Time</label>
                                    <input v-model="responseForm.lead_time" type="text"
                                        class="w-full border rounded p-2" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Payment Terms</label>
                                    <input v-model="responseForm.payment_terms" type="text"
                                        class="w-full border rounded p-2" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Grand Total (₱)</label>
                                    <input v-model.number="responseForm.grand_total" type="number" step="0.01"
                                        class="w-full border rounded p-2" />
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Notes to Client (optional)</label>
                            <textarea v-model="responseForm.notes" rows="3"
                                class="w-full border rounded p-2"></textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button @click="showDetailModal = false" class="px-4 py-2 border rounded">Cancel</button>
                        <button @click="submitResponse" class="px-4 py-2 bg-blue-600 text-white rounded"
                            :class="responseForm.action === 'reject' ? 'bg-red-600' : ''">
                            {{ responseForm.action === 'approve' ? 'Send Response' : 'Confirm Rejection' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>