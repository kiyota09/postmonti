<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import {
    Search, X, Package, Layers, Boxes, BarChart3,
    ArrowDownToLine, Archive, Zap, ChevronRight, ChevronLeft,
    ShoppingBag, Clock, AlertCircle, ChevronDown,
    Weight, Ruler, Tag,
} from 'lucide-vue-next';

const ChevronRightIcon = ChevronRight;

const props = defineProps({
    invProducts: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({
            todaySales: '0.00', monthlyRevenue: '0.00',
            activeProducts: 0, lowStockCount: 0,
            pendingCredit: 0, pendingTiering: 0,
        }),
    },
    onlineSales: { type: Array, default: () => [] },
    pipelineDetails: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ search: '' }) },
});

// ── Tab / search / filter ─────────────────────────────────────────────────────
const activeTab = ref('catalog');
const searchQuery = ref('');
const catFilter = ref('All');

// ── Stats ─────────────────────────────────────────────────────────────────────
const statsData = computed(() => [
    { label: 'Today Sales', value: `₱${props.stats.todaySales}`, icon: Zap, color: 'text-blue-600', bg: 'bg-blue-50 dark:bg-blue-900/20' },
    { label: 'Low Stock', value: props.stats.lowStockCount, icon: Archive, color: 'text-rose-600', bg: 'bg-rose-50 dark:bg-rose-900/20' },
    { label: 'Active SKUs', value: props.stats.activeProducts, icon: Package, color: 'text-indigo-600', bg: 'bg-indigo-50 dark:bg-indigo-900/20' },
    { label: 'Monthly Rev.', value: `₱${props.stats.monthlyRevenue}`, icon: BarChart3, color: 'text-amber-600', bg: 'bg-amber-50 dark:bg-amber-900/20' },
]);

// ── Catalog computed ──────────────────────────────────────────────────────────
const categories = computed(() => ['All', ...new Set(props.invProducts.map(p => p.category))]);

const filtered = computed(() => {
    let list = props.invProducts;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(p =>
            p.name.toLowerCase().includes(q) ||
            p.sku.toLowerCase().includes(q) ||
            p.product_id.toLowerCase().includes(q)
        );
    }
    if (catFilter.value !== 'All') list = list.filter(p => p.category === catFilter.value);
    return [...list].sort((a, b) => {
        const aHas = a.images && a.images.length > 0 ? 0 : 1;
        const bHas = b.images && b.images.length > 0 ? 0 : 1;
        return aHas - bHas;
    });
});

// ── Pipeline ──────────────────────────────────────────────────────────────────
const creditReviewItems = computed(() => props.pipelineDetails.filter(i => i.status === 'credit_review'));
const tierAssignmentItems = computed(() => props.pipelineDetails.filter(i => i.status === 'tier_assignment'));

// ── Helpers ───────────────────────────────────────────────────────────────────
const fmt = (n) => Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const margin = (p) => {
    if (!p.sellingPrice) return '0.0';
    return (((p.sellingPrice - p.unitCost) / p.sellingPrice) * 100).toFixed(1);
};

const bomHasAlert = (p) => p.materials.some(m => m.stockStatus !== 'In Stock');

const getStatusStyles = (status) => ({
    approved: 'bg-emerald-50 text-emerald-600 border-emerald-200',
    credit_review: 'bg-orange-50 text-orange-600 border-orange-200',
    tier_assignment: 'bg-blue-50 text-blue-600 border-blue-200',
}[status] ?? 'bg-gray-50 text-gray-500 border-gray-200');

// ── Auto-slide ────────────────────────────────────────────────────────────────
const cardSlide = ref({});
const slideIdx = (id) => cardSlide.value[id] ?? 0;
const autoSlideIntervals = {};

const startAutoSlide = (id, total) => {
    if (autoSlideIntervals[id]) return;
    autoSlideIntervals[id] = setInterval(() => {
        cardSlide.value[id] = ((cardSlide.value[id] ?? 0) + 1) % total;
    }, 3000);
};
const stopAutoSlide = (id) => {
    if (autoSlideIntervals[id]) { clearInterval(autoSlideIntervals[id]); delete autoSlideIntervals[id]; }
};
const resetAutoSlide = (id, total) => { stopAutoSlide(id); startAutoSlide(id, total); };

