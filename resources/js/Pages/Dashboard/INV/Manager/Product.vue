<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Search, X, ChevronRight, ChevronLeft, Package, Layers,
    Tag, Ruler, Weight, Palette, Clock,
    AlertTriangle, Boxes,
    ChevronDown, Info, Zap, Plus, Trash2,
    Pencil, Upload, ImageIcon,
} from 'lucide-vue-next';

// alias so template can use ChevronRightIcon without conflicting with the card's chevron
const ChevronRightIcon = ChevronRight;

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps({
    products: { type: Array, default: () => [] },
    masterMaterials: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
});

const products = ref(props.products);
watch(() => props.products, v => (products.value = v), { deep: true });

// ── UI State ──────────────────────────────────────────────────────────────────
const isLoaded = ref(false);
const searchQuery = ref('');
const catFilter = ref('All');
const selectedProduct = ref(null);
const activeTab = ref('bom');
const expandedMat = ref(null);
const showAddProduct = ref(false);
const showEditProduct = ref(false);
const processing = ref(false);

// Per-card image slider index keyed by product.id
const cardSlide = ref({});
const slideIdx = (productId) => cardSlide.value[productId] ?? 0;

// ── Auto-slide ────────────────────────────────────────────────────────────────
const autoSlideIntervals = {};

const startAutoSlide = (productId, total) => {
    if (autoSlideIntervals[productId]) return;
    autoSlideIntervals[productId] = setInterval(() => {
        cardSlide.value[productId] = ((cardSlide.value[productId] ?? 0) + 1) % total;
    }, 3000);
};

const stopAutoSlide = (productId) => {
    if (autoSlideIntervals[productId]) {
        clearInterval(autoSlideIntervals[productId]);
        delete autoSlideIntervals[productId];
    }
};

const resetAutoSlide = (productId, total) => {
    stopAutoSlide(productId);
    startAutoSlide(productId, total);
};

const initAutoSlide = () => {
    products.value.forEach(p => {
        if (p.images && p.images.length > 1) {
            startAutoSlide(p.id, p.images.length);
        }
    });
};

const slideNext = (productId, total, e) => {
    e.stopPropagation();
    cardSlide.value[productId] = ((cardSlide.value[productId] ?? 0) + 1) % total;
    resetAutoSlide(productId, total);
};
const slidePrev = (productId, total, e) => {
    e.stopPropagation();
    cardSlide.value[productId] = ((cardSlide.value[productId] ?? 0) - 1 + total) % total;
    resetAutoSlide(productId, total);
};

onMounted(() => {
    setTimeout(() => (isLoaded.value = true), 60);
    initAutoSlide();
});

onUnmounted(() => {
    Object.values(autoSlideIntervals).forEach(id => clearInterval(id));
});

watch(() => products.value, () => initAutoSlide(), { deep: true });

// ── Shared form shape ─────────────────────────────────────────────────────────
const ALL_SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '28', '30', '32', '34', '36', '38', 'One Size'];

const blankForm = () => ({
    name: '', category: '', subcategory: '', status: 'Active',
    color_tag: '', color_hex: '#000000', color_name: '',
    weight: '', dimensions: '', batch_size: '', lead_time: '',
    unit_cost: '', selling_price: '', stock_on_hand: '',
    moq: '', certification: '', description: '',
    sizes: [], bom: [], specs: [],
});

// ── Add form ──────────────────────────────────────────────────────────────────
const newProduct = ref(blankForm());
const newBomLine = ref({ material_id: '', qty: 1 });
const newSpecLine = ref({ label: '', value: '' });

// Image upload (add)
const addImageInput = ref(null);
const addImageFiles = ref([]);
const addImagePreviews = ref([]);

const onAddImageChange = (e) => {
    const files = Array.from(e.target.files || []);
    files.forEach(f => {
        addImageFiles.value.push(f);
        const reader = new FileReader();
        reader.onload = ev => addImagePreviews.value.push(ev.target.result);
        reader.readAsDataURL(f);
    });
    e.target.value = '';
};

const removeAddPreview = (i) => {
    addImageFiles.value.splice(i, 1);
    addImagePreviews.value.splice(i, 1);
};

const resetAddForm = () => {
    newProduct.value = blankForm();
    newBomLine.value = { material_id: '', qty: 1 };
    newSpecLine.value = { label: '', value: '' };
    addImageFiles.value = [];
    addImagePreviews.value = [];
};

// BOM helpers (add)
const addBomLine = () => {
    if (!newBomLine.value.material_id || !newBomLine.value.qty) return;
    const mat = props.masterMaterials.find(m => m.id == newBomLine.value.material_id);
    if (!mat) return;
    if (newProduct.value.bom.some(b => b.material_id == mat.id)) {
        alert('This material is already in the BOM.');
        return;
    }
    newProduct.value.bom.push({
        material_id: mat.id, sku_ref: mat.mat_id, name: mat.name,
        qty: Number(newBomLine.value.qty), unit: mat.unit,
        category: mat.category, warehouse_note: props.warehouses[0]?.name ?? '',
        unit_cost: mat.cost,
    });
    newBomLine.value = { material_id: '', qty: 1 };
};
const removeBomLine = (i) => newProduct.value.bom.splice(i, 1);

const addSpecLine = () => {
    if (!newSpecLine.value.label || !newSpecLine.value.value) return;
    newProduct.value.specs.push({ ...newSpecLine.value });
    newSpecLine.value = { label: '', value: '' };
};
const removeSpecLine = (i) => newProduct.value.specs.splice(i, 1);

const bomTotal = computed(() => newProduct.value.bom.reduce((s, b) => s + b.unit_cost * b.qty, 0));
const previewMargin = computed(() => {
    const sp = Number(newProduct.value.selling_price);
    const uc = Number(newProduct.value.unit_cost);
    if (!sp) return '0.0';
    return (((sp - uc) / sp) * 100).toFixed(1);
});

