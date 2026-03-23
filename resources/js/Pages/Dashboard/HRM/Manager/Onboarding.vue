<script setup>
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, computed, watch } from 'vue'
import draggable from 'vuedraggable'
import {
    UserPlus, CheckCircle2, Clock, ArrowUpRight, Users, Briefcase,
    Calendar, GripVertical, X, FileText, User, Home, FileCheck,
    Upload, Trash2, ShieldCheck, Save, CheckCircle, AlertCircle, Lock,
    Eye, UserMinus, UserCheck, Mail, Phone, Info, Maximize2, Shield
} from 'lucide-vue-next'

const props = defineProps({
    applicants: {
        type: Array,
        default: () => []
    }
})

/* -------------------------
   MODAL & TOAST STATE
--------------------------*/
const isModalOpen = ref(false)
const showToast = ref(false)
const toastMessage = ref('')

const isConfirmModalOpen = ref(false)
const isViewModalOpen = ref(false)
const isFailModalOpen = ref(false)
const isAccountModalOpen = ref(false)
const isRoleSelectionModalOpen = ref(false) // New: For role selection
const selectedRole = ref('HRM') // Default selection
const pendingMove = ref(null)
const selectedApplicant = ref(null)

const roles = ['HRM', 'SCM', 'FIN', 'CRM', 'ECO']

const triggerToast = (msg) => {
    toastMessage.value = msg;
    showToast.value = true;
    setTimeout(() => showToast.value = false, 3000);
};

/* -------------------------
   FORM (Includes Schedule)
--------------------------*/
const form = useForm({
    // Applicant Info
    first_name: '',
    middle_name: '',
    last_name: '',
    email: '',
    phone_number: '',
    street_address: '',
    street_address_line2: '',
    city: '',
    state_province: '',
    postal_zip_code: '',
    position_applied: '',
    expected_salary: '',
    notice_period: 'immediate',
    textile_experience: 'no',
    status: 'Interview', // Defaulting to Interview to move to first column
    sss_file: null,
    philhealth_file: null,
    pagibig_file: null,

    // Integrated Schedule Info
    scheduled_at: '',
    interview_type: '',
    duration: '45',
    interviewer: '',
    location: '',
    notes: ''
});

/* -------------------------
   FILE HANDLERS
--------------------------*/
const handleFileUpload = (event, type) => {
    const file = event.target.files[0]
    if (file) {
        if (file.size > 2 * 1024 * 1024) {
            triggerToast('File size must be less than 2MB');
            return;
        }
        form[type + '_file'] = file
    }
}

const removeFile = (type) => {
    form[type + '_file'] = null
}

const submitForm = () => {
    if (!form.scheduled_at || !form.interview_type) {
        triggerToast('Please set the interview schedule.');
        return;
    }

    form.status = 'Interview';

    form.post(route('hrm.applicants.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isModalOpen.value = false;
            form.reset();
            triggerToast('Applicant registered & Interview scheduled!');
        },
        onError: (errors) => {
            console.error(errors);
            triggerToast('Error adding applicant. Please check required fields.');
        }
    })
}

/* -------------------------
   PIPELINE LOGIC
--------------------------*/
const pipeline = ref({
    'FOR INTERVIEW': [],
    'FINAL INTERVIEW': [],
    'ONBOARDING': []
})

watch(() => props.applicants, (newApplicants) => {
    // Filter out anyone whose status is 'Account Created'
    const activeApplicants = newApplicants.filter(a => a.status !== 'Account Created');

    pipeline.value = {
        'FOR INTERVIEW': activeApplicants.filter(a => a.has_interview && a.status !== 'final' && a.status !== 'onboard' && a.status !== 'Rejected'),
        'FINAL INTERVIEW': activeApplicants.filter(a => a.status?.toLowerCase() === 'final'),
        'ONBOARDING': activeApplicants.filter(a => a.status?.toLowerCase() === 'onboard')
    }
}, { immediate: true })

const checkMove = (evt) => {
    const fromStage = evt.from.closest('[data-status]')?.dataset.status;
    const toStage = evt.to.closest('[data-status]')?.dataset.status;
    if (fromStage === 'FOR INTERVIEW' && toStage !== 'FINAL INTERVIEW') return false;
    if (fromStage === 'FINAL INTERVIEW' && toStage !== 'ONBOARDING') return false;
    if (fromStage === 'ONBOARDING') return false;
    return true;
};

