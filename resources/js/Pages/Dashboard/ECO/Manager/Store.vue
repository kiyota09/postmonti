<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import {
    Search,
    Package,
    Boxes,
    ArrowDownToLine,
    Zap,
    ChevronRight,
    ChevronLeft,
    ShoppingBag,
    AlertCircle,
    ChevronDown,
    Weight,
    Ruler,
    Tag,
    X,
    Eye,
    Layers,
    FileText,
    Warehouse,
    DollarSign,
    CheckCircle,
    AlertTriangle
} from 'lucide-vue-next';

const props = defineProps({
    products: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ search: '', category: 'All' }) },
});

// ── State ─────────────────────────────────────────────────────────────
const searchQuery = ref(props.filters.search || '');
const catFilter = ref(props.filters.category || 'All');
const showDetailModal = ref(false);
const selectedProduct = ref(null);

// ── Computed categories ────────────────────────────────────────────────
const categories = computed(() => {
    const cats = ['All', ...new Set(props.products.map(p => p.category))];
    return cats;
});

// ── Filtered products ─────────────────────────────────────────────────
const filteredProducts = computed(() => {
    let list = props.products;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(p =>
            p.name.toLowerCase().includes(q) ||
            p.sku.toLowerCase().includes(q) ||
            p.product_id.toLowerCase().includes(q)
        );
    }
    if (catFilter.value !== 'All') {
        list = list.filter(p => p.category === catFilter.value);
    }
    return list;
});

// ── Image slider state ───────────────────────────────────────────────
const cardSlide = ref({});
const autoSlideIntervals = {};

const startAutoSlide = (id, total) => {
    if (autoSlideIntervals[id]) return;
    autoSlideIntervals[id] = setInterval(() => {
        cardSlide.value[id] = ((cardSlide.value[id] ?? 0) + 1) % total;
    }, 4000);
};

const stopAutoSlide = (id) => {
    if (autoSlideIntervals[id]) {
        clearInterval(autoSlideIntervals[id]);
        delete autoSlideIntervals[id];
    }
};

const slidePrev = (id, total, e) => {
    e.stopPropagation();
    cardSlide.value[id] = ((cardSlide.value[id] ?? 0) - 1 + total) % total;
    stopAutoSlide(id);
    startAutoSlide(id, total);
};

const slideNext = (id, total, e) => {
    e.stopPropagation();
    cardSlide.value[id] = ((cardSlide.value[id] ?? 0) + 1) % total;
    stopAutoSlide(id);
    startAutoSlide(id, total);
};

// ── Initialize auto-slide when products load ─────────────────────────
onMounted(() => {
    props.products.forEach(p => {
        if (p.images && p.images.length > 1) {
            startAutoSlide(p.id, p.images.length);
        }
    });
});

onUnmounted(() => {
    Object.values(autoSlideIntervals).forEach(clearInterval);
});

watch(() => props.products, (newProds) => {
    Object.values(autoSlideIntervals).forEach(clearInterval);
    Object.keys(autoSlideIntervals).forEach(key => delete autoSlideIntervals[key]);
    newProds.forEach(p => {
        if (p.images && p.images.length > 1) {
            startAutoSlide(p.id, p.images.length);
        }
    });
}, { deep: true });

// ── Helpers ───────────────────────────────────────────────────────────
const fmt = (n) => Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const margin = (p) => {
    if (!p.sellingPrice) return '0.0';
    return (((p.sellingPrice - p.unitCost) / p.sellingPrice) * 100).toFixed(1);
};

// ── Search debounce ───────────────────────────────────────────────────
let searchTimeout;
const updateSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('eco.manager.store'), {
            search: searchQuery.value,
            category: catFilter.value
        }, { preserveState: true, replace: true });
    }, 300);
};

// ── Open product modal ────────────────────────────────────────────────
const openDetail = (product) => {
    selectedProduct.value = product;
    showDetailModal.value = true;
};
</script>

