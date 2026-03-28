<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    ShoppingBag,
    Plus,
    X,
    ChevronDown,
    MapPin,
    Building2,
    Phone,
    Mail,
    DollarSign,
    Truck,
    Calendar,
    FileText,
    Package,
    CheckCircle,
    AlertCircle,
    Loader2,
    Heart,
    Star,
    Clock,
    Tag,
    CreditCard,
    Zap
} from 'lucide-vue-next';

const props = defineProps({
    products: Array,
    client: Object,
});

const showModal = ref(false);
const selectedProduct = ref(null);
const activeSection = ref('header');
const isLoading = ref(false);
const notification = ref({ show: false, type: 'success', message: '' });

// Form for quotation
const form = useForm({
    rfq_reference: '',
    valid_until: '',
    billing_address: '',
    shipping_address: '',
    lead_time: '',
    incoterms: '',
    shipping_method: '',
    payment_terms: '',
    shipping_cost: 0,
    tax: 0,
    currency: 'PHP',
    terms_conditions: '',
    custom_notes: '',
    items: [],
});

// Open modal with pre-filled product
const openModal = (product) => {
    selectedProduct.value = product;
    form.items = [{
        product_id: product.id,
        quantity: 1,
        unit_price: product.selling_price,
        discount: 0,
        technical_specs: '',
    }];
    if (props.client) {
        form.billing_address = props.client.company_address || '';
        form.shipping_address = props.client.company_address || '';
    }
    activeSection.value = 'header';
    showModal.value = true;
};

// Add a new line item
const addItem = () => {
    form.items.push({
        product_id: null,
        quantity: 1,
        unit_price: 0,
        discount: 0,
        technical_specs: '',
    });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

// Submit quotation
const submitQuotation = () => {
    if (!form.valid_until || !form.billing_address || !form.shipping_address || !form.payment_terms) {
        notification.value = {
            show: true,
            type: 'error',
            message: 'Please fill in all required fields (*)'
        };
        setTimeout(() => notification.value.show = false, 3000);
        return;
    }

    if (form.items.length === 0) {
        notification.value = {
            show: true,
            type: 'error',
            message: 'Please add at least one product'
        };
        setTimeout(() => notification.value.show = false, 3000);
        return;
    }

    isLoading.value = true;
    form.post(route('client.quotations.store'), {
        onSuccess: () => {
            showModal.value = false;
            isLoading.value = false;
            notification.value = {
                show: true,
                type: 'success',
                message: 'Quotation sent successfully!'
            };
            setTimeout(() => notification.value.show = false, 3000);
            form.reset();
        },
        onError: (errors) => {
            isLoading.value = false;
            const errorMsg = Object.values(errors)[0] || 'Failed to send quotation.';
            notification.value = {
                show: true,
                type: 'error',
                message: errorMsg
            };
            setTimeout(() => notification.value.show = false, 3000);
        }
    });
};

// Calculate subtotal
const subtotal = computed(() => {
    return form.items.reduce((sum, item) => {
        const total = item.quantity * item.unit_price - (item.discount || 0);
        return sum + (isNaN(total) ? 0 : total);
    }, 0);
});

const grandTotal = computed(() => {
    return subtotal.value + (form.shipping_cost || 0) + (form.tax || 0);
});

// Format currency
const formatCurrency = (val) => '₱' + Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });

// Stock status helper
const stockStatus = (stock) => {
    if (stock <= 0) return { text: 'Out of Stock', class: 'text-red-600 bg-red-50' };
    if (stock < 50) return { text: 'Low Stock', class: 'text-amber-600 bg-amber-50' };
    return { text: 'In Stock', class: 'text-emerald-600 bg-emerald-50' };
};

// Scroll to section
const scrollToSection = (section) => {
    const element = document.getElementById(section);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
};
</script>

