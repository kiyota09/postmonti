<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    History, Package, MessageSquare, Phone, Mail, Search,
    TrendingUp, User, CheckCircle, Building2, Clock,
    MoreHorizontal, LayoutGrid, LayoutDashboard
} from 'lucide-vue-next';

const props = defineProps({
    customers: { type: Array, default: () => [] },
    customer: { type: Object, default: null },
    interactionHistory: { type: Array, default: () => [] },
    liveProduction: { type: Array, default: () => [] },
});

const searchQuery = ref('');

// Filter all business partners from the database
const filteredCustomers = computed(() => {
    return props.customers.filter(c =>
        c.company_name?.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(value || 0);
};
</script>

<template>
    <AuthenticatedLayout title="Partner Ecosystem">
        <div class="p-6 bg-[#fdfdfd] dark:bg-zinc-950 min-h-[calc(100vh-64px)] overflow-y-auto custom-scrollbar">

            <div
                class="max-w-[1600px] mx-auto mb-10 flex flex-col md:flex-row justify-between items-end gap-6 border-b border-gray-100 dark:border-zinc-800 pb-8">
                <div class="space-y-1">
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase italic">
                        Partner <span class="text-blue-600">Ecosystem</span>
                    </h1>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] flex items-center gap-2">
                        <LayoutGrid class="w-3 h-3 text-blue-600" />
                        Managing {{ customers.length }} Business Partners
                    </p>
                </div>

                <div class="relative w-full md:w-96 group">
                    <Search
                        class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 group-focus-within:text-blue-600 transition-colors" />
                    <input v-model="searchQuery" type="text" placeholder="SEARCH PARTNERS..."
                        class="w-full pl-11 pr-4 py-4 bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-[1.5rem] text-[10px] font-black uppercase shadow-sm focus:ring-2 focus:ring-blue-500/20 transition-all" />
                </div>
            </div>

            <div class="max-w-[1600px] mx-auto grid grid-cols-1 xl:grid-cols-2 gap-8">
                <div v-for="c in filteredCustomers" :key="c.id"
                    class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-[2.5rem] p-8 shadow-sm hover:shadow-xl hover:border-blue-200 transition-all">

                    <div class="flex items-start justify-between mb-8">
                        <div class="flex items-center gap-6">
                            <div
                                class="h-16 w-16 bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl flex items-center justify-center text-white text-2xl font-black shadow-lg shadow-blue-500/20 uppercase">
                                {{ c.company_name?.charAt(0) }}
                            </div>
                            <div>
                                <h2
                                    class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter italic">
                                    {{ c.company_name }}
                                </h2>
                                <div
                                    class="flex flex-wrap items-center gap-4 mt-1 text-[9px] text-gray-400 font-bold uppercase tracking-widest">
                                    <span class="flex items-center gap-1.5">
                                        <User class="w-3 h-3 text-blue-500" /> {{ c.contact_person }}
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <Mail class="w-3 h-3 text-blue-500" /> {{ c.email }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <span
                            :class="c.status === 'active' ? 'bg-green-50 text-green-600 border-green-100' : 'bg-amber-50 text-amber-600 border-amber-100'"
                            class="px-3 py-1 text-[9px] font-black rounded-lg uppercase border italic">
                            {{ c.status }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div class="space-y-6">
                            <div class="p-6 bg-zinc-900 rounded-[2rem] text-white relative overflow-hidden">
                                <TrendingUp class="absolute -right-2 -top-2 w-20 h-20 opacity-5" />
                                <p class="text-[9px] font-black uppercase opacity-40 tracking-widest mb-1">Credit Limit
                                </p>
                                <h3 class="text-2xl font-black italic tracking-tighter">
                                    {{ formatCurrency(c.credit_limit) }}
                                </h3>
                                <div class="mt-4 flex items-center gap-2 text-[9px] font-bold uppercase opacity-40">
                                    <CheckCircle class="w-3 h-3 text-green-400" /> Verified Partner
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <button
                                    class="flex-1 py-3 bg-blue-600 text-white rounded-xl text-[9px] font-black uppercase shadow-lg shadow-blue-500/10 transition-all">
                                    View Details
                                </button>
                                <button class="p-3 bg-gray-50 dark:bg-zinc-800 rounded-xl text-gray-400">
                                    <MoreHorizontal class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div
                            class="bg-gray-50 dark:bg-zinc-800/50 rounded-[2rem] p-6 border border-gray-100 dark:border-zinc-800 flex flex-col justify-center">
                            <h3
                                class="text-[9px] font-black text-gray-400 uppercase tracking-widest flex items-center gap-2 mb-2">
                                <Building2 class="w-3.5 h-3.5 text-blue-600" /> Category
                            </h3>
                            <p class="text-[11px] font-bold text-gray-700 dark:text-zinc-200 uppercase italic">
                                Registered as {{ c.business_type }}
                            </p>
                            <div
                                class="mt-4 pt-4 border-t border-gray-100 dark:border-zinc-800 flex items-center gap-1 text-[8px] font-black text-gray-400 uppercase">
                                <Clock class="w-2.5 h-2.5" /> Established {{ new Date(c.created_at).toLocaleDateString()
                                }}
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="filteredCustomers.length === 0" class="col-span-full py-40 text-center opacity-40 italic">
                    <LayoutDashboard class="w-16 h-16 mx-auto mb-6 text-gray-200" />
                    <p class="text-lg font-black uppercase tracking-[0.2em]">No Partners Found in Database</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>