const submitProduct = () => {
    if (!newProduct.value.name || !newProduct.value.category) return;
    processing.value = true;
    router.post(route('inv.manager.product.store'), {
        ...newProduct.value,
        images: addImageFiles.value,
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { resetAddForm(); showAddProduct.value = false; },
        onFinish: () => (processing.value = false),
    });
};

// ── Edit form ─────────────────────────────────────────────────────────────────
const editForm = ref(blankForm());
const editProductId = ref(null);
const editBomLine = ref({ material_id: '', qty: 1 });
const editSpecLine = ref({ label: '', value: '' });
const editExistingImages = ref([]); // [{id, url}] — current saved images
const editImageInput = ref(null);
const editImageFiles = ref([]);
const editImagePreviews = ref([]);

const onEditImageChange = (e) => {
    const files = Array.from(e.target.files || []);
    files.forEach(f => {
        editImageFiles.value.push(f);
        const reader = new FileReader();
        reader.onload = ev => editImagePreviews.value.push(ev.target.result);
        reader.readAsDataURL(f);
    });
    e.target.value = '';
};

const removeEditPreview = (i) => {
    editImageFiles.value.splice(i, 1);
    editImagePreviews.value.splice(i, 1);
};

const deleteExistingImage = (imageId) => {
    if (!confirm('Remove this image?')) return;
    router.delete(route('inv.manager.product.image.destroy', { imageId }), {
        preserveScroll: true,
        onSuccess: () => {
            editExistingImages.value = editExistingImages.value.filter(img => img.id !== imageId);
        },
    });
};

const openEditModal = (product, e) => {
    e?.stopPropagation();
    editProductId.value = product.id;
    editExistingImages.value = [...(product.images ?? [])];
    editImageFiles.value = [];
    editImagePreviews.value = [];
    editBomLine.value = { material_id: '', qty: 1 };
    editSpecLine.value = { label: '', value: '' };
    editForm.value = {
        name: product.name,
        category: product.category,
        subcategory: product.subcategory ?? '',
        status: product.status,
        color_tag: product.color_tag ?? '',
        color_hex: product.colorHex ?? '#000000',
        color_name: product.colorName ?? '',
        weight: product.weight ?? '',
        dimensions: product.dimensions ?? '',
        batch_size: product.batch_size ?? '',
        lead_time: product.leadTime ?? '',
        unit_cost: product.unitCost,
        selling_price: product.sellingPrice,
        stock_on_hand: product.stockOnHand ?? 0,
        moq: product.moq ?? '',
        certification: product.certification ?? '',
        description: product.description ?? '',
        sizes: [...(product.sizes ?? [])],
        bom: product.materials.map(m => ({
            material_id: null, // linked separately via sku_ref lookup
            sku_ref: m.sku,
            name: m.name,
            qty: m.qty,
            unit: m.unit,
            category: m.category,
            warehouse_note: m.warehouse,
            unit_cost: m.cost,
        })),
        specs: product.specs.map(s => ({ label: s.label, value: s.value })),
    };
    showEditProduct.value = true;
};

// BOM helpers (edit)
const addEditBomLine = () => {
    if (!editBomLine.value.material_id || !editBomLine.value.qty) return;
    const mat = props.masterMaterials.find(m => m.id == editBomLine.value.material_id);
    if (!mat) return;
    if (editForm.value.bom.some(b => b.sku_ref === mat.mat_id)) {
        alert('This material is already in the BOM.');
        return;
    }
    editForm.value.bom.push({
        material_id: mat.id, sku_ref: mat.mat_id, name: mat.name,
        qty: Number(editBomLine.value.qty), unit: mat.unit,
        category: mat.category, warehouse_note: props.warehouses[0]?.name ?? '',
        unit_cost: mat.cost,
    });
    editBomLine.value = { material_id: '', qty: 1 };
};
const removeEditBomLine = (i) => editForm.value.bom.splice(i, 1);

const addEditSpecLine = () => {
    if (!editSpecLine.value.label || !editSpecLine.value.value) return;
    editForm.value.specs.push({ ...editSpecLine.value });
    editSpecLine.value = { label: '', value: '' };
};
const removeEditSpecLine = (i) => editForm.value.specs.splice(i, 1);

const editBomTotal = computed(() => editForm.value.bom.reduce((s, b) => s + b.unit_cost * b.qty, 0));
const editPreviewMargin = computed(() => {
    const sp = Number(editForm.value.selling_price);
    const uc = Number(editForm.value.unit_cost);
    if (!sp) return '0.0';
    return (((sp - uc) / sp) * 100).toFixed(1);
});

const submitEdit = () => {
    if (!editForm.value.name || !editForm.value.category) return;
    processing.value = true;
    router.post(route('inv.manager.product.update', { id: editProductId.value }), {
        ...editForm.value,
        new_images: editImageFiles.value,
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showEditProduct.value = false;
            editProductId.value = null;
        },
        onFinish: () => (processing.value = false),
    });
};

// ── Delete product ────────────────────────────────────────────────────────────
const deleteProduct = (id, e) => {
    e?.stopPropagation();
    if (!confirm('Delete this product?')) return;
    if (selectedProduct.value?.id === id) selectedProduct.value = null;
    router.delete(route('inv.manager.product.destroy', { id }), { preserveScroll: true });
};

// ── Computed ──────────────────────────────────────────────────────────────────
const categories = computed(() => ['All', ...new Set(products.value.map(p => p.category))]);

