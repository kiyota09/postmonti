<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';

const props = defineProps({
    auth: Object,
    employees: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            present: 0,
            on_leave: 0,
            leaveBalance: 0,
            assignedTasks: 0
        })
    },
    auditLogs: { type: Array, default: () => [] }
});

const getImageUrl = (path) => {
    if (!path) return null;
    if (path.startsWith('http')) return path;
    return `/storage/${path}`;
};

const successMessage = ref(null);
const page = usePage();

watch(() => page.props.flash?.message, (msg) => {
    if (msg) {
        successMessage.value = msg;
        setTimeout(() => successMessage.value = null, 4000);
    }
}, { immediate: true });

const search = ref('');
const selectedDept = ref('ALL');
const showDeactivatedView = ref(false);
const isModalOpen = ref(false);
const isAuditLogOpen = ref(false);
const isReasonModalOpen = ref(false); // Modal for deactivation reason
const deactivationReason = ref('');
const selectedUserPhoto = ref(null);

const departments = ['ALL', 'HRM', 'SCM', 'FIN', 'MAN', 'INV', 'ORD', 'WAR', 'CRM', 'ECO'];

const form = useForm({
    id: null,
    name: '',
    email: '',
    role: '',
    position: '',
    department: '',
    is_active: 1,
});

const filteredByDept = computed(() => {
    let list = props.employees;
    const statusTarget = showDeactivatedView.value ? 0 : 1;
    list = list.filter(u => Number(u.is_active) === statusTarget);
    if (selectedDept.value !== 'ALL') {
        list = list.filter(u => u.role?.toUpperCase() === selectedDept.value);
    }
    if (search.value) {
        const s = search.value.toLowerCase();
        list = list.filter(u =>
            u.name.toLowerCase().includes(s) ||
            u.email.toLowerCase().includes(s) ||
            u.role.toLowerCase().includes(s)
        );
    }
    return list;
});

const managers = computed(() => filteredByDept.value.filter(u => u.position?.toLowerCase() === 'manager'));
const staff = computed(() => filteredByDept.value.filter(u => u.position?.toLowerCase() !== 'manager'));

const openDetails = (user) => {
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.position = user.position;
    form.department = user.department;
    form.is_active = user.is_active;
    selectedUserPhoto.value = getImageUrl(user.profile_photo_path || user.profile_photo_url);
    isModalOpen.value = true;
};

const submitUpdate = () => {
    form.post(route('hrm.employees.update', form.id), {
        preserveScroll: true,
        onSuccess: () => isModalOpen.value = false,
    });
};

// Trigger logic for Toggle Button
const toggleAccountStatus = () => {
    if (form.is_active) {
        isReasonModalOpen.value = true; // Open second modal for deactivation
    } else {
        if (confirm(`Are you sure you want to reactivate ${form.name}?`)) {
            sendToggleRequest('Account Reactivation');
        }
    }
};

const confirmDeactivationWithReason = () => {
    if (!deactivationReason.value.trim()) {
        alert("Please provide a reason for deactivation.");
        return;
    }
    sendToggleRequest(deactivationReason.value);
};

const sendToggleRequest = (reason) => {
    router.delete(route('hrm.employees.destroy', form.id), {
        data: { reason: reason }, // Send real reason data to controller
        preserveScroll: true,
        onSuccess: () => {
            isModalOpen.value = false;
            isReasonModalOpen.value = false;
            deactivationReason.value = '';
        },
    });
};

const getStatusClass = (status) => status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';

onMounted(() => {
    if (typeof Echo !== 'undefined') {
        Echo.channel('users').listen('UserUpdated', () => {
            router.reload({ preserveScroll: true, only: ['employees', 'stats', 'auditLogs'] });
        });
    }
});

onUnmounted(() => {
    if (typeof Echo !== 'undefined') Echo.leave('users');
});
</script>

