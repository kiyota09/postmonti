<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { CheckCircle, XCircle } from 'lucide-vue-next';

const props = defineProps({
    pendingApprovals: Array
});

const processApproval = (id, action) => {
    router.post(route('crm.approval.process', id), { action, reason: action === 'reject' ? prompt('Rejection reason:') : null });
};
</script>

<template>
    <AuthenticatedLayout title="Approval Queue">

        <Head title="Approval Queue" />
        <div class="p-6 max-w-7xl mx-auto">
            <h1 class="text-2xl font-black mb-6">Pending Approvals</h1>
            <div class="space-y-4">
                <div v-for="approval in pendingApprovals" :key="approval.id"
                    class="bg-white p-4 rounded-lg shadow-sm border">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-semibold">Action: {{ approval.action }}</p>
                            <p class="text-sm text-gray-600">Requested by: {{ approval.user.name }}</p>
                            <p class="text-sm text-gray-500">Data: {{ JSON.stringify(approval.data) }}</p>
                            <p class="text-xs text-gray-400">{{ new Date(approval.created_at).toLocaleString() }}</p>
                        </div>
                        <div class="flex gap-2">
                            <button @click="processApproval(approval.id, 'approve')"
                                class="bg-green-600 text-white px-3 py-1 rounded text-sm flex items-center gap-1">
                                <CheckCircle class="w-4 h-4" /> Approve
                            </button>
                            <button @click="processApproval(approval.id, 'reject')"
                                class="bg-red-600 text-white px-3 py-1 rounded text-sm flex items-center gap-1">
                                <XCircle class="w-4 h-4" /> Reject
                            </button>
                        </div>
                    </div>
                </div>
                <div v-if="!pendingApprovals.length" class="text-center py-10 text-gray-500">
                    No pending approvals.
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>