const filtered = computed(() => {
    let list = products.value;
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

const bomCost = (product) => product.materials.reduce((s, m) => s + m.cost * m.qty, 0);
const margin = (product) => {
    if (!product.sellingPrice) return '0.0';
    return (((product.sellingPrice - product.unitCost) / product.sellingPrice) * 100).toFixed(1);
};
const bomHasAlert = (product) => product.materials.some(m => m.stockStatus !== 'In Stock');

// ── Helpers ───────────────────────────────────────────────────────────────────
const matCatColor = {
    'Yarn': 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400',
    'Chemical': 'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-400',
    'Accessory': 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    'Raw Material': 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400',
    'Label': 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400',
    'Packaging': 'bg-rose-50 text-rose-600 dark:bg-rose-900/20 dark:text-rose-400',
};

const stockBadge = (s) => ({
    'In Stock': 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:ring-emerald-800',
    'Low Stock': 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:ring-amber-800',
    'Out of Stock': 'bg-red-50 text-red-600 ring-1 ring-red-200 dark:bg-red-900/20 dark:text-red-400 dark:ring-red-800',
}[s] ?? '');

const fmt = (n) => Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const openProduct = (product) => { selectedProduct.value = product; activeTab.value = 'bom'; expandedMat.value = null; };
const closeModal = () => { selectedProduct.value = null; };
</script>

<template>

    <Head title="Product Catalog | Monti Textile" />
    <AuthenticatedLayout>

        <!-- ── Header ─────────────────────────────────────────────────────── -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4"
            :class="isLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
            style="transition: opacity .45s ease, transform .45s ease;">
            <div>
                <p class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] mb-1">Monti Textile ERP</p>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Product Catalog</h1>
                <p class="text-slate-500 text-sm mt-0.5">Bill of materials, specifications, and raw material breakdown
                    for every product.</p>
            </div>
            <div class="flex items-center gap-3 text-sm flex-shrink-0">
                <span
                    class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 rounded-xl font-bold text-slate-600 dark:text-slate-300">
                    {{ products.length }} Products
                </span>
                <span class="px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 rounded-xl font-bold text-blue-600">
                    {{products.reduce((s, p) => s + p.materials.length, 0)}} Raw Materials
                </span>
                <button @click="showAddProduct = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm font-bold rounded-xl hover:opacity-80 transition shadow-sm">
                    <Plus class="w-4 h-4" /> Add Product
                </button>
            </div>
        </div>

        <!-- ── Filters ────────────────────────────────────────────────────── -->
        <div class="flex flex-wrap gap-3 mb-6 items-center" :class="isLoaded ? 'opacity-100' : 'opacity-0'"
            style="transition: opacity .5s ease .1s;">
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

        <!-- ── Product Grid ───────────────────────────────────────────────── -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <div v-for="(product, i) in filtered" :key="product.id"
                class="group bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden hover:shadow-lg hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-300 cursor-pointer flex flex-col"
                :style="`transition-delay: ${i * 50}ms`"
                :class="isLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-5'"
                @click="openProduct(product)">

                <!-- Cover: slider if multiple images, single image, or color bar -->
                <div class="relative overflow-hidden flex-shrink-0 bg-slate-100 dark:bg-slate-800">

                    <!-- SLIDER (2+ images) -->
                    <template v-if="product.images && product.images.length > 1">
                        <div class="relative w-full aspect-square overflow-hidden"
                            @mouseenter="stopAutoSlide(product.id)"
                            @mouseleave="startAutoSlide(product.id, product.images.length)">
                            <!-- Slides -->
                            <div class="flex h-full transition-transform duration-300 ease-in-out"
                                :style="`transform: translateX(-${slideIdx(product.id) * 100}%)`">
                                <img v-for="img in product.images" :key="img.id" :src="img.url" :alt="product.name"
                                    class="h-full w-full object-cover flex-shrink-0" />
                            </div>

                            <!-- Prev / Next arrows -->
                            <button @click="slidePrev(product.id, product.images.length, $event)"
                                class="absolute left-2 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition z-10">
                                <ChevronLeft class="w-3.5 h-3.5" />
                            </button>
                            <button @click="slideNext(product.id, product.images.length, $event)"
                                class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition z-10">
                                <ChevronRightIcon class="w-3.5 h-3.5" />
                            </button>

                            <!-- Dot indicators -->
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
                        <div class="h-2 w-full" :style="`background-color: ${product.colorHex || '#64748b'}`" />
                    </template>

                    <!-- Action buttons overlay -->
                    <div
                        class="absolute top-2 left-2 flex gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-10">
                        <button @click="openEditModal(product, $event)"
                            class="w-7 h-7 rounded-lg bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm flex items-center justify-center shadow-sm hover:bg-blue-600 hover:text-white transition-all"
                            title="Edit product">
                            <Pencil class="w-3.5 h-3.5" />
                        </button>
                        <button @click="deleteProduct(product.id, $event)"
                            class="w-7 h-7 rounded-lg bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm flex items-center justify-center shadow-sm hover:bg-red-600 hover:text-white transition-all"
                            title="Delete product">
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

                <div class="p-5 flex flex-col gap-4 flex-1">
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
                                    class="w-2 h-2 rounded-full bg-amber-500 flex-shrink-0" title="BOM stock alert" />
                            </div>
                            <h3
                                class="font-black text-slate-900 dark:text-white text-base leading-snug group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                {{ product.name }}
                            </h3>
                            <p class="font-mono text-[11px] text-slate-400 mt-0.5">{{ product.sku }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center"
                            :style="`background-color: ${product.colorHex || '#64748b'}22; border: 1.5px solid ${product.colorHex || '#64748b'}44`">
                            <span class="w-3 h-3 rounded-full"
                                :style="`background-color: ${product.colorHex || '#64748b'}`" />
                        </div>
                    </div>

                    <p class="text-[12px] text-slate-500 dark:text-slate-400 leading-relaxed line-clamp-2">
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
                            <Clock class="w-3.5 h-3.5 text-slate-300 flex-shrink-0" />
                            <span>Lead: {{ product.leadTime || '—' }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            <Boxes class="w-3.5 h-3.5 text-slate-300 flex-shrink-0" />
                            <span>{{ Number(product.stockOnHand).toLocaleString() }} on hand</span>
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
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">BOM Items</p>
                                <p class="text-sm font-black text-slate-800 dark:text-white">{{ product.materials.length
                                }} materials</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Margin</p>
                                <p class="text-sm font-black text-emerald-600">{{ margin(product) }}%</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="text-right">
                                <p class="text-[10px] text-slate-400 font-bold">Selling Price</p>
                                <p class="text-sm font-black text-slate-900 dark:text-white">₱{{
                                    fmt(product.sellingPrice) }}</p>
                            </div>
                            <div
                                class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center group-hover:bg-blue-600 transition-all">
                                <ChevronRight class="w-4 h-4 text-slate-400 group-hover:text-white" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="filtered.length === 0"
                class="col-span-full flex flex-col items-center justify-center py-24 text-slate-400">
                <Package class="w-12 h-12 mb-4 opacity-30" />
                <p class="font-bold text-slate-500">No products match your filters.</p>
                <button @click="searchQuery = ''; catFilter = 'All'"
                    class="mt-3 text-sm text-blue-600 font-bold hover:underline">Clear filters</button>
            </div>
        </div>


        <!-- ══ PRODUCT DETAIL MODAL ══════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="selectedProduct"
                class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-8 bg-black/50 backdrop-blur-sm overflow-y-auto"
                @click.self="closeModal">
                <div
                    class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-4xl mb-8">

                    <div class="h-1.5 w-full rounded-t-2xl"
                        :style="`background-color: ${selectedProduct.colorHex || '#64748b'}`" />

                    <!-- Product image gallery -->
                    <div v-if="selectedProduct.images && selectedProduct.images.length"
                        class="flex gap-2 p-4 border-b border-slate-100 dark:border-slate-800 overflow-x-auto">
                        <img v-for="img in selectedProduct.images" :key="img.id" :src="img.url"
                            :alt="selectedProduct.name"
                            class="h-24 w-24 object-cover rounded-xl flex-shrink-0 border border-slate-200 dark:border-slate-700" />
                    </div>

                    <div
                        class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                <span
                                    class="font-mono text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 px-2 py-0.5 rounded-md">{{
                                        selectedProduct.product_id }}</span>
                                <span
                                    class="text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 px-2 py-0.5 rounded-full">{{
                                        selectedProduct.category }} · {{ selectedProduct.subcategory }}</span>
                                <span
                                    class="text-[10px] font-black bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 px-2 py-0.5 rounded-full ring-1 ring-emerald-200 dark:ring-emerald-800">{{
                                        selectedProduct.status }}</span>
                                <span v-if="selectedProduct.certification"
                                    class="text-[10px] font-bold bg-blue-50 dark:bg-blue-900/20 text-blue-600 px-2 py-0.5 rounded-full ring-1 ring-blue-200 dark:ring-blue-800">
                                    ✓ {{ selectedProduct.certification }}
                                </span>
                            </div>
                            <h2 class="text-xl font-black text-slate-900 dark:text-white leading-tight">{{
                                selectedProduct.name }}</h2>
                            <p class="font-mono text-xs text-slate-400 mt-0.5">{{ selectedProduct.sku }}</p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button @click="openEditModal(selectedProduct); closeModal()"
                                class="p-2 rounded-xl text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition"
                                title="Edit product">
                                <Pencil class="w-4 h-4" />
                            </button>
                            <button @click="deleteProduct(selectedProduct.id)"
                                class="p-2 rounded-xl text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                <Trash2 class="w-4 h-4" />
                            </button>
                            <button @click="closeModal"
                                class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                <X class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Stat Strip -->
                    <div
                        class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-y sm:divide-y-0 divide-slate-100 dark:divide-slate-800 border-b border-slate-100 dark:border-slate-800">
                        <div class="px-5 py-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Unit Cost</p>
                            <p class="text-lg font-black text-slate-900 dark:text-white mt-0.5">₱{{
                                fmt(selectedProduct.unitCost) }}</p>
                        </div>
                        <div class="px-5 py-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Selling Price</p>
                            <p class="text-lg font-black text-emerald-600 mt-0.5">₱{{ fmt(selectedProduct.sellingPrice)
                            }}</p>
                        </div>
                        <div class="px-5 py-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Gross Margin</p>
                            <p class="text-lg font-black text-blue-600 mt-0.5">{{ margin(selectedProduct) }}%</p>
                        </div>
                        <div class="px-5 py-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Stock On Hand</p>
                            <p class="text-lg font-black text-slate-900 dark:text-white mt-0.5">{{
                                Number(selectedProduct.stockOnHand).toLocaleString() }}</p>
                        </div>
                    </div>

                    <!-- Description + Quick Info -->
                    <div
                        class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-5">
                        <div class="sm:col-span-2">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
                                Description</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">{{
                                selectedProduct.description }}</p>
                        </div>
                        <div class="space-y-2.5">
                            <div class="flex items-center gap-2.5">
                                <Palette class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" />
                                <div>
                                    <p class="text-[10px] text-slate-400 font-bold">Colorway</p>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="w-3.5 h-3.5 rounded-full flex-shrink-0"
                                            :style="`background-color: ${selectedProduct.colorHex}`" />
                                        <p class="text-xs font-bold text-slate-700 dark:text-slate-300">{{
                                            selectedProduct.colorName }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <Ruler class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" />
                                <div>
                                    <p class="text-[10px] text-slate-400 font-bold">Dimensions</p>
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300 mt-0.5">{{
                                        selectedProduct.dimensions }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <Tag class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" />
                                <div>
                                    <p class="text-[10px] text-slate-400 font-bold">Available Sizes</p>
                                    <div class="flex flex-wrap gap-1 mt-0.5">
                                        <span v-for="sz in selectedProduct.sizes" :key="sz"
                                            class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500">{{
                                                sz }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <Zap class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" />
                                <div>
                                    <p class="text-[10px] text-slate-400 font-bold">Lead Time / MOQ</p>
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300 mt-0.5">{{
                                        selectedProduct.leadTime }} · MOQ {{ selectedProduct.moq }} pcs</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="flex border-b border-slate-100 dark:border-slate-800 px-6">
                        <button
                            v-for="tab in [{ id: 'bom', label: 'Bill of Materials', icon: Layers }, { id: 'specs', label: 'Technical Specs', icon: Info }]"
                            :key="tab.id" @click="activeTab = tab.id"
                            :class="['flex items-center gap-2 px-1 py-3.5 mr-6 text-sm font-bold border-b-2 transition-colors', activeTab === tab.id ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300']">
                            <component :is="tab.icon" class="w-3.5 h-3.5" />
                            {{ tab.label }}
                            <span v-if="tab.id === 'bom'"
                                :class="['text-[10px] font-black px-1.5 py-0.5 rounded-full', activeTab === 'bom' ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-400']">
                                {{ selectedProduct.materials.length }}
                            </span>
                        </button>
                    </div>

                    <!-- Tab: BOM -->
                    <div v-if="activeTab === 'bom'" class="p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-xs text-slate-500">Total BOM Material Cost</p>
                                <p class="text-2xl font-black text-slate-900 dark:text-white">₱{{
                                    fmt(bomCost(selectedProduct)) }}</p>
                            </div>
                            <div v-if="bomHasAlert(selectedProduct)"
                                class="flex items-center gap-2 px-3 py-2 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                                <AlertTriangle class="w-4 h-4 text-amber-500" />
                                <p class="text-xs font-bold text-amber-700 dark:text-amber-400">Some materials are
                                    low/out of stock</p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div v-for="mat in selectedProduct.materials" :key="mat.sku"
                                class="border border-slate-100 dark:border-slate-800 rounded-xl overflow-hidden hover:border-slate-200 dark:hover:border-slate-700 transition-colors">
                                <button
                                    class="w-full flex items-center gap-4 px-4 py-3.5 text-left hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                                    @click="expandedMat = expandedMat === mat.sku ? null : mat.sku">
                                    <span
                                        :class="['w-2 h-2 rounded-full flex-shrink-0', mat.stockStatus === 'In Stock' ? 'bg-emerald-500' : mat.stockStatus === 'Low Stock' ? 'bg-amber-500' : 'bg-red-500']" />
                                    <span
                                        class="font-mono text-[10px] font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded w-24 flex-shrink-0 truncate">{{
                                            mat.sku }}</span>
                                    <span
                                        class="flex-1 text-sm font-semibold text-slate-800 dark:text-slate-200 min-w-0 truncate">{{
                                            mat.name }}</span>
                                    <span
                                        :class="['text-[10px] font-bold px-2 py-0.5 rounded-full flex-shrink-0 hidden sm:block', matCatColor[mat.category] ?? 'bg-slate-100 text-slate-500']">{{
                                            mat.category }}</span>
                                    <span
                                        class="text-sm font-black text-slate-700 dark:text-slate-300 flex-shrink-0 w-24 text-right">{{
                                            mat.qty }} {{ mat.unit }}</span>
                                    <span
                                        class="text-sm font-black text-slate-900 dark:text-white flex-shrink-0 w-24 text-right">₱{{
                                            fmt(mat.cost * mat.qty) }}</span>
                                    <ChevronDown
                                        :class="['w-3.5 h-3.5 text-slate-400 flex-shrink-0 transition-transform', expandedMat === mat.sku ? 'rotate-180' : '']" />
                                </button>
                                <div v-if="expandedMat === mat.sku"
                                    class="px-4 pb-4 pt-1 bg-slate-50/50 dark:bg-slate-800/30 border-t border-slate-100 dark:border-slate-800 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Unit
                                            Cost</p>
                                        <p class="font-bold text-slate-700 dark:text-slate-300 mt-0.5">₱{{ fmt(mat.cost)
                                        }} / {{ mat.unit }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Qty
                                            Required</p>
                                        <p class="font-bold text-slate-700 dark:text-slate-300 mt-0.5">{{ mat.qty }} {{
                                            mat.unit }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                                            Warehouse</p>
                                        <p class="font-bold text-slate-700 dark:text-slate-300 mt-0.5">{{ mat.warehouse
                                        }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Stock
                                            Status</p>
                                        <span
                                            :class="['inline-block mt-0.5 text-[10px] font-black px-2 py-0.5 rounded-full', stockBadge(mat.stockStatus)]">{{
                                                mat.stockStatus }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Specs -->
                    <div v-else-if="activeTab === 'specs'" class="p-5">
                        <div v-if="selectedProduct.specs.length === 0" class="text-center py-12 text-slate-400">
                            <Info class="w-10 h-10 mx-auto mb-3 opacity-30" />
                            <p>No technical specs recorded for this product.</p>
                        </div>
                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div v-for="spec in selectedProduct.specs" :key="spec.label"
                                class="flex items-start gap-3 px-4 py-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800">
                                <div class="flex-1 min-w-0">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">{{
                                        spec.label }}</p>
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-0.5">{{ spec.value
                                    }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>


        <!-- ══ ADD PRODUCT MODAL ═════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="showAddProduct"
                class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-6 bg-black/50 backdrop-blur-sm overflow-y-auto"
                @click.self="showAddProduct = false">
                <div
                    class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-3xl mb-8">

                    <div
                        class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white">Add New Product</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Product ID and SKU are auto-generated.</p>
                        </div>
                        <button @click="showAddProduct = false; resetAddForm()"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="p-6 space-y-6 max-h-[80vh] overflow-y-auto">

                        <!-- ── Section 1: Basic Info ─────────────────────── -->
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Basic
                                Information</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Product
                                        Name *</label>
                                    <input v-model="newProduct.name" type="text"
                                        placeholder="e.g. Classic Blue Crew-Neck T-Shirt"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Category
                                        *</label>
                                    <input v-model="newProduct.category" type="text" placeholder="e.g. Apparel"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Subcategory</label>
                                    <input v-model="newProduct.subcategory" type="text" placeholder="e.g. T-Shirts"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Status
                                        *</label>
                                    <div class="relative mt-1">
                                        <select v-model="newProduct.status"
                                            class="w-full appearance-none pl-3 pr-8 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 font-medium">
                                            <option>Active</option>
                                            <option>Draft</option>
                                            <option>Inactive</option>
                                        </select>
                                        <ChevronDown
                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Certification</label>
                                    <input v-model="newProduct.certification" type="text" placeholder="e.g. OEKO-TEX"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div class="col-span-2">
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Description</label>
                                    <textarea v-model="newProduct.description" rows="3"
                                        placeholder="Product description…"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 resize-none" />
                                </div>
                            </div>
                        </div>

                        <!-- ── Section 2: Pricing & Stock ───────────────── -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-5">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Pricing &
                                Stock</p>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Unit
                                        Cost (₱) *</label>
                                    <input v-model="newProduct.unit_cost" type="number" min="0" step="0.01"
                                        placeholder="0.00"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Selling
                                        Price (₱) *</label>
                                    <input v-model="newProduct.selling_price" type="number" min="0" step="0.01"
                                        placeholder="0.00"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Gross
                                        Margin</label>
                                    <div
                                        class="mt-1 px-3 py-2.5 text-sm bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl font-black text-emerald-700 dark:text-emerald-400">
                                        {{ previewMargin }}%
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Stock
                                        on Hand</label>
                                    <input v-model="newProduct.stock_on_hand" type="number" min="0" placeholder="0"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">MOQ
                                        (pcs)</label>
                                    <input v-model="newProduct.moq" type="number" min="1" placeholder="e.g. 50"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Batch
                                        Size</label>
                                    <input v-model="newProduct.batch_size" type="number" min="1" placeholder="e.g. 500"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                            </div>
                        </div>

                        <!-- ── Section 3: Details ──────────────────────── -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-5">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Product
                                Details</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Weight /
                                        GSM</label>
                                    <input v-model="newProduct.weight" type="text" placeholder="e.g. 180 gsm"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Dimensions</label>
                                    <input v-model="newProduct.dimensions" type="text"
                                        placeholder="e.g. 70cm × 50cm (M)"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Lead
                                        Time</label>
                                    <input v-model="newProduct.lead_time" type="text" placeholder="e.g. 7 days"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Color
                                        Name</label>
                                    <input v-model="newProduct.color_name" type="text" placeholder="e.g. Royal Blue"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Color
                                        Tag</label>
                                    <input v-model="newProduct.color_tag" type="text" placeholder="e.g. blue / amber"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Color
                                        Hex</label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <input v-model="newProduct.color_hex" type="color"
                                            class="w-12 h-10 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer bg-transparent p-0.5" />
                                        <input v-model="newProduct.color_hex" type="text" placeholder="#000000"
                                            class="flex-1 px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 font-mono" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Section 4: Images ───────────────────────── -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-5">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">
                                Product Images <span class="text-slate-300 font-normal">(optional · jpg, png, webp · max
                                    5MB each)</span>
                            </p>

                            <!-- Previews -->
                            <div v-if="addImagePreviews.length > 0" class="flex flex-wrap gap-3 mb-4">
                                <div v-for="(src, i) in addImagePreviews" :key="i" class="relative">
                                    <img :src="src"
                                        class="w-20 h-20 object-cover rounded-xl border border-slate-200 dark:border-slate-700" />
                                    <button @click="removeAddPreview(i)"
                                        class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center shadow-md hover:bg-red-600 transition z-10"
                                        title="Remove">
                                        <X class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>

                            <input ref="addImageInput" type="file" multiple accept="image/*" class="hidden"
                                @change="onAddImageChange" />
                            <button @click="addImageInput.click()"
                                class="flex items-center gap-2 px-4 py-2.5 text-sm font-bold border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl text-slate-500 hover:border-blue-400 hover:text-blue-600 hover:bg-blue-50/50 dark:hover:bg-blue-900/10 transition-all w-full justify-center">
                                <Upload class="w-4 h-4" />
                                Click to upload images
                            </button>
                        </div>

                        <!-- ── Section 5: Available Sizes ─────────────── -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-5">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Available
                                Sizes</p>
                            <div class="flex flex-wrap gap-2">
                                <label v-for="sz in ALL_SIZES" :key="sz"
                                    class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="checkbox" :value="sz" v-model="newProduct.sizes"
                                        class="w-4 h-4 rounded accent-blue-600" />
                                    <span
                                        class="text-sm font-bold text-slate-600 dark:text-slate-300 px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition">{{
                                            sz }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- ── Section 6: BOM ──────────────────────────── -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-5">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Bill of
                                    Materials</p>
                                <div v-if="newProduct.bom.length > 0" class="text-xs font-bold text-slate-500">
                                    BOM Total: <span class="text-slate-800 dark:text-white">₱{{ fmt(bomTotal) }}</span>
                                </div>
                            </div>
                            <div v-if="newProduct.bom.length > 0" class="space-y-2 mb-4">
                                <div v-for="(line, i) in newProduct.bom" :key="i"
                                    class="flex items-center gap-3 px-3 py-2.5 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                                    <span
                                        class="font-mono text-[10px] font-bold text-slate-400 bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded flex-shrink-0">{{
                                            line.sku_ref }}</span>
                                    <span
                                        class="flex-1 text-sm font-semibold text-slate-700 dark:text-slate-300 min-w-0 truncate">{{
                                            line.name }}</span>
                                    <span class="text-xs font-black text-slate-700 dark:text-slate-300 flex-shrink-0">{{
                                        line.qty }} {{ line.unit }}</span>
                                    <span class="text-xs font-bold text-slate-500 flex-shrink-0">₱{{ fmt(line.unit_cost
                                        * line.qty) }}</span>
                                    <button @click="removeBomLine(i)"
                                        class="p-1 rounded text-slate-400 hover:text-red-500 transition flex-shrink-0">
                                        <X class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                            <div class="flex gap-2 items-end">
                                <div class="flex-1">
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Material</label>
                                    <div class="relative mt-1">
                                        <select v-model="newBomLine.material_id"
                                            class="w-full appearance-none pl-3 pr-8 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200">
                                            <option value="">— select material —</option>
                                            <option v-for="m in masterMaterials" :key="m.id" :value="m.id">{{ m.mat_id
                                            }} — {{ m.name }}</option>
                                        </select>
                                        <ChevronDown
                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                                    </div>
                                </div>
                                <div class="w-24">
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Qty</label>
                                    <input v-model="newBomLine.qty" type="number" min="0.0001" step="0.01"
                                        placeholder="1"
                                        class="mt-1 w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <button @click="addBomLine" :disabled="!newBomLine.material_id || !newBomLine.qty"
                                    class="px-4 py-2 text-sm font-bold rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition disabled:opacity-40 flex-shrink-0">
                                    + Add
                                </button>
                            </div>
                        </div>

                        <!-- ── Section 7: Specs ────────────────────────── -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-5">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Technical
                                Specs <span class="text-slate-300 font-normal">(optional)</span></p>
                            <div v-if="newProduct.specs.length > 0" class="space-y-2 mb-4">
                                <div v-for="(spec, i) in newProduct.specs" :key="i"
                                    class="flex items-center gap-3 px-3 py-2 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                                    <span
                                        class="text-[10px] font-black text-slate-400 uppercase w-32 flex-shrink-0 truncate">{{
                                            spec.label }}</span>
                                    <span class="flex-1 text-sm text-slate-700 dark:text-slate-300 truncate">{{
                                        spec.value }}</span>
                                    <button @click="removeSpecLine(i)"
                                        class="p-1 rounded text-slate-400 hover:text-red-500 transition flex-shrink-0">
                                        <X class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>
                            <div class="flex gap-2 items-end">
                                <div class="flex-1">
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Label</label>
                                    <input v-model="newSpecLine.label" type="text" placeholder="e.g. Fabric Composition"
                                        class="mt-1 w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div class="flex-1">
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Value</label>
                                    <input v-model="newSpecLine.value" type="text" placeholder="e.g. 100% Combed Cotton"
                                        class="mt-1 w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <button @click="addSpecLine" :disabled="!newSpecLine.label || !newSpecLine.value"
                                    class="px-4 py-2 text-sm font-bold rounded-xl bg-slate-600 text-white hover:bg-slate-700 transition disabled:opacity-40 flex-shrink-0">
                                    + Add
                                </button>
                            </div>
                        </div>

                    </div>

                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex gap-3">
                        <button @click="showAddProduct = false; resetAddForm()"
                            class="flex-1 py-2.5 text-sm font-bold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                            Cancel
                        </button>
                        <button @click="submitProduct"
                            :disabled="processing || !newProduct.name || !newProduct.category"
                            class="flex-1 py-2.5 text-sm font-bold rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 hover:opacity-80 transition shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                            Create Product
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>


        <!-- ══ EDIT PRODUCT MODAL ════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="showEditProduct"
                class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-6 bg-black/50 backdrop-blur-sm overflow-y-auto"
                @click.self="showEditProduct = false">
                <div
                    class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-3xl mb-8">

                    <!-- Header -->
                    <div
                        class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white">Edit Product</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Changes are saved immediately on submit.</p>
                        </div>
                        <button @click="showEditProduct = false"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="p-6 space-y-6 max-h-[80vh] overflow-y-auto">

                        <!-- ── Basic Info ──────────────────────────────── -->
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Basic
                                Information</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Product
                                        Name *</label>
                                    <input v-model="editForm.name" type="text"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Category
                                        *</label>
                                    <input v-model="editForm.category" type="text"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Subcategory</label>
                                    <input v-model="editForm.subcategory" type="text"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Status
                                        *</label>
                                    <div class="relative mt-1">
                                        <select v-model="editForm.status"
                                            class="w-full appearance-none pl-3 pr-8 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 font-medium">
                                            <option>Active</option>
                                            <option>Draft</option>
                                            <option>Inactive</option>
                                        </select>
                                        <ChevronDown
                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Certification</label>
                                    <input v-model="editForm.certification" type="text"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div class="col-span-2">
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Description</label>
                                    <textarea v-model="editForm.description" rows="3"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 resize-none" />
                                </div>
                            </div>
                        </div>

                        <!-- ── Pricing & Stock ──────────────────────────── -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-5">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Pricing &
                                Stock</p>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Unit
                                        Cost (₱) *</label>
                                    <input v-model="editForm.unit_cost" type="number" min="0" step="0.01"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Selling
                                        Price (₱) *</label>
                                    <input v-model="editForm.selling_price" type="number" min="0" step="0.01"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Gross
                                        Margin</label>
                                    <div
                                        class="mt-1 px-3 py-2.5 text-sm bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl font-black text-emerald-700 dark:text-emerald-400">
                                        {{ editPreviewMargin }}%
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Stock
                                        on Hand</label>
                                    <input v-model="editForm.stock_on_hand" type="number" min="0"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">MOQ
                                        (pcs)</label>
                                    <input v-model="editForm.moq" type="number" min="1"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Batch
                                        Size</label>
                                    <input v-model="editForm.batch_size" type="number" min="1"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                            </div>
                        </div>

                        <!-- ── Product Details ─────────────────────────── -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-5">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Product
                                Details</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Weight /
                                        GSM</label>
                                    <input v-model="editForm.weight" type="text"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Dimensions</label>
                                    <input v-model="editForm.dimensions" type="text"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Lead
                                        Time</label>
                                    <input v-model="editForm.lead_time" type="text"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Color
                                        Name</label>
                                    <input v-model="editForm.color_name" type="text"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Color
                                        Tag</label>
                                    <input v-model="editForm.color_tag" type="text"
                                        class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Color
                                        Hex</label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <input v-model="editForm.color_hex" type="color"
                                            class="w-12 h-10 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer bg-transparent p-0.5" />
                                        <input v-model="editForm.color_hex" type="text"
                                            class="flex-1 px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 font-mono" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Images ──────────────────────────────────── -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-5">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">
                                Product Images
                            </p>

                            <!-- Existing images -->
                            <div v-if="editExistingImages.length > 0" class="mb-4">
                                <p class="text-[10px] text-slate-400 font-bold mb-2">Saved Images</p>
                                <div class="flex flex-wrap gap-3">
                                    <div v-for="img in editExistingImages" :key="img.id" class="relative">
                                        <img :src="img.url"
                                            class="w-20 h-20 object-cover rounded-xl border border-slate-200 dark:border-slate-700" />
                                        <button @click="deleteExistingImage(img.id)"
                                            class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center shadow-md hover:bg-red-600 transition z-10"
                                            title="Remove image">
                                            <X class="w-3 h-3" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- New image previews -->
                            <div v-if="editImagePreviews.length > 0" class="flex flex-wrap gap-3 mb-4">
                                <div v-for="(src, i) in editImagePreviews" :key="i" class="relative">
                                    <img :src="src"
                                        class="w-20 h-20 object-cover rounded-xl border-2 border-blue-300 dark:border-blue-600" />
                                    <span
                                        class="absolute bottom-1 left-1 text-[9px] font-black bg-blue-600 text-white px-1 rounded">NEW</span>
                                    <button @click="removeEditPreview(i)"
                                        class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center shadow-md hover:bg-red-600 transition z-10"
                                        title="Remove">
                                        <X class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>

                            <input ref="editImageInput" type="file" multiple accept="image/*" class="hidden"
                                @change="onEditImageChange" />
                            <button @click="editImageInput.click()"
                                class="flex items-center gap-2 px-4 py-2.5 text-sm font-bold border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl text-slate-500 hover:border-blue-400 hover:text-blue-600 hover:bg-blue-50/50 dark:hover:bg-blue-900/10 transition-all w-full justify-center">
                                <Upload class="w-4 h-4" />
                                Upload additional images
                            </button>
                        </div>

                        <!-- ── Available Sizes ─────────────────────────── -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-5">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Available
                                Sizes</p>
                            <div class="flex flex-wrap gap-2">
                                <label v-for="sz in ALL_SIZES" :key="sz"
                                    class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="checkbox" :value="sz" v-model="editForm.sizes"
                                        class="w-4 h-4 rounded accent-blue-600" />
                                    <span
                                        class="text-sm font-bold text-slate-600 dark:text-slate-300 px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition">{{
                                            sz }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- ── BOM ─────────────────────────────────────── -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-5">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Bill of
                                    Materials</p>
                                <div v-if="editForm.bom.length > 0" class="text-xs font-bold text-slate-500">
                                    BOM Total: <span class="text-slate-800 dark:text-white">₱{{ fmt(editBomTotal)
                                    }}</span>
                                </div>
                            </div>
                            <div v-if="editForm.bom.length > 0" class="space-y-2 mb-4">
                                <div v-for="(line, i) in editForm.bom" :key="i"
                                    class="flex items-center gap-3 px-3 py-2.5 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                                    <span
                                        class="font-mono text-[10px] font-bold text-slate-400 bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded flex-shrink-0">{{
                                            line.sku_ref }}</span>
                                    <span
                                        class="flex-1 text-sm font-semibold text-slate-700 dark:text-slate-300 min-w-0 truncate">{{
                                            line.name }}</span>
                                    <span class="text-xs font-black text-slate-700 dark:text-slate-300 flex-shrink-0">{{
                                        line.qty }} {{ line.unit }}</span>
                                    <span class="text-xs font-bold text-slate-500 flex-shrink-0">₱{{ fmt(line.unit_cost
                                        * line.qty) }}</span>
                                    <button @click="removeEditBomLine(i)"
                                        class="p-1 rounded text-slate-400 hover:text-red-500 transition flex-shrink-0">
                                        <X class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                            <div class="flex gap-2 items-end">
                                <div class="flex-1">
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Material</label>
                                    <div class="relative mt-1">
                                        <select v-model="editBomLine.material_id"
                                            class="w-full appearance-none pl-3 pr-8 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200">
                                            <option value="">— select material —</option>
                                            <option v-for="m in masterMaterials" :key="m.id" :value="m.id">{{ m.mat_id
                                            }} — {{ m.name }}</option>
                                        </select>
                                        <ChevronDown
                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                                    </div>
                                </div>
                                <div class="w-24">
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Qty</label>
                                    <input v-model="editBomLine.qty" type="number" min="0.0001" step="0.01"
                                        class="mt-1 w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <button @click="addEditBomLine" :disabled="!editBomLine.material_id || !editBomLine.qty"
                                    class="px-4 py-2 text-sm font-bold rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition disabled:opacity-40 flex-shrink-0">
                                    + Add
                                </button>
                            </div>
                        </div>

                        <!-- ── Technical Specs ─────────────────────────── -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-5">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Technical
                                Specs <span class="text-slate-300 font-normal">(optional)</span></p>
                            <div v-if="editForm.specs.length > 0" class="space-y-2 mb-4">
                                <div v-for="(spec, i) in editForm.specs" :key="i"
                                    class="flex items-center gap-3 px-3 py-2 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                                    <span
                                        class="text-[10px] font-black text-slate-400 uppercase w-32 flex-shrink-0 truncate">{{
                                            spec.label }}</span>
                                    <span class="flex-1 text-sm text-slate-700 dark:text-slate-300 truncate">{{
                                        spec.value }}</span>
                                    <button @click="removeEditSpecLine(i)"
                                        class="p-1 rounded text-slate-400 hover:text-red-500 transition flex-shrink-0">
                                        <X class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>
                            <div class="flex gap-2 items-end">
                                <div class="flex-1">
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Label</label>
                                    <input v-model="editSpecLine.label" type="text"
                                        class="mt-1 w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <div class="flex-1">
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Value</label>
                                    <input v-model="editSpecLine.value" type="text"
                                        class="mt-1 w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                                </div>
                                <button @click="addEditSpecLine" :disabled="!editSpecLine.label || !editSpecLine.value"
                                    class="px-4 py-2 text-sm font-bold rounded-xl bg-slate-600 text-white hover:bg-slate-700 transition disabled:opacity-40 flex-shrink-0">
                                    + Add
                                </button>
                            </div>
                        </div>

                    </div>

                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex gap-3">
                        <button @click="showEditProduct = false"
                            class="flex-1 py-2.5 text-sm font-bold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                            Cancel
                        </button>
                        <button @click="submitEdit" :disabled="processing || !editForm.name || !editForm.category"
                            class="flex-1 py-2.5 text-sm font-bold rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

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