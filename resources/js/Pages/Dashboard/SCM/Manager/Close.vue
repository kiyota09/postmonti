<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Boxes,
    AlertTriangle,
    Landmark,
    Receipt,
    BarChart3,
    CheckCircle
} from 'lucide-vue-next';

// Data defined directly in the script - no props needed for testing
const localStats = ref([
    { title: 'Inventory Valuation', value: '$242,500.00', icon: Landmark, color: 'text-blue-600' },
    { title: 'Month Spend', value: '$85,200.00', icon: Receipt, color: 'text-emerald-600' },
    { title: 'Total Variance', value: '-$1,240.00', icon: AlertTriangle, color: 'text-rose-600' },
    { title: 'Open AP Invoices', value: '14', icon: BarChart3, color: 'text-amber-600' }
]);

const closePeriodData = ref([
    { category: 'Raw Materials (Yarn/Fabric)', openingValue: 120000, additions: 45000, consumption: 50000, closingValue: 115000 },
    { category: 'Dyes & Chemicals', openingValue: 15000, additions: 8000, consumption: 6500, closingValue: 16500 },
    { category: 'Trims & Accessories', openingValue: 8000, additions: 3000, consumption: 2500, closingValue: 8500 },
]);

const finalizeMonthEnd = () => {
    alert("SCM Financial Close Successful. Summary sent to General Ledger.");
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="mb-8">
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">SCM Financial Close</h2>
            <p class="text-gray-500 mt-1 font-medium text-sm uppercase tracking-widest">Phase 9: Month-End Valuation</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div v-for="stat in localStats" :key="stat.title"
                class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 rounded-lg bg-gray-50 dark:bg-gray-700">
                        <component :is="stat.icon" class="w-6 h-6" :class="stat.color" />
                    </div>
                </div>
                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">{{ stat.title }}</p>
                <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ stat.value }}</p>
            </div>
        </div>

        <div
            class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div
                class="p-8 border-b border-gray-100 dark:border-gray-700 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-800 dark:text-white">Inventory Valuation Summary</h3>
                    <p class="text-sm text-gray-400 mt-1 italic">Calculated for period ending: Feb 2026</p>
                </div>
                <button @click="finalizeMonthEnd"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-2xl font-black transition-all flex items-center shadow-lg shadow-indigo-100 dark:shadow-none">
                    <CheckCircle class="w-5 h-5 mr-3" />
                    APPROVE & CLOSE BOOKS
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead
                        class="bg-gray-50 dark:bg-gray-900/40 text-[11px] uppercase tracking-[0.2em] text-gray-400 font-black">
                        <tr>
                            <th class="px-8 py-5">Asset Category</th>
                            <th class="px-8 py-5 text-right">Opening Balance</th>
                            <th class="px-8 py-5 text-right">Procured (+)</th>
                            <th class="px-8 py-5 text-right">Consumed (-)</th>
                            <th class="px-8 py-5 text-right">Ending Valuation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="item in closePeriodData" :key="item.category"
                            class="hover:bg-indigo-50/30 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-8 py-6">
                                <span class="block font-black text-gray-800 dark:text-gray-100 text-sm">{{ item.category
                                    }}</span>
                            </td>
                            <td class="px-8 py-6 text-right font-mono text-sm text-gray-500">${{
                                item.openingValue.toLocaleString() }}</td>
                            <td class="px-8 py-6 text-right font-mono text-sm text-emerald-600 font-bold">+ ${{
                                item.additions.toLocaleString() }}</td>
                            <td class="px-8 py-6 text-right font-mono text-sm text-rose-500 font-bold">- ${{
                                item.consumption.toLocaleString() }}</td>
                            <td
                                class="px-8 py-6 text-right font-mono font-black text-gray-900 dark:text-white text-base">
                                ${{ item.closingValue.toLocaleString() }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-8 bg-gray-50 dark:bg-gray-900/20 border-t border-gray-100 dark:border-gray-700">
                <div class="flex items-start gap-4">
                    <AlertTriangle class="w-5 h-5 text-amber-500 mt-0.5" />
                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                        <span class="font-bold text-gray-700 dark:text-gray-200">Final Verification Note:</span>
                        The ending valuation is based on a Weighted Average Cost (WAC). All Goods Received Notes (GRN)
                        for this period have been matched to vendor invoices. The variance of $1,240 is within the
                        acceptable 3% textile shrinkage limit.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>