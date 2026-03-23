<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, computed } from 'vue'
import {
    Calendar,
    Clock,
    CheckCircle2,
    XCircle,
    Plus,
    Info,
    History,
    Search,
    UserCheck,
    AlertTriangle
} from 'lucide-vue-next'

const props = defineProps({
    // These come from your LeaveController@leave (HRM version)
    leave_requests: {
        type: Array,
        default: () => []
    },
    // List of employees to search from for manual entry
    employees: {
        type: Array,
        default: () => []
    }
})

// --- Manual Leave Request Logic ---
const showConfirmModal = ref(false)
const selectedEmployeeName = ref('')
const searchId = ref('')

const manualForm = useForm({
    user_id: '',
    leave_type: '',
    start_date: '',
    end_date: '',
    reason: 'Walk-in Request (Processed by HRM)',
    status: 'approved' // Automatically approved for walk-ins
})

const findEmployee = () => {
    const emp = props.employees.find(e => e.employee_id === searchId.value)
    if (emp) {
        manualForm.user_id = emp.id
        selectedEmployeeName.value = emp.name
    } else {
        manualForm.user_id = ''
        selectedEmployeeName.value = 'Employee Not Found'
    }
}

const openConfirmation = () => {
    if (manualForm.user_id && manualForm.leave_type && manualForm.start_date) {
        showConfirmModal.value = true
    }
}

const submitManualRequest = () => {
    manualForm.post(route('hrm.employee.leave.store_manual'), {
        onSuccess: () => {
            showConfirmModal.value = false
            manualForm.reset()
            searchId.value = ''
            selectedEmployeeName.value = ''
        }
    })
}

// --- Request Action Logic (Approve/Reject) ---
const actionForm = useForm({
    status: ''
})

const updateStatus = (id, newStatus) => {
    actionForm.status = newStatus
    actionForm.patch(route('hrm.employee.leave.update', id))
}

const getStatusClass = (status) => {
    switch (status?.toLowerCase()) {
        case 'approved': return 'bg-emerald-100 text-emerald-700'
        case 'pending': return 'bg-amber-100 text-amber-700'
        case 'rejected': return 'bg-rose-100 text-rose-700'
        default: return 'bg-slate-100 text-slate-700'
    }
}
</script>

<template>

    <Head title="Leave Management - HRM" />

    <AuthenticatedLayout>
        <div class="mb-8">
            <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tight">
                Leave <span class="text-blue-600">Administration</span>
            </h1>
            <p class="text-slate-500 text-sm font-medium">Manage employee leave requests and process manual
                applications.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1">
                <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm sticky top-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-blue-50 rounded-xl text-blue-600">
                            <Plus class="h-5 w-5" />
                        </div>
                        <h2 class="text-lg font-bold text-slate-900">Manual Entry (Walk-in)</h2>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Search
                                Employee ID</label>
                            <div class="relative">
                                <input v-model="searchId" @input="findEmployee" type="text"
                                    placeholder="e.g. MONTI-2026-INV-0003"
                                    class="w-full pl-11 pr-4 py-3 bg-slate-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 transition-all" />
                                <Search class="absolute left-4 top-3.5 h-4 w-4 text-slate-400" />
                            </div>
                            <p v-if="selectedEmployeeName"
                                :class="manualForm.user_id ? 'text-emerald-600' : 'text-rose-500'"
                                class="mt-2 text-[11px] font-bold px-2">
                                {{ selectedEmployeeName }}
                            </p>
                        </div>

                        <form @submit.prevent="openConfirmation" class="space-y-5"
                            :class="{ 'opacity-50 pointer-events-none': !manualForm.user_id }">
                            <div>
                                <label
                                    class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Leave
                                    Type</label>
                                <select v-model="manualForm.leave_type"
                                    class="w-full px-4 py-3 bg-slate-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500">
                                    <option value="" disabled>Select Type</option>
                                    <option value="sick">Sick Leave</option>
                                    <option value="vacation">Vacation</option>
                                    <option value="personal">Personal</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Start
                                        Date</label>
                                    <input v-model="manualForm.start_date" type="date"
                                        class="w-full px-4 py-3 bg-slate-50 border-none rounded-2xl text-sm" />
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">End
                                        Date</label>
                                    <input v-model="manualForm.end_date" type="date"
                                        class="w-full px-4 py-3 bg-slate-50 border-none rounded-2xl text-sm" />
                                </div>
                            </div>

                            <button type="submit" :disabled="!manualForm.user_id || manualForm.processing"
                                class="w-full py-4 bg-slate-900 hover:bg-black text-white rounded-2xl font-bold text-sm transition-all shadow-lg active:scale-95">
                                Process & Approve
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
                    <div class="p-8 border-b border-slate-50 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-emerald-50 rounded-xl text-emerald-600">
                                <History class="h-5 w-5" />
                            </div>
                            <h2 class="text-lg font-bold text-slate-900">Incoming Requests</h2>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50">
                                    <th
                                        class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                        Employee</th>
                                    <th
                                        class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                        Type</th>
                                    <th
                                        class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                        Duration</th>
                                    <th
                                        class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="request in leave_requests" :key="request.id"
                                    class="group hover:bg-slate-50/50 transition-all">
                                    <td class="px-8 py-5">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-900">{{ request.user?.name
                                                }}</span>
                                            <span class="text-[10px] text-blue-600 font-black uppercase">{{
                                                request.user?.employee_id }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5">
                                        <span class="text-sm font-bold text-slate-700 uppercase">{{ request.leave_type
                                            }}</span>
                                    </td>
                                    <td class="px-8 py-5">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-semibold text-slate-600">{{ request.start_date
                                                }}</span>
                                            <span class="text-[9px] text-slate-400 font-bold uppercase">To {{
                                                request.end_date }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5">
                                        <div v-if="request.status === 'pending'" class="flex gap-2">
                                            <button @click="updateStatus(request.id, 'approved')"
                                                class="p-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition-colors">
                                                <CheckCircle2 class="h-5 w-5" />
                                            </button>
                                            <button @click="updateStatus(request.id, 'rejected')"
                                                class="p-2 bg-rose-50 text-rose-600 rounded-lg hover:bg-rose-100 transition-colors">
                                                <XCircle class="h-5 w-5" />
                                            </button>
                                        </div>
                                        <span v-else
                                            :class="[getStatusClass(request.status), 'px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider inline-flex items-center gap-2']">
                                            {{ request.status }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="leave_requests.length === 0">
                                    <td colspan="4" class="px-8 py-10 text-center text-slate-400 italic text-sm">No
                                        leave requests found in database.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showConfirmModal"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white rounded-[2.5rem] p-8 max-w-md w-full shadow-2xl">
                <div class="flex flex-col items-center text-center">
                    <div class="size-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6">
                        <UserCheck class="h-8 w-8" />
                    </div>
                    <h3 class="text-xl font-black text-slate-900 uppercase italic">Confirm Manual Request</h3>
                    <p class="text-slate-500 text-sm mt-2 mb-8">
                        You are about to process an automatic <span
                            class="text-emerald-600 font-bold uppercase">Approved</span> leave for
                        <span class="text-slate-900 font-black">{{ selectedEmployeeName }}</span>.
                    </p>

                    <div class="w-full grid grid-cols-2 gap-4">
                        <button @click="showConfirmModal = false"
                            class="py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold text-sm hover:bg-slate-200 transition-all">
                            Cancel
                        </button>
                        <button @click="submitManualRequest"
                            class="py-4 bg-blue-600 text-white rounded-2xl font-bold text-sm hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all">
                            Confirm & Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>