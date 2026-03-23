<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    Plus,
    Search,
    Pencil,
    Tag,
    Users,
    Percent,
    X,
    ShieldCheck,
    ClipboardCheck,
    Zap,
    AlertCircle,
    AlertTriangle
} from 'lucide-vue-next';

const props = defineProps({
    priceBooks: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    pendingTiering: {
        type: Array,
        default: () => []
    },
    filters: { type: Object, default: () => ({ search: '' }) },
});

// --- State Management ---
const activeTab = ref('tiers');
const showCreateModal = ref(false);
const showEditModal = ref(false); // NEW: Edit Modal State
const showConfirmModal = ref(false);
const search = ref(props.filters.search);

// --- Form Logic: Create New Tier ---
const createForm = useForm({
    name: '',
    min_quantity: '',
    discount_percentage: '',
});

// --- Form Logic: Edit Existing Tier ---
const editForm = useForm({
    id: null,
    name: '',
    min_quantity: '',
    discount_percentage: '',
});

const openEditModal = (tier) => {
    editForm.id = tier.id;
    editForm.name = tier.name;
    editForm.min_quantity = tier.min_quantity;
    editForm.discount_percentage = tier.discount_percentage;
    showEditModal.value = true;
};

const submitCreate = () => {
    createForm.post(route('eco.manager.book.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        }
    });
};

const submitUpdate = () => {
    editForm.patch(route('eco.manager.book.update', editForm.id), {
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
        }
    });
};

// --- Workflow Action: Automated Analysis ---
const confirmConfig = ref({
    title: '',
    message: '',
    action: () => { }
});

const handleApplyTier = (order) => {
    confirmConfig.value = {
        title: 'Apply Pricing Tier',
        message: `Analyze order for ${order.client?.company_name}? The system will automatically apply the best qualified discount based on the ${order.items_sum_quantity} items in this order.`,
        action: () => router.post(route('eco.manager.book.apply-tier', order.id), {}, {
            onSuccess: () => showConfirmModal.value = false
        })
    };
    showConfirmModal.value = true;
};

// --- Search Logic ---
let searchTimeout;
const updateSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('eco.manager.book'), { search: search.value }, { preserveState: true, replace: true });
    }, 300);
};

const stats = computed(() => {
    const tiersCount = props.priceBooks?.data?.length || 0;
    return [
        { label: 'Pending Tiering', value: props.pendingTiering.length, icon: Zap, color: 'text-orange-500', bg: 'bg-orange-50' },
        { label: 'Active Tiers', value: tiersCount, icon: Tag, color: 'text-indigo-600', bg: 'bg-indigo-50' },
        { label: 'System Reach', value: 'Active', icon: Users, color: 'text-blue-600', bg: 'bg-blue-50' },
        { label: 'Average Save', value: 'Dynamic', icon: Percent, color: 'text-amber-600', bg: 'bg-amber-50' },
    ];
});
</script>