<template>

    <Head title="Product Catalog" />
    <AuthenticatedLayout>
        <!-- Notification Toast -->
        <div v-if="notification.show"
            class="fixed top-5 right-5 z-[9999] flex items-center gap-3 px-5 py-3.5 rounded-xl shadow-xl border text-sm font-medium transition-all duration-300"
            :class="notification.type === 'success' ? 'bg-white dark:bg-gray-800 border-emerald-300 text-emerald-700 dark:text-emerald-400' : 'bg-white dark:bg-gray-800 border-red-300 text-red-700 dark:text-red-400'">
            <component :is="notification.type === 'success' ? CheckCircle : AlertCircle"
                class="w-5 h-5 flex-shrink-0" />
            {{ notification.message }}
            <button @click="notification.show = false" class="ml-2 opacity-60 hover:opacity-100">
                <X class="w-4 h-4" />
            </button>
        </div>

        <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
            <!-- Hero Section -->
            <div class="mb-10 text-center">
                <div
                    class="flex items-center justify-center gap-2 text-blue-600 font-black text-[10px] uppercase tracking-[0.2em] mb-3">
                    <Package class="h-3.5 w-3.5" />
                    B2B Exclusive
                </div>
                <h1 class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-white tracking-tight uppercase">
                    Product <span class="text-blue-600">Catalog</span>
                </h1>
                <p class="text-sm text-gray-500 mt-2 max-w-2xl mx-auto">
                    Browse our premium textile collection. Click "Make Quotation" to request a custom quote.
                </p>
            </div>

            <!-- Product Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <div v-for="product in products" :key="product.id"
                    class="group bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col">
                    <!-- Image Container -->
                    <div class="relative h-56 bg-gray-50 dark:bg-gray-800 overflow-hidden">
                        <img v-if="product.images && product.images[0]" :src="product.images[0]" :alt="product.name"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                            <Package class="h-12 w-12" />
                        </div>
                        <!-- Stock Badge -->
                        <div class="absolute top-3 left-3">
                            <span :class="stockStatus(product.stock_on_hand).class"
                                class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider">
                                {{ stockStatus(product.stock_on_hand).text }}
                            </span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-5 flex flex-col flex-1">
                        <div class="mb-2">
                            <div class="flex items-center gap-2 mb-1">
                                <span
                                    class="font-mono text-[10px] font-bold text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">
                                    {{ product.sku }}
                                </span>
                                <span
                                    class="text-[10px] font-bold text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-full">
                                    {{ product.category }}
                                </span>
                            </div>
                            <h3
                                class="font-black text-gray-900 dark:text-white text-base leading-tight group-hover:text-blue-600 transition-colors">
                                {{ product.name }}
                            </h3>
                            <p v-if="product.description" class="text-xs text-gray-500 mt-1 line-clamp-2">
                                {{ product.description }}
                            </p>
                        </div>

                        <!-- Sizes -->
                        <div v-if="product.sizes && product.sizes.length" class="flex flex-wrap gap-1 mb-3">
                            <span v-for="sz in product.sizes" :key="sz"
                                class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600">
                                {{ sz }}
                            </span>
                        </div>

                        <!-- Price -->
                        <div
                            class="mt-auto pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <div>
                                <p class="text-[10px] text-gray-400 font-bold uppercase">Unit Price</p>
                                <p class="text-lg font-black text-blue-600 dark:text-blue-400">₱{{
                                    product.selling_price.toLocaleString() }}</p>
                            </div>
                            <button @click="openModal(product)"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-lg shadow-blue-500/20">
                                <Plus class="h-3.5 w-3.5" /> Make Quotation
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quotation Modal -->
            <Teleport to="body">
                <div v-if="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                    @click.self="showModal = false">
                    <div
                        class="bg-white dark:bg-gray-900 w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                        <!-- Modal Header -->
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center flex-shrink-0">
                            <div>
                                <h2 class="text-xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                                    <FileText class="h-5 w-5 text-blue-600" />
                                    Request Quotation
                                </h2>
                                <p class="text-xs text-gray-500 mt-1">Create a formal quotation request for Monti
                                    Textile</p>
                            </div>
                            <button @click="showModal = false"
                                class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Progress Indicator (Mobile friendly) -->
                        <div
                            class="px-6 py-3 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 flex overflow-x-auto no-scrollbar gap-2">
                            <button @click="scrollToSection('header')"
                                class="text-xs font-bold px-3 py-1 rounded-full whitespace-nowrap transition-all"
                                :class="activeSection === 'header' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600'">
                                1. Info
                            </button>
                            <button @click="scrollToSection('entity')"
                                class="text-xs font-bold px-3 py-1 rounded-full whitespace-nowrap transition-all"
                                :class="activeSection === 'entity' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600'">
                                2. Address
                            </button>
                            <button @click="scrollToSection('logistics')"
                                class="text-xs font-bold px-3 py-1 rounded-full whitespace-nowrap transition-all"
                                :class="activeSection === 'logistics' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600'">
                                3. Terms
                            </button>
                            <button @click="scrollToSection('items')"
                                class="text-xs font-bold px-3 py-1 rounded-full whitespace-nowrap transition-all"
                                :class="activeSection === 'items' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600'">
                                4. Items
                            </button>
                            <button @click="scrollToSection('financial')"
                                class="text-xs font-bold px-3 py-1 rounded-full whitespace-nowrap transition-all"
                                :class="activeSection === 'financial' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600'">
                                5. Summary
                            </button>
                        </div>

                        <!-- Scrollable Content -->
                        <div class="flex-1 overflow-y-auto p-6 space-y-8">
                            <form @submit.prevent="submitQuotation" id="quotationForm">
                                <!-- Header Information -->
                                <div id="header" class="scroll-mt-4">
                                    <h3
                                        class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                        <Calendar class="h-5 w-5 text-blue-500" />
                                        Header Information
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">RFQ
                                                Reference</label>
                                            <input v-model="form.rfq_reference" type="text"
                                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white dark:bg-gray-900"
                                                placeholder="e.g., RFQ-2026-001" />
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Valid
                                                Until *</label>
                                            <input v-model="form.valid_until" type="date" required
                                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white dark:bg-gray-900" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Entity Details -->
                                <div id="entity" class="scroll-mt-4">
                                    <h3
                                        class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                        <Building2 class="h-5 w-5 text-blue-500" />
                                        Entity Details
                                    </h3>
                                    <div class="space-y-4">
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Billing
                                                Address *</label>
                                            <textarea v-model="form.billing_address" rows="2" required
                                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white dark:bg-gray-900"
                                                placeholder="Full address with postal code"></textarea>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Shipping
                                                Address *</label>
                                            <textarea v-model="form.shipping_address" rows="2" required
                                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white dark:bg-gray-900"
                                                placeholder="Full address for delivery"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Commercial & Logistics Terms -->
                                <div id="logistics" class="scroll-mt-4">
                                    <h3
                                        class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                        <Truck class="h-5 w-5 text-blue-500" />
                                        Commercial & Logistics Terms
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Lead
                                                Time</label>
                                            <input v-model="form.lead_time" type="text"
                                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg"
                                                placeholder="e.g., 30 Days" />
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Incoterms</label>
                                            <select v-model="form.incoterms"
                                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg">
                                                <option value="">Select</option>
                                                <option>EXW (Ex Works)</option>
                                                <option>FOB (Free on Board)</option>
                                                <option>CIF (Cost, Insurance, Freight)</option>
                                                <option>DDP (Delivered Duty Paid)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Shipping
                                                Method</label>
                                            <input v-model="form.shipping_method" type="text"
                                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg"
                                                placeholder="e.g., Air Freight, Ocean Freight" />
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Payment
                                                Terms *</label>
                                            <input v-model="form.payment_terms" type="text" required
                                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500/20"
                                                placeholder="e.g., Net 30, 50% DP" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Line Items -->
                                <div id="items" class="scroll-mt-4">
                                    <h3
                                        class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                        <Package class="h-5 w-5 text-blue-500" />
                                        Line Items
                                    </h3>
                                    <div v-for="(item, idx) in form.items" :key="idx"
                                        class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 mb-3 relative">
                                        <div class="flex justify-between items-start mb-3">
                                            <select v-model="item.product_id"
                                                class="flex-1 border border-gray-200 dark:border-gray-700 rounded-lg p-2 mr-2 bg-white dark:bg-gray-900">
                                                <option value="">Select Product</option>
                                                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{
                                                    p.sku }})</option>
                                            </select>
                                            <button type="button" @click="removeItem(idx)"
                                                class="text-red-500 hover:text-red-700">
                                                <X class="h-5 w-5" />
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                            <input v-model.number="item.quantity" type="number" placeholder="Qty"
                                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-2" />
                                            <input v-model.number="item.unit_price" type="number"
                                                placeholder="Unit Price"
                                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-2" />
                                            <input v-model.number="item.discount" type="number"
                                                placeholder="Discount (₱)"
                                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-2" />
                                            <input v-model="item.technical_specs" placeholder="Tech Specs"
                                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-2" />
                                        </div>
                                    </div>
                                    <button type="button" @click="addItem"
                                        class="text-sm text-blue-600 font-bold flex items-center gap-1 mt-2 hover:text-blue-700">
                                        <Plus class="h-4 w-4" /> Add Another Item
                                    </button>
                                </div>

                                <!-- Financial Summary -->
                                <div id="financial" class="scroll-mt-4">
                                    <h3
                                        class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                        <DollarSign class="h-5 w-5 text-blue-500" />
                                        Financial Summary
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Shipping
                                                Cost (₱)</label>
                                            <input v-model.number="form.shipping_cost" type="number" step="0.01"
                                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg" />
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Tax
                                                (₱)</label>
                                            <input v-model.number="form.tax" type="number" step="0.01"
                                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg" />
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Currency</label>
                                            <select v-model="form.currency"
                                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg">
                                                <option>PHP</option>
                                                <option>USD</option>
                                                <option>EUR</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                        <div class="space-y-2">
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-600">Subtotal:</span>
                                                <span class="font-bold">{{ formatCurrency(subtotal) }}</span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-600">Shipping:</span>
                                                <span class="font-bold">{{ formatCurrency(form.shipping_cost) }}</span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-600">Tax:</span>
                                                <span class="font-bold">{{ formatCurrency(form.tax) }}</span>
                                            </div>
                                            <div
                                                class="border-t border-gray-200 dark:border-gray-700 pt-2 mt-2 flex justify-between text-lg font-bold">
                                                <span>Grand Total:</span>
                                                <span class="text-blue-600">{{ formatCurrency(grandTotal) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Terms & Conditions -->
                                <div>
                                    <h3
                                        class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                        <FileText class="h-5 w-5 text-blue-500" />
                                        Terms & Conditions
                                    </h3>
                                    <textarea v-model="form.terms_conditions" rows="3"
                                        class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg"
                                        placeholder="Standard terms: e.g., Fabric weight tolerance ±5%, returns policy, etc."></textarea>
                                </div>

                                <!-- Custom Notes -->
                                <div>
                                    <h3
                                        class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                        <MessageSquare class="h-5 w-5 text-blue-500" />
                                        Custom Notes
                                    </h3>
                                    <textarea v-model="form.custom_notes" rows="2"
                                        class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg"
                                        placeholder="Any special instructions for the sales team"></textarea>
                                </div>
                            </form>
                        </div>

                        <!-- Modal Footer -->
                        <div
                            class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex justify-end gap-3 flex-shrink-0">
                            <button type="button" @click="showModal = false"
                                class="px-6 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm font-bold hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                Cancel
                            </button>
                            <button type="submit" form="quotationForm" :disabled="isLoading"
                                class="px-6 py-2 rounded-lg bg-blue-600 text-white text-sm font-bold hover:bg-blue-700 transition flex items-center gap-2 shadow-lg shadow-blue-500/20 disabled:opacity-50">
                                <Loader2 v-if="isLoading" class="h-4 w-4 animate-spin" />
                                <Send v-else class="h-4 w-4" />
                                {{ isLoading ? 'Sending...' : 'Send Quotation' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scroll-mt-4 {
    scroll-margin-top: 1rem;
}
</style>