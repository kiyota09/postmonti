<script setup>
import { Head, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, computed } from 'vue'
import {
    Calendar, Clock, Video, MapPin, XCircle, ClipboardList,
    ExternalLink, PlayCircle, CalendarClock, CheckCircle2,
    Eye, Edit3, User, Mail, Phone, Briefcase, CheckCheck,
    Send, AlertTriangle, ClipboardPlus
} from 'lucide-vue-next'

const props = defineProps({
    todays_interviews: Array,
    upcoming_applicants: Array,
    past_interviews: Array
})

// Modal States
const showScoreModal = ref(false)
const showConfirmModal = ref(false)
const showDetailsModal = ref(false)
const showRescheduleModal = ref(false)
const showStatusConfirmModal = ref(false)
const selectedInterview = ref(null)

// Opens the confirmation modal for status update
const confirmStatusUpdate = (item) => {
    selectedInterview.value = item;
    showStatusConfirmModal.value = true;
}

// The actual submission logic
function submitStatus() {
    const item = selectedInterview.value;
    router.post('InterviewController/update-status', {
        id: item.id,
        status: 'final'
    }, {
        onSuccess: () => {
            console.log('Update successful!');
            showStatusConfirmModal.value = false;
            selectedInterview.value = null;
        },
        onError: (errors) => {
            console.error('Validation or Server Error:', errors);
        }
    });
}

const form = useForm({
    notes: '',
    status: 'Pending',
    new_date: '',
    new_time: '',
})

const filteredUpcomingApplicants = computed(() => props.upcoming_applicants)

const openConfirmModal = (interview) => {
    selectedInterview.value = interview
    showConfirmModal.value = true
}

const openDetailsModal = (interview) => {
    selectedInterview.value = interview
    showDetailsModal.value = true
}

const openRescheduleModal = (interview) => {
    selectedInterview.value = interview
    form.new_date = interview.raw_date || ''
    form.new_time = interview.raw_time || ''
    showRescheduleModal.value = true
}

const startInterview = () => {
    showConfirmModal.value = false
    form.notes = selectedInterview.value.notes || ''
    showScoreModal.value = true
}

const submitEvaluation = () => {
    form.post(route('hrm.employee.interview.evaluate', selectedInterview.value.id), {
        onSuccess: () => {
            showScoreModal.value = false
            form.reset()
            selectedInterview.value = null
        }
    })
}

const submitReschedule = () => {
    form.post(route('hrm.employee.interview.reschedule', selectedInterview.value.id), {
        onSuccess: () => {
            showRescheduleModal.value = false
            form.reset()
            selectedInterview.value = null
        }
    })
}

// Utility for UI consistency
const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'Passed': return 'bg-emerald-50 text-emerald-600 border-emerald-100'   // ✅ added
        case 'Hired': return 'bg-emerald-50 text-emerald-600 border-emerald-100'
        case 'Rejected': return 'bg-rose-50 text-rose-600 border-rose-100'
        case 'Interview': return 'bg-blue-50 text-blue-600 border-blue-100'
        default: return 'bg-slate-50 text-slate-600 border-slate-100'
    }
}

const getInterviewTypeIcon = (type) => {
    return (type?.toLowerCase() === 'virtual' || type?.toLowerCase() === 'phone') ? Video : MapPin
}
</script>

