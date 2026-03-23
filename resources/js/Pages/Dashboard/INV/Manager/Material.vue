<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Search, Plus, ChevronDown, ArrowRightLeft, AlertTriangle, X,
    Edit2, Trash2, ArrowUpDown, Send, PackageCheck, Warehouse,
    TrendingUp, TrendingDown, Package, Layers, FlaskConical,
    CheckCircle, DollarSign, BarChart2, ShoppingCart, ClipboardList,
    ArrowRight, Zap,
} from 'lucide-vue-next';

// ─── Props from Inertia ───────────────────────────────────────────────────────
const props = defineProps({
    warehouses: { type: Array, default: () => [] },
    materials: { type: Array, default: () => [] },
    materialRequests: { type: Array, default: () => [] }, // Tracks request history
});

const warehouses = ref(props.warehouses);
const materials = ref(props.materials);
const materialRequests = ref(props.materialRequests || []);

watch(() => props.warehouses, v => (warehouses.value = v), { deep: true });
watch(() => props.materials, v => (materials.value = v), { deep: true });
watch(() => props.materialRequests, v => (materialRequests.value = v || []), { deep: true });

// ─── UI State ─────────────────────────────────────────────────────────────────
const searchQuery = ref('');
const categoryFilter = ref('All');
const statusFilter = ref('All');
const sortField = ref('name');
const sortDir = ref('asc');
const expandedMat = ref(null);
const processing = ref(false);

const showDelegateModal = ref(false);
const showAddModal = ref(false);
const showProcurementModal = ref(false);
const delegateSuccess = ref(false);
const procurementSuccess = ref(false);

// View mode for the procurement modal: 'history' | 'form' | 'success'
const procurementView = ref('history');

const delegateForm = ref({ materialId: null, fromWarehouse: null, toWarehouse: null, qty: '' });
const newMaterial = ref({ name: '', category: '', unit: '', quantity: '', unit_cost: '', reorder_point: '' });

// ─── Procurement Request Form (Upgraded to useForm) ───────────────────────────
const procurementTarget = ref(null);
const procurementForm = useForm({
    material_id: null,
    material_name: '',
    category: '',
    unit: '',
    current_stock: 0,
    reorder_point: 0,
    required_qty: '',
    urgency: 'Medium',
    notes: '',
});

// ─── Helpers ──────────────────────────────────────────────────────────────────
const totalQty = (mat) => Object.values(mat.stock).reduce((a, b) => a + Number(b), 0);

const matStatus = (mat) => {
    const t = totalQty(mat);
    if (t <= 0) return 'Out of Stock';
    if (t <= mat.reorder) return 'Low Stock';
    return 'In Stock';
};

