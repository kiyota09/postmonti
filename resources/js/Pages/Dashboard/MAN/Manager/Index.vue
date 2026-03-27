<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Users, Package, Factory, TrendingUp } from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    staff: Array,
});

// Make staff reactive with newRole property
const staffList = ref([]);

onMounted(() => {
    staffList.value = props.staff.map(staff => ({
        ...staff,
        newRole: staff.manufacturing_role || ''
    }));
});

const updateStaffRole = (staffId, newRole) => {
    if (confirm('Change staff role?')) {
        router.post(route('man.manager.update-staff-role', staffId), { manufacturing_role: newRole }, {
            preserveScroll: true,
            onSuccess: () => {
                // Update local state
                const index = staffList.value.findIndex(s => s.id === staffId);
                if (index !== -1) {
                    staffList.value[index].manufacturing_role = newRole;
                    staffList.value[index].newRole = newRole;
                }
            }
        });
    }
};
</script>

<template>
    <AuthenticatedLayout>

        <Head title="Manufacturing Manager" />
        <div class="p-6 max-w-7xl mx-auto">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div
                    class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Received Orders</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.receivedOrders }}</p>
                        </div>
                        <Package class="w-8 h-8 text-blue-500" />
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">In Production</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.inProduction }}</p>
                        </div>
                        <Factory class="w-8 h-8 text-yellow-500" />
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Active Machines</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.activeMachines }}</p>
                        </div>
                        <TrendingUp class="w-8 h-8 text-green-500" />
                    </div>
                </div>
            </div>

            <!-- Staff List with Role Change -->
            <div
                class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-800/50">
                    <h2 class="text-lg font-bold flex items-center gap-2 text-gray-900 dark:text-white">
                        <Users class="w-5 h-5" /> Manufacturing Staff
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-zinc-800/50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Name</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Current Role</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Change Role</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            <tr v-for="user in staffList" :key="user.id">
                                <td class="px-6 py-4 text-gray-900 dark:text-white">{{ user.name }}</td>
                                <td class="px-6 py-4 capitalize text-gray-500 dark:text-gray-400">
                                    {{ user.manufacturing_role?.replace('_', ' ') || 'Unassigned' }}
                                </td>
                                <td class="px-6 py-4">
                                    <select v-model="user.newRole" @change="updateStaffRole(user.id, user.newRole)"
                                        class="border border-gray-300 dark:border-zinc-700 rounded-lg p-1 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                                        <option value="">Select Role</option>
                                        <option value="knitting_yarn">Knitting Yarn</option>
                                        <option value="dyeing_color">Dyeing Color</option>
                                        <option value="dyeing_fabric_softener">Dyeing Fabric Softener</option>
                                        <option value="dyeing_squeezer">Dyeing Squeezer</option>
                                        <option value="dyeing_ironing">Dyeing Ironing</option>
                                        <option value="dyeing_forming">Dyeing Forming</option>
                                        <option value="dyeing_packaging">Dyeing Packaging</option>
                                        <option value="maintenance_checker">Maintenance Checker</option>
                                        <option value="checker_quality">Checker Quality</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-8 flex gap-4">
                <Link :href="route('man.manager.production')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold text-sm shadow-md transition">
                    View Production Orders
                </Link>
                <Link :href="route('man.manager.rejected')"
                    class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-bold text-sm shadow-md transition">
                    View Rejected Items
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>