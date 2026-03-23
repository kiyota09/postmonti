<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    Search,
    Filter,
    Users,
    TrendingUp,
    ShieldCheck,
    CreditCard,
    MapPin,
    ExternalLink,
    Mail,
    Phone,
    Briefcase,
    ChevronRight,
    ArrowUpRight,
    CircleDollarSign
} from 'lucide-vue-next';

// Tab State
const activeTab = ref('directory'); // 'directory' or 'analytics'

// Mock Data for Partner Directory
const partners = ref([
    {
        id: 'PTN-1026',
        name: 'TechLogistics Corp',
        contact: 'Juan Dela Cruz',
        email: 'juan@techlog.ph',
        status: 'Active',
        credit_status: 'Good Standing',
        top_product: 'Industrial Grade Silk',
        total_spend: '₱1.2M'
    },
    {
        id: 'PTN-3026',
        name: 'Manila Build Supplies',
        contact: 'Maria Santos',
        email: 'maria@mbs.com.ph',
        status: 'Active',
        credit_status: 'Flagged (Overdue)',
        top_product: 'Premium Cotton Roll',
        total_spend: '₱850K'
    }
]);

const stats = computed(() => [
    { label: 'Active Partners', value: '1,240', icon: Users, color: 'text-indigo-600', bg: 'bg-indigo-50' },
    { label: 'High-Volume Buyers', value: '85', icon: TrendingUp, color: 'text-emerald-600', bg: 'bg-emerald-50' },
    { label: 'Credit Risk Entities', value: '03', icon: CircleDollarSign, color: 'text-rose-600', bg: 'bg-rose-50' },
    { label: 'Retention Rate', value: '98%', icon: ShieldCheck, color: 'text-blue-600', bg: 'bg-blue-50' },
]);

const getCreditStyle = (status) => {
    return status.includes('Flagged')
        ? 'bg-rose-50 text-rose-600 border-rose-100'
        : 'bg-emerald-50 text-emerald-600 border-emerald-100';
};
</script>