<template>

    <Head title="Interview Management" />

    <AuthenticatedLayout>
        <div class="mb-8">
            <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                Interview <span class="text-blue-600">Management</span>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">
                Manage your daily schedule and candidate evaluations.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 space-y-6">
                <div
                    class="bg-white dark:bg-slate-800 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-700 shadow-sm">
                    <div class="flex items-center justify-between mb-8">
                        <h2
                            class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest flex items-center gap-2">
                            <Clock class="h-4 w-4 text-blue-600" /> Today's Agenda
                        </h2>
                        <span
                            class="px-3 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-600 text-[10px] font-black rounded-lg">
                            {{ todays_interviews.length }} SESSIONS
                        </span>
                    </div>

                    <div class="space-y-6">
                        <div v-if="todays_interviews.length === 0" class="text-center py-10">
                            <p class="text-xs font-bold text-slate-400 uppercase">No interviews for today</p>
                        </div>

                        <div v-for="interview in todays_interviews" :key="interview.id"
                            class="relative pl-6 border-l-2 border-slate-100 dark:border-slate-700 group hover:border-blue-500 transition-colors">
                            <div
                                class="absolute -left-[5px] top-0 h-2 w-2 rounded-full bg-slate-300 group-hover:bg-blue-500">
                            </div>

                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-black text-blue-600 uppercase">{{ interview.time }}</span>
                                <span class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase">
                                    <component :is="getInterviewTypeIcon(interview.type)" class="h-3 w-3" />
                                    {{ interview.type }}
                                </span>
                            </div>

                            <div
                                class="p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-transparent group-hover:border-blue-100 transition-all">
                                <div class="flex items-center gap-3 mb-3">
                                    <div
                                        class="h-8 w-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">
                                        {{ interview.avatar }}
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ interview.name
                                        }}</h4>
                                        <p class="text-[10px] text-slate-400 font-medium">{{ interview.position }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="openDetailsModal(interview)"
                                        class="flex-1 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-[10px] font-bold uppercase tracking-wider hover:bg-blue-600 hover:text-white transition-all">
                                        Details
                                    </button>
                                    <button @click="openConfirmModal(interview)"
                                        class="flex-1 py-2 bg-blue-600 text-white rounded-xl text-[10px] font-bold uppercase tracking-wider shadow-lg shadow-blue-500/20 transition-all">
                                        Score
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-900 rounded-[2rem] p-8 text-white">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="p-3 bg-white/10 rounded-2xl">
                            <ClipboardList class="h-6 w-6 text-blue-400" />
                        </div>
                        <h3 class="text-sm font-black uppercase tracking-widest">Interview Guide</h3>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed mb-6">Use the evaluation form to record technical
                        scores and culture fit for each applicant.</p>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-8">
                <div
                    class="bg-white dark:bg-slate-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
                    <div class="p-8 border-b border-slate-50 dark:border-slate-700 flex justify-between items-center">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-3">
                            <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-xl text-blue-600">
                                <CalendarClock class="h-5 w-5" />
                            </div>
                            Upcoming Schedule for Initial Interview
                        </h2>
                        <span class="text-xs font-bold text-slate-400">
                            {{ filteredUpcomingApplicants.length }} scheduled interviews
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 dark:bg-slate-900/50">
                                    <th
                                        class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                        Candidate</th>
                                    <th
                                        class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                        Date</th>
                                    <th
                                        class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                        Type</th>
                                    <th
                                        class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                <tr v-if="filteredUpcomingApplicants.length === 0">
                                    <td colspan="4"
                                        class="px-8 py-10 text-center text-xs font-bold text-slate-400 uppercase">
                                        No upcoming interviews scheduled
                                    </td>
                                </tr>
                                <tr v-for="applicant in filteredUpcomingApplicants" :key="applicant.id"
                                    class="group hover:bg-slate-50/50 transition-all">
                                    <td class="px-8 py-5">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{
                                                applicant.name }}</span>
                                            <span
                                                class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">{{
                                                    applicant.position }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5">
                                        <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{
                                            applicant.date }}</span>
                                    </td>
                                    <td class="px-8 py-5">
                                        <span class="text-[10px] font-black text-slate-400 uppercase">{{ applicant.type
                                        }}</span>
                                    </td>
                                    <td class="px-8 py-5 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button @click="openDetailsModal(applicant)"
                                                class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all"
                                                title="View Details">
                                                <Eye class="h-4 w-4" />
                                            </button>
                                            <button @click="openRescheduleModal(applicant)"
                                                class="p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-xl transition-all"
                                                title="Reschedule">
                                                <Edit3 class="h-4 w-4" />
                                            </button>
                                            <button @click="openConfirmModal(applicant)"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl text-[10px] font-black uppercase tracking-wider hover:bg-blue-700 transition-all">
                                                <PlayCircle class="h-4 w-4" /> Start
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-slate-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
                    <div class="p-8 border-b border-slate-50 dark:border-slate-700 flex justify-between items-center">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-3">
                            <div class="p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl text-emerald-600">
                                <CheckCircle2 class="h-5 w-5" />
                            </div>
                            Recent Evaluations (Proceed to Final Interview)
                        </h2>
                        <span class="text-xs font-bold text-slate-400">
                            {{ past_interviews.length }} completed
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 dark:bg-slate-900/50">
                                    <th
                                        class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                        Candidate</th>
                                    <th
                                        class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                        Position</th>
                                    <th
                                        class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                        Status</th>
                                    <th
                                        class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                <tr v-for="evalItem in past_interviews" :key="evalItem.id"
                                    class="group hover:bg-slate-50/50 transition-all">
                                    <td class="px-8 py-5">
                                        <span class="text-sm font-bold text-slate-900 dark:text-white">{{ evalItem.name
                                        }}</span>
                                    </td>
                                    <td class="px-8 py-5">
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">{{
                                            evalItem.position }}</span>
                                    </td>
                                    <td class="px-8 py-5">
                                        <span :class="getStatusBadgeClass(evalItem.status)"
                                            class="px-3 py-1 border rounded-lg text-[10px] font-black uppercase">
                                            {{ evalItem.status }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-5 text-right">
                                        <button @click="openDetailsModal(evalItem)"
                                            class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all">
                                            <ExternalLink class="h-4 w-4" />
                                        </button>
                                        <button @click="followupBtn(evalItem)"
                                            class="ml-2 px-3 py-1 bg-blue-600 text-white rounded-xl text-[10px] font-black uppercase tracking-wider hover:bg-blue-700 transition-all">
                                            <ClipboardPlus class="h-4 w-4" />
                                        </button>
                                        <!-- ✅ Only show "Pass to Final" button for passed candidates -->
                                        <button v-if="evalItem.status === 'Passed'"
                                            @click="confirmStatusUpdate(evalItem)"
                                            class="ml-2 px-3 py-1 bg-emerald-500 text-white rounded-xl text-[10px] font-black uppercase tracking-wider hover:bg-emerald-600 transition-all">
                                            <CheckCheck class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showStatusConfirmModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-slate-800 w-full max-w-md rounded-[2.5rem] p-8 shadow-2xl text-center">
                <div
                    class="h-16 w-16 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <CheckCheck class="h-8 w-8" />
                </div>
                <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">Passed to HR Manager for Final
                    Interview?</h3>
                <p class="text-sm text-slate-500 mb-8">Are you sure you want to mark <b>{{ selectedInterview?.name
                        }}</b>'s status as final? This action will update the recruitment records.</p>
                <div class="flex gap-3">
                    <button @click="showStatusConfirmModal = false"
                        class="flex-1 py-4 text-[10px] font-black uppercase text-slate-400 hover:text-slate-600">Cancel</button>
                    <button @click="submitStatus"
                        class="flex-1 py-4 bg-emerald-500 text-white rounded-2xl text-[10px] font-black uppercase shadow-lg shadow-emerald-500/20 hover:bg-emerald-600 transition-all">Confirm
                        Change</button>
                </div>
            </div>
        </div>

        <div v-if="showDetailsModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-slate-800 w-full max-w-md rounded-[2.5rem] p-8 shadow-2xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Candidate
                        Info</h3>
                    <button @click="showDetailsModal = false">
                        <XCircle class="h-6 w-6 text-slate-300 hover:text-rose-500 transition-colors" />
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl">
                        <div class="p-3 bg-blue-100 text-blue-600 rounded-xl">
                            <User class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase">Full Name</p>
                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ selectedInterview?.name }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl">
                        <div class="p-3 bg-emerald-100 text-emerald-600 rounded-xl">
                            <Briefcase class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase">Target Position</p>
                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ selectedInterview?.position
                                }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl">
                        <div class="p-3 bg-purple-100 text-purple-600 rounded-xl">
                            <Mail class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase">Email Address</p>
                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ selectedInterview?.email ||
                                'N/A' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl">
                        <div class="p-3 bg-amber-100 text-amber-600 rounded-xl">
                            <Phone class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase">Phone Number</p>
                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ selectedInterview?.phone ||
                                'N/A' }}</p>
                        </div>
                    </div>
                </div>
                <button @click="showDetailsModal = false"
                    class="w-full mt-8 py-4 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all">
                    Close Details
                </button>
            </div>
        </div>

        <div v-if="showRescheduleModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-slate-800 w-full max-w-md rounded-[2.5rem] p-8 shadow-2xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Reschedule
                    </h3>
                    <button @click="showRescheduleModal = false">
                        <XCircle class="h-6 w-6 text-slate-300 hover:text-rose-500 transition-colors" />
                    </button>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">New
                            Date</label>
                        <input type="date" v-model="form.new_date"
                            class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">New
                            Time</label>
                        <input type="time" v-model="form.new_time"
                            class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500" />
                    </div>

                    <div class="flex gap-3">
                        <button @click="showRescheduleModal = false"
                            class="flex-1 py-4 text-[10px] font-black uppercase text-slate-400 hover:text-slate-600 transition-all">Cancel</button>
                        <button @click="submitReschedule" :disabled="form.processing"
                            class="flex-1 py-4 bg-amber-500 text-white rounded-2xl text-[10px] font-black uppercase shadow-lg shadow-amber-500/20 hover:bg-amber-600 transition-all">
                            {{ form.processing ? 'Updating...' : 'Update Schedule' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showConfirmModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-slate-800 w-full max-w-md rounded-[2.5rem] p-8 shadow-2xl text-center">
                <PlayCircle class="h-12 w-12 text-blue-600 mx-auto mb-4" />
                <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">Start Initial Interview?</h3>
                <p class="text-sm text-slate-500 mb-8">Ready to evaluate <b>{{ selectedInterview?.name }}</b> for <b>{{
                    selectedInterview?.position }}</b>?</p>
                <div class="flex gap-3">
                    <button @click="showConfirmModal = false"
                        class="flex-1 py-4 text-[10px] font-black uppercase text-slate-400 hover:text-slate-600">Cancel</button>
                    <button @click="startInterview"
                        class="flex-1 py-4 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all">Confirm</button>
                </div>
            </div>
        </div>

        <div v-if="showScoreModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-slate-800 w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase">Evaluation</h3>
                    <button @click="showScoreModal = false">
                        <XCircle class="h-6 w-6 text-slate-300 hover:text-rose-500 transition-colors" />
                    </button>
                </div>
                <div class="space-y-6">
                    <div>
                        <label
                            class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Candidate</label>
                        <div
                            class="bg-slate-50 dark:bg-slate-900 p-4 rounded-xl border border-slate-100 dark:border-slate-700">
                            <p class="text-sm font-bold text-slate-900 dark:text-white mb-1">{{ selectedInterview?.name
                            }}</p>
                            <p class="text-[10px] text-slate-400">{{ selectedInterview?.position }}</p>
                        </div>
                    </div>
                    <div>
                        <label
                            class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Technical
                            Feedback</label>
                        <textarea v-model="form.notes" rows="4"
                            class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500"
                            placeholder="Notes on performance..."></textarea>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Final
                            Decision</label>
                        <div class="grid grid-cols-2 gap-4">
                            <button @click="form.status = 'Passed'"
                                :class="form.status === 'Passed' ? 'bg-emerald-500 text-white ring-4 ring-emerald-100' : 'bg-slate-50 text-slate-400'"
                                class="py-4 rounded-2xl font-black text-[10px] uppercase transition-all">Passed</button>
                            <button @click="form.status = 'Rejected'"
                                :class="form.status === 'Rejected' ? 'bg-rose-50 text-white ring-4 ring-rose-100' : 'bg-slate-50 text-slate-400'"
                                class="py-4 rounded-2xl font-black text-[10px] uppercase transition-all">Rejected</button>
                        </div>
                    </div>
                    <button @click="submitEvaluation" :disabled="form.processing || form.status === 'Pending'"
                        class="w-full py-4 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all shadow-xl shadow-slate-900/20">
                        {{ form.processing ? 'Saving...' : 'Complete Evaluation' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Add any custom styles if needed */
</style>