const fmt = (n) => Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const colorMap = {
    blue: { btn: 'bg-blue-600 text-white shadow-blue-500/30', badge: 'bg-blue-50 text-blue-700 ring-1 ring-blue-200', bar: 'bg-blue-500', dot: 'bg-blue-500' },
    emerald: { btn: 'bg-emerald-600 text-white shadow-emerald-500/30', badge: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200', bar: 'bg-emerald-500', dot: 'bg-emerald-500' },
    amber: { btn: 'bg-amber-500 text-white shadow-amber-500/30', badge: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200', bar: 'bg-amber-500', dot: 'bg-amber-500' },
    violet: { btn: 'bg-violet-600 text-white shadow-violet-500/30', badge: 'bg-violet-50 text-violet-700 ring-1 ring-violet-200', bar: 'bg-violet-500', dot: 'bg-violet-500' },
    rose: { btn: 'bg-rose-600 text-white shadow-rose-500/30', badge: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200', bar: 'bg-rose-500', dot: 'bg-rose-500' },
    cyan: { btn: 'bg-cyan-600 text-white shadow-cyan-500/30', badge: 'bg-cyan-50 text-cyan-700 ring-1 ring-cyan-200', bar: 'bg-cyan-500', dot: 'bg-cyan-500' },
};
const getColor = (c) => colorMap[c] ?? colorMap.blue;

const statusStyle = (s) => ({
    'In Stock': 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
    'Low Stock': 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
    'Out of Stock': 'bg-red-50 text-red-600 ring-1 ring-red-200',
}[s] ?? '');

const requestStatusStyle = (s) => ({
    'pending': 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    'rfq_sent': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    'po_created': 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
    'fulfilled': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
})[s] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';

const catColor = {
    'Raw Material': 'bg-blue-50 text-blue-700',
    'Chemical': 'bg-violet-50 text-violet-700',
    'Accessory': 'bg-emerald-50 text-emerald-700',
    'Packaging': 'bg-amber-50 text-amber-700',
    'Supplies': 'bg-cyan-50 text-cyan-700',
};

// ─── Computed ─────────────────────────────────────────────────────────────────
const categories = computed(() => ['All', ...new Set(materials.value.map(m => m.category))]);

const filteredMaterials = computed(() => {
    let items = [...materials.value];
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        items = items.filter(m => m.name.toLowerCase().includes(q) || m.mat_id.toLowerCase().includes(q));
    }
    if (categoryFilter.value !== 'All') items = items.filter(m => m.category === categoryFilter.value);
    if (statusFilter.value !== 'All') items = items.filter(m => matStatus(m) === statusFilter.value);
    items.sort((a, b) => {
        let va, vb;
        if (sortField.value === 'totalQty') { va = totalQty(a); vb = totalQty(b); }
        else if (sortField.value === 'totalValue') { va = totalQty(a) * a.cost; vb = totalQty(b) * b.cost; }
        else { va = a[sortField.value]; vb = b[sortField.value]; }
        if (typeof va === 'string') { va = va.toLowerCase(); vb = vb.toLowerCase(); }
        if (va < vb) return sortDir.value === 'asc' ? -1 : 1;
        if (va > vb) return sortDir.value === 'asc' ? 1 : -1;
        return 0;
    });
    return items;
});

const globalStats = computed(() => {
    const all = materials.value;
    return {
        total: all.length,
        inStock: all.filter(m => matStatus(m) === 'In Stock').length,
        lowStock: all.filter(m => matStatus(m) === 'Low Stock').length,
        outOfStock: all.filter(m => matStatus(m) === 'Out of Stock').length,
        totalValue: all.reduce((acc, m) => acc + totalQty(m) * m.cost, 0),
    };
});

const warehouseBreakdown = (mat) =>
    warehouses.value.map(w => ({ ...w, qty: Number(mat.stock[w.id] ?? mat.stock[String(w.id)] ?? 0) }));

const delegateMaterial = computed(() =>
    materials.value.find(m => m.id === delegateForm.value.materialId) ?? null
);
const fromOptions = computed(() =>
    !delegateMaterial.value ? [] :
        warehouses.value.filter(w => (Number(delegateMaterial.value.stock[w.id] ?? delegateMaterial.value.stock[String(w.id)] ?? 0)) > 0)
);
const toOptions = computed(() =>
    warehouses.value.filter(w => w.id !== delegateForm.value.fromWarehouse)
);
const maxQty = computed(() =>
    delegateMaterial.value && delegateForm.value.fromWarehouse
        ? Number(delegateMaterial.value.stock[delegateForm.value.fromWarehouse] ?? delegateMaterial.value.stock[String(delegateForm.value.fromWarehouse)] ?? 0)
        : 0
);

const suggestedQty = computed(() => {
    if (!procurementTarget.value) return 1;
    const current = totalQty(procurementTarget.value);
    const reorder = procurementTarget.value.reorder;
    const gap = Math.max(reorder - current, 1);
    return gap;
});

const activeMaterialRequests = computed(() => {
    if (!procurementTarget.value) return [];
    return materialRequests.value.filter(r => r.material_id === procurementTarget.value.id).reverse();
});
const hasHistory = computed(() => activeMaterialRequests.value.length > 0);
const pendingReqCount = (matId) => materialRequests.value.filter(r => r.material_id === matId && r.status === 'pending').length;

const autoUrgency = (mat) => {
    const s = matStatus(mat);
    if (s === 'Out of Stock') return 'High';
    if (s === 'Low Stock') return 'Medium';
    return 'Low';
};

// ─── Methods ──────────────────────────────────────────────────────────────────
const setSort = (f) => {
    if (sortField.value === f) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    else { sortField.value = f; sortDir.value = 'asc'; }
};

const openDelegate = (mat) => {
    delegateForm.value = { materialId: mat.id, fromWarehouse: null, toWarehouse: null, qty: '' };
    delegateSuccess.value = false;
    showDelegateModal.value = true;
};

const openProcurement = (mat) => {
    procurementTarget.value = mat;

    const reqs = materialRequests.value.filter(r => r.material_id === mat.id);
    if (reqs.length > 0) {
        procurementView.value = 'history';
        showProcurementModal.value = true;
    } else {
        startNewRequest();
    }
};

const startNewRequest = () => {
    const mat = procurementTarget.value;
    procurementForm.material_id = mat.id;
    procurementForm.material_name = mat.name;
    procurementForm.category = mat.category;
    procurementForm.unit = mat.unit;
    procurementForm.current_stock = totalQty(mat);
    procurementForm.reorder_point = mat.reorder;
    procurementForm.required_qty = suggestedQty.value;
    procurementForm.urgency = autoUrgency(mat);
    procurementForm.notes = '';
    procurementForm.clearErrors();

    procurementSuccess.value = false;
    procurementView.value = 'form';
    showProcurementModal.value = true;
};

const confirmDelegate = () => {
    const { materialId, fromWarehouse, toWarehouse, qty } = delegateForm.value;
    const amount = Number(qty);
    if (!materialId || !fromWarehouse || !toWarehouse || !amount) return;
    processing.value = true;
    router.post(route('inv.manager.material.delegate'), {
        material_id: materialId,
        from_warehouse: fromWarehouse,
        to_warehouse: toWarehouse,
        quantity: amount,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            delegateSuccess.value = true;
            setTimeout(() => { showDelegateModal.value = false; delegateSuccess.value = false; }, 1200);
        },
        onFinish: () => (processing.value = false),
    });
};

const submitProcurementRequest = () => {
    if (!procurementForm.required_qty || Number(procurementForm.required_qty) <= 0) return;

    procurementForm.transform((data) => ({
        ...data,
        required_qty: Number(data.required_qty)
    })).post(route('inv.manager.material.procurement'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            procurementView.value = 'success';
            procurementSuccess.value = true;

            if (Array.isArray(materialRequests.value)) {
                materialRequests.value.push({
                    id: Date.now(),
                    material_id: procurementForm.material_id,
                    req_number: 'NEW-REQ',
                    required_qty: procurementForm.required_qty,
                    urgency: procurementForm.urgency,
                    status: 'pending',
                    created_at: new Date().toISOString()
                });
            }

            setTimeout(() => {
                showProcurementModal.value = false;
                procurementSuccess.value = false;
            }, 1800);
        },
    });
};

const addMaterial = () => {
    if (!newMaterial.value.name) return;
    processing.value = true;
    router.post(route('inv.manager.material.store'), {
        name: newMaterial.value.name,
        category: newMaterial.value.category || 'Raw Material',
        unit: newMaterial.value.unit || 'pcs',
        quantity: newMaterial.value.quantity || 0,
        unit_cost: newMaterial.value.unit_cost || 0,
        reorder_point: newMaterial.value.reorder_point || 0,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newMaterial.value = { name: '', category: '', unit: '', quantity: '', unit_cost: '', reorder_point: '' };
            showAddModal.value = false;
        },
        onFinish: () => (processing.value = false),
    });
};

const deleteMaterial = (id) => {
    if (!confirm('Delete this material from the master list?')) return;
    router.delete(route('inv.manager.material.destroy', { id }), {
        preserveScroll: true,
        onSuccess: () => {
            if (expandedMat.value === id) expandedMat.value = null;
        },
    });
};
</script>

