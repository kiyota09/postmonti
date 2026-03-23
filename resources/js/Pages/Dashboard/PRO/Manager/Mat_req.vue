<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ClipboardList, Send, ArrowRight, X, CheckCircle, Users } from 'lucide-vue-next';

const props = defineProps({
    materialRequests: Array,
    warehouses: Array,
    suppliers: Array,
    stats: Object,
});

const showRFQModal = ref(false);
const selectedRequest = ref(null);
const rfqForm = useForm({
    mr_id: null,
    deadline: '',
    delivery_address: '',
    payment_terms: 'Net 30',
    notes: '',
    selected_suppliers: [],
});
const supplierStep = ref(false);

const openRFQ = (req) => {
    selectedRequest.value = req;
    rfqForm.mr_id = req.id;
    rfqForm.deadline = '';
    rfqForm.delivery_address = '';
    rfqForm.payment_terms = 'Net 30';
    rfqForm.notes = '';
    rfqForm.selected_suppliers = [];
    supplierStep.value = false;
    showRFQModal.value = true;
};

const proceedToSuppliers = () => {
    if (!rfqForm.deadline || !rfqForm.delivery_address) {
        alert('Please fill all required fields');
        return;
    }
    supplierStep.value = true;
};

const toggleSupplier = (id) => {
    const idx = rfqForm.selected_suppliers.indexOf(id);
    if (idx === -1) rfqForm.selected_suppliers.push(id);
    else rfqForm.selected_suppliers.splice(idx, 1);
};

const submitRFQ = () => {
    if (rfqForm.selected_suppliers.length === 0) return;
    rfqForm.post(route('pro.manager.rfq.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showRFQModal.value = false;
        },
    });
};
</script>

<template>

    <Head title="PRO - Material Requests" />
    <AuthenticatedLayout>
        <div class="mb-6">
            <h2 class="text-2xl font-bold">Material Requests</h2>
            <p class="text-sm text-gray-500">Requests forwarded by SCM. Create RFQs to start procurement.</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div v-if="materialRequests.length === 0" class="text-center py-12 text-gray-400">
                    No material requests forwarded.
                </div>
                <div class="space-y-4">
                    <div v-for="req in materialRequests" :key="req.id"
                        class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                        <div>
                            <p class="font-bold text-lg">{{ req.material_name }}</p>
                            <p class="text-xs text-gray-500">Qty: {{ req.required_qty }} {{ req.unit }} | Urgency: {{
                                req.urgency }} | Ref: {{ req.req_number }}</p>
                            <p v-if="req.notes" class="text-xs text-gray-400 mt-1">{{ req.notes }}</p>
                        </div>
                        <div class="mt-3 sm:mt-0">
                            <button @click="openRFQ(req)"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold flex items-center gap-1">
                                Create RFQ
                                <Send class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RFQ Modal -->
        <Teleport to="body">
            <div v-if="showRFQModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="showRFQModal = false">
                <div class="bg-white dark:bg-gray-900 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                        <h3 class="text-xl font-black">Create RFQ</h3>
                        <button @click="showRFQModal = false" class="p-2 rounded-lg hover:bg-gray-100">
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div v-if="!supplierStep">
                            <div class="bg-blue-50 p-4 rounded-xl mb-4">
                                <p class="font-bold">Material: {{ selectedRequest.material_name }}</p>
                                <p class="text-sm">Required Qty: {{ selectedRequest.required_qty }} {{
                                    selectedRequest.unit }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-bold uppercase">Response Deadline *</label>
                                    <input v-model="rfqForm.deadline" type="date"
                                        :min="new Date().toISOString().slice(0, 10)"
                                        class="w-full mt-1 px-3 py-2 border rounded-xl" />
                                </div>
                                <div>
                                    <label class="text-xs font-bold uppercase">Delivery Address *</label>
                                    <select v-model="rfqForm.delivery_address"
                                        class="w-full mt-1 px-3 py-2 border rounded-xl">
                                        <option value="">Select Warehouse</option>
                                        <option v-for="wh in warehouses" :key="wh.id"
                                            :value="wh.name + ' - ' + wh.location">{{ wh.name }} ({{ wh.location }})
                                        </option>
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label class="text-xs font-bold uppercase">Payment Terms</label>
                                    <select v-model="rfqForm.payment_terms"
                                        class="w-full mt-1 px-3 py-2 border rounded-xl">
                                        <option>Net 30</option>
                                        <option>Net 45</option>
                                        <option>Cash on Delivery</option>
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label class="text-xs font-bold uppercase">Notes</label>
                                    <textarea v-model="rfqForm.notes" rows="2"
                                        class="w-full mt-1 px-3 py-2 border rounded-xl"></textarea>
                                </div>
                            </div>
                        </div>
                        <div v-else>
                            <p class="text-sm mb-4">Select suppliers to send this RFQ to:</p>
                            <div class="space-y-2 max-h-80 overflow-y-auto">
                                <div v-for="sup in suppliers" :key="sup.id" @click="toggleSupplier(sup.id)"
                                    class="flex items-center p-3 border rounded-xl cursor-pointer hover:bg-gray-50"
                                    :class="rfqForm.selected_suppliers.includes(sup.id) ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                                    <div class="w-6 h-6 mr-3 flex items-center justify-center">
                                        <CheckCircle v-if="rfqForm.selected_suppliers.includes(sup.id)"
                                            class="w-5 h-5 text-blue-600" />
                                        <div v-else class="w-5 h-5 border border-gray-300 rounded-full"></div>
                                    </div>
                                    <div>
                                        <p class="font-bold">{{ sup.business_name }}</p>
                                        <p class="text-xs text-gray-500">{{ sup.representative_name }} | {{ sup.email }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-3">
                        <button @click="supplierStep = !supplierStep"
                            class="px-4 py-2 border rounded-xl text-sm font-bold">
                            {{ supplierStep ? 'Back' : 'Next' }}
                        </button>
                        <button v-if="supplierStep" @click="submitRFQ"
                            :disabled="rfqForm.selected_suppliers.length === 0"
                            class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold">
                            Send RFQ
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>