<template>

    <Head title="Partner Directory - Staff Portal" />

    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-8 p-4 lg:p-10">

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div
                        class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Briefcase class="h-3.5 w-3.5" />
                        Client Relations & CRM
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Partner <span class="text-indigo-600">Directory</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Strategic oversight of B2B relationships and wholesale purchasing behavior
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        class="flex items-center gap-2 px-6 py-3 rounded-[1.5rem] bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm text-[10px] font-black uppercase tracking-widest text-gray-600 dark:text-gray-300 hover:bg-gray-50 transition-all">
                        <MapPin class="h-4 w-4" />
                        Regional Map
                    </button>
                    <button
                        class="flex items-center gap-2 px-6 py-3 rounded-[1.5rem] bg-indigo-600 text-white shadow-lg shadow-indigo-500/20 text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition-all">
                        <CreditCard class="h-4 w-4" />
                        Credit Sync
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div v-for="stat in stats" :key="stat.label"
                    class="p-7 rounded-[2.5rem] bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm transition-all hover:shadow-md">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ stat.label }}</p>
                    <div class="flex items-center justify-between">
                        <h3 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">{{
                            stat.value }}</h3>
                        <div :class="stat.bg" class="p-2.5 rounded-xl">
                            <component :is="stat.icon" :class="stat.color" class="h-6 w-6" />
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div
                    class="p-8 border-b border-gray-50 dark:border-gray-800 flex flex-col lg:flex-row justify-between items-center gap-6">
                    <div
                        class="flex p-1.5 bg-gray-50 dark:bg-gray-950 rounded-2xl w-full lg:w-auto overflow-x-auto no-scrollbar">
                        <button @click="activeTab = 'directory'"
                            :class="activeTab === 'directory' ? 'bg-white dark:bg-gray-800 shadow-sm text-indigo-600' : 'text-gray-400'"
                            class="flex-1 lg:flex-none px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                            Partner List
                        </button>
                        <button @click="activeTab = 'analytics'"
                            :class="activeTab === 'analytics' ? 'bg-white dark:bg-gray-800 shadow-sm text-indigo-600' : 'text-gray-400'"
                            class="flex-1 lg:flex-none px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                            Purchasing Behavior
                        </button>
                    </div>

                    <div class="flex items-center gap-4 w-full lg:w-auto">
                        <div class="relative flex-1 lg:w-80 group">
                            <Search
                                class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 group-focus-within:text-indigo-600 transition-colors" />
                            <input type="text" placeholder="Search by Business Entity..."
                                class="w-full pl-11 pr-4 py-3.5 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase tracking-widest focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all placeholder:text-gray-400">
                        </div>
                        <button
                            class="p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-indigo-600 transition-colors">
                            <Filter class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                                <th class="px-8 py-5">Partner Profile</th>
                                <th class="px-8 py-5">Communication Liaison</th>
                                <th class="px-8 py-5 text-center">Credit Status</th>
                                <th class="px-8 py-5">Top SKU Acquisition</th>
                                <th class="px-8 py-5 text-right px-10">Operations</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="partner in partners" :key="partner.id"
                                class="group hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-all">
                                <td class="px-8 py-7">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="h-12 w-12 rounded-[1rem] bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 border border-indigo-100 dark:border-indigo-800/50 shadow-sm">
                                            <Briefcase class="h-6 w-6" />
                                        </div>
                                        <div>
                                            <p
                                                class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-tighter">
                                                {{ partner.name }}</p>
                                            <p
                                                class="text-[10px] font-bold text-gray-400 uppercase tracking-widest italic">
                                                Partner ID: #{{ partner.id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-7">
                                    <div class="space-y-1">
                                        <p
                                            class="text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-tight">
                                            {{ partner.contact }}</p>
                                        <div
                                            class="flex items-center gap-2 text-[10px] font-bold text-indigo-500 uppercase">
                                            <Mail class="h-3 w-3" /> {{ partner.email }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-7">
                                    <div class="flex justify-center">
                                        <span :class="getCreditStyle(partner.credit_status)"
                                            class="px-4 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border transition-all flex items-center gap-2">
                                            <div :class="partner.credit_status.includes('Flagged') ? 'bg-rose-500' : 'bg-emerald-500'"
                                                class="h-1.5 w-1.5 rounded-full"></div>
                                            {{ partner.credit_status }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-8 py-7">
                                    <div class="flex flex-col">
                                        <p
                                            class="text-xs font-black text-gray-900 dark:text-white italic tracking-tighter">
                                            {{ partner.top_product }}</p>
                                        <p class="text-[9px] font-black text-emerald-600 uppercase tracking-widest">LTV:
                                            {{ partner.total_spend }}</p>
                                    </div>
                                </td>
                                <td class="px-8 py-7">
                                    <div class="flex justify-end gap-2 px-2">
                                        <button
                                            class="p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-indigo-600 transition-all"
                                            title="View Invoices">
                                            <CreditCard class="h-4 w-4" />
                                        </button>
                                        <button
                                            class="p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-indigo-600 transition-all"
                                            title="Contact Details">
                                            <Phone class="h-4 w-4" />
                                        </button>
                                        <button
                                            class="flex items-center gap-2 px-5 py-2.5 bg-gray-950 dark:bg-white text-white dark:text-gray-900 rounded-xl text-[9px] font-black uppercase tracking-widest hover:scale-105 transition-all shadow-md">
                                            Wholesale Profile
                                            <ExternalLink class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div
                class="flex flex-col md:flex-row items-center justify-between p-8 rounded-[2.5rem] bg-indigo-50 dark:bg-indigo-900/10 border border-dashed border-indigo-200 dark:border-indigo-800">
                <div class="flex items-center gap-5 text-center md:text-left mb-4 md:mb-0">
                    <div
                        class="h-14 w-14 rounded-[1.5rem] bg-white dark:bg-gray-900 flex items-center justify-center text-indigo-600 shadow-sm">
                        <ShieldCheck class="h-7 w-7" />
                    </div>
                    <div>
                        <h4 class="text-sm font-black uppercase tracking-tight text-gray-900 dark:text-white">Credit
                            Linkage System Active</h4>
                        <p class="text-xs font-medium text-gray-500 italic">Financial statuses are automatically synced
                            with the Credit Management ledger.</p>
                    </div>
                </div>
                <button
                    class="flex items-center gap-2 text-xs font-black text-indigo-600 uppercase tracking-widest hover:underline px-8 py-3 rounded-2xl bg-white dark:bg-gray-900 shadow-sm active:scale-95 transition-all">
                    View Overdue Ledger
                    <ArrowUpRight class="h-4 w-4" />
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.tracking-tighter {
    letter-spacing: -0.05em;
}

.tracking-tight {
    letter-spacing: -0.02em;
}

::-webkit-scrollbar {
    width: 5px;
    height: 5px;
}

::-webkit-scrollbar-thumb {
    background: #E5E7EB;
    border-radius: 10px;
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>