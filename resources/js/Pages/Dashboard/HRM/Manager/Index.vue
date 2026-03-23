<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
    Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot
} from '@headlessui/vue'
import {
    Users, UserPlus, Calendar, TrendingUp, UserCheck, ArrowUpCircle,
    Eye, ShieldOff, ShieldCheck, Award, X, History, UserMinus,
    CheckCircle2, XCircle, Star, Building2, ClipboardList,
    LayoutDashboard, CheckCircle, RotateCcw
} from 'lucide-vue-next'

// ─────────────────────────────────────────────
// Props
// ─────────────────────────────────────────────
const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            totalEmployees: 0,
            activeRecruitment: 0,
            pendingLeaves: 0,
            attendanceRate: 0
        })
    },
    suggestedTrainees: {
        type: Array,
        default: () => []
    },
    allEmployees: {
        type: Array,
        default: () => []
    }
})

// ─────────────────────────────────────────────
// Toast Notification System
// ─────────────────────────────────────────────
const showToast = ref(false)
const toastMsg = ref('')
const triggerToast = (msg) => {
    toastMsg.value = msg
    showToast.value = true
    setTimeout(() => { showToast.value = false }, 4000)
}

// ─────────────────────────────────────────────
// Main Tab Toggle
// ─────────────────────────────────────────────
const activeTab = ref('employees') // 'employees' | 'trainees'

// ─────────────────────────────────────────────
// Employee Display State & Filters
// ─────────────────────────────────────────────
const departments = ['ALL', 'HRM', 'SCM', 'FIN', 'MAN', 'INV', 'ORD', 'WAR', 'CRM', 'ECO']
const activeDept = ref('ALL')
const showDeactivated = ref(false) // Toggle for active vs deactivated accounts

// BULLETPROOF STATUS CHECKER: Supports 'status' string or 'is_active' boolean from Database
const checkActive = (emp) => {
    return emp.status === 'Active' || emp.status === 'active' || emp.is_active === 1 || emp.is_active === true;
}

const filteredEmployees = computed(() => {
    let result = props.allEmployees

    // Filter by Active/Deactivated Status
    result = result.filter(emp => checkActive(emp) === !showDeactivated.value)

    // Filter by Department
    if (activeDept.value !== 'ALL') {
        result = result.filter(e => e.role === activeDept.value)
    }

    return result
})

const filteredManagers = computed(() => filteredEmployees.value.filter(e => e.position === 'manager' || e.position === 'Manager'))
const filteredStaff = computed(() => filteredEmployees.value.filter(e => e.position === 'staff' || e.position === 'Staff'))

// ─────────────────────────────────────────────
// Modals State & Logic
// ─────────────────────────────────────────────

// 1. View Details Modal
const isViewingEmployee = ref(false)
const selectedEmployee = ref(null)
const openViewEmployee = (emp) => { selectedEmployee.value = emp; isViewingEmployee.value = true }
const closeViewEmployee = () => { isViewingEmployee.value = false; selectedEmployee.value = null }

// 2. View Audit Logs (History) Modal
const isViewingHistory = ref(false)
const historyEmployee = ref(null)
const openHistoryModal = (emp) => { historyEmployee.value = emp; isViewingHistory.value = true }
const closeHistoryModal = () => { isViewingHistory.value = false; historyEmployee.value = null }

// 3. Deactivate / Activate Modals
const isDeactivating = ref(false)
const isActivating = ref(false)
const targetAccount = ref(null)
const statusReason = ref('')

const openDeactivateModal = (emp) => {
    targetAccount.value = emp
    statusReason.value = ''
    isDeactivating.value = true
}

const openActivateModal = (emp) => {
    targetAccount.value = emp
    statusReason.value = ''
    isActivating.value = true
}

const closeStatusModals = () => {
    isDeactivating.value = false
    isActivating.value = false
    targetAccount.value = null
    statusReason.value = ''
}

// Submits the toggle status command to backend
const toggleAccountStatus = (action) => {
    router.post(route('hrm.employees.toggle-status', targetAccount.value.id), {
        action: action, // 'deactivate' or 'reactivate'
        reason: statusReason.value || 'No reason provided'
    }, {
        preserveScroll: true,
        onSuccess: () => {
            const actionText = action === 'reactivate' ? 'reactivated' : 'deactivated';
            triggerToast(`${targetAccount.value.name}'s account has been successfully ${actionText}.`)
            closeStatusModals()
        },
        onError: (errors) => {
            const msg = Object.values(errors)[0] || 'Error updating account status.';
            triggerToast(`Failed: ${msg}`);
        }
    })
}

// ─────────────────────────────────────────────
// Trainee & Grading State
// ─────────────────────────────────────────────
const pendingGrading = computed(() => props.suggestedTrainees.filter(t => !t.trainee_grade))
const alreadyGraded = computed(() => props.suggestedTrainees.filter(t => t.trainee_grade))

// Grading Modal
const isGrading = ref(false)
const gradingTrainee = ref(null)
const isSavingGrade = ref(false)

const gradeData = ref({ skills_performance: 0, behaviour: 0, technicals: 0, safety_awareness: 0, productivity: 0 })
const gradeCriteria = [
    { id: 'skills_performance', label: 'Skills Performance' },
    { id: 'behaviour', label: 'Behaviour' },
    { id: 'technicals', label: 'Technicals' },
    { id: 'safety_awareness', label: 'Safety Awareness' },
    { id: 'productivity', label: 'Productivity' },
]

const gradeIsValid = computed(() => gradeCriteria.every(c => gradeData.value[c.id] >= 1))

const gradeTotal = computed(() => {
    if (!gradeIsValid.value) return null
    const sum = gradeCriteria.reduce((acc, c) => acc + Number(gradeData.value[c.id]), 0)
    return ((sum / 25) * 100).toFixed(1)
})

const isPassing = computed(() => gradeTotal.value !== null && Number(gradeTotal.value) >= 80)

const openGradingModal = (trainee) => {
    gradingTrainee.value = trainee
    if (trainee.trainee_grade) {
        Object.keys(gradeData.value).forEach(k => gradeData.value[k] = trainee.trainee_grade[k] || 0)
    } else {
        Object.keys(gradeData.value).forEach(k => gradeData.value[k] = 0)
    }
    isGrading.value = true
}

