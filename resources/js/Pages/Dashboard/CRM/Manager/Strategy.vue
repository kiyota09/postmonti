<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    BarChart,
    PieChart,
    ArrowDownRight,
    Target,
    TrendingUp,
    RefreshCcw,
    FileSpreadsheet
} from 'lucide-vue-next';

// Receive real data from StrategyController.php
const props = defineProps({
    lostReasons: Array, // Contains { reason: string, count: number }
    stats: Object       // Contains { forecastedRevenue, leadVelocity }
});

// Mock marketing data remains for UI structure until you add a marketing table
const marketingCampaigns = [
    { source: 'Industry Expo 2025', leads: 120, conversion: '15%', revenue: '₱1.5M' },
    { source: 'B2B Referral', leads: 45, conversion: '40%', revenue: '₱2.1M' },
    { source: 'Digital Ads (LinkedIn)', leads: 300, conversion: '2%', revenue: '₱600k' },
];

// Helper to calculate bar widths for lost reasons
const totalLostDeals = computed(() => {
    return props.lostReasons?.reduce((sum, item) => sum + item.count, 0) || 0;
});

const calculatePercentage = (count) => {
    if (totalLostDeals.value === 0) return 0;
    return Math.round((count / totalLostDeals.value) * 100);
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
};

const getBarColor = (index) => {
    const colors = ['bg-red-500', 'bg-amber-500', 'bg-blue-500', 'bg-zinc-500'];
    return colors[index % colors.length];
};
</script>

<template>
    <AuthenticatedLayout title="Strategic CRM Reports">
        <div class="p-4 md:p-8 space-y-6 bg-slate-50/50 dark:bg-zinc-950 min-h-screen">

            <div
                class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-200 dark:border-zinc-800 pb-6">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white uppercase">
                        Strategic <span class="text-blue-600">Analytics</span>
                    </h1>
                    <p class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-widest mt-1">
                        Long-term Planning, Lost Deal Analysis & ROI
                    </p>
                </div>
                <div class="flex gap-2">
                    <button
                        class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl text-[10px] font-black uppercase hover:bg-gray-50 transition shadow-sm">
                        <FileSpreadsheet class="w-4 h-4" /> Export CSV
                    </button>
                    <button
                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl text-[10px] font-black uppercase hover:bg-blue-700 transition shadow-md shadow-blue-500/20">
                        <RefreshCcw class="w-4 h-4" /> Refresh Data
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    class="p-5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl shadow-sm">
                    <div class="flex justify-between items-center text-blue-600 mb-2">
                        <Target class="w-5 h-5" />
                        <span class="text-[10px] font-black uppercase">Quota Tracking</span>
                    </div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Annual Progress</p>
                    <h3 class="text-xl font-black mt-1">82%</h3>
                    <div class="w-full bg-gray-100 dark:bg-zinc-800 h-1.5 rounded-full mt-3">
                        <div class="bg-blue-600 h-1.5 rounded-full" style="width: 82%"></div>
                    </div>
                </div>

                <div
                    class="p-5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl shadow-sm">
                    <div class="flex justify-between items-center text-green-600 mb-2">
                        <TrendingUp class="w-5 h-5" />
                        <span class="text-[10px] font-black uppercase">Potential</span>
                    </div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Forecasted Revenue</p>
                    <h3 class="text-xl font-black mt-1">{{ formatCurrency(stats?.forecastedRevenue || 0) }}</h3>
                </div>

                <div
                    class="p-5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl shadow-sm">
                    <div class="flex justify-between items-center text-red-600 mb-2">
                        <ArrowDownRight class="w-5 h-5" />
                        <span class="text-[10px] font-black uppercase">Cycle</span>
                    </div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Avg. Deal Time</p>
                    <h3 class="text-xl font-black mt-1">24 Days</h3>
                </div>

                <div
                    class="p-5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl shadow-sm">
                    <div class="flex justify-between items-center text-zinc-600 mb-2">
                        <BarChart class="w-5 h-5" />
                        <span class="text-[10px] font-black uppercase">Growth</span>
                    </div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Weekly Lead Velocity</p>
                    <h3 class="text-xl font-black mt-1">{{ stats?.leadVelocity || 0 }} / Week</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div
                    class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6">
                    <div class="flex items-center gap-2 mb-6">
                        <PieChart class="w-5 h-5 text-blue-600" />
                        <h3 class="text-sm font-black uppercase tracking-wider">Lost-Deal Analysis (Reasons)</h3>
                    </div>
                    <div v-if="lostReasons?.length > 0" class="space-y-5">
                        <div v-for="(item, index) in lostReasons" :key="item.reason">
                            <div class="flex justify-between text-[11px] font-black uppercase mb-2">
                                <span>{{ item.reason || 'Reason Unspecified' }}</span>
                                <span class="text-gray-400">{{ item.count }} DEALS ({{ calculatePercentage(item.count)
                                    }}%)</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-zinc-800 h-3 rounded-md overflow-hidden">
                                <div :class="[getBarColor(index), 'h-3 rounded-md transition-all duration-1000']"
                                    :style="{ width: calculatePercentage(item.count) + '%' }">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="p-12 text-center text-xs font-bold text-gray-400 uppercase">
                        No data available for lost-deal analysis.
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100 dark:border-zinc-800 flex items-center gap-2">
                        <TrendingUp class="w-5 h-5 text-green-600" />
                        <h3 class="text-sm font-black uppercase tracking-wider">Marketing ROI by Source</h3>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-zinc-800/30">
                                <th class="p-4 text-[10px] font-black uppercase text-gray-500 tracking-wider">Source
                                </th>
                                <th
                                    class="p-4 text-[10px] font-black uppercase text-gray-500 tracking-wider text-center">
                                    Leads</th>
                                <th
                                    class="p-4 text-[10px] font-black uppercase text-gray-500 tracking-wider text-center">
                                    Conv. %</th>
                                <th
                                    class="p-4 text-[10px] font-black uppercase text-gray-500 tracking-wider text-right">
                                    Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            <tr v-for="source in marketingCampaigns" :key="source.source"
                                class="hover:bg-slate-50 transition">
                                <td class="p-4 text-xs font-black uppercase">{{ source.source }}</td>
                                <td class="p-4 text-xs font-bold text-center">{{ source.leads }}</td>
                                <td class="p-4 text-xs font-bold text-center text-blue-600">{{ source.conversion }}</td>
                                <td class="p-4 text-xs font-black text-right">{{ source.revenue }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>