const initAutoSlide = () => {
    props.invProducts.forEach(p => { if (p.images && p.images.length > 1) startAutoSlide(p.id, p.images.length); });
};

const slideNext = (id, total, e) => {
    e.stopPropagation();
    cardSlide.value[id] = ((cardSlide.value[id] ?? 0) + 1) % total;
    resetAutoSlide(id, total);
};
const slidePrev = (id, total, e) => {
    e.stopPropagation();
    cardSlide.value[id] = ((cardSlide.value[id] ?? 0) - 1 + total) % total;
    resetAutoSlide(id, total);
};

onMounted(() => initAutoSlide());
onUnmounted(() => Object.values(autoSlideIntervals).forEach(id => clearInterval(id)));
watch(() => props.invProducts, () => initAutoSlide(), { deep: true });
</script>

<template>

    <Head title="ECO Dashboard - Catalog Master" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                <div class="space-y-1">
                    <div
                        class="flex items-center gap-2 text-blue-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Zap class="h-3 w-3 fill-current" /> Infrastructure Live
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Catalog <span class="text-blue-600">Master</span>
                    </h1>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        class="px-6 py-3 rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 text-[11px] font-black uppercase tracking-widest text-gray-600 hover:bg-gray-50 transition-all flex items-center gap-2">
                        <ArrowDownToLine class="h-4 w-4" /> Export
                    </button>
                </div>
            </div>

            <!-- Stats strip -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="stat in statsData" :key="stat.label"
                    class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm flex items-center justify-between hover:shadow-md transition-all">
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ stat.label }}</p>
                        <p class="text-3xl font-black text-gray-900 dark:text-white mt-1 tracking-tighter italic">{{
                            stat.value }}</p>
                    </div>
                    <div :class="[stat.bg, stat.color]" class="h-14 w-14 rounded-2xl flex items-center justify-center">
                        <component :is="stat.icon" class="h-7 w-7" />
                    </div>
                </div>
            </div>

            <!-- Main 8/4 grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                <!-- Left 8 cols -->
                <div class="lg:col-span-8 space-y-6">

                    <!-- Tabs -->
                    <div class="flex items-center border-b border-gray-100 dark:border-slate-800 pb-4 gap-8">
                        <button @click="activeTab = 'catalog'"
                            :class="activeTab === 'catalog' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-400 hover:text-gray-600'"
                            class="pb-2 text-[11px] font-black uppercase tracking-widest transition-all">
                            Product Catalog
                        </button>
                        <button @click="activeTab = 'pipeline'"
                            :class="activeTab === 'pipeline' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-400 hover:text-gray-600'"
                            class="pb-2 text-[11px] font-black uppercase tracking-widest transition-all italic">
                            Department Pipeline
                        </button>
                    </div>

                    <!-- ── CATALOG TAB ─────────────────────────────────────── -->
                    <template v-if="activeTab === 'catalog'">

                        <!-- Search + filter row -->
                        <div class="flex flex-wrap gap-3 items-center">
                            <div class="relative flex-1 min-w-[200px] max-w-xs">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                                <input v-model="searchQuery" type="text" placeholder="Search product, SKU..."
                                    class="pl-9 pr-4 py-2.5 w-full text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 placeholder-slate-400" />
                            </div>
                            <div class="relative">
                                <select v-model="catFilter"
                                    class="appearance-none pl-3 pr-8 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 font-semibold">
                                    <option v-for="c in categories" :key="c">{{ c }}</option>
                                </select>
                                <ChevronDown
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                            </div>
                            <div v-if="searchQuery || catFilter !== 'All'" class="flex items-center gap-1.5">
                                <span class="text-xs text-slate-400 font-medium">{{ filtered.length }} results</span>
                                <button @click="searchQuery = ''; catFilter = 'All'"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                    <X class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Product Card Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div v-for="product in filtered" :key="product.id"
                                class="group bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden hover:shadow-lg hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-300 flex flex-col">

                                <!-- Image area -->
                                <div class="relative overflow-hidden flex-shrink-0 bg-slate-100 dark:bg-slate-800">

                                    <!-- SLIDER (2+ images) -->
                                    <template v-if="product.images && product.images.length > 1">
                                        <div class="relative w-full aspect-square overflow-hidden"
                                            @mouseenter="stopAutoSlide(product.id)"
                                            @mouseleave="startAutoSlide(product.id, product.images.length)">
                                            <div class="flex h-full transition-transform duration-300 ease-in-out"
                                                :style="`transform: translateX(-${slideIdx(product.id) * 100}%)`">
                                                <img v-for="img in product.images" :key="img.id" :src="img.url"
                                                    :alt="product.name"
                                                    class="h-full w-full object-cover flex-shrink-0" />
                                            </div>
                                            <button @click="slidePrev(product.id, product.images.length, $event)"
                                                class="absolute left-2 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition z-10">
                                                <ChevronLeft class="w-3.5 h-3.5" />
                                            </button>
                                            <button @click="slideNext(product.id, product.images.length, $event)"
                                                class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition z-10">
                                                <ChevronRightIcon class="w-3.5 h-3.5" />
                                            </button>
                                            <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1 z-10">
                                                <button v-for="(_, di) in product.images" :key="di"
                                                    @click.stop="cardSlide[product.id] = di"
                                                    :class="['w-1.5 h-1.5 rounded-full transition-all', di === slideIdx(product.id) ? 'bg-white scale-125' : 'bg-white/50']" />
                                            </div>
                                        </div>
                                    </template>

                                    <!-- SINGLE image -->
                                    <template v-else-if="product.images && product.images.length === 1">
                                        <img :src="product.images[0].url" :alt="product.name"
                                            class="w-full aspect-square object-cover group-hover:scale-105 transition-transform duration-500" />
                                    </template>

                                    <!-- No image — color bar -->
                                    <template v-else>
                                        <div class="h-2 w-full"
                                            :style="`background-color: ${product.colorHex || '#64748b'}`" />
                                    </template>

                                    <!-- Status badge -->
                                    <div class="absolute top-2 right-2 z-10">
                                        <span :class="product.status === 'Active'
                                            ? 'bg-emerald-500/90 text-white'
                                            : product.status === 'Draft'
                                                ? 'bg-amber-400/90 text-white'
                                                : 'bg-slate-400/90 text-white'"
                                            class="text-[9px] font-black px-2 py-0.5 rounded-full backdrop-blur-sm uppercase tracking-wider">
                                            {{ product.status }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Card body -->
                                <div class="p-5 flex flex-col gap-3 flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                                <span
                                                    class="font-mono text-[10px] font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-md">
                                                    {{ product.product_id }}
                                                </span>
                                                <span
                                                    class="text-[10px] font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">
                                                    {{ product.subcategory || product.category }}
                                                </span>
                                                <span v-if="bomHasAlert(product)"
                                                    class="w-2 h-2 rounded-full bg-amber-500 flex-shrink-0"
                                                    title="BOM stock alert" />
                                            </div>
                                            <h3
                                                class="font-black text-slate-900 dark:text-white text-base leading-snug group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                                {{ product.name }}
                                            </h3>
                                            <p class="font-mono text-[11px] text-slate-400 mt-0.5">{{ product.sku }}</p>
                                        </div>
                                        <div class="w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center"
                                            :style="`background-color: ${product.colorHex || '#64748b'}22; border: 1.5px solid ${product.colorHex || '#64748b'}44`">
                                            <span class="w-3 h-3 rounded-full"
                                                :style="`background-color: ${product.colorHex || '#64748b'}`" />
                                        </div>
                                    </div>

                                    <p
                                        class="text-[12px] text-slate-500 dark:text-slate-400 leading-relaxed line-clamp-2">
                                        {{ product.description }}
                                    </p>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="flex items-center gap-2 text-xs text-slate-500">
                                            <Weight class="w-3.5 h-3.5 text-slate-300 flex-shrink-0" />
                                            <span>{{ product.weight || '—' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-slate-500">
                                            <Ruler class="w-3.5 h-3.5 text-slate-300 flex-shrink-0" />
                                            <span class="truncate">{{ product.dimensions || '—' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-slate-500">
                                            <Boxes class="w-3.5 h-3.5 text-slate-300 flex-shrink-0" />
                                            <span>{{ Number(product.stockOnHand).toLocaleString() }} on hand</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-slate-500">
                                            <Tag class="w-3.5 h-3.5 text-slate-300 flex-shrink-0" />
                                            <span>MOQ {{ product.moq || '—' }}</span>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="sz in product.sizes" :key="sz"
                                            class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                                            {{ sz }}
                                        </span>
                                    </div>

                                    <div
                                        class="pt-3 mt-auto border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div>
                                                <p
                                                    class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                                                    BOM</p>
                                                <p class="text-sm font-black text-slate-800 dark:text-white">{{
                                                    product.materials.length }} items</p>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                                                    Margin</p>
                                                <p class="text-sm font-black text-emerald-600">{{ margin(product) }}%
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[10px] text-slate-400 font-bold">Selling Price</p>
                                            <p class="text-sm font-black text-slate-900 dark:text-white">₱{{
                                                fmt(product.sellingPrice) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty state -->
                            <div v-if="filtered.length === 0"
                                class="col-span-full flex flex-col items-center justify-center py-20 text-slate-400">
                                <Package class="w-12 h-12 mb-4 opacity-30" />
                                <p class="font-bold text-slate-500">No products match your filters.</p>
                                <button @click="searchQuery = ''; catFilter = 'All'"
                                    class="mt-3 text-sm text-blue-600 font-bold hover:underline">Clear filters</button>
                            </div>
                        </div>
                    </template>

                    <!-- ── PIPELINE TAB ────────────────────────────────────── -->
                    <div v-if="activeTab === 'pipeline'"
                        class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-gray-100 dark:border-slate-800 shadow-sm p-8 space-y-10">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div
                                class="p-6 bg-orange-50/50 rounded-3xl border border-orange-100 flex items-center justify-between">
                                <div>
                                    <h4 class="text-[10px] font-black uppercase text-orange-900 tracking-widest italic">
                                        Awaiting Credit
                                        Review</h4>
                                    <p class="text-3xl font-black text-orange-600 mt-1 italic tracking-tighter">{{
                                        props.stats.pendingCredit
                                        }}</p>
                                </div>
                                <Clock class="h-8 w-8 text-orange-300" />
                            </div>
                            <div
                                class="p-6 bg-indigo-50/50 rounded-3xl border border-indigo-100 flex items-center justify-between">
                                <div>
                                    <h4 class="text-[10px] font-black uppercase text-indigo-900 tracking-widest italic">
                                        Awaiting Bulk
                                        Tiering</h4>
                                    <p class="text-3xl font-black text-indigo-600 mt-1 italic tracking-tighter">{{
                                        props.stats.pendingTiering }}</p>
                                </div>
                                <Layers class="h-8 w-8 text-indigo-300" />
                            </div>
                        </div>

                        <div class="space-y-8">
                            <div v-if="creditReviewItems.length" class="space-y-4">
                                <div class="flex items-center gap-2 px-2">
                                    <AlertCircle class="h-4 w-4 text-orange-500" />
                                    <h5 class="text-[11px] font-black uppercase text-gray-500 tracking-widest italic">
                                        Action Required:
                                        Credit Queue</h5>
                                </div>
                                <div class="border border-gray-100 rounded-3xl overflow-hidden shadow-sm">
                                    <table class="w-full text-left text-sm">
                                        <thead
                                            class="bg-gray-50 text-[10px] font-bold uppercase text-gray-400 tracking-wider">
                                            <tr>
                                                <th class="px-6 py-4">Partner Agency</th>
                                                <th class="px-6 py-4 italic">PO Ref</th>
                                                <th class="px-6 py-4 text-right italic">Valuation</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50 bg-white">
                                            <tr v-for="item in creditReviewItems" :key="item.id"
                                                class="hover:bg-gray-50/50 transition-colors">
                                                <td
                                                    class="px-6 py-4 font-black text-gray-700 uppercase tracking-tighter text-xs">
                                                    {{
                                                    item.client?.company_name }}</td>
                                                <td class="px-6 py-4 text-xs font-mono font-bold text-gray-400">#{{
                                                    item.po_number }}</td>
                                                <td
                                                    class="px-6 py-4 text-right font-black text-orange-600 italic text-xs">
                                                    ₱{{
                                                    parseFloat(item.total_amount).toLocaleString() }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div v-if="tierAssignmentItems.length" class="space-y-4">
                                <div class="flex items-center gap-2 px-2">
                                    <AlertCircle class="h-4 w-4 text-blue-500" />
                                    <h5 class="text-[11px] font-black uppercase text-gray-500 tracking-widest italic">
                                        Action Required: Tier
                                        Assignment</h5>
                                </div>
                                <div class="border border-gray-100 rounded-3xl overflow-hidden shadow-sm">
                                    <table class="w-full text-left text-sm">
                                        <thead
                                            class="bg-gray-50 text-[10px] font-bold uppercase text-gray-400 tracking-wider">
                                            <tr>
                                                <th class="px-6 py-4">Partner Agency</th>
                                                <th class="px-6 py-4 italic">PO Ref</th>
                                                <th class="px-6 py-4 text-right italic">Valuation</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50 bg-white">
                                            <tr v-for="item in tierAssignmentItems" :key="item.id"
                                                class="hover:bg-gray-50/50 transition-colors">
                                                <td
                                                    class="px-6 py-4 font-black text-gray-700 uppercase tracking-tighter text-xs">
                                                    {{
                                                    item.client?.company_name }}</td>
                                                <td class="px-6 py-4 text-xs font-mono font-bold text-gray-400">#{{
                                                    item.po_number }}</td>
                                                <td
                                                    class="px-6 py-4 text-right font-black text-blue-600 italic text-xs">
                                                    ₱{{
                                                    parseFloat(item.total_amount).toLocaleString() }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div v-if="!creditReviewItems.length && !tierAssignmentItems.length"
                                class="py-12 text-center bg-gray-50 rounded-3xl border-2 border-dashed border-gray-100">
                                <p class="text-xs font-black text-gray-400 uppercase tracking-widest italic">All
                                    pipeline protocols cleared
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right 4 cols: Live Order Stream -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="flex items-center gap-2 px-2">
                        <ShoppingBag class="h-4 w-4 text-blue-600" />
                        <h3 class="text-xs font-black uppercase tracking-widest text-gray-500 italic">Live Order Stream
                        </h3>
                    </div>

                    <div class="space-y-4">
                        <div v-for="sale in onlineSales" :key="sale.id"
                            class="p-5 bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-100 shadow-sm hover:border-blue-200 transition-all group">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <p
                                        class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-tighter italic">
                                        {{
                                        sale.client?.company_name }}</p>
                                    <p class="text-[9px] font-bold text-gray-400 uppercase italic">#{{ sale.po_number }}
                                    </p>
                                </div>
                                <span :class="getStatusStyles(sale.status)"
                                    class="px-2 py-1 rounded-lg text-[8px] font-black uppercase tracking-widest border border-current italic">
                                    {{ sale.status.replace('_', ' ') }}
                                </span>
                            </div>
                            <div class="flex justify-between items-end border-t border-gray-50 pt-3 italic">
                                <span class="text-sm font-black text-blue-600 italic tracking-tighter">₱{{
                                    parseFloat(sale.total_amount).toLocaleString() }}</span>
                                <Link :href="route('eco.employee.ordermng')"
                                    class="text-[9px] font-black text-gray-400 uppercase group-hover:text-blue-600 flex items-center gap-1 transition-all">
                                    Analyze
                                    <ChevronRight class="h-3 w-3" />
                                </Link>
                            </div>
                        </div>

                        <div v-if="!onlineSales.length"
                            class="text-center py-10 bg-gray-50/50 rounded-[2rem] border border-dashed border-gray-200">
                            <Archive class="h-8 w-8 text-gray-200 mx-auto mb-2" />
                            <p class="text-[10px] font-black text-gray-300 uppercase tracking-widest italic">System
                                Idle: No recent
                                activity</p>
                        </div>
                    </div>
                </div>
            </div>
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
</style>