<template>

    <Head title="HRM - Workforce Directory" />

    <AuthenticatedLayout>
        <Transition enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="successMessage"
                class="fixed top-5 right-5 z-[100] max-w-sm w-full bg-white shadow-2xl rounded-2xl border-l-4 border-green-500 p-4 flex items-center gap-3">
                <div class="bg-green-100 p-2 rounded-full text-green-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <p class="text-sm text-gray-600 font-bold">{{ successMessage }}</p>
            </div>
        </Transition>

        <template #header>
            <div class="py-4">
                <h2 class="font-bold text-3xl text-gray-800 leading-tight">Workforce Directory</h2>
                <p class="text-sm text-gray-500 font-medium">
                    {{ showDeactivatedView ? 'Viewing Deactivated Directory' : 'Viewing Active Directory' }}
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <div class="flex flex-col md:flex-row gap-6 justify-between items-center">
                    <div class="w-full max-w-lg">
                        <div class="relative">
                            <input v-model="search" type="text" placeholder="Search by name, email, or role..."
                                class="w-full pl-12 pr-4 py-4 border-gray-200 rounded-2xl shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white" />
                            <span class="absolute left-4 top-4 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="bg-white px-6 py-3 rounded-2xl border border-gray-100 shadow-sm text-center">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Listed</p>
                            <p class="text-xl font-bold text-gray-800">{{ filteredByDept.length }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <button @click="isAuditLogOpen = true"
                            class="px-5 py-2.5 bg-white text-gray-700 text-sm font-bold rounded-xl hover:bg-gray-50 transition flex items-center gap-2 border border-gray-200 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Audit Logs
                        </button>

                        <button @click="showDeactivatedView = !showDeactivatedView"
                            :class="['px-5 py-2.5 rounded-xl text-sm font-black uppercase transition-all border shadow-sm',
                                showDeactivatedView ? 'bg-indigo-600 text-white border-indigo-700' : 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100']">
                            {{ showDeactivatedView ? 'Show Active Staff' : 'Deactivated Accounts' }}
                        </button>
                    </div>
                </div>

                <div
                    class="bg-white p-2.5 rounded-3xl shadow-sm border border-gray-100 flex overflow-x-auto gap-3 no-scrollbar">
                    <button v-for="dept in departments" :key="dept" @click="selectedDept = dept"
                        :class="['px-8 py-3 rounded-2xl text-xs font-black transition-all uppercase tracking-widest whitespace-nowrap',
                            selectedDept === dept ? 'bg-indigo-600 text-white shadow-xl translate-y-[-2px]' : 'text-gray-400 hover:bg-gray-50']">
                        {{ dept }}
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    <div v-for="(list, title) in { 'Managers': managers, 'General Staff': staff }" :key="title"
                        class="space-y-6">
                        <div class="flex items-center justify-between border-b-2 border-indigo-50 pb-3">
                            <h3 class="text-lg font-black text-indigo-900 uppercase tracking-widest">{{ title }} ({{
                                list.length
                                }})</h3>
                            <span v-if="showDeactivatedView"
                                class="text-[10px] font-black text-red-600 uppercase tracking-widest bg-red-100 px-3 py-1 rounded-full">Archive
                                Mode</span>
                        </div>

                        <div v-if="list.length === 0"
                            class="text-center py-24 bg-gray-50 rounded-[2.5rem] border-2 border-dashed border-gray-200 text-gray-400 text-sm italic">
                            No records found for {{ title.toLowerCase() }} in {{ selectedDept }}.
                        </div>

                        <div v-for="user in list" :key="user.id"
                            class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-between hover:border-indigo-400 hover:shadow-xl transition-all group">
                            <div class="flex items-center gap-5">
                                <div class="h-16 w-16 flex-shrink-0 relative">
                                    <img v-if="user.profile_photo_path || user.profile_photo_url"
                                        :src="getImageUrl(user.profile_photo_path || user.profile_photo_url)"
                                        class="h-full w-full rounded-2xl object-cover border-2 border-white shadow-md" />
                                    <div v-else
                                        class="h-full w-full bg-indigo-600 rounded-2xl flex items-center justify-center text-white font-black text-2xl shadow-md">
                                        {{ user.name.charAt(0) }}</div>
                                    <div v-if="!user.is_active"
                                        class="absolute -top-1 -right-1 h-5 w-5 bg-red-500 border-2 border-white rounded-full">
                                    </div>
                                </div>
                                <div>
                                    <h4
                                        class="font-bold text-gray-900 text-lg leading-tight group-hover:text-indigo-600 transition-colors">
                                        {{ user.name }}</h4>
                                    <p class="text-[11px] text-gray-400 font-bold uppercase tracking-widest">{{
                                        user.role }} •
                                        {{ user.email }}</p>
                                    <div class="mt-2 flex items-center gap-2">
                                        <span
                                            :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter', getStatusClass(user.is_active)]">
                                            {{ user.is_active ? 'Active' : 'Deactivated' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <button @click="openDetails(user)"
                                class="text-xs font-black uppercase text-indigo-600 border-2 border-indigo-50 px-6 py-3 rounded-2xl hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-all shadow-sm">Manage</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isModalOpen"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gray-900/40 backdrop-blur-md">
            <div
                class="bg-white rounded-[2.5rem] shadow-2xl max-w-md w-full overflow-hidden transform transition-all border border-white/20">
                <form @submit.prevent="submitUpdate">
                    <div class="bg-indigo-700 p-10 text-white relative">
                        <button type="button" @click="isModalOpen = false"
                            class="absolute top-6 right-6 text-white/70 hover:text-white text-3xl leading-none">&times;</button>
                        <div class="flex items-center gap-8 relative">
                            <div class="h-24 w-24 flex-shrink-0">
                                <img v-if="selectedUserPhoto" :src="selectedUserPhoto"
                                    class="h-full w-full rounded-3xl object-cover border-4 border-white/20 shadow-2xl" />
                                <div v-else
                                    class="h-full w-full bg-white/20 rounded-3xl flex items-center justify-center text-4xl font-black border-4 border-white/20 shadow-2xl">
                                    {{ form.name.charAt(0) }}</div>
                            </div>
                            <div>
                                <h2 class="text-3xl font-black leading-tight">{{ form.name }}</h2>
                                <p class="text-indigo-200 text-sm font-bold uppercase tracking-widest mt-2">{{ form.role
                                    }} • {{
                                    form.position }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-10 space-y-8">
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Full
                                    Name</label>
                                <input v-model="form.name" type="text"
                                    class="w-full border-gray-100 rounded-2xl py-4 text-sm font-bold bg-gray-50 focus:ring-2 focus:ring-indigo-500 transition-all" />
                            </div>
                            <div class="grid grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label
                                        class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Department</label>
                                    <select v-model="form.role"
                                        class="w-full border-gray-100 rounded-2xl py-4 text-sm font-bold bg-gray-50">
                                        <option v-for="dept in departments.filter(d => d !== 'ALL')" :key="dept">{{ dept
                                            }}
                                        </option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <label
                                        class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Position</label>
                                    <select v-model="form.position"
                                        class="w-full border-gray-100 rounded-2xl py-4 text-sm font-bold bg-gray-50">
                                        <option value="manager">Manager</option>
                                        <option value="staff">Staff</option>
                                        <option value="trainee">Trainee</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="pt-8 border-t border-gray-100 flex flex-col gap-4">
                            <button type="submit" :disabled="form.processing"
                                class="w-full py-5 bg-indigo-600 text-white rounded-[1.5rem] text-xs font-black uppercase hover:bg-indigo-700 shadow-xl shadow-indigo-100 transition-all">Save
                                Profile Changes</button>
                            <button type="button" @click="toggleAccountStatus"
                                :class="['w-full py-5 rounded-[1.5rem] text-xs font-black uppercase transition-all border-2',
                                    form.is_active ? 'bg-red-50 text-red-600 border-red-100 hover:bg-red-600 hover:text-white' : 'bg-green-50 text-green-600 border-green-100 hover:bg-green-600 hover:text-white']">
                                {{ form.is_active ? 'Terminate Employee Access' : 'Restore Employee Access' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="isReasonModalOpen"
            class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-md">
            <div class="bg-white rounded-[2.5rem] shadow-2xl max-w-md w-full overflow-hidden border border-white">
                <div class="bg-red-600 p-8 text-white">
                    <h2 class="text-xl font-black uppercase tracking-tight">Termination Reason</h2>
                    <p class="text-red-100 text-xs mt-1">Provide a reason for deactivating this account (e.g.,
                        Resignation or
                        Violation).</p>
                </div>
                <div class="p-8 space-y-4">
                    <textarea v-model="deactivationReason" rows="4" placeholder="Type reason for termination..."
                        class="w-full border-gray-100 rounded-2xl bg-gray-50 text-sm font-bold focus:ring-red-500 focus:border-red-500"></textarea>

                    <div class="flex gap-3 pt-2">
                        <button @click="isReasonModalOpen = false"
                            class="flex-1 py-4 text-gray-400 font-bold uppercase text-xs">Cancel</button>
                        <button @click="confirmDeactivationWithReason"
                            class="flex-1 py-4 bg-red-600 text-white rounded-2xl font-black uppercase text-xs shadow-lg shadow-red-100 hover:bg-red-700">Confirm
                            Deactivation</button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isAuditLogOpen"
            class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xl">
            <div class="bg-white rounded-[3rem] shadow-2xl max-w-2xl w-full overflow-hidden border border-white">
                <div class="bg-gray-900 p-12 text-white flex justify-between items-center relative">
                    <div>
                        <h2 class="text-4xl font-black tracking-tight">System Audit Logs</h2>
                        <p class="text-indigo-400 text-xs font-bold uppercase tracking-widest mt-2">Activity Tracking &
                            Reasons
                        </p>
                    </div>
                    <button @click="isAuditLogOpen = false"
                        class="text-white/50 hover:text-white text-4xl transition-colors">&times;</button>
                </div>

                <div class="p-12 bg-gray-50 h-[550px] overflow-y-auto custom-scrollbar">
                    <div v-if="auditLogs.length === 0" class="text-center py-24">
                        <div class="inline-flex p-8 bg-gray-100 rounded-full mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-gray-400 font-bold uppercase text-xs tracking-widest">No activity recorded yet
                        </p>
                    </div>

                    <div v-else class="space-y-6">
                        <div v-for="log in auditLogs" :key="log.id"
                            class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-100 flex items-start gap-6">
                            <div
                                :class="['p-4 rounded-2xl', log.action === 'deactivate' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600']">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path v-if="log.action === 'deactivate'" stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="2"
                                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-900 font-medium">
                                    <span class="font-black text-indigo-600">Administrator</span> {{ log.action }}d
                                    <span class="font-black underline">{{ log.target_name }}</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-1 italic">Reason: {{ log.reason }}</p>
                                <p
                                    class="text-[10px] text-gray-400 font-black mt-2 uppercase tracking-widest flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ log.created_at }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-10 bg-white border-t border-gray-100 flex justify-end">
                    <button @click="isAuditLogOpen = false"
                        class="px-12 py-4 bg-gray-900 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-gray-800 transition shadow-2xl">Close
                        Logs</button>
                </div>
            </div>
        </div>
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

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}
</style>