const closeGradingModal = () => { isGrading.value = false; gradingTrainee.value = null; }

const submitGrade = () => {
    if (!gradingTrainee.value || !gradeIsValid.value) return
    isSavingGrade.value = true

    router.post(route('hrm.manager.grade-trainee', gradingTrainee.value.id), gradeData.value, {
        preserveScroll: true,
        onSuccess: () => { triggerToast('Trainee grades saved successfully.'); closeGradingModal() },
        onError: (errors) => {
            const msg = errors?.grade || Object.values(errors)[0] || 'Validation failed.';
            triggerToast(`Save failed: ${msg}`)
        },
        onFinish: () => { isSavingGrade.value = false }
    })
}

// Confirm Promotion Modal
const isConfirmingPromotion = ref(false)

const confirmPromotion = (t) => { selectedTrainee.value = t; isConfirmingPromotion.value = true }
const closePromotionModal = () => { isConfirmingPromotion.value = false; selectedTrainee.value = null }

const promoteToStaff = () => {
    if (!selectedTrainee.value) return
    router.post(route('hrm.manager.finalize-promotion', selectedTrainee.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            triggerToast(`${selectedTrainee.value.name} has been promoted to Staff!`)
            closePromotionModal()
        },
    })
}

// ─────────────────────────────────────────────
// UI Helpers
// ─────────────────────────────────────────────
const getInitials = (name) => name?.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) ?? '?'

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const deptColors = { HRM: 'bg-pink-100 text-pink-700', SCM: 'bg-blue-100 text-blue-700', FIN: 'bg-emerald-100 text-emerald-700', MAN: 'bg-purple-100 text-purple-700', INV: 'bg-amber-100 text-amber-700', ORD: 'bg-green-100 text-green-700', WAR: 'bg-orange-100 text-orange-700', CRM: 'bg-red-100 text-red-700', ECO: 'bg-teal-100 text-teal-700' }
const getDeptClass = (role) => deptColors[role] ?? 'bg-slate-100 text-slate-600'
const avatarColors = ['bg-blue-50 text-blue-600', 'bg-violet-50 text-violet-600', 'bg-emerald-50 text-emerald-600', 'bg-orange-50 text-orange-600', 'bg-pink-50 text-pink-600', 'bg-teal-50 text-teal-600']
const getAvatarColor = (id) => avatarColors[id % avatarColors.length]
const gradeColor = (score) => score == null ? 'text-slate-400' : (score >= 80 ? 'text-emerald-500' : (score >= 60 ? 'text-blue-600' : 'text-red-500'))
</script>

