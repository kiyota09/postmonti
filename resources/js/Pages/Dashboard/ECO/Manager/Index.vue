<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    BuildingOfficeIcon, CheckIcon, XMarkIcon, ClockIcon,
    ShieldCheckIcon, UserGroupIcon, BanknotesIcon,
    ExclamationTriangleIcon, InboxIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    pendingCompanies: { type: Array, default: () => [] },
    verifiedCompanies: { type: Array, default: () => [] }
});

const showModal = ref(false);
const modalConfig = ref({ title: '', message: '', confirmText: '', confirmColor: '', action: null });
const activeTab = ref('approvals');

const currentList = computed(() => {
    return activeTab.value === 'approvals' ? props.pendingCompanies : props.verifiedCompanies;
});

const openConfirmation = (title, message, confirmText, color, action) => {
    modalConfig.value = { title, message, confirmText, confirmColor: color, action };
    showModal.value = true;
};

const executeUpdate = (id, status) => {
    router.patch(route('eco.manager.clients.status.update', id), { status }, {
        preserveScroll: true,
        onSuccess: () => showModal.value = false
    });
};

const handleApprove = (company) => {
    openConfirmation('Approve Business', `Activate ${company.company_name}?`, 'Confirm Approval', 'bg-green-600', () => executeUpdate(company.id, 'active'));
};

const handleReject = (company) => {
    openConfirmation('Reject Application', `Reject ${company.company_name}?`, 'Reject Business', 'bg-red-600', () => executeUpdate(company.id, 'rejected'));
};

const handleToggleStatus = (company) => {
    const isCurrentlyActive = company.status.toLowerCase() === 'active';
    const nextStatus = isCurrentlyActive ? 'suspended' : 'active';
    openConfirmation(`${isCurrentlyActive ? 'Suspend' : 'Activate'} Account`, `Change status for ${company.company_name}?`, 'Confirm', isCurrentlyActive ? 'bg-red-600' : 'bg-green-600', () => executeUpdate(company.id, nextStatus));
};

const stats = computed(() => [
    { label: 'Pending', value: props.pendingCompanies.length, icon: ClockIcon, color: 'text-orange-500' },
    { label: 'Verified', value: props.verifiedCompanies.filter(c => c.status.toLowerCase() === 'active').length, icon: ShieldCheckIcon, color: 'text-green-600' },
    { label: 'Total Clients', value: props.pendingCompanies.length + props.verifiedCompanies.length, icon: UserGroupIcon, color: 'text-blue-600' },
    { label: 'System Credit', value: '₱4.2M', icon: BanknotesIcon, color: 'text-purple-600' },
]);
</script>

<template>

    <Head title="Partner Verification" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-8 p-4 lg:p-10">
            <h1 class="text-4xl font-black text-gray-900 dark:text-white uppercase">Partner <span
                    class="text-indigo-600">Verification</span></h1>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div v-for="stat in stats" :key="stat.label"
                    class="p-7 rounded-[2.5rem] bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ stat.label }}</p>
                    <div class="flex items-center justify-between">
                        <h3 class="text-3xl font-black text-gray-900 dark:text-white">{{ stat.value }}</h3>
                        <component :is="stat.icon" :class="stat.color" class="h-6 w-6" />
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-gray-50 flex gap-4">
                    <button @click="activeTab = 'approvals'"
                        :class="activeTab === 'approvals' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-500'"
                        class="px-6 py-2 rounded-xl text-[10px] font-black uppercase">Pending ({{
                            pendingCompanies.length }})</button>
                    <button @click="activeTab = 'verified'"
                        :class="activeTab === 'verified' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-500'"
                        class="px-6 py-2 rounded-xl text-[10px] font-black uppercase">Directory ({{
                            verifiedCompanies.length }})</button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 text-[10px] font-black uppercase text-gray-400">
                                <th class="px-8 py-5">Entity</th>
                                <th class="px-8 py-5">Status</th>
                                <th class="px-8 py-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="currentList.length === 0">
                                <td colspan="3"
                                    class="px-8 py-20 text-center text-gray-300 uppercase font-black italic">No records
                                    found</td>
                            </tr>
                            <tr v-for="company in currentList" :key="company.id"
                                class="hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6">
                                    <p class="text-sm font-black uppercase">{{ company.company_name }}</p>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-widest">{{ company.email }}
                                    </p>
                                </td>
                                <td class="px-8 py-6">
                                    <span :class="{
                                        'bg-orange-50 text-orange-600': company.status.toLowerCase() === 'pending',
                                        'bg-green-50 text-green-600': company.status.toLowerCase() === 'active',
                                        'bg-red-50 text-red-600': ['suspended', 'rejected'].includes(company.status.toLowerCase())
                                    }"
                                        class="px-3 py-1 rounded-lg text-[9px] font-black uppercase border border-current">
                                        {{ company.status }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div v-if="activeTab === 'approvals'" class="flex justify-end gap-2">
                                        <button @click="handleApprove(company)"
                                            class="p-2 bg-green-50 text-green-600 rounded-lg">
                                            <CheckIcon class="h-4 w-4" />
                                        </button>
                                        <button @click="handleReject(company)"
                                            class="p-2 bg-red-50 text-red-600 rounded-lg">
                                            <XMarkIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <button v-else @click="handleToggleStatus(company)"
                                        :class="company.status.toLowerCase() === 'active' ? 'bg-red-600' : 'bg-green-600'"
                                        class="px-4 py-2 text-white rounded-lg text-[9px] font-black uppercase">
                                        {{ company.status.toLowerCase() === 'active' ? 'Suspend' : 'Activate' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="showModal"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-900 w-full max-w-md rounded-[2.5rem] p-8">
                <h3 class="text-xl font-black uppercase mb-4">{{ modalConfig.title }}</h3>
                <p class="text-sm text-gray-500 mb-8">{{ modalConfig.message }}</p>
                <div class="flex gap-3">
                    <button @click="showModal = false"
                        class="flex-1 py-4 bg-gray-100 rounded-2xl text-[10px] font-black uppercase">Cancel</button>
                    <button @click="modalConfig.action" :class="modalConfig.confirmColor"
                        class="flex-1 py-4 text-white rounded-2xl text-[10px] font-black uppercase">{{
                            modalConfig.confirmText }}</button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>