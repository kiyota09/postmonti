<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    Building2, CheckCircle, XCircle, Clock, Search,
    ChevronDown, X, Plus, Trash2, FileText,
    ShieldCheck, AlertCircle, Users, ClipboardList,
    Eye, ArrowDownToLine, Zap,
} from 'lucide-vue-next';

const props = defineProps({
    isManager: { type: Boolean, default: false },
    registrations: { type: Array, default: () => [] },
    myRegistration: { type: Object, default: null },
});

// ── Search / filter ───────────────────────────────────────────────────────────
const search = ref('');
const statusTab = ref('all');

const TABS = [
    { key: 'all', label: 'All' },
    { key: 'pending', label: 'Pending' },
    { key: 'approved', label: 'Approved' },
    { key: 'rejected', label: 'Rejected' },
];

const filtered = computed(() => {
    let list = props.registrations;
    if (search.value) {
        const q = search.value.toLowerCase();
        list = list.filter(r =>
            r.business_name.toLowerCase().includes(q) ||
            r.email.toLowerCase().includes(q) ||
            r.representative_name.toLowerCase().includes(q)
        );
    }
    if (statusTab.value !== 'all') list = list.filter(r => r.status === statusTab.value);
    return list;
});

// ── Stats ─────────────────────────────────────────────────────────────────────
const stats = computed(() => [
    { label: 'Total Vendors', value: props.registrations.length, icon: Users, color: 'text-blue-600', bg: 'bg-blue-50 dark:bg-blue-900/20' },
    { label: 'Pending Review', value: props.registrations.filter(r => r.status === 'pending').length, icon: Clock, color: 'text-amber-500', bg: 'bg-amber-50 dark:bg-amber-900/20' },
    { label: 'Official', value: props.registrations.filter(r => r.status === 'approved').length, icon: CheckCircle, color: 'text-emerald-600', bg: 'bg-emerald-50 dark:bg-emerald-900/20' },
    { label: 'Rejected', value: props.registrations.filter(r => r.status === 'rejected').length, icon: XCircle, color: 'text-red-500', bg: 'bg-red-50 dark:bg-red-900/20' },
]);

// ── Status helpers ────────────────────────────────────────────────────────────
const statusBadge = (s) => ({
    pending: 'bg-amber-50 text-amber-600 border-amber-200',
    approved: 'bg-emerald-50 text-emerald-600 border-emerald-200',
    rejected: 'bg-red-50 text-red-600 border-red-200',
}[s] ?? 'bg-gray-50 text-gray-400 border-gray-200');

const statusDot = (s) => ({
    pending: 'bg-amber-400',
    approved: 'bg-emerald-500',
    rejected: 'bg-red-500',
}[s] ?? 'bg-gray-300');

// ── Detail / action modal ─────────────────────────────────────────────────────
const showModal = ref(false);
const modalMode = ref('view'); // 'view' | 'reject' | 'requirements' | 'approve'
const selectedVendor = ref(null);
const processing = ref(false);
const requirementLines = ref([{ requirement_name: '', description: '', value: '' }]);
const rejectionReason = ref('');

const openModal = (vendor, mode = 'view') => {
    selectedVendor.value = vendor;
    modalMode.value = mode;
    rejectionReason.value = '';

    // Load existing requirements from DB or provide a blank slate
    requirementLines.value = vendor.requirements?.length
        ? vendor.requirements.map(r => ({ requirement_name: r.requirement_name, description: r.description ?? '', value: r.value ?? '' }))
        : [{ requirement_name: '', description: '', value: '' }];

    showModal.value = true;
};

// ── Approve & Set Requirements ────────────────────────────────────────────────
const submitApprove = () => {
    const validReqs = requirementLines.value.filter(r => r.requirement_name.trim() !== '');
    processing.value = true;

    // Using absolute path to prevent Ziggy routing issues
    router.post(`/dashboard/scm/vendor/registrations/${selectedVendor.value.id}/approve`, {
        requirements: validReqs,
    }, {
        preserveScroll: true,
        onSuccess: () => { showModal.value = false; },
        onFinish: () => (processing.value = false),
    });
};

// ── Reject ────────────────────────────────────────────────────────────────────
const submitReject = () => {
    if (!rejectionReason.value.trim()) return;
    processing.value = true;
    router.post(`/dashboard/scm/vendor/registrations/${selectedVendor.value.id}/reject`, {
        rejection_reason: rejectionReason.value,
    }, {
        preserveScroll: true,
        onSuccess: () => { showModal.value = false; },
        onFinish: () => (processing.value = false),
    });
};

