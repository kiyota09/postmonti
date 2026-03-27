<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    Zap,
    TrendingUp,
    Target,
    MessageSquare,
    ChevronRight,
    Clock,
    CheckCircle2,
    Briefcase
} from 'lucide-vue-next';

// Props passed from StaffDayController.php
const props = defineProps({
    notifications: Array,        // New Inquiries assigned to the staff
    myActiveLeadsCount: Number,  // Count of deals in Negotiation/Approval
    personalRevenue: Number,     // Sum of approved Purchase Orders
    target: Number,              // Revenue quota
});

// Calculate progress percentage for the revenue target with safety checks
const revenueProgress = computed(() => {
    if (!props.target || props.target === 0) return '0.0';
    const progress = ((props.personalRevenue ?? 0) / props.target) * 100;
    return Math.min(progress, 100).toFixed(1);
});

const stats = computed(() => [
    {
        label: 'Active Pipeline',
        value: props.myActiveLeadsCount ?? 0,
        icon: Briefcase,
        color: 'text-blue-600',
        bg: 'bg-blue-50'
    },
    {
        label: 'Personal Revenue',
        value: `₱${(props.personalRevenue ?? 0).toLocaleString()}`,
        icon: TrendingUp,
        color: 'text-emerald-600',
        bg: 'bg-emerald-50'
    },
    {
        label: 'Annual Target',
        value: `₱${(props.target ?? 0).toLocaleString()}`,
        icon: Target,
        color: 'text-indigo-600',
        bg: 'bg-indigo-50'
    },
]);
</script>

<template>

    <Head title="CRM Staff - My Day" />

    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                <div class="space-y-1">
                    <div
                        class="flex items-center gap-2 text-blue-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Zap class="h-3 w-3 fill-current" />
                        Staff Performance Live
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        My <span class="text-blue-600">Day</span>
                    </h1>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div v-for="stat in stats" :key="stat.label"
                    class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm flex items-center justify-between hover:shadow-md transition-all">
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ stat.label }}</p>
                        <p class="text-3xl font-black text-gray-900 dark:text-white mt-1 italic tracking-tighter">
                            {{ stat.value }}
                        </p>
                    </div>
                    <div :class="[stat.bg, stat.color]" class="h-14 w-14 rounded-2xl flex items-center justify-center">
                        <component :is="stat.icon" class="h-7 w-7" />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <div class="lg:col-span-8">
                    <div
                        class="bg-white dark:bg-gray-900 p-8 rounded-[3rem] border border-gray-100 dark:border-gray-800 shadow-sm h-full">
                        <div class="flex items-center justify-between mb-8">
                            <h2
                                class="text-xl font-black text-gray-900 dark:text-white uppercase italic tracking-tighter">
                                Quota Analytics
                            </h2>
                            <span class="text-xs font-black text-blue-600 uppercase">{{ revenueProgress }}%
                                Achieved</span>
                        </div>

                        <div class="space-y-6">
                            <div class="w-full bg-gray-50 dark:bg-gray-800 h-4 rounded-full overflow-hidden">
                                <div class="bg-blue-600 h-full transition-all duration-1000 text-indigo-600"
                                    :style="{ width: `${revenueProgress}%` }"></div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium italic">
                                Total approved revenue for current fiscal year: ₱{{ (personalRevenue ??
                                    0).toLocaleString() }}.
                                Goal: ₱{{ (target ?? 0).toLocaleString() }}.
                            </p>
                            <Link :href="route('crm.lead')"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase shadow-lg hover:bg-blue-700 transition-all">
                                Open Deal Pipeline
                                <ChevronRight class="h-4 w-4" />
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-4 space-y-6">
                    <div class="flex items-center gap-2 px-2">
                        <MessageSquare class="h-4 w-4 text-blue-600" />
                        <h3 class="text-xs font-black uppercase tracking-widest text-gray-500">Urgent Inquiries</h3>
                    </div>

                    <div class="space-y-4">
                        <div v-for="lead in notifications" :key="lead.id"
                            class="p-5 bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-100 dark:border-gray-800 shadow-sm hover:border-blue-200 transition-all group">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <p
                                        class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-tighter italic">
                                        {{ lead.company_name }}
                                    </p>
                                    <p class="text-[9px] font-bold text-gray-400 uppercase">
                                        {{ lead.interest_fabric }} Inquiry
                                    </p>
                                </div>
                                <span
                                    class="px-2 py-1 bg-blue-50 text-blue-600 rounded-lg text-[8px] font-black uppercase tracking-widest border border-blue-100">
                                    New
                                </span>
                            </div>
                            <div
                                class="flex justify-between items-end border-t border-gray-50 dark:border-gray-800 pt-3">
                                <div class="flex items-center gap-2 text-gray-400">
                                    <Clock class="h-3 w-3" />
                                    <span class="text-[9px] font-black uppercase italic">Pending Review</span>
                                </div>
                                <Link :href="route('crm.lead')"
                                    class="text-[9px] font-black text-blue-600 uppercase flex items-center gap-1">
                                    Convert
                                    <ChevronRight class="h-3 w-3" />
                                </Link>
                            </div>
                        </div>

                        <div v-if="!notifications || notifications.length === 0"
                            class="text-center py-10 bg-gray-50/50 dark:bg-zinc-800/50 rounded-[2rem] border border-dashed border-gray-200 dark:border-gray-700">
                            <CheckCircle2 class="h-8 w-8 text-gray-200 mx-auto mb-2" />
                            <p class="text-[10px] font-black text-gray-300 uppercase tracking-widest italic">Inbox Clear
                            </p>
                        </div>
                    </div>
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