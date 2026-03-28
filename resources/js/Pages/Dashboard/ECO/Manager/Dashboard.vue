<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ShoppingBag, Package, TrendingUp } from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    pipeline: Object,
});
</script>

<template>

    <Head title="ECO MANAGER DASHBOARD" />
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-8 p-4">
            <h1 class="text-2xl font-bold">E-Commerce Dashboard</h1>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500">Today's Sales</p>
                            <p class="text-3xl font-bold">₱{{ stats.todaySales }}</p>
                        </div>
                        <ShoppingBag class="h-8 w-8 text-green-500" />
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500">Active Products</p>
                            <p class="text-3xl font-bold">{{ stats.activeProducts }}</p>
                        </div>
                        <Package class="h-8 w-8 text-blue-500" />
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500">Monthly Revenue</p>
                            <p class="text-3xl font-bold">₱{{ stats.monthlyRevenue }}</p>
                        </div>
                        <TrendingUp class="h-8 w-8 text-purple-500" />
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border">
                <h2 class="text-xl font-bold mb-4">Department Pipeline</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="p-4 bg-orange-50 rounded-lg">
                        <p class="text-orange-600">Credit Review</p>
                        <p class="text-2xl font-bold">{{ pipeline.credit_review }}</p>
                    </div>
                    <div class="p-4 bg-blue-50 rounded-lg">
                        <p class="text-blue-600">Tier Assignment</p>
                        <p class="text-2xl font-bold">{{ pipeline.tier_assignment }}</p>
                    </div>
                    <div class="p-4 bg-purple-50 rounded-lg">
                        <p class="text-purple-600">Pending Client Approval</p>
                        <p class="text-2xl font-bold">{{ pipeline.pending_client_approval }}</p>
                    </div>
                    <div class="p-4 bg-green-50 rounded-lg">
                        <p class="text-green-600">Approved</p>
                        <p class="text-2xl font-bold">{{ pipeline.approved }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>