// ── Requirements (Update Only) ────────────────────────────────────────────────
const addReqLine = () => requirementLines.value.push({ requirement_name: '', description: '', value: '' });
const removeReqLine = (i) => { if (requirementLines.value.length > 1) requirementLines.value.splice(i, 1); };

const submitRequirements = () => {
    const validReqs = requirementLines.value.filter(r => r.requirement_name.trim() !== '');
    processing.value = true;
    router.post(`/dashboard/scm/vendor/registrations/${selectedVendor.value.id}/requirements`, {
        requirements: validReqs,
    }, {
        preserveScroll: true,
        onSuccess: () => { showModal.value = false; },
        onFinish: () => (processing.value = false),
    });
};
</script>

<template>

    <Head title="Vendor Management - SCM" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                <div class="space-y-1">
                    <div
                        class="flex items-center gap-2 text-blue-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Zap class="h-3 w-3 fill-current" /> Supply Chain Management
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Vendor <span class="text-blue-600">Registry</span>
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Manage vendor registrations, approvals, and
                        compliance requirements from the database.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="stat in stats" :key="stat.label"
                    class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm flex items-center justify-between hover:shadow-md transition-all">
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ stat.label }}</p>
                        <p class="text-3xl font-black text-gray-900 dark:text-white mt-1 tracking-tighter italic">{{
                            stat.value }}</p>
                    </div>
                    <div :class="[stat.bg, stat.color]"
                        class="h-14 w-14 rounded-2xl flex items-center justify-center flex-shrink-0">
                        <component :is="stat.icon" class="h-7 w-7" />
                    </div>
                </div>
            </div>

            <div v-if="isManager"
                class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div
                    class="p-8 border-b border-gray-50 dark:border-gray-800 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                    <div class="flex p-1.5 bg-gray-50 dark:bg-gray-950 rounded-2xl overflow-x-auto no-scrollbar gap-1">
                        <button v-for="tab in TABS" :key="tab.key" @click="statusTab = tab.key"
                            :class="statusTab === tab.key
                                ? 'bg-white dark:bg-gray-800 shadow-sm text-blue-600' : 'text-gray-400 hover:text-gray-600'"
                            class="px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest whitespace-nowrap transition-all">
                            {{ tab.label }}
                        </button>
                    </div>

                    <div class="relative w-full lg:w-72 flex-shrink-0 group">
                        <Search
                            class="absolute left-4 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400 group-focus-within:text-blue-600 transition-colors" />
                        <input v-model="search" type="text" placeholder="Search vendor, email..."
                            class="w-full pl-11 pr-4 py-3 rounded-2xl bg-gray-50 dark:bg-gray-950 border border-gray-100 dark:border-gray-800 text-[10px] font-black uppercase tracking-widest text-gray-700 dark:text-gray-300 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all" />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead
                            class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-8 py-5">Business & Contact</th>
                                <th class="px-8 py-5">Representative</th>
                                <th class="px-8 py-5 text-center">Status</th>
                                <th class="px-8 py-5 text-center">Requirements</th>
                                <th class="px-8 py-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="vendor in filtered" :key="vendor.id"
                                class="group hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="h-11 w-11 rounded-[1rem] bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 border border-blue-100 dark:border-blue-800 flex-shrink-0">
                                            <Building2 class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p
                                                class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-tighter italic">
                                                {{ vendor.business_name }}
                                            </p>
                                            <p
                                                class="text-[10px] font-bold text-gray-400 uppercase tracking-widest italic">
                                                {{ vendor.email }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300">{{
                                        vendor.representative_name }}</p>
                                    <p class="text-[10px] text-gray-400 font-bold">{{ vendor.phone_number }}</p>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <span :class="statusDot(vendor.status)"
                                            class="w-1.5 h-1.5 rounded-full flex-shrink-0"></span>
                                        <span :class="statusBadge(vendor.status)"
                                            class="px-3 py-1 rounded-xl text-[9px] font-black uppercase tracking-widest border">
                                            {{ vendor.status === 'approved' ? 'Official' : vendor.status }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <button @click="openModal(vendor, 'requirements')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[9px] font-black uppercase tracking-widest text-slate-500 hover:text-blue-600 hover:border-blue-300 hover:bg-blue-50 transition-all">
                                        <ClipboardList class="h-3 w-3" />
                                        {{ vendor.requirements?.length ?? 0 }} req.
                                    </button>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openModal(vendor, 'view')"
                                            class="p-2 rounded-xl text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all"
                                            title="View details">
                                            <Eye class="h-4 w-4" />
                                        </button>
                                        <template v-if="vendor.status === 'pending'">
                                            <button @click="openModal(vendor, 'approve')"
                                                class="px-4 py-2 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 text-[9px] font-black uppercase tracking-widest hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition-all disabled:opacity-40">
                                                Approve
                                            </button>
                                            <button @click="openModal(vendor, 'reject')"
                                                class="px-4 py-2 rounded-xl bg-red-50 text-red-500 border border-red-200 text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white hover:border-red-500 transition-all">
                                                Reject
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filtered.length === 0">
                                <td colspan="5" class="px-8 py-20 text-center">
                                    <Building2 class="h-10 w-10 text-gray-200 mx-auto mb-3" />
                                    <p class="text-[10px] font-black text-gray-300 uppercase tracking-widest italic">No
                                        vendor registrations found
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else class="max-w-2xl">
                <div v-if="myRegistration"
                    class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm p-10 space-y-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter italic">
                            {{
                                myRegistration.business_name }}</h3>
                        <span :class="statusBadge(myRegistration.status)"
                            class="px-4 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border">
                            {{ myRegistration.status === 'approved' ? 'Official Vendor' : myRegistration.status }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">
                                Representative</p>
                            <p class="font-bold text-gray-700 dark:text-gray-300">{{ myRegistration.representative_name
                            }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Contact</p>
                            <p class="font-bold text-gray-700 dark:text-gray-300">{{ myRegistration.phone_number }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Address</p>
                            <p class="font-bold text-gray-700 dark:text-gray-300">{{ myRegistration.address }}</p>
                        </div>
                    </div>
                    <div v-if="myRegistration.status === 'rejected' && myRegistration.rejection_reason"
                        class="p-4 bg-red-50 dark:bg-red-900/20 rounded-2xl border border-red-200 dark:border-red-800">
                        <p class="text-[10px] font-black text-red-500 uppercase tracking-widest mb-1">Rejection Reason
                        </p>
                        <p class="text-sm text-red-600 dark:text-red-400">{{ myRegistration.rejection_reason }}</p>
                    </div>
                    <div v-if="myRegistration.requirements?.length" class="space-y-2">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Database Requirements
                        </p>
                        <div v-for="req in myRegistration.requirements" :key="req.id"
                            class="flex items-start justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
                            <div>
                                <p class="text-xs font-black text-gray-700 dark:text-gray-300 uppercase">{{
                                    req.requirement_name }}
                                </p>
                                <p v-if="req.description" class="text-[10px] text-gray-400 mt-0.5">{{ req.description }}
                                </p>
                            </div>
                            <span v-if="req.value"
                                class="text-[10px] font-black text-blue-600 bg-blue-50 px-2 py-0.5 rounded-lg">{{
                                    req.value }}</span>
                        </div>
                    </div>
                </div>
                <div v-else
                    class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm p-16 text-center">
                    <Building2 class="h-12 w-12 text-gray-200 mx-auto mb-4" />
                    <p class="text-[10px] font-black text-gray-300 uppercase tracking-widest italic">No registration on
                        record for
                        your account.</p>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/60 backdrop-blur-md"
                @click.self="showModal = false">
                <div
                    class="bg-white dark:bg-gray-900 w-full max-w-2xl rounded-[3rem] border border-gray-100 dark:border-gray-800 shadow-2xl overflow-hidden">

                    <div class="px-10 py-8 bg-blue-600 text-white flex justify-between items-center">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 rounded-2xl bg-white/10 flex items-center justify-center">
                                <component
                                    :is="modalMode === 'reject' ? XCircle : (modalMode === 'requirements' || modalMode === 'approve') ? ClipboardList : Eye"
                                    class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-2xl font-black uppercase tracking-tighter italic">
                                    {{ modalMode === 'reject' ? 'Reject Vendor' : modalMode === 'requirements' ?
                                        'SetRequirements' : modalMode === 'approve' ? 'Approve Vendor' : 'Vendor Details' }}
                                </h3>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80">{{
                                    selectedVendor?.business_name }}</p>
                            </div>
                        </div>
                        <button @click="showModal = false"
                            class="p-3 bg-white/10 rounded-2xl hover:bg-white/20 transition-all">
                            <X class="h-6 w-6" />
                        </button>
                    </div>

                    <div v-if="modalMode === 'view'" class="p-10 space-y-6 max-h-[60vh] overflow-y-auto no-scrollbar">
                        <div class="grid grid-cols-2 gap-5">
                            <div v-for="field in [
                                { label: 'Business Name', value: selectedVendor.business_name },
                                { label: 'Representative', value: selectedVendor.representative_name },
                                { label: 'Email', value: selectedVendor.email },
                                { label: 'Phone', value: selectedVendor.phone_number },
                                { label: 'Submitted', value: selectedVendor.created_at },
                                { label: 'Status', value: selectedVendor.status === 'approved' ? 'Official' : selectedVendor.status },
                            ]" :key="field.label"
                                class="p-4 bg-gray-50 dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ field.label
                                }}</p>
                                <p class="text-sm font-black text-gray-900 dark:text-white mt-1 uppercase italic">{{
                                    field.value
                                }}</p>
                            </div>
                            <div
                                class="col-span-2 p-4 bg-gray-50 dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Address</p>
                                <p class="text-sm font-bold text-gray-700 dark:text-gray-300 mt-1">{{
                                    selectedVendor.address }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="modalMode === 'reject'" class="p-10 space-y-5">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Provide a reason for rejecting <strong
                                class="text-gray-900 dark:text-white">{{ selectedVendor.business_name }}</strong>. This
                            will be
                            visible to the vendor.</p>
                        <div>
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Rejection
                                Reason
                                *</label>
                            <textarea v-model="rejectionReason" rows="4"
                                placeholder="Explain why this registration is being rejected..."
                                class="mt-2 w-full px-4 py-3 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl focus:outline-none focus:ring-2 focus:ring-red-500/20 text-gray-700 dark:text-gray-300 resize-none" />
                        </div>
                    </div>

                    <div v-else-if="modalMode === 'requirements' || modalMode === 'approve'"
                        class="p-10 space-y-5 max-h-[60vh] overflow-y-auto no-scrollbar">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <span v-if="modalMode === 'approve'">To make <strong
                                    class="text-gray-900 dark:text-white">{{
                                        selectedVendor.business_name }}</strong> an Official Vendor, define their required
                                compliance checks below.</span>
                            <span v-else>Update compliance requirements for <strong
                                    class="text-gray-900 dark:text-white">{{
                                        selectedVendor.business_name }}</strong> in the database.</span>
                        </p>
                        <div class="space-y-3">
                            <div v-for="(req, i) in requirementLines" :key="i"
                                class="grid grid-cols-3 gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
                                <div>
                                    <label
                                        class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Requirement
                                        Name</label>
                                    <input v-model="req.requirement_name" type="text" placeholder="e.g. ISO 9001"
                                        class="mt-1 w-full px-3 py-2 text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-gray-700 dark:text-gray-300" />
                                </div>
                                <div>
                                    <label
                                        class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Description</label>
                                    <input v-model="req.description" type="text" placeholder="Optional details"
                                        class="mt-1 w-full px-3 py-2 text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-gray-700 dark:text-gray-300" />
                                </div>
                                <div class="flex gap-2 items-end">
                                    <div class="flex-1">
                                        <label
                                            class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Value/Target</label>
                                        <input v-model="req.value" type="text" placeholder="e.g. Certified"
                                            class="mt-1 w-full px-3 py-2 text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-gray-700 dark:text-gray-300" />
                                    </div>
                                    <button @click="removeReqLine(i)"
                                        class="p-2 rounded-xl text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all flex-shrink-0">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button @click="addReqLine"
                            class="w-full py-2.5 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700 text-[10px] font-black uppercase tracking-widest text-gray-400 hover:border-blue-400 hover:text-blue-600 hover:bg-blue-50/50 transition-all flex items-center justify-center gap-2">
                            <Plus class="h-4 w-4" /> Add Requirement
                        </button>
                    </div>

                    <div
                        class="px-10 py-6 bg-gray-50/50 dark:bg-gray-800/30 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                        <button @click="showModal = false"
                            class="px-8 py-3 rounded-2xl bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-[10px] font-black uppercase tracking-widest text-gray-600 hover:bg-gray-100 transition-all">
                            {{ modalMode === 'view' ? 'Close' : 'Cancel' }}
                        </button>

                        <button v-if="modalMode === 'reject'" @click="submitReject"
                            :disabled="processing || !rejectionReason.trim()"
                            class="px-10 py-3 rounded-2xl bg-red-600 text-white text-[10px] font-black uppercase tracking-widest shadow-xl hover:scale-105 active:scale-95 transition-all disabled:opacity-40">
                            Confirm Rejection
                        </button>

                        <button v-if="modalMode === 'approve'" @click="submitApprove" :disabled="processing"
                            class="px-10 py-3 rounded-2xl bg-emerald-600 text-white text-[10px] font-black uppercase tracking-widest shadow-xl hover:scale-105 active:scale-95 transition-all disabled:opacity-40">
                            Approve & Make Official
                        </button>

                        <button v-if="modalMode === 'requirements'" @click="submitRequirements" :disabled="processing"
                            class="px-10 py-3 rounded-2xl bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest shadow-xl hover:scale-105 active:scale-95 transition-all disabled:opacity-40">
                            Save Requirements
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>