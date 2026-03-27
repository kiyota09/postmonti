<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    MessageSquareWarning,
    Timer,
    UserMinus,
    Search,
    ArrowUpRight
} from 'lucide-vue-next';

// Receive real data from OversightController.php
const props = defineProps({
    tickets: Array,       // Based on crm_interactions where type is 'System'
    atRiskClients: Array  // Clients with no orders in 60+ days
});

// Helper to format currency for the at-risk client values
const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
};
</script>

<template>
    <AuthenticatedLayout title="SLA & Quality Oversight">
        <div class="p-4 md:p-8 space-y-6 bg-slate-50/50 dark:bg-zinc-950 min-h-screen">

            <div
                class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-200 dark:border-zinc-800 pb-6">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white uppercase">
                        SLA & <span class="text-blue-600">Quality Oversight</span>
                    </h1>
                    <p class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-widest mt-1">
                        Monitoring Customer Satisfaction & Technical Support
                    </p>
                </div>
                <div class="flex gap-2">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input type="text" placeholder="Search Tickets..."
                            class="pl-10 pr-4 py-2 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl text-xs font-bold focus:ring-2 focus:ring-blue-500 outline-none w-64" />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div
                    class="p-6 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl shadow-sm">
                    <div class="flex justify-between items-start">
                        <Timer class="w-8 h-8 text-blue-600 bg-blue-50 dark:bg-blue-900/20 p-1.5 rounded-lg" />
                        <span
                            class="text-[10px] font-black text-green-600 bg-green-50 px-2 py-0.5 rounded-full uppercase">Stable</span>
                    </div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-4">Avg. Resolution Time
                    </p>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1">18.4 Hours</h3>
                </div>

                <div
                    class="p-6 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl shadow-sm">
                    <div class="flex justify-between items-start">
                        <MessageSquareWarning
                            class="w-8 h-8 text-amber-500 bg-amber-50 dark:bg-amber-900/20 p-1.5 rounded-lg" />
                    </div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-4">Open Quality Tickets
                    </p>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ tickets?.length || 0 }} Active
                    </h3>
                </div>

                <div
                    class="p-6 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl shadow-sm">
                    <div class="flex justify-between items-start">
                        <UserMinus class="w-8 h-8 text-red-500 bg-red-50 dark:bg-red-900/20 p-1.5 rounded-lg" />
                    </div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-4">Red-Flag Clients</p>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ atRiskClients?.length || 0 }}
                        At Risk</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div
                    class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center">
                        <h3 class="text-sm font-black uppercase tracking-wider">Active Quality Issues</h3>
                        <button class="text-[10px] font-black text-blue-600 uppercase tracking-tighter">View All
                            Records</button>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-zinc-800">
                        <div v-for="ticket in tickets" :key="ticket.id"
                            class="p-4 hover:bg-slate-50 transition flex items-center justify-between group">
                            <div class="flex gap-4 items-start">
                                <div
                                    class="bg-slate-100 dark:bg-zinc-800 p-2 rounded-lg font-mono text-[10px] font-bold text-gray-600 dark:text-gray-400">
                                    TK-{{ ticket.id }}
                                </div>
                                <div>
                                    <p class="text-sm font-black uppercase text-gray-900 dark:text-white leading-none">
                                        Real-time Log</p>
                                    <p class="text-xs text-gray-500 mt-1 italic">"{{ ticket.note }}"</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-right">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">{{ new
                                    Date(ticket.created_at).toLocaleDateString() }}</span>
                                <button class="p-2 opacity-0 group-hover:opacity-100 transition">
                                    <ArrowUpRight class="w-4 h-4 text-blue-600" />
                                </button>
                            </div>
                        </div>
                        <div v-if="!tickets || tickets.length === 0"
                            class="p-12 text-center text-xs font-bold text-gray-400 uppercase tracking-widest">
                            No active quality issues found.
                        </div>
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-4 bg-red-50 dark:bg-red-900/10 border-b border-red-100 dark:border-red-900/20">
                        <h3 class="text-sm font-black text-red-700 dark:text-red-400 uppercase tracking-wider">Churn
                            Risk (60+ Days)</h3>
                    </div>
                    <div class="p-4 space-y-4">
                        <div v-for="client in atRiskClients" :key="client.company_name"
                            class="p-3 border border-gray-100 dark:border-zinc-800 rounded-xl hover:border-red-200 transition">
                            <div class="flex justify-between items-center">
                                <p class="text-xs font-black uppercase text-gray-900 dark:text-white">{{
                                    client.company_name }}</p>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1 uppercase font-bold tracking-tighter">Last Active:
                                {{ new Date(client.lastOrder).toLocaleDateString() }}</p>
                            <button
                                class="w-full mt-3 py-2 bg-zinc-900 text-white rounded-lg text-[10px] font-black uppercase hover:bg-black transition">Assign
                                Follow-up</button>
                        </div>
                        <div v-if="!atRiskClients || atRiskClients.length === 0"
                            class="text-center py-8 text-[10px] font-bold text-gray-400 uppercase">
                            No at-risk clients identified.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>