const onDragEnd = (evt) => {
    const applicant = evt.item._underlying_vm_
    const fromStatus = evt.from.closest('[data-status]')?.dataset.status
    const toStatus = evt.to.closest('[data-status]')?.dataset.status
    if (!toStatus || !applicant || fromStatus === toStatus) return
    pendingMove.value = {
        id: applicant.id,
        name: `${applicant.first_name} ${applicant.last_name}`,
        from: fromStatus,
        to: toStatus
    };
    isConfirmModalOpen.value = true;
}

const confirmMove = () => {
    if (!pendingMove.value) return;
    router.patch(route('hrm.applicants.update-stage', pendingMove.value.id), {
        status: pendingMove.value.to
    }, {
        preserveScroll: true,
        onSuccess: () => {
            triggerToast(`Moved ${pendingMove.value.name} to ${pendingMove.value.to}`);
            isConfirmModalOpen.value = false;
            pendingMove.value = null;
        }
    })
}

const cancelMove = () => {
    isConfirmModalOpen.value = false;
    pendingMove.value = null;
    router.reload({ only: ['applicants'] });
}

/* -------------------------
   ACTIONS LOGIC
--------------------------*/
const openViewModal = (applicant) => {
    selectedApplicant.value = applicant;
    isViewModalOpen.value = true;
}

const openFailModal = (applicant) => {
    selectedApplicant.value = applicant;
    isFailModalOpen.value = true;
}

const openAccountModal = (applicant) => {
    selectedApplicant.value = applicant;
    isAccountModalOpen.value = true;
}

const proceedToRoleSelection = () => {
    isAccountModalOpen.value = false;
    isRoleSelectionModalOpen.value = true;
}

const submitFailedStatus = () => {
    router.patch(route('hrm.applicants.update-stage', selectedApplicant.value.id), {
        status: 'Rejected'
    }, {
        preserveScroll: true,
        onSuccess: () => {
            triggerToast(`${selectedApplicant.value.first_name} marked as failed.`);
            isFailModalOpen.value = false;
            selectedApplicant.value = null;
        }
    })
}

const confirmAccountCreation = () => {
    if (!selectedApplicant.value) return;

    router.post(route('hrm.applicants.create-user', selectedApplicant.value.id), {
        position: 'trainee', // Default as requested
        role: selectedRole.value // Selection from the second modal
    }, {
        preserveScroll: true,
        onSuccess: () => {
            triggerToast(`User account created for ${selectedApplicant.value.first_name}!`);
            isRoleSelectionModalOpen.value = false;
            selectedApplicant.value = null;
        },
        onError: () => {
            triggerToast('Failed to create account. Check if email already exists.');
        }
    })
}

const stats = computed(() => [
    { label: 'Total Applicants', value: props.applicants.length, icon: Users, color: 'text-blue-600', bg: 'bg-blue-50' },
    { label: 'For Interview', value: pipeline.value['FOR INTERVIEW'].length, icon: Calendar, color: 'text-indigo-600', bg: 'bg-indigo-50' },
    { label: 'Final Interview', value: pipeline.value['FINAL INTERVIEW'].length, icon: Clock, color: 'text-amber-600', bg: 'bg-amber-50' },
    { label: 'Onboarding', value: pipeline.value['ONBOARDING'].length, icon: CheckCircle2, color: 'text-emerald-600', bg: 'bg-emerald-50' },
])
</script>

