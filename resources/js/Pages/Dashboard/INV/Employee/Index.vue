<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    stockLevels: Array,
    stats: Object,
});
</script>

<template>

    <Head title="Inventory Stock Control" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Inventory: Stock Control
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div
                        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Items</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ props.stats &&
                            props.stats.totalItems ?
                            props.stats.totalItems : 0 }}</p>
                    </div>
                    <div
                        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Low Stock Items</p>
                        <p class="text-2xl font-bold text-orange-500">{{ props.stats && props.stats.lowStock ?
                            props.stats.lowStock : 0 }}</p>
                    </div>
                    <div
                        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Out of Stock</p>
                        <p class="text-2xl font-bold text-red-500">{{ props.stats && props.stats.outOfStock ?
                            props.stats.outOfStock : 0 }}</p>
                    </div>
                    <div
                        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Warehouse Capacity</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ props.stats &&
                            props.stats.warehouseCapacity ? props.stats.warehouseCapacity : 0 }}%</p>
                    </div>
                </div>

                <div class="overflow-hidden bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <p class="font-bold">Welcome, {{ props.user?.name }}!</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Inventory Staff Dashboard - Manage stock
                            levels and
                            inventory operations</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Current Stock Levels</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead
                                    class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-6 py-3">Product Name</th>
                                        <th class="px-6 py-3">SKU</th>
                                        <th class="px-6 py-3">Quantity</th>
                                        <th class="px-6 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in props.stockLevels" :key="item.id"
                                        class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ item.name }}
                                        </td>
                                        <td class="px-6 py-4 font-mono">{{ item.sku }}</td>
                                        <td class="px-6 py-4">{{ item.quantity }}</td>
                                        <td class="px-6 py-4">
                                            <span :class="{
                                                'text-red-500 font-bold': item.quantity < 10,
                                                'text-orange-500 font-semibold': item.quantity >= 10 && item.quantity < 50,
                                                'text-green-500': item.quantity >= 50
                                            }">
                                                {{ item.status }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="props.stockLevels.length === 0">
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                            No inventory data available.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>