<template>

    <Head title="Pricing Architect - ECO Manager" />

    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div
                        class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <ShieldCheck class="h-3.5 w-3.5" />
                        Revenue & Margin Control
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Pricing <span class="text-indigo-600">Architect</span>
                    </h1>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="showCreateModal = true"
                        class="flex items-center gap-2 px-6 py-3 rounded-[1.5rem] bg-indigo-600 text-white shadow-lg shadow-indigo-500/20 text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition-all">
                        <Plus class="h-4 w-4" />
                        New Tier
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div v-for="stat in stats" :key="stat.label"
                    class="p-7 rounded-[2.5rem] bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm transition-all hover:shadow-md">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ stat.label }}</p>
                    <div class="flex items-center justify-between">
                        <h3 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter italic">
                            {{ stat.value }}
                        </h3>
                        <div :class="stat.bg" class="p-2.5 rounded-xl">
                            <component :is="stat.icon" :class="stat.color" class="h-6 w-6" />
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="pendingTiering.length > 0"
                class="space-y-6 bg-indigo-50/30 dark:bg-indigo-950/10 p-8 rounded-[3rem] border border-indigo-100 dark:border-indigo-900/30">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter italic">
                            Orders Awaiting Tiering</h2>
                        <p class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest italic">
                            The system will apply tiers based on the highest qualified item threshold.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div v-for="order in pendingTiering" :key="order.id"
                        class="flex items-center justify-between p-6 bg-white dark:bg-gray-950 rounded-[2rem] border border-gray-100 dark:border-gray-800 shadow-sm transition-all hover:border-indigo-200">
                        <div class="flex items-center gap-5">
                            <div
                                class="h-12 w-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                <ClipboardCheck class="h-6 w-6" />
                            </div>
                            <div>
                                <h4
                                    class="text-sm font-black text-gray-900 dark:text-white uppercase italic tracking-tighter">
                                    {{ order.client?.company_name }}
                                </h4>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                    Sub: ₱{{ parseFloat(order.subtotal).toLocaleString() }} • Qty: {{
                                        order.items_sum_quantity || 0 }} Items
                                </p>
                            </div>
                        </div>
                        <button @click="handleApplyTier(order)"
                            class="px-6 py-3 bg-indigo-600 text-white rounded-xl text-[9px] font-black uppercase tracking-widest shadow-md hover:bg-indigo-700 transition-all flex items-center gap-2">
                            <Zap class="h-3.5 w-3.5" />
                            Run Analysis
                        </button>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div
                    class="p-8 border-b border-gray-50 dark:border-gray-800 flex flex-col lg:flex-row justify-between items-center gap-6">
                    <div class="flex p-1.5 bg-gray-50 dark:bg-gray-950 rounded-2xl">
                        <button @click="activeTab = 'tiers'"
                            :class="activeTab === 'tiers' ? 'bg-white dark:bg-gray-800 shadow-sm text-indigo-600' : 'text-gray-400'"
                            class="px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest">Pricing
                            Tiers</button>
                    </div>
                    <div class="relative flex-1 lg:w-80 group">
                        <Search
                            class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 group-focus-within:text-indigo-600" />
                        <input v-model="search" @input="updateSearch" type="text" placeholder="Search pricing logic..."
                            class="w-full pl-11 pr-4 py-3.5 rounded-2xl border-gray-100 dark:bg-gray-950 text-[10px] font-black uppercase tracking-widest">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                                <th class="px-8 py-5">Tier Configuration</th>
                                <th class="px-8 py-5">Discount %</th>
                                <th class="px-8 py-5">Item Threshold</th>
                                <th class="px-8 py-5 text-right px-10">Operations</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="book in priceBooks.data" :key="book.id"
                                class="group hover:bg-gray-50/30 transition-all">
                                <td class="px-8 py-6 flex items-center gap-4">
                                    <div
                                        class="h-10 w-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                        <Tag class="h-5 w-5" />
                                    </div>
                                    <span
                                        class="text-sm font-black text-gray-900 dark:text-white uppercase italic tracking-tighter">
                                        {{ book.name }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-lg font-black text-indigo-600 italic">
                                    -{{ book.discount_percentage }}%
                                </td>
                                <td class="px-8 py-6 text-[10px] font-black text-gray-500 uppercase">
                                    {{ (book.min_quantity || 0).toLocaleString() }}+ Items
                                </td>
                                <td class="px-8 py-6 text-right px-10">
                                    <button @click="openEditModal(book)"
                                        class="p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-indigo-600 transition-all">
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="showCreateModal"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-950/60 backdrop-blur-md p-4">
            <div
                class="bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100 dark:border-gray-800">
                <div class="px-8 py-6 bg-indigo-600 text-white flex justify-between items-center">
                    <h3 class="font-black text-sm uppercase tracking-widest italic">Configure New Tier</h3>
                    <button @click="showCreateModal = false">
                        <X class="h-6 w-6" />
                    </button>
                </div>
                <form @submit.prevent="submitCreate" class="p-8 space-y-6">
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-400 block mb-2">Tier Name</label>
                        <input v-model="createForm.name" type="text"
                            class="w-full rounded-xl border-gray-100 dark:bg-gray-950 text-xs font-bold" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase text-gray-400 block mb-2">Min. Items
                                Threshold</label>
                            <input v-model="createForm.min_quantity" type="number"
                                class="w-full rounded-xl border-gray-100 dark:bg-gray-950 text-xs font-bold" required>
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-gray-400 block mb-2">Discount %</label>
                            <input v-model="createForm.discount_percentage" type="number" step="0.01"
                                class="w-full rounded-xl border-gray-100 dark:bg-gray-950 text-xs font-bold" required>
                        </div>
                    </div>
                    <button type="submit" :disabled="createForm.processing"
                        class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg hover:brightness-110 transition-all">
                        Finalize Configuration
                    </button>
                </form>
            </div>
        </div>

        <div v-if="showEditModal"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-950/60 backdrop-blur-md p-4">
            <div
                class="bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100 dark:border-gray-800">
                <div class="px-8 py-6 bg-indigo-600 text-white flex justify-between items-center">
                    <h3 class="font-black text-sm uppercase tracking-widest italic">Edit Tier Configuration</h3>
                    <button @click="showEditModal = false">
                        <X class="h-6 w-6" />
                    </button>
                </div>
                <form @submit.prevent="submitUpdate" class="p-8 space-y-6">
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-400 block mb-2">Tier Name</label>
                        <input v-model="editForm.name" type="text"
                            class="w-full rounded-xl border-gray-100 dark:bg-gray-950 text-xs font-bold" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase text-gray-400 block mb-2">Min. Items
                                Threshold</label>
                            <input v-model="editForm.min_quantity" type="number"
                                class="w-full rounded-xl border-gray-100 dark:bg-gray-950 text-xs font-bold" required>
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-gray-400 block mb-2">Discount %</label>
                            <input v-model="editForm.discount_percentage" type="number" step="0.01"
                                class="w-full rounded-xl border-gray-100 dark:bg-gray-950 text-xs font-bold" required>
                        </div>
                    </div>
                    <button type="submit" :disabled="editForm.processing"
                        class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg hover:brightness-110 transition-all">
                        Update Configuration
                    </button>
                </form>
            </div>
        </div>

        <div v-if="showConfirmModal"
            class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div
                class="bg-white dark:bg-gray-900 w-full max-w-md rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-2xl p-8 text-center">
                <div
                    class="h-16 w-16 rounded-3xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 mx-auto mb-6">
                    <Zap class="h-8 w-8" />
                </div>
                <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter mb-2 italic">
                    {{ confirmConfig.title }}
                </h3>
                <p class="text-sm text-gray-500 mb-8 font-medium italic">{{ confirmConfig.message }}</p>
                <div class="flex gap-3">
                    <button @click="showConfirmModal = false"
                        class="flex-1 py-4 rounded-2xl bg-gray-50 text-[10px] font-black uppercase text-gray-400">Cancel</button>
                    <button @click="confirmConfig.action"
                        class="flex-1 py-4 rounded-2xl bg-indigo-600 text-white text-[10px] font-black uppercase shadow-lg">Begin
                        Analysis</button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.tracking-tighter {
    letter-spacing: -0.05em;
}
</style>