<template>

    <Head title="Recruitment Pipeline" />

    <AuthenticatedLayout>
        <Transition name="toast">
            <div v-if="showToast"
                class="fixed top-6 right-6 z-[100] flex items-center gap-3 px-6 py-4 bg-slate-900 text-white rounded-2xl shadow-2xl border border-white/10">
                <CheckCircle class="h-5 w-5 text-emerald-400" />
                <p class="text-sm font-bold uppercase tracking-tight">{{ toastMessage }}</p>
            </div>
        </Transition>

        <div v-if="isViewModalOpen" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="isViewModalOpen = false"></div>
            <div
                class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl flex flex-col max-h-[85vh] overflow-hidden">
                <div
                    class="px-8 py-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
                    <h2 class="text-xl font-black text-slate-900 uppercase tracking-tight">Applicant <span
                            class="text-blue-600">Profile</span></h2>
                    <button @click="isViewModalOpen = false"
                        class="p-2 hover:bg-slate-100 rounded-full transition-colors">
                        <X class="h-5 w-5 text-slate-400" />
                    </button>
                </div>
                <div class="overflow-y-auto p-8 space-y-6">
                    <div class="flex items-center gap-4 bg-blue-50 p-6 rounded-3xl">
                        <div
                            class="h-16 w-16 bg-blue-600 rounded-2xl flex items-center justify-center text-white text-2xl font-black">
                            {{ selectedApplicant?.first_name.charAt(0) }}</div>
                        <div>
                            <h3 class="text-lg font-black text-slate-900">{{ selectedApplicant?.first_name }} {{
                                selectedApplicant?.last_name }}</h3>
                            <p class="text-sm font-bold text-blue-600 uppercase tracking-widest">{{
                                selectedApplicant?.position_applied }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 p-4 rounded-2xl">
                            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Email Address</p>
                            <p class="text-sm font-bold text-slate-700">{{ selectedApplicant?.email }}</p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-2xl">
                            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Contact Number</p>
                            <p class="text-sm font-bold text-slate-700">{{ selectedApplicant?.phone_number || 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="p-8 border-t border-slate-100 flex justify-end">
                    <button @click="isViewModalOpen = false"
                        class="px-8 py-3 bg-slate-900 text-white rounded-xl font-black text-xs uppercase">Close
                        Preview</button>
                </div>
            </div>
        </div>

        <div v-if="isFailModalOpen" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="isFailModalOpen = false"></div>
            <div class="relative w-full max-sm bg-white rounded-[2rem] p-8 shadow-2xl text-center">
                <div class="h-16 w-16 bg-rose-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <UserMinus class="h-8 w-8 text-rose-600" />
                </div>
                <h3 class="text-lg font-black text-slate-900 uppercase">Mark as Failed?</h3>
                <p class="text-slate-500 text-sm mt-2 mb-8">This will move <b>{{ selectedApplicant?.first_name }}</b> to
                    the rejected list.</p>
                <div class="flex gap-3">
                    <button @click="isFailModalOpen = false"
                        class="flex-1 px-6 py-3 text-slate-500 font-bold">Cancel</button>
                    <button @click="submitFailedStatus"
                        class="flex-1 bg-rose-600 text-white py-3 rounded-xl font-bold">Confirm Fail</button>
                </div>
            </div>
        </div>

        <div v-if="isAccountModalOpen" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="isAccountModalOpen = false"></div>
            <div class="relative w-full max-w-sm bg-white rounded-[2rem] p-8 shadow-2xl text-center">
                <div class="h-16 w-16 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <UserCheck class="h-8 w-8 text-emerald-600" />
                </div>
                <h3 class="text-lg font-black text-slate-900 uppercase">Create ERP Account?</h3>
                <p class="text-slate-500 text-sm mt-2 mb-8">Would you like to register <b>{{
                    selectedApplicant?.first_name
                        }}</b> as a trainee? You will select their department next.</p>
                <div class="flex flex-col gap-3">
                    <button @click="proceedToRoleSelection"
                        class="w-full bg-emerald-600 text-white py-4 rounded-xl font-bold shadow-lg shadow-emerald-500/20 active:scale-95 transition-all">Yes,
                        Proceed</button>
                    <button @click="isAccountModalOpen = false"
                        class="w-full px-6 py-3 text-slate-500 font-bold hover:bg-slate-50 rounded-xl transition-all">Cancel</button>
                </div>
            </div>
        </div>

        <div v-if="isRoleSelectionModalOpen" class="fixed inset-0 z-[130] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="isRoleSelectionModalOpen = false">
            </div>
            <div class="relative w-full max-w-md bg-white rounded-[2rem] p-8 shadow-2xl">
                <div class="text-center mb-6">
                    <div class="h-14 w-14 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <Shield class="h-6 w-6 text-blue-600" />
                    </div>
                    <h3 class="text-lg font-black text-slate-900 uppercase">Select Department Role</h3>
                    <p class="text-slate-500 text-xs">Assign a module access role for this trainee.</p>
                </div>

                <div class="grid grid-cols-3 gap-2 mb-8">
                    <button v-for="role in roles" :key="role" @click="selectedRole = role"
                        :class="selectedRole === role ? 'bg-blue-600 text-white border-blue-600' : 'bg-slate-50 text-slate-600 border-slate-100 hover:border-blue-200'"
                        class="py-3 px-2 rounded-xl border text-xs font-black transition-all">
                        {{ role }}
                    </button>
                </div>

                <div class="flex flex-col gap-3">
                    <button @click="confirmAccountCreation"
                        class="w-full bg-slate-900 text-white py-4 rounded-xl font-bold text-sm uppercase tracking-widest shadow-xl active:scale-95 transition-all">
                        Finalize Account
                    </button>
                    <button @click="isRoleSelectionModalOpen = false"
                        class="w-full py-3 text-slate-400 font-bold text-xs">Go Back</button>
                </div>
            </div>
        </div>

        <div v-if="isConfirmModalOpen" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="cancelMove"></div>
            <div
                class="relative w-full max-w-sm bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 shadow-2xl text-center">
                <div
                    class="h-20 w-20 bg-blue-50 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                    <ArrowUpRight class="h-10 w-10 text-blue-600" />
                </div>
                <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight mb-2">Stage Promotion</h3>
                <p class="text-slate-500 text-sm leading-relaxed mb-8">Move <span class="font-bold text-slate-900">{{
                    pendingMove?.name }}</span> from <span class="font-bold text-amber-600">{{ pendingMove?.from
                        }}</span> to <span class="font-bold text-emerald-600">{{ pendingMove?.to }}</span>?</p>
                <div class="flex flex-col gap-3">
                    <button @click="confirmMove"
                        class="w-full bg-blue-600 text-white py-4 rounded-2xl font-bold text-sm shadow-xl shadow-blue-500/25 active:scale-95 transition-all">Yes,
                        Promote Candidate</button>
                    <button @click="cancelMove"
                        class="w-full px-6 py-4 rounded-2xl text-sm font-bold text-slate-500 hover:bg-slate-100 transition-all">No,
                        Keep in Stage</button>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Recruitment
                    <span class="text-blue-600">Pipeline</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Manage the end-to-end recruitment
                    journey.</p>
            </div>
            <button @click="isModalOpen = true"
                class="flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-2xl font-bold text-sm shadow-lg shadow-blue-500/20 active:scale-95">
                <UserPlus class="h-4 w-4" /> Add New Applicant
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div v-for="stat in stats" :key="stat.label"
                class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-100 shadow-sm">
                <div class="flex items-center justify-between">
                    <div :class="[stat.bg, 'p-2.5 rounded-xl']">
                        <component :is="stat.icon" :class="[stat.color, 'h-5 w-5']" />
                    </div>
                    <ArrowUpRight class="h-4 w-4 text-slate-300" />
                </div>
                <div class="mt-4">
                    <p class="text-sm font-medium text-slate-500">{{ stat.label }}</p>
                    <p class="text-2xl font-bold text-slate-900">{{ stat.value }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div v-for="(list, stage) in pipeline" :key="stage" class="flex flex-col min-w-[300px]"
                :data-status="stage">
                <div class="flex items-center justify-between mb-4 px-2">
                    <h2
                        class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-blue-600"></span> {{ stage }}
                    </h2>
                    <span class="px-2 py-0.5 rounded-md bg-slate-100 text-[10px] font-bold text-slate-500">{{
                        list.length }}</span>
                </div>

                <draggable v-model="pipeline[stage]" group="applicants" item-key="id" animation="250"
                    ghost-class="ghost-class" :move="checkMove" @end="onDragEnd"
                    class="space-y-4 min-h-[600px] p-4 bg-slate-50/50 dark:bg-slate-900/30 rounded-[2.5rem] border-2 border-dashed border-slate-200 dark:border-slate-700">
                    <template #item="{ element }">
                        <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-100 shadow-sm transition-all group relative overflow-hidden"
                            :class="stage === 'ONBOARDING' ? 'cursor-default opacity-90 grayscale-[0.3]' : 'cursor-grab active:cursor-grabbing hover:border-blue-400'">

                            <div v-if="stage === 'ONBOARDING'"
                                class="absolute top-2 right-2 flex items-center gap-1 px-2 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-[9px] font-black uppercase tracking-widest z-10">
                                <Lock class="h-2.5 w-2.5" /> Stage Locked
                            </div>

                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                                        {{ element.first_name?.charAt(0) }}</div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{
                                            element.first_name }} {{ element.last_name }}</p>
                                        <p class="text-[10px] font-medium text-slate-400 uppercase tracking-tight">{{
                                            element.email }}</p>
                                    </div>
                                </div>
                                <GripVertical v-if="stage !== 'ONBOARDING'"
                                    class="h-4 w-4 text-slate-300 group-hover:text-slate-400" />
                            </div>

                            <div v-if="stage !== 'ONBOARDING'" class="flex gap-2 mb-4">
                                <button @click="openViewModal(element)"
                                    class="flex-1 flex items-center justify-center gap-1.5 py-2 bg-slate-50 text-slate-600 rounded-xl text-[10px] font-black uppercase tracking-tighter hover:bg-blue-50 hover:text-blue-600 transition-all border border-slate-100">
                                    <Eye class="h-3 w-3" /> View
                                </button>
                                <button @click="openFailModal(element)"
                                    class="flex-1 flex items-center justify-center gap-1.5 py-2 bg-slate-50 text-slate-600 rounded-xl text-[10px] font-black uppercase tracking-tighter hover:bg-rose-50 hover:text-rose-600 transition-all border border-slate-100">
                                    <UserMinus class="h-3 w-3" /> Failed
                                </button>
                            </div>
                            <div v-else class="mb-4">
                                <button @click="openAccountModal(element)"
                                    class="w-full flex items-center justify-center gap-2 py-3 bg-emerald-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-100">
                                    <UserCheck class="h-4 w-4" /> Create ERP Account
                                </button>
                            </div>

                            <div class="flex justify-between items-center border-t border-slate-50 pt-4">
                                <span class="text-[10px] font-black text-slate-400 uppercase">{{ element.created_at ?
                                    new Date(element.created_at).toLocaleDateString() : 'New' }}</span>
                                <div class="flex gap-2 text-slate-400">
                                    <Phone class="h-3 w-3" />
                                    <Mail class="h-3 w-3" />
                                </div>
                            </div>
                        </div>
                    </template>
                </draggable>
            </div>
        </div>

        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="isModalOpen = false"></div>
            <div
                class="relative w-full max-w-4xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                <div
                    class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white dark:bg-slate-900 z-20">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">New <span
                                class="text-blue-600">Applicant</span></h2>
                        <p class="text-xs text-slate-500 font-medium">Registration and Interview Scheduling.</p>
                    </div>
                    <button @click="isModalOpen = false"
                        class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors">
                        <X class="h-5 w-5 text-slate-400" />
                    </button>
                </div>
                <div class="overflow-y-auto p-8 custom-scrollbar space-y-8">
                    <form @submit.prevent="submitForm" class="space-y-8">
                        <div class="bg-blue-50/50 dark:bg-blue-900/10 p-6 rounded-[2rem] border border-blue-100">
                            <h4 class="text-sm font-black text-slate-800 mb-6 uppercase flex items-center gap-2">
                                <User class="h-4 w-4 text-blue-600" /> Personal Info
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="space-y-1.5"><label class="text-xs font-bold text-slate-500">First Name
                                        *</label><input v-model="form.first_name" type="text" required
                                        class="input-style">
                                </div>
                                <div class="space-y-1.5"><label class="text-xs font-bold text-slate-500">Middle
                                        Name</label><input v-model="form.middle_name" type="text" class="input-style">
                                </div>
                                <div class="space-y-1.5"><label class="text-xs font-bold text-slate-500">Last Name
                                        *</label><input v-model="form.last_name" type="text" required
                                        class="input-style"></div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <input v-model="form.email" type="email" placeholder="Email *" class="input-style"
                                    required>
                                <input v-model="form.phone_number" type="tel" placeholder="Phone *" class="input-style"
                                    required>
                                <input v-model="form.position_applied" type="text" placeholder="Position *"
                                    class="input-style" required>
                                <select v-model="form.notice_period" class="input-style select-style">
                                    <option value="immediate">Immediate</option>
                                    <option value="15_days">15 Days</option>
                                    <option value="30_days">30 Days</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <input v-model="form.street_address" type="text" placeholder="Street Address *"
                                    class="input-style" required>
                                <input v-model="form.city" type="text" placeholder="City *" class="input-style"
                                    required>
                                <input v-model="form.state_province" type="text" placeholder="State/Province *"
                                    class="input-style" required>
                                <input v-model="form.postal_zip_code" type="text" placeholder="Postal Code *"
                                    class="input-style" required>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <select v-model="form.textile_experience" class="input-style select-style">
                                    <option value="yes">Textile Experience: Yes</option>
                                    <option value="no">Textile Experience: No</option>
                                </select>
                                <input v-model="form.expected_salary" type="number"
                                    placeholder="Expected Salary (Optional)" class="input-style">
                            </div>
                        </div>

                        <div class="bg-indigo-50/50 p-6 rounded-[2rem] border border-indigo-100">
                            <h4 class="text-sm font-black text-indigo-700 mb-6 uppercase flex items-center gap-2">
                                <Calendar class="h-4 w-4" /> Set Interview Schedule
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500">Interview Type *</label>
                                    <select v-model="form.interview_type" required class="input-style select-style">
                                        <option value="">Select type</option>
                                        <option value="phone">Phone Screen</option>
                                        <option value="technical">Technical Interview</option>
                                        <option value="behavioral">Behavioral Interview</option>
                                        <option value="video">Video Conference</option>
                                        <option value="onsite">On-site Interview</option>
                                    </select>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500">Date & Time *</label>
                                    <input type="datetime-local" v-model="form.scheduled_at" required
                                        class="input-style">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500">Duration (Minutes)</label>
                                    <select v-model="form.duration" class="input-style select-style">
                                        <option value="15">15m</option>
                                        <option value="30">30m</option>
                                        <option value="45">45m</option>
                                        <option value="60">1h</option>
                                    </select>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500">Location</label>
                                    <input v-model="form.location" type="text" placeholder="Office/Link"
                                        class="input-style">
                                </div>
                            </div>
                            <div class="mt-4">
                                <input v-model="form.interviewer" type="text" placeholder="Interviewer(s)"
                                    class="input-style mb-4">
                                <textarea v-model="form.notes" rows="2" placeholder="Notes for the interview..."
                                    class="input-style resize-none"></textarea>
                            </div>
                        </div>

                        <div class="bg-emerald-50/20 p-6 rounded-[2rem] border border-emerald-100">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div v-for="type in ['sss', 'philhealth', 'pagibig']" :key="type" class="space-y-2">
                                    <p class="text-[10px] font-black text-slate-400 uppercase ml-1">{{
                                        type.toUpperCase() }} ID
                                    </p>
                                    <div :class="form[type + '_file'] ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 bg-white dark:bg-slate-800'"
                                        class="relative h-24 rounded-2xl border-2 border-dashed flex flex-col items-center justify-center p-2 group transition-all cursor-pointer">
                                        <template v-if="!form[type + '_file']">
                                            <Upload class="h-4 w-4 text-slate-300 group-hover:text-blue-500" /><input
                                                type="file" @change="(e) => handleFileUpload(e, type)"
                                                class="absolute inset-0 opacity-0 cursor-pointer"
                                                accept=".jpg,.jpeg,.png">
                                        </template>
                                        <template v-else>
                                            <FileCheck class="h-5 w-5 text-emerald-600" /><span
                                                class="text-[8px] truncate w-20 text-center text-slate-600 dark:text-slate-300">{{
                                                    form[type +
                                                        '_file'].name }}</span><button @click="removeFile(type)" type="button"
                                                class="mt-1 text-rose-500">
                                                <Trash2 class="h-3 w-3" />
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex justify-between items-center pt-8 border-t border-slate-100 dark:border-slate-800 sticky bottom-0 bg-white dark:bg-slate-900 py-4 z-10">
                            <div class="flex items-center text-[10px] font-black uppercase text-slate-400">
                                <ShieldCheck class="h-4 w-4 text-emerald-500 mr-2" /> Secured & Encrypted
                            </div>
                            <div class="flex gap-3">
                                <button type="button" @click="isModalOpen = false"
                                    class="px-6 py-3 text-slate-500 font-bold hover:bg-slate-100 dark:hover:bg-slate-800 rounded-2xl transition-all">Cancel</button>
                                <button type="submit" :disabled="form.processing"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-10 py-3 rounded-2xl font-bold flex items-center gap-2 transition-all shadow-lg shadow-blue-500/20 active:scale-95 disabled:opacity-50">
                                    <Save class="h-4 w-4" /> {{ form.processing ? 'Saving...' : 'Save & Schedule' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.input-style {
    @apply w-full px-5 py-3.5 rounded-2xl bg-white dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 text-sm focus:ring-2 focus:ring-blue-600 transition-all;
}

.select-style {
    @apply appearance-none bg-no-repeat bg-right;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-size: 1.2rem;
    background-position: calc(100% - 1rem);
}

.ghost-class {
    background: #eff6ff !important;
    border: 2px solid #3b82f6 !important;
    opacity: 0.8;
}

.toast-enter-active,
.toast-leave-active {
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.toast-enter-from,
.toast-leave-to {
    transform: translateY(-20px);
    opacity: 0;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
}
</style>