<template>

    <Head title="Master Materials | Monti Textile" />
    <AuthenticatedLayout>

        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Master
                    Materials</h1>
                <p class="text-slate-500 text-xs sm:text-sm mt-1">
                    Overview of all raw materials — delegate stock across warehouse locations.
                </p>
            </div>
            <button @click="showAddModal = true"
                class="w-full md:w-auto inline-flex justify-center items-center gap-2 px-5 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm font-bold rounded-xl hover:opacity-80 transition shadow-sm">
                <Plus class="w-4 h-4" />
                Add Material
            </button>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 mb-6">
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest truncate">Total Mat.</p>
                    <div class="p-1.5 sm:p-2 bg-slate-100 dark:bg-slate-800 rounded-lg">
                        <Layers class="w-3.5 h-3.5 text-slate-500" />
                    </div>
                </div>
                <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">{{ globalStats.total }}</p>
                <p class="text-[10px] sm:text-xs text-slate-400 mt-0.5">unique SKUs</p>
            </div>
            <div
                class="col-span-2 md:col-span-1 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest truncate">Total Value</p>
                    <div class="p-1.5 sm:p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <TrendingUp class="w-3.5 h-3.5 text-blue-500" />
                    </div>
                </div>
                <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">
                    ₱{{ globalStats.totalValue >= 1000000
                        ? (globalStats.totalValue / 1000000).toFixed(2) + 'M'
                        : (globalStats.totalValue / 1000).toFixed(1) + 'K' }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-400 mt-0.5">across warehouses</p>
            </div>
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest truncate">In Stock</p>
                    <div class="p-1.5 sm:p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                        <CheckCircle class="w-3.5 h-3.5 text-emerald-500" />
                    </div>
                </div>
                <p class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400">{{ globalStats.inStock
                    }}</p>
            </div>
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest truncate">Low</p>
                    <div class="p-1.5 sm:p-2 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                        <AlertTriangle class="w-3.5 h-3.5 text-amber-500" />
                    </div>
                </div>
                <p class="text-2xl sm:text-3xl font-black text-amber-600 dark:text-amber-400">{{ globalStats.lowStock }}
                </p>
            </div>
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest truncate">Out</p>
                    <div class="p-1.5 sm:p-2 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <TrendingDown class="w-3.5 h-3.5 text-red-500" />
                    </div>
                </div>
                <p class="text-2xl sm:text-3xl font-black text-red-600 dark:text-red-400">{{ globalStats.outOfStock }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6 sm:mb-8">
            <div v-for="wh in warehouses" :key="wh.id"
                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-3 sm:p-4 flex items-center gap-3 sm:gap-4">
                <div :class="['p-2 sm:p-2.5 rounded-xl shadow-md flex-shrink-0', getColor(wh.color).btn]">
                    <Warehouse class="w-4 h-4" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-200 truncate">{{ wh.name }}
                    </p>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 mt-0.5 truncate">{{ wh.location }}</p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-base sm:text-lg font-black text-slate-900 dark:text-white leading-none">
                        {{materials.filter(m => Number(m.stock[wh.id] ?? m.stock[String(wh.id)] ?? 0) > 0).length}}
                    </p>
                    <p class="text-[9px] sm:text-[10px] text-slate-400">SKUs</p>
                </div>
            </div>
        </div>

        <div v-if="globalStats.lowStock + globalStats.outOfStock > 0"
            class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 px-4 sm:px-5 py-3.5 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl">
            <div class="flex items-start sm:items-center gap-3">
                <AlertTriangle class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5 sm:mt-0" />
                <p class="text-xs sm:text-sm font-medium text-amber-800 dark:text-amber-300 leading-snug">
                    {{ globalStats.lowStock + globalStats.outOfStock }} material(s) need restocking —
                    <span class="font-black">{{ globalStats.outOfStock }} out of stock</span>,
                    {{ globalStats.lowStock }} low.
                </p>
            </div>
            <button @click="statusFilter = 'Low Stock'"
                class="text-xs font-black text-amber-700 dark:text-amber-300 whitespace-nowrap hover:underline self-end sm:self-auto">
                Filter Low →
            </button>
        </div>

        <div
            class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">

            <div
                class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 px-4 sm:px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                <div class="relative w-full sm:flex-1">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input v-model="searchQuery" type="text" placeholder="Search materials by name or ID…"
                        class="w-full pl-9 pr-4 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 transition" />
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <div class="relative flex-1 sm:w-40">
                        <select v-model="categoryFilter"
                            class="w-full appearance-none pl-3 pr-8 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 font-bold transition">
                            <option v-for="c in categories" :key="c">{{ c }}</option>
                        </select>
                        <ChevronDown
                            class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                    </div>
                    <div class="relative flex-1 sm:w-40">
                        <select v-model="statusFilter"
                            class="w-full appearance-none pl-3 pr-8 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 font-bold transition">
                            <option>All</option>
                            <option>In Stock</option>
                            <option>Low Stock</option>
                            <option>Out of Stock</option>
                        </select>
                        <ChevronDown
                            class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                    </div>
                </div>
            </div>

            <div class="md:hidden flex flex-col divide-y divide-slate-100 dark:divide-slate-800">
                <div v-if="filteredMaterials.length === 0" class="p-10 text-center text-slate-400">
                    <Package class="w-10 h-10 mx-auto mb-3 opacity-30" />
                    <p class="text-sm font-medium">No materials found.</p>
                </div>

                <div v-for="mat in filteredMaterials" :key="'mob-' + mat.id"
                    class="p-4 flex flex-col gap-3 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                    <div class="flex justify-between items-start gap-2">
                        <div class="flex flex-col gap-1.5 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span
                                    class="font-mono text-[10px] font-bold text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-md">{{
                                    mat.mat_id }}</span>
                                <span
                                    :class="['text-[9px] font-bold px-2 py-0.5 rounded-full whitespace-nowrap', catColor[mat.category] ?? 'bg-slate-100 text-slate-500']">{{
                                    mat.category }}</span>
                            </div>
                            <p class="font-bold text-slate-800 dark:text-slate-200 text-sm leading-tight truncate">{{
                                mat.name }}</p>
                        </div>
                        <span
                            :class="['text-[9px] font-black uppercase tracking-widest px-2 py-1 rounded-md whitespace-nowrap text-center', statusStyle(matStatus(mat))]">{{
                            matStatus(mat) }}</span>
                    </div>

                    <div
                        class="grid grid-cols-3 gap-2 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                        <div>
                            <p class="text-[9px] text-slate-400 uppercase font-black tracking-widest mb-0.5">Stock</p>
                            <p
                                :class="['font-black text-sm', totalQty(mat) === 0 ? 'text-red-500' : totalQty(mat) <= mat.reorder ? 'text-amber-600' : 'text-slate-900 dark:text-white']">
                                {{ Number(totalQty(mat)).toLocaleString() }} <span class="text-[9px] text-slate-500">{{
                                    mat.unit }}</span>
                            </p>
                        </div>
                        <div>
                            <p class="text-[9px] text-slate-400 uppercase font-black tracking-widest mb-0.5">Cost</p>
                            <p class="font-bold text-sm text-slate-700 dark:text-slate-300">₱{{ fmt(mat.cost) }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] text-slate-400 uppercase font-black tracking-widest mb-0.5">Value</p>
                            <p class="font-bold text-sm text-slate-700 dark:text-slate-300">₱{{ fmt(totalQty(mat) *
                                mat.cost) }}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-1">
                        <button @click="expandedMat = expandedMat === mat.id ? null : mat.id"
                            class="text-xs font-bold text-slate-500 dark:text-slate-400 flex items-center gap-1.5 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <Warehouse class="w-3.5 h-3.5" /> Warehouses
                            <ChevronDown :class="{ 'rotate-180': expandedMat === mat.id }"
                                class="w-3 h-3 transition-transform" />
                        </button>

                        <div class="flex items-center gap-2">
                            <button @click="openDelegate(mat)"
                                class="inline-flex items-center gap-1.5 px-3 py-2 text-[10px] font-black uppercase tracking-wider rounded-lg bg-blue-600 text-white shadow-sm shadow-blue-500/20 active:scale-95 transition-all">
                                <ArrowRightLeft class="w-3.5 h-3.5" /> Delegate
                            </button>
                            <button @click="openProcurement(mat)" :class="[
                                'relative inline-flex flex-shrink-0 items-center justify-center p-2 rounded-lg transition-all shadow-sm active:scale-95',
                                matStatus(mat) === 'Out of Stock' ? 'bg-red-600 text-white shadow-red-500/20' : matStatus(mat) === 'Low Stock' ? 'bg-amber-500 text-white shadow-amber-500/20' : 'bg-slate-700 dark:bg-slate-600 text-white shadow-slate-500/20'
                            ]">
                                <ShoppingCart class="w-4 h-4" />
                                <span v-if="pendingReqCount(mat.id) > 0"
                                    class="absolute -top-1.5 -right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-blue-500 text-[9px] text-white ring-2 ring-white dark:ring-slate-900 font-bold shadow-sm">
                                    {{ pendingReqCount(mat.id) }}
                                </span>
                            </button>
                            <button @click="deleteMaterial(mat.id)"
                                class="p-2 rounded-lg text-slate-400 border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 active:scale-95 transition-all">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="expandedMat === mat.id"
                        class="mt-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <div class="grid grid-cols-2 gap-2.5">
                            <div v-for="wh in warehouseBreakdown(mat)" :key="wh.id"
                                :class="['rounded-xl border p-2.5 transition-all flex flex-col', wh.qty > 0 ? 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700' : 'bg-slate-50 dark:bg-slate-800/30 border-slate-100 dark:border-slate-800 opacity-60']">
                                <div class="flex items-center gap-1.5 mb-1.5">
                                    <span :class="['w-2 h-2 rounded-full flex-shrink-0', getColor(wh.color).dot]" />
                                    <p class="text-[10px] font-bold text-slate-700 dark:text-slate-300 truncate">{{
                                        wh.name }}</p>
                                </div>
                                <p
                                    :class="['text-base font-black leading-none mt-auto', wh.qty > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400']">
                                    {{ Number(wh.qty).toLocaleString() }} <span
                                        class="text-[9px] text-slate-400 font-normal ml-0.5">{{ mat.unit }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="px-5 py-3.5 w-8"></th>
                            <th v-for="col in [
                                { label: 'MAT ID', field: 'mat_id' },
                                { label: 'Material Name', field: 'name' },
                                { label: 'Category', field: 'category' },
                                { label: 'Total Qty', field: 'totalQty' },
                                { label: 'Unit Cost (₱)', field: 'cost' },
                                { label: 'Total Value', field: 'totalValue' },
                                { label: 'Warehouses', field: null },
                                { label: 'Status', field: null },
                                { label: '', field: null },
                            ]" :key="col.label"
                                class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">
                                <button v-if="col.field" @click="setSort(col.field)"
                                    class="flex items-center gap-1 hover:text-slate-800 dark:hover:text-white transition">
                                    {{ col.label }}
                                    <ArrowUpDown class="w-3 h-3" />
                                </button>
                                <span v-else>{{ col.label }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-if="filteredMaterials.length === 0">
                            <td colspan="10" class="px-5 py-20 text-center text-slate-400 text-sm font-medium">
                                <Package class="w-10 h-10 mx-auto mb-3 opacity-30" />
                                No materials found.
                            </td>
                        </tr>

                        <template v-for="mat in filteredMaterials" :key="mat.id">
                            <tr
                                :class="['hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors group', expandedMat === mat.id ? 'bg-slate-50/60 dark:bg-slate-800/40' : '']">
                                <td class="pl-5 py-4">
                                    <button @click="expandedMat = expandedMat === mat.id ? null : mat.id"
                                        class="p-1 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition text-slate-400">
                                        <ChevronDown
                                            :class="['w-3.5 h-3.5 transition-transform', expandedMat === mat.id ? 'rotate-180' : '']" />
                                    </button>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="font-mono text-xs font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-md">
                                        {{ mat.mat_id }}
                                    </span>
                                </td>
                                <td
                                    class="px-5 py-4 font-semibold text-slate-800 dark:text-slate-200 max-w-[220px] truncate">
                                    {{ mat.name }}
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        :class="['text-[10px] font-bold px-2.5 py-1 rounded-full whitespace-nowrap', catColor[mat.category] ?? 'bg-slate-100 text-slate-500']">
                                        {{ mat.category }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span
                                        :class="['font-black text-base', totalQty(mat) === 0 ? 'text-red-500' : totalQty(mat) <= mat.reorder ? 'text-amber-600' : 'text-slate-900 dark:text-white']">
                                        {{ Number(totalQty(mat)).toLocaleString() }}
                                    </span>
                                    <span class="text-slate-400 text-xs ml-1">{{ mat.unit }}</span>
                                </td>
                                <td class="px-5 py-4 font-semibold text-slate-700 dark:text-slate-300">₱{{ fmt(mat.cost)
                                    }}</td>
                                <td class="px-5 py-4 font-semibold text-slate-700 dark:text-slate-300">₱{{
                                    fmt(totalQty(mat) * mat.cost) }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-1.5 flex-wrap w-24">
                                        <span v-for="wh in warehouses" :key="wh.id"
                                            :title="wh.name + ': ' + Number(mat.stock[wh.id] ?? mat.stock[String(wh.id)] ?? 0) + ' ' + mat.unit"
                                            :class="['w-5 h-5 rounded-full flex items-center justify-center text-[8px] font-black transition-all',
                                                Number(mat.stock[wh.id] ?? mat.stock[String(wh.id)] ?? 0) > 0
                                                    ? getColor(wh.color).btn + ' shadow-sm'
                                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-300 dark:text-slate-600'
                                            ]">
                                            {{ wh.name.charAt(0) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        :class="['text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full whitespace-nowrap', statusStyle(matStatus(mat))]">
                                        {{ matStatus(mat) }}
                                    </span>
                                </td>

                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <button @click="openDelegate(mat)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-[10px] font-black rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm whitespace-nowrap">
                                            <ArrowRightLeft class="w-3 h-3" /> Delegate
                                        </button>

                                        <button @click="openProcurement(mat)" :class="[
                                            'relative inline-flex items-center gap-1 px-2.5 py-1.5 text-[10px] font-black rounded-lg transition shadow-sm whitespace-nowrap',
                                            matStatus(mat) === 'Out of Stock' ? 'bg-red-600 hover:bg-red-700 text-white ring-2 ring-red-400/40' : matStatus(mat) === 'Low Stock' ? 'bg-amber-500 hover:bg-amber-600 text-white ring-2 ring-amber-400/40' : 'bg-slate-700 hover:bg-slate-800 dark:bg-slate-600 dark:hover:bg-slate-500 text-white'
                                        ]">
                                            <ShoppingCart class="w-3 h-3" />
                                            Procurement
                                            <span v-if="pendingReqCount(mat.id) > 0"
                                                class="absolute -top-1.5 -right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-blue-500 text-[8px] text-white ring-2 ring-white dark:ring-slate-900 shadow-sm">
                                                {{ pendingReqCount(mat.id) }}
                                            </span>
                                        </button>

                                        <button @click="deleteMaterial(mat.id)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="expandedMat === mat.id">
                                <td colspan="10" class="px-5 pb-5 pt-1 bg-slate-50/50 dark:bg-slate-800/20">
                                    <div class="ml-8">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">
                                            Warehouse Distribution</p>
                                        <div class="grid grid-cols-4 gap-3">
                                            <div v-for="wh in warehouseBreakdown(mat)" :key="wh.id"
                                                :class="['rounded-xl border p-3.5 transition-all', wh.qty > 0 ? 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700' : 'bg-slate-50 dark:bg-slate-800/50 border-slate-100 dark:border-slate-800 opacity-60']">
                                                <div class="flex items-center gap-2 mb-2.5">
                                                    <span
                                                        :class="['w-2 h-2 rounded-full flex-shrink-0', getColor(wh.color).dot]" />
                                                    <p
                                                        class="text-xs font-bold text-slate-700 dark:text-slate-300 truncate">
                                                        {{ wh.name }}</p>
                                                </div>
                                                <p
                                                    :class="['text-2xl font-black', wh.qty > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-300 dark:text-slate-600']">
                                                    {{ Number(wh.qty).toLocaleString() }}</p>
                                                <p class="text-[10px] text-slate-400 mt-0.5">{{ mat.unit }}</p>
                                                <div
                                                    class="mt-3 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                                    <div :class="['h-full rounded-full transition-all', getColor(wh.color).bar]"
                                                        :style="{ width: totalQty(mat) > 0 ? (wh.qty / totalQty(mat) * 100) + '%' : '0%' }" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div
                class="px-4 sm:px-5 py-3 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-left">
                <p class="text-xs text-slate-400 font-medium">
                    Showing <span class="font-bold text-slate-600 dark:text-slate-300">{{ filteredMaterials.length
                        }}</span>
                    of <span class="font-bold text-slate-600 dark:text-slate-300">{{ materials.length }}</span>
                    materials
                </p>
                <div class="flex flex-wrap items-center justify-center gap-2">
                    <span v-if="globalStats.lowStock > 0"
                        class="flex items-center gap-1.5 text-[10px] font-black text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full ring-1 ring-amber-200">
                        <AlertTriangle class="w-3 h-3" /> {{ globalStats.lowStock }} low
                    </span>
                    <span v-if="globalStats.outOfStock > 0"
                        class="flex items-center gap-1.5 text-[10px] font-black text-red-600 bg-red-50 px-2.5 py-1 rounded-full ring-1 ring-red-200">
                        {{ globalStats.outOfStock }} out
                    </span>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showProcurementModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                @click.self="showProcurementModal = false">
                <div
                    class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">

                    <div v-if="procurementView === 'success'"
                        class="flex flex-col items-center py-10 sm:py-14 gap-4 px-6 overflow-y-auto">
                        <div
                            class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <CheckCircle class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                        </div>
                        <p class="text-lg font-black text-slate-900 dark:text-white">Request Sent to SCM!</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400 text-center">
                            The procurement request for <strong>{{ procurementForm.material_name }}</strong> has been
                            forwarded.
                        </p>
                        <div
                            class="flex items-center gap-2 mt-1 px-4 py-2.5 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 text-[10px] sm:text-xs font-bold text-blue-700 dark:text-blue-300">
                            <ArrowRight class="w-4 h-4 flex-shrink-0" />
                            SCM Manager will review and send RFQ to suppliers.
                        </div>
                    </div>

                    <template v-else-if="procurementView === 'history'">
                        <div
                            class="px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 flex-shrink-0">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="p-2 sm:p-2.5 rounded-xl flex-shrink-0 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400">
                                        <ClipboardList class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <h3
                                            class="text-sm sm:text-base font-black text-slate-900 dark:text-white leading-tight">
                                            Procurement Requests</h3>
                                        <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1">
                                            Existing requests for <strong>{{ procurementTarget?.name }}</strong>.
                                        </p>
                                    </div>
                                </div>
                                <button @click="showProcurementModal = false"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-white/60 dark:hover:bg-slate-700 transition flex-shrink-0">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="p-4 sm:p-6 overflow-y-auto space-y-3 bg-slate-50/50 dark:bg-slate-900/50 flex-1">
                            <div v-for="req in activeMaterialRequests" :key="req.id"
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800 shadow-sm">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span
                                            class="font-mono text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300">{{
                                            req.req_number || 'PENDING-REQ' }}</span>
                                        <span
                                            :class="['text-[9px] font-black px-2 py-0.5 rounded-md uppercase tracking-wider', requestStatusStyle(req.status)]">
                                            {{ (req.status || 'pending').replace('_', ' ') }}
                                        </span>
                                    </div>
                                    <p class="text-sm font-medium text-slate-800 dark:text-slate-200">
                                        <strong>{{ Number(req.required_qty).toLocaleString() }}</strong> {{
                                        procurementTarget?.unit }} requested
                                    </p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">Requested on: {{ req.created_at ? new
                                        Date(req.created_at).toLocaleDateString() : 'Just now' }}</p>
                                </div>
                                <div class="text-left sm:text-right">
                                    <span
                                        :class="['text-[9px] font-black uppercase tracking-widest px-2 py-1 rounded-lg border-2', req.urgency === 'High' ? 'text-red-500 border-red-200 dark:border-red-900' : req.urgency === 'Medium' ? 'text-amber-500 border-amber-200 dark:border-amber-900' : 'text-blue-500 border-blue-200 dark:border-blue-900']">
                                        {{ req.urgency }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            class="px-5 sm:px-6 py-4 border-t border-slate-100 dark:border-slate-700 flex flex-col-reverse sm:flex-row gap-3 bg-slate-50 dark:bg-slate-800 flex-shrink-0">
                            <button @click="showProcurementModal = false"
                                class="w-full sm:flex-1 py-2.5 text-sm font-bold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 transition">
                                Close
                            </button>
                            <button @click="startNewRequest()"
                                class="w-full sm:flex-1 inline-flex items-center justify-center gap-2 py-2.5 text-sm font-black rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition shadow-lg shadow-blue-500/20">
                                <Plus class="w-4 h-4" />
                                Make Another Request
                            </button>
                        </div>
                    </template>

                    <template v-else-if="procurementView === 'form'">
                        <div :class="[
                            'px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-100 dark:border-slate-700 transition-colors flex-shrink-0',
                            procurementForm.urgency === 'High' ? 'bg-red-50 dark:bg-red-900/20' : procurementForm.urgency === 'Medium' ? 'bg-amber-50 dark:bg-amber-900/20' : 'bg-slate-50 dark:bg-slate-800'
                        ]">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <button v-if="hasHistory" @click="procurementView = 'history'"
                                        class="mt-1 p-1 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition flex-shrink-0">
                                        <ArrowRight class="w-4 h-4 rotate-180 text-slate-500" />
                                    </button>
                                    <div :class="[
                                        'p-2 sm:p-2.5 rounded-xl flex-shrink-0',
                                        procurementForm.urgency === 'High' ? 'bg-red-100 dark:bg-red-900/40' : procurementForm.urgency === 'Medium' ? 'bg-amber-100 dark:bg-amber-900/40' : 'bg-slate-100 dark:bg-slate-700'
                                    ]">
                                        <ShoppingCart :class="[
                                            'w-4 h-4 sm:w-5 sm:h-5',
                                            procurementForm.urgency === 'High' ? 'text-red-600 dark:text-red-400' : procurementForm.urgency === 'Medium' ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500'
                                        ]" />
                                    </div>
                                    <div>
                                        <h3
                                            class="text-sm sm:text-base font-black text-slate-900 dark:text-white leading-tight">
                                            New Procurement Request</h3>
                                        <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1">
                                            This request will appear in the SCM module for sourcing.
                                        </p>
                                    </div>
                                </div>
                                <button @click="showProcurementModal = false"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-white/60 dark:hover:bg-slate-700 transition flex-shrink-0">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="p-4 sm:p-6 overflow-y-auto space-y-4 sm:space-y-5 flex-1">
                            <div
                                class="px-3 sm:px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                                            <span
                                                class="font-mono text-[9px] sm:text-[10px] text-slate-400 bg-white dark:bg-slate-700 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-600">{{
                                                procurementTarget?.mat_id }}</span>
                                            <span
                                                :class="['text-[9px] sm:text-[10px] font-bold px-2 py-0.5 rounded-full', catColor[procurementForm.category] ?? 'bg-slate-100 text-slate-500']">{{
                                                procurementForm.category }}</span>
                                        </div>
                                        <p class="font-black text-slate-800 dark:text-slate-200 text-sm truncate">{{
                                            procurementForm.material_name }}</p>
                                    </div>
                                    <span
                                        :class="['text-[9px] sm:text-[10px] font-black px-2.5 py-1 rounded-full flex-shrink-0', statusStyle(matStatus(procurementTarget))]">
                                        {{ matStatus(procurementTarget) }}
                                    </span>
                                </div>

                                <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                                    <div
                                        class="bg-white dark:bg-slate-700 rounded-lg px-2 py-2 border border-slate-200 dark:border-slate-600">
                                        <p
                                            class="text-[9px] sm:text-[10px] text-slate-400 font-semibold uppercase tracking-wide">
                                            Stock</p>
                                        <p
                                            :class="['text-sm sm:text-base font-black mt-0.5', procurementForm.current_stock <= 0 ? 'text-red-600' : 'text-slate-800 dark:text-white']">
                                            {{ Number(procurementForm.current_stock).toLocaleString() }}</p>
                                        <p class="text-[9px] sm:text-[10px] text-slate-400">{{ procurementForm.unit }}
                                        </p>
                                    </div>
                                    <div
                                        class="bg-white dark:bg-slate-700 rounded-lg px-2 py-2 border border-slate-200 dark:border-slate-600">
                                        <p
                                            class="text-[9px] sm:text-[10px] text-slate-400 font-semibold uppercase tracking-wide">
                                            Reorder</p>
                                        <p
                                            class="text-sm sm:text-base font-black text-slate-800 dark:text-white mt-0.5">
                                            {{ Number(procurementForm.reorder_point).toLocaleString() }}</p>
                                        <p class="text-[9px] sm:text-[10px] text-slate-400">{{ procurementForm.unit }}
                                        </p>
                                    </div>
                                    <div
                                        class="bg-amber-50 dark:bg-amber-900/20 rounded-lg px-2 py-2 border border-amber-200 dark:border-amber-700">
                                        <p
                                            class="text-[9px] sm:text-[10px] text-amber-600 font-semibold uppercase tracking-wide">
                                            Gap</p>
                                        <p
                                            class="text-sm sm:text-base font-black text-amber-700 dark:text-amber-300 mt-0.5">
                                            {{ procurementForm.current_stock < procurementForm.reorder_point ?
                                                (procurementForm.reorder_point -
                                                procurementForm.current_stock).toLocaleString() : '—' }}</p>
                                                <p class="text-[9px] sm:text-[10px] text-amber-500">below</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Required
                                        Quantity <span class="text-red-500">*</span></label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <input v-model="procurementForm.required_qty" type="number" min="1"
                                            :placeholder="'e.g. ' + suggestedQty"
                                            class="flex-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 font-bold" />
                                        <span
                                            class="text-xs font-bold text-slate-500 dark:text-slate-400 flex-shrink-0 w-8">{{
                                            procurementForm.unit }}</span>
                                    </div>
                                    <div v-if="suggestedQty > 0" class="mt-1.5 flex justify-between items-center">
                                        <p class="text-[10px] text-slate-400">Suggested: <span
                                                class="font-bold text-slate-600 dark:text-slate-300">{{
                                                Number(suggestedQty).toLocaleString() }}</span></p>
                                        <button @click="procurementForm.required_qty = suggestedQty"
                                            class="text-[10px] font-black text-blue-600 hover:underline px-1 py-0.5">Use
                                            Suggested</button>
                                    </div>
                                </div>

                                <div>
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Urgency
                                        Level</label>
                                    <div class="mt-1 flex flex-col sm:flex-row gap-2">
                                        <button v-for="level in ['High', 'Medium', 'Low']" :key="level"
                                            @click="procurementForm.urgency = level" :class="[
                                                'flex-1 py-2 sm:py-2.5 text-[10px] sm:text-xs font-black rounded-xl border-2 transition-all',
                                                procurementForm.urgency === level
                                                    ? level === 'High' ? 'border-red-500 bg-red-600 text-white shadow-md shadow-red-500/20' : level === 'Medium' ? 'border-amber-500 bg-amber-500 text-white shadow-md shadow-amber-500/20' : 'border-blue-500 bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                                    : 'border-slate-200 dark:border-slate-600 text-slate-500 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-500'
                                            ]">
                                            {{ level === 'High' ? '🔴' : level === 'Medium' ? '🟡' : '🔵' }} {{ level }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Notes
                                    <span class="normal-case font-medium text-slate-400">(optional)</span></label>
                                <textarea v-model="procurementForm.notes" rows="2"
                                    placeholder="e.g. Specific grade required, preferred supplier..."
                                    class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 resize-none"></textarea>
                            </div>
                        </div>

                        <div
                            class="px-5 sm:px-6 py-4 border-t border-slate-100 dark:border-slate-700 flex flex-col-reverse sm:flex-row gap-3 bg-slate-50 dark:bg-slate-800/50 flex-shrink-0">
                            <button @click="showProcurementModal = false"
                                class="w-full sm:flex-1 py-2.5 text-sm font-bold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 transition">
                                Cancel
                            </button>
                            <button @click="submitProcurementRequest"
                                :disabled="procurementForm.processing || !procurementForm.required_qty || Number(procurementForm.required_qty) <= 0"
                                class="w-full sm:flex-1 inline-flex items-center justify-center gap-2 py-2.5 text-sm font-black rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition shadow-lg shadow-blue-500/20 disabled:opacity-40 disabled:cursor-not-allowed">
                                <Send class="w-4 h-4" />
                                {{ procurementForm.processing ? 'Sending...' : 'Send Request' }}
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </Teleport>


        <Teleport to="body">
            <div v-if="showDelegateModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                @click.self="showDelegateModal = false">
                <div
                    class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-lg p-5 sm:p-6 overflow-y-auto max-h-[90vh]">

                    <div v-if="delegateSuccess" class="flex flex-col items-center py-10 gap-4">
                        <div
                            class="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                            <PackageCheck class="w-8 h-8 text-emerald-500" />
                        </div>
                        <p class="text-lg font-black text-slate-900 dark:text-white">Stock Delegated!</p>
                        <p class="text-sm text-slate-400">Warehouse inventory has been updated.</p>
                    </div>

                    <template v-else>
                        <div class="flex items-center justify-between mb-5 sm:mb-6">
                            <div>
                                <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">Delegate
                                    Material</h3>
                                <p class="text-[10px] sm:text-xs text-slate-400 mt-0.5">Transfer stock between warehouse
                                    locations.</p>
                            </div>
                            <button @click="showDelegateModal = false"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <div v-if="delegateMaterial"
                            class="mb-5 px-3 sm:px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center gap-3">
                            <div
                                class="p-2 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 hidden sm:block">
                                <Package class="w-4 h-4 text-slate-500" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate">{{
                                    delegateMaterial.name }}</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="font-mono text-[10px] text-slate-400">{{ delegateMaterial.mat_id
                                        }}</span>
                                    <span class="text-[10px] text-slate-400">·</span>
                                    <span class="text-[10px] font-bold text-slate-500">{{
                                        Number(totalQty(delegateMaterial)).toLocaleString() }} {{ delegateMaterial.unit
                                        }} total</span>
                                </div>
                            </div>
                            <span
                                :class="['text-[9px] sm:text-[10px] font-black px-2 py-1 rounded-full', statusStyle(matStatus(delegateMaterial))]">
                                {{ matStatus(delegateMaterial) }}
                            </span>
                        </div>

                        <div class="space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                                <div class="flex-1">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">From
                                        Warehouse *</label>
                                    <div class="relative mt-1">
                                        <select v-model="delegateForm.fromWarehouse"
                                            @change="delegateForm.toWarehouse = null; delegateForm.qty = ''"
                                            class="w-full appearance-none pl-3 pr-8 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 font-medium">
                                            <option :value="null">Source…</option>
                                            <option v-for="wh in fromOptions" :key="wh.id" :value="wh.id">{{ wh.name }}
                                                ({{ Number(delegateMaterial?.stock[wh.id] ??
                                                    delegateMaterial?.stock[String(wh.id)] ?? 0).toLocaleString() }})
                                            </option>
                                        </select>
                                        <ChevronDown
                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                                    </div>
                                </div>
                                <div
                                    class="hidden sm:flex p-2 bg-blue-50 dark:bg-blue-900/20 rounded-full border border-blue-200 dark:border-blue-800 self-end mb-0.5">
                                    <ArrowRightLeft class="w-3.5 h-3.5 text-blue-500" />
                                </div>
                                <div class="flex-1">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">To
                                        Warehouse *</label>
                                    <div class="relative mt-1">
                                        <select v-model="delegateForm.toWarehouse"
                                            :disabled="!delegateForm.fromWarehouse"
                                            class="w-full appearance-none pl-3 pr-8 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                                            <option :value="null">Destination…</option>
                                            <option v-for="wh in toOptions" :key="wh.id" :value="wh.id">{{ wh.name }}
                                            </option>
                                        </select>
                                        <ChevronDown
                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Quantity
                                    to Transfer *</label>
                                <div class="mt-1 flex flex-col sm:flex-row sm:items-center gap-2">
                                    <input v-model="delegateForm.qty" type="number" min="1" :max="maxQty"
                                        :disabled="!delegateForm.fromWarehouse"
                                        :placeholder="maxQty > 0 ? 'Max ' + maxQty.toLocaleString() : '0'"
                                        class="flex-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 disabled:opacity-50" />
                                    <div class="flex items-center gap-2 justify-between">
                                        <p v-if="delegateForm.fromWarehouse && maxQty > 0"
                                            class="text-[10px] text-slate-400 sm:hidden ml-1">Available: <span
                                                class="font-bold text-slate-600 dark:text-slate-300">{{
                                                maxQty.toLocaleString() }}</span></p>
                                        <button v-if="maxQty > 0" @click="delegateForm.qty = maxQty"
                                            class="w-auto px-4 sm:px-3 py-2.5 text-xs font-black text-blue-600 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl hover:bg-blue-100 transition whitespace-nowrap">
                                            Transfer All
                                        </button>
                                    </div>
                                </div>
                                <p v-if="delegateForm.fromWarehouse && maxQty > 0"
                                    class="hidden sm:block text-[10px] text-slate-400 mt-1">Available: <span
                                        class="font-bold text-slate-600 dark:text-slate-300">{{ maxQty.toLocaleString()
                                        }} {{ delegateMaterial?.unit }}</span></p>
                                <p v-if="Number(delegateForm.qty) > maxQty && maxQty > 0"
                                    class="text-[10px] text-red-500 font-bold mt-1">Exceeds available stock.</p>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-col-reverse sm:flex-row gap-3">
                            <button @click="showDelegateModal = false"
                                class="w-full sm:flex-1 py-2.5 text-sm font-bold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                Cancel
                            </button>
                            <button @click="confirmDelegate"
                                :disabled="processing || !delegateForm.fromWarehouse || !delegateForm.toWarehouse || !delegateForm.qty || Number(delegateForm.qty) > maxQty || Number(delegateForm.qty) <= 0"
                                class="w-full sm:flex-1 inline-flex items-center justify-center gap-2 py-2.5 text-sm font-bold rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition shadow-lg shadow-blue-500/20 disabled:opacity-40 disabled:cursor-not-allowed">
                                <Send class="w-4 h-4" /> Confirm
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </Teleport>


        <Teleport to="body">
            <div v-if="showAddModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                @click.self="showAddModal = false">
                <div
                    class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-lg p-5 sm:p-6 overflow-y-auto max-h-[90vh]">
                    <div class="flex items-center justify-between mb-5 sm:mb-6">
                        <div>
                            <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">Add Material</h3>
                            <p class="text-[10px] sm:text-xs text-slate-400 mt-0.5">Mat ID is auto-generated.</p>
                        </div>
                        <button @click="showAddModal = false"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Material Name
                                *</label>
                            <input v-model="newMaterial.name" type="text" placeholder="Full material name"
                                class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Category
                                *</label>
                            <div class="relative mt-1">
                                <select v-model="newMaterial.category"
                                    class="w-full appearance-none pl-3 pr-8 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 font-medium">
                                    <option value="">Select…</option>
                                    <option>Raw Material</option>
                                    <option>Chemical</option>
                                    <option>Accessory</option>
                                    <option>Packaging</option>
                                    <option>Supplies</option>
                                </select>
                                <ChevronDown
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Unit
                                *</label>
                            <input v-model="newMaterial.unit" type="text" placeholder="rolls, kg, pcs…"
                                class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Unit Cost (₱)
                                *</label>
                            <input v-model="newMaterial.unit_cost" type="number" min="0" step="0.01" placeholder="0.00"
                                class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Initial
                                Qty</label>
                            <input v-model="newMaterial.quantity" type="number" min="0" placeholder="0"
                                class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Reorder
                                Point</label>
                            <input v-model="newMaterial.reorder_point" type="number" min="0" placeholder="0"
                                class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                        </div>
                    </div>
                    <div class="mt-6 flex flex-col-reverse sm:flex-row gap-3">
                        <button @click="showAddModal = false"
                            class="w-full sm:flex-1 py-2.5 text-sm font-bold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                            Cancel
                        </button>
                        <button @click="addMaterial" :disabled="processing || !newMaterial.name"
                            class="w-full sm:flex-1 py-2.5 text-sm font-bold rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 hover:opacity-80 transition shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                            Add to Master List
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>