<template>
    <Head title="Product Store - ECO Manager" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Package class="h-3.5 w-3.5" />
                        Digital Catalog
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Product <span class="text-indigo-600">Store</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Browse the complete textile catalog sourced from inventory.
                    </p>
                </div>
                <button
                    class="px-6 py-3 rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 text-[11px] font-black uppercase tracking-widest text-gray-600 hover:bg-gray-50 transition-all flex items-center gap-2">
                    <ArrowDownToLine class="h-4 w-4" /> Export Catalog
                </button>
            </div>

            <!-- Stats (optional but nice) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Products</p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ props.products.length }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Categories</p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ categories.length - 1 }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Active Products</p>
                    <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                        {{ props.products.filter(p => p.status === 'Active').length }}
                    </p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Low Stock</p>
                    <p class="text-3xl font-black text-amber-600 mt-1">
                        {{ props.products.filter(p => p.stockOnHand < 100).length }}
                    </p>
                </div>
            </div>

            <!-- Search & Filter -->
            <div class="flex flex-wrap gap-3 items-center justify-between">
                <div class="flex gap-3 flex-wrap">
                    <div class="relative min-w-[240px] group">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 group-focus-within:text-indigo-600" />
                        <input v-model="searchQuery" @input="updateSearch" type="text"
                            placeholder="Search by name, SKU, product ID..."
                            class="pl-10 pr-4 py-3 w-full text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                    </div>
                    <div class="relative">
                        <select v-model="catFilter" @change="updateSearch"
                            class="appearance-none pl-4 pr-10 py-3 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 font-semibold">
                            <option v-for="c in categories" :key="c">{{ c }}</option>
                        </select>
                        <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" />
                    </div>
                </div>
                <div v-if="searchQuery || catFilter !== 'All'" class="flex items-center gap-2">
                    <span class="text-xs text-gray-500">{{ filteredProducts.length }} results</span>
                    <button @click="searchQuery = ''; catFilter = 'All'; updateSearch()"
                        class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                        <X class="h-4 w-4 text-gray-400" />
                    </button>
                </div>
            </div>

            <!-- Product Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <div v-for="product in filteredProducts" :key="product.id"
                    class="group bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden hover:shadow-xl hover:border-indigo-200 dark:hover:border-indigo-800 transition-all duration-300 flex flex-col cursor-pointer"
                    @click="openDetail(product)">

                    <!-- Image area with slider -->
                    <div class="relative overflow-hidden bg-gray-100 dark:bg-gray-800 h-64">
                        <template v-if="product.images && product.images.length > 1">
                            <div class="relative w-full h-full"
                                @mouseenter="stopAutoSlide(product.id)"
                                @mouseleave="startAutoSlide(product.id, product.images.length)">
                                <div class="flex h-full transition-transform duration-300 ease-in-out"
                                    :style="`transform: translateX(-${(cardSlide[product.id] ?? 0) * 100}%)`">
                                    <img v-for="img in product.images" :key="img.id" :src="img.url" :alt="product.name"
                                        class="h-full w-full object-cover flex-shrink-0" />
                                </div>
                                <button @click="slidePrev(product.id, product.images.length, $event)"
                                    class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition z-10">
                                    <ChevronLeft class="w-4 h-4" />
                                </button>
                                <button @click="slideNext(product.id, product.images.length, $event)"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition z-10">
                                    <ChevronRight class="w-4 h-4" />
                                </button>
                                <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1 z-10">
                                    <button v-for="(_, idx) in product.images" :key="idx"
                                        @click.stop="cardSlide[product.id] = idx; stopAutoSlide(product.id); startAutoSlide(product.id, product.images.length)"
                                        :class="['w-1.5 h-1.5 rounded-full transition-all', (cardSlide[product.id] ?? 0) === idx ? 'bg-white scale-125' : 'bg-white/50']" />
                                </div>
                            </div>
                        </template>
                        <template v-else-if="product.images && product.images.length === 1">
                            <img :src="product.images[0].url" :alt="product.name" class="h-full w-full object-cover" />
                        </template>
                        <template v-else>
                            <div class="h-full w-full flex items-center justify-center text-gray-300">
                                <Package class="h-16 w-16" />
                            </div>
                        </template>

                        <!-- Status badge -->
                        <div class="absolute top-3 right-3 z-10">
                            <span :class="product.status === 'Active'
                                ? 'bg-emerald-500/90 text-white'
                                : 'bg-slate-500/90 text-white'"
                                class="text-[10px] font-black px-2 py-0.5 rounded-full backdrop-blur-sm uppercase">
                                {{ product.status }}
                            </span>
                        </div>
                    </div>

                    <!-- Card content -->
                    <div class="p-5 flex flex-col gap-3 flex-1">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-mono text-[10px] font-bold text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">
                                    {{ product.product_id }}
                                </span>
                                <span class="text-[10px] font-bold text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-full">
                                    {{ product.subcategory || product.category }}
                                </span>
                            </div>
                            <h3 class="font-black text-gray-900 dark:text-white text-base leading-tight group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                {{ product.name }}
                            </h3>
                            <p class="font-mono text-[11px] text-gray-400 mt-0.5">{{ product.sku }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="flex items-center gap-1.5 text-gray-500">
                                <Weight class="w-3.5 h-3.5" />
                                <span>{{ product.weight || '—' }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-gray-500">
                                <Ruler class="w-3.5 h-3.5" />
                                <span class="truncate">{{ product.dimensions || '—' }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-gray-500">
                                <Boxes class="w-3.5 h-3.5" />
                                <span>{{ product.stockOnHand?.toLocaleString() || 0 }} units</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-gray-500">
                                <Tag class="w-3.5 h-3.5" />
                                <span>MOQ {{ product.moq || '—' }}</span>
                            </div>
                        </div>

                        <div class="pt-3 mt-auto border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <div>
                                <p class="text-[10px] text-gray-400 font-bold uppercase">Unit Price</p>
                                <p class="text-lg font-black text-indigo-600 dark:text-indigo-400">₱{{ fmt(product.sellingPrice) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-gray-400 font-bold uppercase">Margin</p>
                                <p class="text-sm font-black text-emerald-600">{{ margin(product) }}%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="filteredProducts.length === 0"
                class="col-span-full flex flex-col items-center justify-center py-20 text-gray-400 bg-gray-50 dark:bg-gray-800/30 rounded-[3rem] border-2 border-dashed border-gray-200 dark:border-gray-700">
                <Package class="w-16 h-16 mb-4 opacity-30" />
                <p class="font-bold text-gray-500">No products match your filters.</p>
                <button @click="searchQuery = ''; catFilter = 'All'; updateSearch()"
                    class="mt-3 text-sm text-indigo-600 font-bold hover:underline">
                    Clear filters
                </button>
            </div>
        </div>

        <!-- Product Detail Modal (No Quotation Button) -->
        <Teleport to="body">
            <div v-if="showDetailModal && selectedProduct"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="showDetailModal = false">
                <div class="bg-white dark:bg-gray-900 w-full max-w-4xl rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                    <!-- Modal header -->
                    <div class="px-8 py-6 bg-indigo-600 text-white flex justify-between items-center flex-shrink-0">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 rounded-2xl bg-white/10 flex items-center justify-center">
                                <Eye class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-black uppercase tracking-tighter italic">Product Details</h3>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80">{{ selectedProduct.sku }}</p>
                            </div>
                        </div>
                        <button @click="showDetailModal = false" class="p-2 bg-white/10 rounded-xl hover:bg-white/20 transition-all">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Modal body -->
                    <div class="p-8 overflow-y-auto flex-1">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Left: Images -->
                            <div class="space-y-4">
                                <div class="bg-gray-100 dark:bg-gray-800 rounded-2xl overflow-hidden">
                                    <img v-if="selectedProduct.images && selectedProduct.images[0]" :src="selectedProduct.images[0].url" :alt="selectedProduct.name"
                                        class="w-full object-cover max-h-80" />
                                    <div v-else class="h-64 flex items-center justify-center text-gray-400">
                                        <Package class="h-16 w-16" />
                                    </div>
                                </div>
                                <div v-if="selectedProduct.images && selectedProduct.images.length > 1" class="flex gap-2 overflow-x-auto pb-2">
                                    <img v-for="img in selectedProduct.images" :key="img.id" :src="img.url"
                                        class="h-20 w-20 rounded-lg object-cover border-2 border-gray-200 dark:border-gray-700 cursor-pointer hover:border-indigo-500 transition-all" />
                                </div>
                            </div>

                            <!-- Right: Details -->
                            <div class="space-y-6">
                                <div>
                                    <h2 class="text-2xl font-black text-gray-900 dark:text-white">{{ selectedProduct.name }}</h2>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="font-mono text-xs text-gray-500">{{ selectedProduct.product_id }}</span>
                                        <span class="text-xs text-gray-400">•</span>
                                        <span class="text-xs text-gray-500">{{ selectedProduct.category }}</span>
                                    </div>
                                </div>

                                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-5 space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Unit Price</span>
                                        <span class="text-2xl font-black text-indigo-600">₱{{ fmt(selectedProduct.sellingPrice) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Cost</span>
                                        <span class="font-bold text-gray-700 dark:text-gray-300">₱{{ fmt(selectedProduct.unitCost) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Stock On Hand</span>
                                        <span :class="selectedProduct.stockOnHand < 50 ? 'text-red-600' : 'text-emerald-600'" class="font-bold">
                                            {{ selectedProduct.stockOnHand?.toLocaleString() || 0 }} units
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">MOQ</span>
                                        <span class="font-bold">{{ selectedProduct.moq || '—' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Lead Time</span>
                                        <span class="font-bold">{{ selectedProduct.leadTime || '—' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Certification</span>
                                        <span class="font-bold">{{ selectedProduct.certification || '—' }}</span>
                                    </div>
                                </div>

                                <div v-if="selectedProduct.description" class="border-l-4 border-indigo-300 pl-4 italic text-gray-600 dark:text-gray-400">
                                    {{ selectedProduct.description }}
                                </div>

                                <!-- Sizes -->
                                <div v-if="selectedProduct.sizes && selectedProduct.sizes.length">
                                    <h4 class="text-xs font-black uppercase text-gray-500 mb-2">Available Sizes</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="sz in selectedProduct.sizes" :key="sz"
                                            class="px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-xs font-bold">
                                            {{ sz }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Specs -->
                                <div v-if="selectedProduct.specs && selectedProduct.specs.length">
                                    <h4 class="text-xs font-black uppercase text-gray-500 mb-2">Technical Specifications</h4>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div v-for="spec in selectedProduct.specs" :key="spec.label" class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-1">
                                            <span class="text-sm font-medium">{{ spec.label }}</span>
                                            <span class="text-sm font-bold">{{ spec.value }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- BOM -->
                                <div v-if="selectedProduct.materials && selectedProduct.materials.length">
                                    <h4 class="text-xs font-black uppercase text-gray-500 mb-2 flex items-center gap-2">
                                        <Layers class="h-4 w-4" /> Bill of Materials
                                    </h4>
                                    <div class="space-y-2 max-h-48 overflow-y-auto">
                                        <div v-for="mat in selectedProduct.materials" :key="mat.sku"
                                            class="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                            <div>
                                                <p class="text-sm font-bold">{{ mat.name }}</p>
                                                <p class="text-[10px] text-gray-500">SKU: {{ mat.sku }} | {{ mat.category }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-black">{{ mat.qty }} {{ mat.unit }}</p>
                                                <p class="text-[10px]" :class="mat.stockStatus === 'In Stock' ? 'text-emerald-600' : 'text-red-600'">
                                                    {{ mat.stockStatus }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal footer - No Quotation Button -->
                    <div class="px-8 py-6 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 flex justify-end gap-3 flex-shrink-0">
                        <button @click="showDetailModal = false"
                            class="px-6 py-3 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-[10px] font-black uppercase tracking-widest text-gray-600 hover:bg-gray-50 transition-all">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.tracking-tighter { letter-spacing: -0.05em; }
</style>