<template>

    <Head title="HRM Manager Dashboard" />

    <AuthenticatedLayout>

        <div class="fixed top-4 right-4 z-[200] pointer-events-none w-full max-w-xs sm:max-w-sm px-4 sm:px-0">
            <Transition name="toast">
                <div v-if="showToast"
                    class="flex items-center gap-3 px-6 py-4 bg-slate-900 text-white rounded-2xl shadow-2xl border border-white/10 pointer-events-auto">
                    <CheckCircle class="h-5 w-5 text-emerald-400 flex-shrink-0" />
                    <p class="text-sm font-bold tracking-tight">{{ toastMsg }}</p>
                </div>
            </Transition>
        </div>

        <div class="mb-6 sm:mb-8">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                HRM <span class="text-blue-600">Manager</span> Dashboard
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
                Manage your workforce, employee access levels, and trainee promotions.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
            <div
                class="bg-white dark:bg-slate-800 p-4 sm:p-6 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm flex flex-col justify-center">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                        <Users class="h-6 w-6 text-blue-600" />
                    </div>
                </div>
                <p class="text-sm font-medium text-slate-500">Total Employees</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.totalEmployees }}</p>
            </div>
            <div
                class="bg-white dark:bg-slate-800 p-4 sm:p-6 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm flex flex-col justify-center">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl">
                        <UserPlus class="h-6 w-6 text-indigo-600" />
                    </div>
                </div>
                <p class="text-sm font-medium text-slate-500">Pending Trainees</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white">{{ suggestedTrainees.length }}</p>
            </div>
            <div
                class="bg-white dark:bg-slate-800 p-4 sm:p-6 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm flex flex-col justify-center">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                        <Calendar class="h-6 w-6 text-amber-600" />
                    </div>
                </div>
                <p class="text-sm font-medium text-slate-500">Pending Leaves</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.pendingLeaves }}</p>
            </div>
            <div
                class="bg-white dark:bg-slate-800 p-4 sm:p-6 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm flex flex-col justify-center">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                        <TrendingUp class="h-6 w-6 text-emerald-600" />
                    </div>
                </div>
                <p class="text-sm font-medium text-slate-500">Attendance Rate</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.attendanceRate }}%</p>
            </div>
        </div>

        <div
            class="inline-flex p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl mb-6 border border-slate-200 dark:border-slate-700 overflow-x-auto no-scrollbar max-w-full">
            <button @click="activeTab = 'employees'" :class="[
                'flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 flex-shrink-0',
                activeTab === 'employees' ? 'bg-white dark:bg-slate-700 text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'
            ]">
                <Building2 class="h-4 w-4" /> Employee Directory
            </button>
            <button @click="activeTab = 'trainees'" :class="[
                'flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 flex-shrink-0',
                activeTab === 'trainees' ? 'bg-white dark:bg-slate-700 text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'
            ]">
                <Award class="h-4 w-4" /> Trainee Grades
                <span v-if="suggestedTrainees.length > 0"
                    class="ml-1 bg-indigo-500 text-white text-[10px] font-black px-1.5 py-0.5 rounded-full leading-none">
                    {{ suggestedTrainees.length }}
                </span>
            </button>
        </div>


        <div v-if="activeTab === 'employees'">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6 gap-4">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-black text-slate-400 uppercase tracking-widest mr-1">Filter:</span>
                    <button v-for="dept in departments" :key="dept" @click="activeDept = dept" :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-widest transition-all',
                        activeDept === dept ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'bg-white dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700 hover:border-blue-300 hover:text-blue-600'
                    ]">{{ dept }}</button>
                </div>

                <button @click="showDeactivated = !showDeactivated" :class="[
                    'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest transition-all shadow-sm',
                    showDeactivated
                        ? 'bg-red-50 text-red-600 border border-red-200 hover:bg-red-100'
                        : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700'
                ]">
                    <UserMinus class="w-4 h-4" v-if="!showDeactivated" />
                    <UserCheck class="w-4 h-4" v-else />
                    {{ showDeactivated ? 'View Active Personnel' : 'View Deactivated Accounts' }}
                </button>
            </div>

            <div v-if="showDeactivated"
                class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800/30 flex items-start gap-3">
                <ShieldOff class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" />
                <div>
                    <h4 class="text-sm font-black text-red-800 dark:text-red-400 uppercase tracking-widest">Deactivated
                        Accounts Archive</h4>
                    <p class="text-xs text-red-600 dark:text-red-300 mt-1">You are currently viewing deactivated
                        personnel accounts. These users have had their system access suspended. You can view their audit
                        logs or reactivate their accounts below.</p>
                </div>
            </div>

            <div
                class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden mb-6">
                <div class="px-4 sm:px-6 py-4 border-b border-slate-50 dark:border-slate-700 flex items-center gap-3">
                    <div :class="showDeactivated ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600'"
                        class="p-1.5 rounded-lg">
                        <ShieldCheck class="h-4 w-4" />
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">{{ showDeactivated ?
                            'DeactivatedManagers' : 'Active Managers' }}</h2>
                        <p class="text-xs text-slate-400">{{ filteredManagers.length }} record{{ filteredManagers.length
                            !== 1 ? 's' : '' }}</p>
                    </div>
                </div>

                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/60 dark:bg-slate-900/40">
                                <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    Employee</th>
                                <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    Department</th>
                                <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">
                                    Account Control</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-700/60">
                            <tr v-for="emp in filteredManagers" :key="emp.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-900/20 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div v-if="emp.profile_photo_url"
                                            class="h-10 w-10 rounded-xl overflow-hidden flex-shrink-0">
                                            <img :src="emp.profile_photo_url" :alt="emp.name"
                                                class="h-full w-full object-cover" />
                                        </div>
                                        <div v-else
                                            :class="['h-10 w-10 rounded-xl flex items-center justify-center font-black text-sm flex-shrink-0', getAvatarColor(emp.id)]">
                                            {{ getInitials(emp.name) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ emp.name }}
                                            </p>
                                            <p class="text-[10px] font-black text-slate-400 uppercase">ID: #{{
                                                emp.employee_id || emp.id }}</p>
                                            <p class="text-[11px] text-slate-400">{{ emp.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="['px-2 py-1 rounded-md text-[10px] font-black uppercase', getDeptClass(emp.role)]">{{
                                            emp.role }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase', checkActive(emp) ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700']">
                                        <span class="h-1.5 w-1.5 rounded-full"
                                            :class="checkActive(emp) ? 'bg-emerald-500' : 'bg-red-500'"></span>
                                        {{ checkActive(emp) ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openViewEmployee(emp)"
                                            class="p-2 rounded-xl bg-slate-50 dark:bg-slate-700/50 text-slate-500 hover:text-blue-600 transition-colors"
                                            title="View Profile">
                                            <Eye class="h-4 w-4" />
                                        </button>
                                        <button @click="openHistoryModal(emp)"
                                            class="p-2 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors"
                                            title="Audit Logs">
                                            <History class="h-4 w-4" />
                                        </button>
                                        <button v-if="checkActive(emp)" @click="openDeactivateModal(emp)"
                                            class="p-2 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 transition-colors"
                                            title="Deactivate">
                                            <ShieldOff class="h-4 w-4" />
                                        </button>
                                        <button v-else @click="openActivateModal(emp)"
                                            class="p-2 rounded-xl bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors"
                                            title="Reactivate">
                                            <ShieldCheck class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredManagers.length === 0">
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-slate-500">No records found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="md:hidden divide-y divide-slate-50 dark:divide-slate-700/60">
                    <div v-if="filteredManagers.length === 0" class="p-6 text-center text-sm text-slate-500">No records
                        found.</div>
                    <div v-for="emp in filteredManagers" :key="emp.id" class="p-4 flex flex-col gap-3">
                        <div class="flex items-center gap-3">
                            <div v-if="emp.profile_photo_url"
                                class="h-10 w-10 rounded-xl overflow-hidden flex-shrink-0">
                                <img :src="emp.profile_photo_url" :alt="emp.name" class="h-full w-full object-cover" />
                            </div>
                            <div v-else
                                :class="['h-10 w-10 rounded-xl flex items-center justify-center font-black text-sm flex-shrink-0', getAvatarColor(emp.id)]">
                                {{ getInitials(emp.name) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ emp.name }}</p>
                                <p class="text-[10px] font-black text-slate-400 uppercase truncate">ID: #{{
                                    emp.employee_id || emp.id }}</p>
                            </div>
                            <span
                                :class="['inline-flex items-center gap-1 px-2 py-1 rounded-full text-[9px] font-black uppercase', checkActive(emp) ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700']">
                                {{ checkActive(emp) ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <span
                                :class="['px-2 py-1 rounded-md text-[9px] font-black uppercase', getDeptClass(emp.role)]">{{
                                    emp.role }}</span>
                            <div class="flex items-center gap-2">
                                <button @click="openViewEmployee(emp)"
                                    class="p-2 rounded-xl bg-slate-50 dark:bg-slate-700/50 text-slate-500 hover:text-blue-600 transition-colors"
                                    title="View Profile">
                                    <Eye class="h-4 w-4" />
                                </button>
                                <button @click="openHistoryModal(emp)"
                                    class="p-2 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors"
                                    title="Audit Logs">
                                    <History class="h-4 w-4" />
                                </button>
                                <button v-if="checkActive(emp)" @click="openDeactivateModal(emp)"
                                    class="p-2 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 transition-colors"
                                    title="Deactivate">
                                    <ShieldOff class="h-4 w-4" />
                                </button>
                                <button v-else @click="openActivateModal(emp)"
                                    class="p-2 rounded-xl bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors"
                                    title="Reactivate">
                                    <ShieldCheck class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden mb-6">
                <div class="px-4 sm:px-6 py-4 border-b border-slate-50 dark:border-slate-700 flex items-center gap-3">
                    <div :class="showDeactivated ? 'bg-red-50 text-red-600' : 'bg-indigo-50 text-indigo-600'"
                        class="p-1.5 rounded-lg">
                        <UserCheck class="h-4 w-4" />
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">{{ showDeactivated ?
                            'DeactivatedStaff' : 'Active Staff' }}</h2>
                        <p class="text-xs text-slate-400">{{ filteredStaff.length }} record{{ filteredStaff.length !== 1
                            ? 's' : '' }}</p>
                    </div>
                </div>

                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/60 dark:bg-slate-900/40">
                                <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    Employee</th>
                                <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    Department</th>
                                <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">
                                    Account Control</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-700/60">
                            <tr v-for="emp in filteredStaff" :key="emp.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-900/20 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div v-if="emp.profile_photo_url"
                                            class="h-10 w-10 rounded-xl overflow-hidden flex-shrink-0">
                                            <img :src="emp.profile_photo_url" :alt="emp.name"
                                                class="h-full w-full object-cover" />
                                        </div>
                                        <div v-else
                                            :class="['h-10 w-10 rounded-xl flex items-center justify-center font-black text-sm flex-shrink-0', getAvatarColor(emp.id)]">
                                            {{ getInitials(emp.name) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ emp.name }}
                                            </p>
                                            <p class="text-[10px] font-black text-slate-400 uppercase">ID: #{{
                                                emp.employee_id || emp.id }}</p>
                                            <p class="text-[11px] text-slate-400">{{ emp.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="['px-2 py-1 rounded-md text-[10px] font-black uppercase', getDeptClass(emp.role)]">{{
                                            emp.role }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase', checkActive(emp) ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700']">
                                        <span class="h-1.5 w-1.5 rounded-full"
                                            :class="checkActive(emp) ? 'bg-emerald-500' : 'bg-red-500'"></span>
                                        {{ checkActive(emp) ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openViewEmployee(emp)"
                                            class="p-2 rounded-xl bg-slate-50 dark:bg-slate-700/50 text-slate-500 hover:text-blue-600 transition-colors"
                                            title="View Profile">
                                            <Eye class="h-4 w-4" />
                                        </button>
                                        <button @click="openHistoryModal(emp)"
                                            class="p-2 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors"
                                            title="Audit Logs">
                                            <History class="h-4 w-4" />
                                        </button>
                                        <button v-if="checkActive(emp)" @click="openDeactivateModal(emp)"
                                            class="p-2 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 transition-colors"
                                            title="Deactivate">
                                            <ShieldOff class="h-4 w-4" />
                                        </button>
                                        <button v-else @click="openActivateModal(emp)"
                                            class="p-2 rounded-xl bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors"
                                            title="Reactivate">
                                            <ShieldCheck class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredStaff.length === 0">
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-slate-500">No records found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="md:hidden divide-y divide-slate-50 dark:divide-slate-700/60">
                    <div v-if="filteredStaff.length === 0" class="p-6 text-center text-sm text-slate-500">No records
                        found.</div>
                    <div v-for="emp in filteredStaff" :key="emp.id" class="p-4 flex flex-col gap-3">
                        <div class="flex items-center gap-3">
                            <div v-if="emp.profile_photo_url"
                                class="h-10 w-10 rounded-xl overflow-hidden flex-shrink-0">
                                <img :src="emp.profile_photo_url" :alt="emp.name" class="h-full w-full object-cover" />
                            </div>
                            <div v-else
                                :class="['h-10 w-10 rounded-xl flex items-center justify-center font-black text-sm flex-shrink-0', getAvatarColor(emp.id)]">
                                {{ getInitials(emp.name) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ emp.name }}</p>
                                <p class="text-[10px] font-black text-slate-400 uppercase truncate">ID: #{{
                                    emp.employee_id || emp.id }}</p>
                            </div>
                            <span
                                :class="['inline-flex items-center gap-1 px-2 py-1 rounded-full text-[9px] font-black uppercase', checkActive(emp) ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700']">
                                {{ checkActive(emp) ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <span
                                :class="['px-2 py-1 rounded-md text-[9px] font-black uppercase', getDeptClass(emp.role)]">{{
                                    emp.role }}</span>
                            <div class="flex items-center gap-2">
                                <button @click="openViewEmployee(emp)"
                                    class="p-2 rounded-xl bg-slate-50 dark:bg-slate-700/50 text-slate-500 hover:text-blue-600 transition-colors"
                                    title="View Profile">
                                    <Eye class="h-4 w-4" />
                                </button>
                                <button @click="openHistoryModal(emp)"
                                    class="p-2 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors"
                                    title="Audit Logs">
                                    <History class="h-4 w-4" />
                                </button>
                                <button v-if="checkActive(emp)" @click="openDeactivateModal(emp)"
                                    class="p-2 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 transition-colors"
                                    title="Deactivate">
                                    <ShieldOff class="h-4 w-4" />
                                </button>
                                <button v-else @click="openActivateModal(emp)"
                                    class="p-2 rounded-xl bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors"
                                    title="Reactivate">
                                    <ShieldCheck class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="activeTab === 'trainees'">
            <div class="space-y-6 sm:space-y-8">

                <div
                    class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden mb-6">
                    <div
                        class="px-4 sm:px-6 py-4 border-b border-slate-50 dark:border-slate-700 flex items-center gap-3">
                        <div class="p-1.5 bg-amber-50 rounded-lg">
                            <ClipboardList class="h-4 w-4 text-amber-600" />
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-slate-900 dark:text-white">Pending Grading</h2>
                            <p class="text-xs text-slate-400">{{ pendingGrading.length }} trainee{{
                                pendingGrading.length !== 1 ? 's' : '' }} awaiting evaluation</p>
                        </div>
                    </div>

                    <div class="md:hidden divide-y divide-slate-50 dark:divide-slate-700/60">
                        <div v-if="pendingGrading.length === 0" class="p-6 text-center text-sm text-slate-500">
                            No trainees pending grading.
                        </div>
                        <div v-for="t in pendingGrading" :key="t.id" class="p-4 flex flex-col gap-3">
                            <div class="flex items-center gap-3">
                                <div v-if="t.profile_photo_url"
                                    class="h-10 w-10 rounded-xl overflow-hidden flex-shrink-0">
                                    <img :src="t.profile_photo_url" :alt="t.name" class="h-full w-full object-cover" />
                                </div>
                                <div v-else
                                    :class="['h-10 w-10 rounded-xl flex items-center justify-center font-black text-sm flex-shrink-0', getAvatarColor(t.id)]">
                                    {{ getInitials(t.name) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ t.name }}
                                    </p>
                                    <p class="text-[10px] font-black text-slate-400 uppercase truncate">ID: #{{
                                        t.employee_id || t.id }}</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <div class="flex items-center gap-2">
                                    <span
                                        :class="['px-2 py-1 rounded-md text-[9px] font-black uppercase', getDeptClass(t.role)]">{{
                                            t.role }}</span>
                                    <span v-if="t.promotion_suggested"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[9px] font-black uppercase bg-blue-100 text-blue-700">
                                        <ArrowUpCircle class="h-3 w-3" /> Sug.
                                    </span>
                                </div>
                                <button @click="openGradingModal(t)"
                                    class="px-4 py-2 rounded-xl bg-blue-600 text-white text-[10px] font-bold uppercase shadow-md hover:bg-blue-700 transition-all active:scale-95">
                                    Grade Now
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50/60 dark:bg-slate-900/40">
                                    <th
                                        class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        Trainee</th>
                                    <th
                                        class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        Department</th>
                                    <th
                                        class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        Suggested</th>
                                    <th
                                        class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">
                                        Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-700/60">
                                <tr v-for="t in pendingGrading" :key="t.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-900/20 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div v-if="t.profile_photo_url"
                                                class="h-10 w-10 rounded-xl overflow-hidden flex-shrink-0">
                                                <img :src="t.profile_photo_url" :alt="t.name"
                                                    class="h-full w-full object-cover" />
                                            </div>
                                            <div v-else
                                                :class="['h-10 w-10 rounded-xl flex items-center justify-center font-black text-sm flex-shrink-0', getAvatarColor(t.id)]">
                                                {{ getInitials(t.name) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ t.name }}
                                                </p>
                                                <p class="text-[10px] font-black text-slate-400 uppercase">ID: #{{
                                                    t.employee_id || t.id }}</p>
                                                <p class="text-[11px] text-slate-400">{{ t.email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            :class="['px-2 py-1 rounded-md text-[10px] font-black uppercase', getDeptClass(t.role)]">{{
                                                t.role }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span v-if="t.promotion_suggested"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-blue-100 text-blue-700">
                                            <ArrowUpCircle class="h-3.5 w-3.5" /> Suggested
                                        </span>
                                        <span v-else class="text-[10px] font-black text-slate-400 uppercase">Not
                                            Yet</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click="openGradingModal(t)"
                                            class="px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-bold uppercase shadow-md hover:bg-blue-700 transition-all active:scale-95">
                                            Grade Now
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="pendingGrading.length === 0">
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-slate-500">No trainees
                                        pending grading.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden mb-6">
                    <div
                        class="px-4 sm:px-6 py-4 border-b border-slate-50 dark:border-slate-700 flex items-center gap-3">
                        <div class="p-1.5 bg-emerald-50 rounded-lg">
                            <Award class="h-4 w-4 text-emerald-600" />
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-slate-900 dark:text-white">Graded Trainees</h2>
                            <p class="text-xs text-slate-400">{{ alreadyGraded.length }} trainee{{ alreadyGraded.length
                                !== 1 ? 's' : '' }} evaluated</p>
                        </div>
                    </div>

                    <div class="md:hidden divide-y divide-slate-50 dark:divide-slate-700/60">
                        <div v-if="alreadyGraded.length === 0" class="p-6 text-center text-sm text-slate-500">
                            No graded trainees yet.
                        </div>
                        <div v-for="t in alreadyGraded" :key="t.id" class="p-4 flex flex-col gap-3">
                            <div class="flex items-center gap-3">
                                <div v-if="t.profile_photo_url"
                                    class="h-10 w-10 rounded-xl overflow-hidden flex-shrink-0">
                                    <img :src="t.profile_photo_url" :alt="t.name" class="h-full w-full object-cover" />
                                </div>
                                <div v-else
                                    :class="['h-10 w-10 rounded-xl flex items-center justify-center font-black text-sm flex-shrink-0', getAvatarColor(t.id)]">
                                    {{ getInitials(t.name) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ t.name }}
                                    </p>
                                    <p class="text-[10px] font-black text-slate-400 uppercase truncate">ID: #{{
                                        t.employee_id || t.id }}</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <div class="flex items-center gap-2">
                                    <span
                                        :class="['px-2 py-1 rounded-md text-[9px] font-black uppercase', getDeptClass(t.role)]">{{
                                            t.role }}</span>
                                    <span
                                        :class="['font-bold text-[11px]', gradeColor(t.trainee_grade.total_percentage)]">{{
                                            t.trainee_grade.total_percentage }}%</span>
                                </div>
                                <div class="flex gap-2">
                                    <button v-if="t.trainee_grade.total_percentage >= 80" @click="confirmPromotion(t)"
                                        class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-[10px] font-bold uppercase shadow-md hover:bg-emerald-700 transition-all active:scale-95">
                                        Promote
                                    </button>
                                    <button v-else @click="openGradingModal(t)"
                                        class="px-4 py-2 rounded-xl bg-amber-600 text-white text-[10px] font-bold uppercase shadow-md hover:bg-amber-700 transition-all active:scale-95 flex items-center gap-1">
                                        <RotateCcw class="h-3 w-3" /> Re-Eval
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50/60 dark:bg-slate-900/40">
                                    <th
                                        class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        Trainee</th>
                                    <th
                                        class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        Department</th>
                                    <th
                                        class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        Score</th>
                                    <th
                                        class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">
                                        Status / Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-700/60">
                                <tr v-for="t in alreadyGraded" :key="t.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-900/20 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div v-if="t.profile_photo_url"
                                                class="h-10 w-10 rounded-xl overflow-hidden flex-shrink-0">
                                                <img :src="t.profile_photo_url" :alt="t.name"
                                                    class="h-full w-full object-cover" />
                                            </div>
                                            <div v-else
                                                :class="['h-10 w-10 rounded-xl flex items-center justify-center font-black text-sm flex-shrink-0', getAvatarColor(t.id)]">
                                                {{ getInitials(t.name) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ t.name }}
                                                </p>
                                                <p class="text-[10px] font-black text-slate-400 uppercase">ID: #{{
                                                    t.employee_id || t.id }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            :class="['px-2 py-1 rounded-md text-[10px] font-black uppercase', getDeptClass(t.role)]">{{
                                                t.role }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="['font-bold', gradeColor(t.trainee_grade.total_percentage)]">{{
                                            t.trainee_grade.total_percentage }}%</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <div v-if="t.trainee_grade.total_percentage >= 80"
                                                class="flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-700">
                                                <CheckCircle2 class="h-3.5 w-3.5" /> Qualified
                                            </div>
                                            <div v-else
                                                class="flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-red-100 text-red-700">
                                                <XCircle class="h-3.5 w-3.5" /> Below Threshold
                                            </div>
                                            <button v-if="t.trainee_grade.total_percentage >= 80"
                                                @click="confirmPromotion(t)"
                                                class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold uppercase shadow-md hover:bg-emerald-700 transition-all active:scale-95">
                                                Confirm Promotion
                                            </button>
                                            <button v-else @click="openGradingModal(t)"
                                                class="px-4 py-2 rounded-xl bg-amber-600 text-white text-xs font-bold uppercase shadow-md hover:bg-amber-700 transition-all active:scale-95 flex items-center gap-1">
                                                <RotateCcw class="h-3.5 w-3.5" /> Re-Evaluate
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="alreadyGraded.length === 0">
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-slate-500">No graded
                                        trainees yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <TransitionRoot as="template" :show="isDeactivating">
            <Dialog as="div" class="relative z-50" @close="closeStatusModals">
                <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0"
                    enter-to="opacity-100" leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 overflow-y-auto w-full px-4">
                    <div class="flex min-h-full items-center justify-center py-4">
                        <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100" leave="ease-in duration-150"
                            leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                            <DialogPanel
                                class="relative w-full max-w-md transform rounded-2xl bg-white dark:bg-slate-800 shadow-2xl border border-red-100 dark:border-red-900/40 overflow-hidden">
                                <div class="p-6">
                                    <div class="flex items-start gap-4">
                                        <div
                                            class="flex-shrink-0 h-11 w-11 rounded-xl bg-red-100 flex items-center justify-center">
                                            <ShieldOff class="h-5 w-5 text-red-600" />
                                        </div>
                                        <div class="flex-1">
                                            <DialogTitle
                                                class="text-base font-black text-slate-900 dark:text-white uppercase">
                                                Deactivate Account</DialogTitle>
                                            <p class="text-sm text-slate-500 mt-1">Suspend access for <span
                                                    class="font-bold text-slate-900 dark:text-white">{{
                                                        targetAccount?.name }}</span>.</p>
                                        </div>
                                    </div>
                                    <div class="mt-5">
                                        <label
                                            class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Reason
                                            for Deactivation <span class="text-red-500">*</span></label>
                                        <textarea v-model="statusReason" rows="3"
                                            placeholder="e.g. End of contract, policy violation..."
                                            class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900/40 text-sm text-slate-900 dark:text-white focus:ring-red-400 focus:border-red-400"></textarea>
                                    </div>
                                    <div class="mt-5 flex flex-col sm:flex-row-reverse gap-3">
                                        <button @click="toggleAccountStatus('deactivate')"
                                            :disabled="!statusReason.trim()"
                                            :class="['w-full sm:flex-none py-3 px-5 rounded-xl text-xs font-black uppercase text-white transition-all', statusReason.trim() ? 'bg-red-600 hover:bg-red-700' : 'bg-red-300 cursor-not-allowed']">Confirm
                                            Deactivation</button>
                                        <button @click="closeStatusModals"
                                            class="w-full sm:flex-none py-3 px-5 rounded-xl text-xs font-black uppercase text-slate-700 bg-slate-100 hover:bg-slate-200">Cancel</button>
                                    </div>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <TransitionRoot as="template" :show="isActivating">
            <Dialog as="div" class="relative z-50" @close="closeStatusModals">
                <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0"
                    enter-to="opacity-100" leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 overflow-y-auto w-full px-4">
                    <div class="flex min-h-full items-center justify-center py-4">
                        <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100" leave="ease-in duration-150"
                            leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                            <DialogPanel
                                class="relative w-full max-w-md transform rounded-2xl bg-white dark:bg-slate-800 shadow-2xl border border-emerald-100 dark:border-emerald-900/40 overflow-hidden">
                                <div class="p-6">
                                    <div class="flex items-start gap-4">
                                        <div
                                            class="flex-shrink-0 h-11 w-11 rounded-xl bg-emerald-100 flex items-center justify-center">
                                            <ShieldCheck class="h-5 w-5 text-emerald-600" />
                                        </div>
                                        <div class="flex-1">
                                            <DialogTitle
                                                class="text-base font-black text-slate-900 dark:text-white uppercase">
                                                Reactivate Account</DialogTitle>
                                            <p class="text-sm text-slate-500 mt-1">Restore system access for <span
                                                    class="font-bold text-slate-900 dark:text-white">{{
                                                        targetAccount?.name }}</span>.</p>
                                        </div>
                                    </div>
                                    <div class="mt-5">
                                        <label
                                            class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Reason
                                            for Reactivation <span class="text-emerald-500">*</span></label>
                                        <textarea v-model="statusReason" rows="3"
                                            placeholder="e.g. Cleared for return, contract renewed..."
                                            class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900/40 text-sm text-slate-900 dark:text-white focus:ring-emerald-400 focus:border-emerald-400"></textarea>
                                    </div>
                                    <div class="mt-5 flex flex-col sm:flex-row-reverse gap-3">
                                        <button @click="toggleAccountStatus('reactivate')"
                                            :disabled="!statusReason.trim()"
                                            :class="['w-full sm:flex-none py-3 px-5 rounded-xl text-xs font-black uppercase text-white transition-all', statusReason.trim() ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-emerald-300 cursor-not-allowed']">Confirm
                                            Reactivation</button>
                                        <button @click="closeStatusModals"
                                            class="w-full sm:flex-none py-3 px-5 rounded-xl text-xs font-black uppercase text-slate-700 bg-slate-100 hover:bg-slate-200">Cancel</button>
                                    </div>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <TransitionRoot as="template" :show="isViewingHistory">
            <Dialog as="div" class="relative z-50" @close="closeHistoryModal">
                <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0"
                    enter-to="opacity-100" leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 overflow-y-auto w-full px-4">
                    <div class="flex min-h-full items-center justify-center py-4">
                        <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100" leave="ease-in duration-150"
                            leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                            <DialogPanel
                                class="relative w-full max-w-lg transform rounded-2xl bg-white dark:bg-slate-800 shadow-2xl border border-slate-100 dark:border-slate-700 overflow-hidden flex flex-col max-h-[80vh]">
                                <div
                                    class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between bg-slate-50 dark:bg-slate-800">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-indigo-100 text-indigo-600 rounded-lg">
                                            <History class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <DialogTitle
                                                class="text-base font-black text-slate-900 dark:text-white uppercase">
                                                Audit Logs</DialogTitle>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{
                                                historyEmployee?.name }}</p>
                                        </div>
                                    </div>
                                    <button @click="closeHistoryModal"
                                        class="p-1 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 transition-colors">
                                        <X class="h-5 w-5" />
                                    </button>
                                </div>
                                <div
                                    class="p-6 overflow-y-auto custom-scrollbar flex-1 bg-slate-50/50 dark:bg-slate-900/30">
                                    <div v-if="!historyEmployee?.audit_logs || historyEmployee.audit_logs.length === 0"
                                        class="text-center py-10">
                                        <ShieldCheck
                                            class="h-10 w-10 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
                                        <p class="text-sm font-bold text-slate-500">No status changes recorded.</p>
                                    </div>
                                    <div v-else class="space-y-4">
                                        <div v-for="log in historyEmployee.audit_logs" :key="log.id"
                                            class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-100 dark:border-slate-700 shadow-sm relative">
                                            <div
                                                class="absolute top-4 right-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                                {{ formatDate(log.created_at) }}</div>
                                            <div class="flex items-center gap-2 mb-2">
                                                <span v-if="log.action === 'deactivate'"
                                                    class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-red-100 text-red-700">Deactivated</span>
                                                <span v-else-if="log.action === 'reactivate'"
                                                    class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-emerald-100 text-emerald-700">Reactivated</span>
                                                <span v-else
                                                    class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-blue-100 text-blue-700 capitalize">{{
                                                        log.action }}</span>
                                            </div>
                                            <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-2">"{{
                                                log.reason }}"</p>
                                            <p class="text-[10px] text-slate-500 mt-2 font-medium">Authorized by Admin
                                                ID: <span class="font-bold text-slate-700 dark:text-slate-300">{{
                                                    log.admin?.name || log.admin_id || 'System' }}</span></p>
                                        </div>
                                    </div>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <TransitionRoot as="template" :show="isViewingEmployee">
            <Dialog as="div" class="relative z-50" @close="closeViewEmployee">
                <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0"
                    enter-to="opacity-100" leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 overflow-y-auto w-full px-4">
                    <div class="flex min-h-full items-center justify-center py-4">
                        <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100" leave="ease-in duration-150"
                            leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                            <DialogPanel
                                class="relative w-full max-w-md transform rounded-2xl bg-white dark:bg-slate-800 shadow-2xl border border-slate-100 dark:border-slate-700 overflow-hidden">
                                <div
                                    class="px-6 pt-6 pb-4 border-b border-slate-50 dark:border-slate-700 flex items-start justify-between">
                                    <DialogTitle class="text-base font-black text-slate-900 dark:text-white uppercase">
                                        Employee Details</DialogTitle>
                                    <button @click="closeViewEmployee"
                                        class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 transition-colors">
                                        <X class="h-5 w-5" />
                                    </button>
                                </div>
                                <div v-if="selectedEmployee" class="p-6">
                                    <div class="flex items-center gap-4 mb-6">
                                        <div v-if="selectedEmployee.profile_photo_url"
                                            class="h-16 w-16 rounded-2xl overflow-hidden flex-shrink-0">
                                            <img :src="selectedEmployee.profile_photo_url"
                                                class="h-full w-full object-cover" />
                                        </div>
                                        <div v-else
                                            :class="['h-16 w-16 rounded-2xl flex items-center justify-center font-black text-xl flex-shrink-0', getAvatarColor(selectedEmployee.id)]">
                                            {{ getInitials(selectedEmployee.name) }}
                                        </div>
                                        <div>
                                            <p class="text-lg font-black text-slate-900 dark:text-white">{{
                                                selectedEmployee.name }}</p>
                                            <p class="text-xs font-bold text-slate-400 uppercase">{{
                                                selectedEmployee.position }} · ID #{{ selectedEmployee.employee_id ||
                                                    selectedEmployee.id }}</p>
                                            <span
                                                :class="['inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase', checkActive(selectedEmployee) ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700']">
                                                {{ checkActive(selectedEmployee) ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="bg-slate-50 dark:bg-slate-900/40 rounded-xl p-3">
                                            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Email</p>
                                            <p
                                                class="text-[10px] sm:text-xs font-medium text-slate-700 dark:text-slate-300 break-all">
                                                {{ selectedEmployee.email }}</p>
                                        </div>
                                        <div class="bg-slate-50 dark:bg-slate-900/40 rounded-xl p-3">
                                            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Department
                                            </p>
                                            <span
                                                :class="['px-2 py-0.5 rounded-md text-[10px] font-black uppercase', getDeptClass(selectedEmployee.role)]">{{
                                                    selectedEmployee.role }}</span>
                                        </div>
                                        <div class="bg-slate-50 dark:bg-slate-900/40 rounded-xl p-3">
                                            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Position</p>
                                            <p class="text-xs font-bold text-slate-700 dark:text-slate-300 capitalize">
                                                {{ selectedEmployee.position }}</p>
                                        </div>
                                        <div class="bg-slate-50 dark:bg-slate-900/40 rounded-xl p-3">
                                            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Join Date
                                            </p>
                                            <p class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                                {{ formatDate(selectedEmployee.join_date) }}
                                            </p>
                                        </div>
                                    </div>
                                    <button @click="closeViewEmployee"
                                        class="mt-5 w-full py-3 sm:py-2.5 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm font-bold hover:bg-slate-200 dark:hover:bg-slate-600 transition-all">Close</button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <TransitionRoot as="template" :show="isGrading">
            <Dialog as="div" class="relative z-50" @close="closeGradingModal">
                <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0"
                    enter-to="opacity-100" leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 overflow-y-auto w-full px-4">
                    <div class="flex min-h-full items-center justify-center py-4">
                        <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100" leave="ease-in duration-150"
                            leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                            <DialogPanel
                                class="relative w-full max-w-xl transform overflow-hidden rounded-[2rem] bg-white dark:bg-slate-800 p-6 sm:p-8 shadow-2xl border border-slate-100 dark:border-slate-700">
                                <div class="flex items-center mb-6">
                                    <h3 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white uppercase">
                                        Coursework Grading</h3>
                                    <button @click="closeGradingModal"
                                        class="ml-auto text-slate-400 hover:text-slate-600 transition-colors">
                                        <X class="h-6 w-6" />
                                    </button>
                                </div>
                                <p class="text-sm text-slate-500 mb-6">
                                    Rate <b class="text-slate-900 dark:text-white">{{ gradingTrainee?.name }}</b> based
                                    on their training performance.
                                    <span class="block mt-1 text-[11px] text-amber-600 font-bold">80% average (20 of 25
                                        stars) required for promotion.</span>
                                </p>
                                <div class="space-y-6">
                                    <div v-for="criterion in gradeCriteria" :key="criterion.id">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                                            {{ criterion.label }}</p>
                                        <div class="flex gap-2">
                                            <button v-for="star in 5" :key="star" type="button"
                                                @click="gradeData[criterion.id] = star"
                                                class="focus:outline-none transition-transform active:scale-110">
                                                <Star
                                                    :class="[gradeData[criterion.id] >= star ? 'text-amber-400 fill-amber-400' : 'text-slate-200 dark:text-slate-600', 'h-7 w-7 transition-colors cursor-pointer']" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="gradeIsValid"
                                    :class="['mt-6 p-4 rounded-2xl border-2 transition-all', isPassing ? 'border-emerald-300 bg-emerald-50 dark:bg-emerald-900/20' : 'border-red-200 bg-red-50 dark:bg-red-900/20']">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                                Total Average</p>
                                            <p
                                                :class="['text-3xl font-black mt-0.5', isPassing ? 'text-emerald-600' : 'text-red-500']">
                                                {{ gradeTotal }}%</p>
                                        </div>
                                        <div v-if="isPassing"
                                            class="flex flex-col sm:flex-row items-center gap-2 px-3 py-2 bg-emerald-100 text-emerald-700 rounded-xl text-center">
                                            <CheckCircle2 class="h-4 w-4" />
                                            <span class="text-xs font-black uppercase">Will be promoted</span>
                                        </div>
                                        <div v-else
                                            class="flex flex-col sm:flex-row items-center gap-2 px-3 py-2 bg-red-100 text-red-600 rounded-xl text-center">
                                            <XCircle class="h-4 w-4" />
                                            <span class="text-xs font-black uppercase">Below threshold</span>
                                        </div>
                                    </div>
                                    <p v-if="isPassing"
                                        class="text-[10px] sm:text-xs text-emerald-600 mt-2 font-medium">✓ This trainee
                                        will automatically be promoted to Staff upon saving.</p>
                                </div>
                                <p v-else class="mt-4 text-xs text-slate-400 text-center font-bold text-amber-600">Rate
                                    all 5 criteria to see the score preview and enable saving.</p>
                                <div class="mt-8 flex flex-col gap-3">
                                    <button @click="submitGrade" :disabled="isSavingGrade || !gradeIsValid"
                                        :class="['w-full py-4 rounded-xl font-bold uppercase text-xs tracking-widest transition-all', (isSavingGrade || !gradeIsValid) ? 'bg-slate-300 text-slate-500 cursor-not-allowed' : 'bg-slate-900 dark:bg-blue-600 text-white hover:opacity-90 shadow-lg active:scale-95']">{{
                                            isSavingGrade ? 'Saving...' : 'Save Trainee Grades' }}</button>
                                    <button @click="closeGradingModal"
                                        class="w-full py-3 text-slate-400 font-bold hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xl transition-all text-xs uppercase">Cancel</button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <TransitionRoot as="template" :show="isConfirmingPromotion">
            <Dialog as="div" class="relative z-50" @close="closePromotionModal">
                <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0"
                    enter-to="opacity-100" leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 overflow-y-auto w-full px-4">
                    <div class="flex min-h-full items-center justify-center py-4">
                        <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100" leave="ease-in duration-150"
                            leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                            <DialogPanel
                                class="relative w-full max-w-sm transform bg-white dark:bg-slate-800 rounded-[2rem] p-6 sm:p-8 shadow-2xl text-center border border-blue-100 dark:border-blue-900/40">
                                <div
                                    class="h-20 w-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <UserCheck class="h-10 w-10 text-blue-600" />
                                </div>
                                <DialogTitle class="text-xl font-black text-slate-900 dark:text-white uppercase">Confirm
                                    Promotion</DialogTitle>
                                <p class="text-slate-500 text-sm mt-2 mb-3 leading-relaxed">
                                    Promote <b class="text-slate-900 dark:text-white">{{ selectedTrainee?.name }}</b> to
                                    official <span class="text-blue-600 font-bold">Staff</span>?
                                </p>
                                <div v-if="selectedTrainee?.trainee_grade"
                                    class="flex flex-col sm:flex-row items-center justify-center gap-2 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl mb-6">
                                    <CheckCircle2 class="h-4 w-4 text-emerald-600 flex-shrink-0 hidden sm:block" />
                                    <p class="text-[10px] sm:text-xs text-emerald-700 font-black">
                                        Final Score: {{ selectedTrainee.trainee_grade.total_percentage }}% — Qualified
                                    </p>
                                </div>
                                <div class="flex flex-col gap-3">
                                    <button @click="promoteToStaff"
                                        class="w-full bg-blue-600 text-white py-3.5 sm:py-4 rounded-xl font-bold shadow-lg shadow-blue-500/20 active:scale-95 transition-all uppercase text-xs tracking-widest hover:bg-blue-700">Confirm
                                        Promotion</button>
                                    <button @click="closePromotionModal"
                                        class="w-full px-6 py-3 text-slate-400 font-bold hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xl transition-all text-xs uppercase">Cancel</button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

    </AuthenticatedLayout>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-12px);
}

/* Hide scrollbar for horizontal scrolling tabs */
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.3);
    border-radius: 10px;
}
</style>