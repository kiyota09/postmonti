<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    ShoppingBag,
    Search,
    Filter,
    Star,
    CreditCard,
    ArrowRight,
    Plus,
    Minus,
    Zap,
    Clock,
    Image as ImageIcon,
    ClipboardList,
    CheckCircle2,
    AlertCircle,
    ChevronRight,
    Loader2
} from 'lucide-vue-next';

const props = defineProps({
    products: Array,
    // New prop for tracking the workflow
    purchaseOrders: {
        type: Array,
        default: () => []
    },
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

// --- CATEGORY FILTERING ---
const categories = ['All Fabrics', 'Cotton', 'Polyester', 'Silk', 'Knitted', 'Denim'];
const activeCategory = ref('All Fabrics');

const filteredProducts = computed(() => {
    if (activeCategory.value === 'All Fabrics') return props.products;
    return props.products.filter(p => p.category === activeCategory.value);
});

// --- CART & PO SUBMISSION LOGIC ---
const cart = ref({});

const addToCart = (product) => {
    if (!cart.value[product.id]) {
        cart.value[product.id] = { ...product, quantity: 1 };
    } else {
        cart.value[product.id].quantity++;
    }
};

const removeFromCart = (productId) => {
    if (cart.value[productId] && cart.value[productId].quantity > 1) {
        cart.value[productId].quantity--;
    } else {
        delete cart.value[productId];
    }
};

const cartTotal = computed(() => {
    return Object.values(cart.value).reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

const poForm = useForm({
    items: [],
    total: 0
});

const submitPurchaseOrder = () => {
    poForm.items = Object.values(cart.value);
    poForm.total = cartTotal.value;

    // This route will trigger the "Credit Review" process in the Eco Manager department
    poForm.post(route('client.purchase-order.store'), {
        onSuccess: () => cart.value = {},
    });
};

// --- CLIENT APPROVAL ACTIONS ---
const acceptQuotation = (id) => {
    router.post(route('client.quotation.accept', id));
};

// --- STATUS THEME HELPER ---
const getStatusTheme = (status) => {
    switch (status) {
        case 'credit_review': return 'bg-orange-50 text-orange-600 border-orange-100'; // ECO Manager
        case 'tier_assignment': return 'bg-indigo-50 text-indigo-600 border-indigo-100'; // HR Manager
        case 'pending_client_approval': return 'bg-blue-50 text-blue-600 border-blue-100';
        case 'approved': return 'bg-green-50 text-green-600 border-green-100';
        case 'rejected': return 'bg-red-50 text-red-600 border-red-100';
        default: return 'bg-gray-50 text-gray-500 border-gray-100';
    }
};
</script>

<template>

    <Head title="Partner Storefront" />

    <AuthenticatedLayout>
        <div class="max-w-[1400px] mx-auto space-y-10">

            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                <div class="space-y-1">
                    <div
                        class="flex items-center gap-2 text-blue-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Zap class="h-3 w-3 fill-current" />
                        Exclusive B2B Storefront
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Welcome, <span class="text-blue-600">{{ client?.company_name || 'Partner' }}</span>
                    </h1>
                    <p class="text-sm font-bold text-gray-400 uppercase tracking-tight">
                        Authorized Buyer: {{ client?.contact_person ?? 'Representative' }}
                    </p>
                </div>

                <div
                    class="flex items-center gap-4 bg-white dark:bg-gray-900 p-4 rounded-[2rem] border border-gray-100 dark:border-gray-800 shadow-xl shadow-gray-200/50">
                    <div
                        class="h-12 w-12 rounded-2xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600">
                        <CreditCard class="h-6 w-6" />
                    </div>
                    <div class="pr-4 border-r border-gray-100 dark:border-gray-800">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Available Credit</p>
                        <p class="text-xl font-black text-gray-900 dark:text-white">
                            ₱{{ client?.credit_limit ? parseFloat(client.credit_limit).toLocaleString() : '0' }}
                        </p>
                    </div>
                    <div class="pl-2">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">Cart</p>
                        <p class="text-sm font-black text-blue-600 text-center">₱{{ cartTotal.toLocaleString() }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                <div class="flex flex-col md:flex-row items-center gap-4">
                    <div class="flex-1 flex items-center gap-2 overflow-x-auto pb-2 md:pb-0 no-scrollbar">
                        <button v-for="cat in categories" :key="cat" @click="activeCategory = cat" :class="[
                            'px-6 py-3 rounded-2xl text-[11px] font-black uppercase tracking-widest transition-all duration-300 whitespace-nowrap border',
                            activeCategory === cat
                                ? 'bg-blue-600 text-white border-blue-600 shadow-lg shadow-blue-500/20'
                                : 'bg-white dark:bg-gray-900 text-gray-500 border-gray-100 dark:border-gray-800 hover:border-blue-200'
                        ]">
                            {{ cat }}
                        </button>
                    </div>

                    <div class="relative w-full md:w-64 group">
                        <Search
                            class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 group-focus-within:text-blue-600 transition-colors" />
                        <input type="text" placeholder="Search catalog..."
                            class="w-full pl-11 pr-4 py-3 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-900 text-xs font-bold focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div v-for="product in filteredProducts" :key="product.id"
                        class="group relative flex flex-col rounded-[2.5rem] bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 p-5 transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/5 hover:-translate-y-2">

                        <div
                            class="relative aspect-square overflow-hidden rounded-[2rem] bg-gray-50 dark:bg-gray-800 mb-6">
                            <img v-if="product.image_url" :src="product.image_url" :alt="product.name"
                                class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110" />
                            <div v-else class="h-full w-full flex items-center justify-center text-gray-300">
                                <ImageIcon class="h-12 w-12" />
                            </div>
                        </div>

                        <div class="space-y-1 px-2">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3
                                        class="text-base font-black text-gray-900 dark:text-white uppercase tracking-tight">
                                        {{ product.name }}</h3>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">SKU: {{
                                        product.sku }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-black text-blue-600">₱{{
                                        parseFloat(product.price).toLocaleString() }}</p>
                                </div>
                            </div>

                            <div class="mt-6 flex items-center gap-3">
                                <div
                                    class="flex-1 flex items-center justify-between rounded-2xl bg-gray-50 dark:bg-gray-800/50 p-1 border border-gray-100 dark:border-gray-700">
                                    <button @click="removeFromCart(product.id)"
                                        class="p-2 text-gray-400 hover:text-blue-600">
                                        <Minus class="h-4 w-4" />
                                    </button>
                                    <span class="text-xs font-black text-gray-900 dark:text-white">
                                        {{ cart[product.id]?.quantity || 0 }}
                                    </span>
                                    <button @click="addToCart(product)" class="p-2 text-gray-400 hover:text-blue-600">
                                        <Plus class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div class="space-y-1">
                        <h2 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">
                            Purchase Order Pipeline</h2>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">B2B Workflow Tracking
                            (Credit & Tiering)</p>
                    </div>

                    <button v-if="cartTotal > 0" @click="submitPurchaseOrder"
                        class="px-8 py-4 bg-blue-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-blue-500/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
                        <ClipboardList class="h-4 w-4" />
                        Initialize Purchase Order
                    </button>
                </div>

                <div
                    class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr
                                    class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                                    <th class="px-8 py-5">PO Number</th>
                                    <th class="px-8 py-5">Subtotal</th>
                                    <th class="px-8 py-5">Workflow Process</th>
                                    <th class="px-8 py-5">Assigned Tier</th>
                                    <th class="px-8 py-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                <tr v-for="po in purchaseOrders" :key="po.id"
                                    class="group hover:bg-gray-50/50 transition-all">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="h-8 w-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500">
                                                <ClipboardList class="h-4 w-4" />
                                            </div>
                                            <span class="text-sm font-black text-gray-900 dark:text-white uppercase">{{
                                                po.po_number }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-sm font-bold text-gray-500 italic">
                                        ₱{{ parseFloat(po.total_amount).toLocaleString() }}
                                    </td>
                                    <td class="px-8 py-6">
                                        <div :class="getStatusTheme(po.status)"
                                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border text-[9px] font-black uppercase tracking-widest">
                                            <Loader2 v-if="po.status === 'credit_review'"
                                                class="h-3 w-3 animate-spin" />
                                            <CheckCircle2 v-else class="h-3 w-3" />
                                            {{ po.status.replace('_', ' ') }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <span v-if="po.tier_level"
                                            class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-2 py-1 rounded-lg uppercase italic">
                                            {{ po.tier_level }} Partner
                                        </span>
                                        <span v-else class="text-[10px] font-bold text-gray-300 uppercase">Awaiting HR
                                            Tiering</span>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <div v-if="po.status === 'pending_client_approval'"
                                            class="flex justify-end gap-2">
                                            <button @click="acceptQuotation(po.id)"
                                                class="px-4 py-2 bg-blue-600 text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-blue-700">Accept
                                                Quotation</button>
                                            <button
                                                class="px-4 py-2 bg-red-50 text-red-500 rounded-xl text-[9px] font-black uppercase tracking-widest">Reject</button>
                                        </div>
                                        <Link v-else href="#"
                                            class="text-gray-400 hover:text-blue-600 transition-colors">
                                            <ChevronRight class="h-5 w-5 ml-auto" />
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="purchaseOrders.length === 0">
                                    <td colspan="5" class="px-8 py-20 text-center">
                                        <AlertCircle class="h-8 w-8 text-gray-200 mx-auto mb-2" />
                                        <p class="text-xs font-black text-gray-300 uppercase tracking-widest">No Active
                                            Purchase Orders</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-[2.5rem] bg-gray-900 p-8 text-white mt-12">
                <div class="absolute right-0 top-0 h-full w-1/3 bg-gradient-to-l from-blue-600/20 to-transparent"></div>
                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="flex items-center gap-6">
                        <div
                            class="h-16 w-16 rounded-[2rem] bg-white/10 flex items-center justify-center backdrop-blur-xl">
                            <Clock class="h-8 w-8 text-blue-400" />
                        </div>
                        <div>
                            <h4 class="text-lg font-black uppercase tracking-tight">Production Pipeline</h4>
                            <p class="text-sm text-gray-400 font-medium italic">
                                You have <span class="text-blue-400 font-bold">{{ stats.pending_orders }} confirmed
                                    orders</span> currently being processed.
                            </p>
                        </div>
                    </div>
                    <Link :href="route('client.orders')"
                        class="group flex items-center gap-3 bg-white text-gray-900 px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition-transform hover:scale-105 active:scale-95">
                        View Detailed Status
                        <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.tracking-tighter {
    letter-spacing: -0.05em;
}
</style>