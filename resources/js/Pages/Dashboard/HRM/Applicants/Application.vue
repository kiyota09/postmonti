<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    Search,
    UserPlus,
    Filter,
    MoreHorizontal,
    Mail,
    FileText,
    User,
    Calendar,
    Home,
    Phone,
    Briefcase,
    Upload,
    ShieldCheck,
    X,
    Save,
    FileCheck,
    Trash2,
    Info,
    Clock,
    Eye,
    ExternalLink,
    CheckCircle2,
    Maximize2
} from 'lucide-vue-next';

const props = defineProps({
    applicants: {
        type: Array,
        default: () => []
    }
});

// --- UI States ---
const searchQuery = ref('');
const isModalOpen = ref(false);
const isScheduleModalOpen = ref(false);
const isViewModalOpen = ref(false);
const isImageViewerOpen = ref(false);
const selectedApplicant = ref(null);
const viewingApplicant = ref(null);
const currentImageUrl = ref('');

// --- Toast Logic ---
const showToast = ref(false);
const toastMessage = ref('');

const triggerToast = (msg) => {
    toastMessage.value = msg;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 3000);
};

// --- Forms ---
const form = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    street_address: '',
    street_address_line2: '',
    city: '',
    state_province: '',
    postal_zip_code: '',
    email: '',
    phone_number: '',
    position_applied: '',
    expected_salary: '',
    notice_period: 'immediate',
    textile_experience: 'no',
    sss_file: null,
    philhealth_file: null,
    pagibig_file: null
});

const scheduleForm = useForm({
    scheduled_at: '',
    interview_type: '',
    duration: '45',
    interviewer: '',
    location: '',
    notes: ''
});

// --- Computed ---
const filteredApplicants = computed(() => {
    const list = props.applicants ?? [];
    return list.filter(person => {
        const status = person?.status?.toLowerCase() ?? 'pending';
        const isPending = status === 'pending';

        const name = person?.name?.toLowerCase() ?? '';
        const position = person?.position?.toLowerCase() ?? '';
        const email = person?.email?.toLowerCase() ?? '';
        const search = searchQuery.value.toLowerCase();

        const matchesSearch = name.includes(search) ||
            position.includes(search) ||
            email.includes(search);

        return isPending && matchesSearch;
    });
});

const minInterviewDate = computed(() => {
    if (!selectedApplicant.value) return '';
    const date = new Date();
    if (selectedApplicant.value.notice_period === '30_days') {
        date.setDate(date.getDate() + 30);
    } else if (selectedApplicant.value.notice_period === '15_days') {
        date.setDate(date.getDate() + 15);
    }
    return date.toISOString().slice(0, 16);
});

// --- Methods ---
const openImageViewer = (imageUrl) => {
    currentImageUrl.value = imageUrl;
    isImageViewerOpen.value = true;
};

const closeImageViewer = () => {
    isImageViewerOpen.value = false;
    currentImageUrl.value = '';
};

const openScheduleModal = (applicant) => {
    selectedApplicant.value = applicant;
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(9, 0, 0, 0);
    scheduleForm.scheduled_at = tomorrow.toISOString().slice(0, 16);
    isScheduleModalOpen.value = true;
};

const closeScheduleModal = () => {
    isScheduleModalOpen.value = false;
    scheduleForm.reset();
    selectedApplicant.value = null;
};

const openViewModal = (applicant) => {
    viewingApplicant.value = applicant;
    isViewModalOpen.value = true;
};

const closeViewModal = () => {
    isViewModalOpen.value = false;
    viewingApplicant.value = null;
};

const handleFileUpload = (event, type) => {
    const file = event.target.files[0];
    if (file) {
        if (file.size > 2 * 1024 * 1024) {
            triggerToast('File size must be less than 2MB');
            return;
        }
        const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!validTypes.includes(file.type)) {
            triggerToast('Only JPEG and PNG files are allowed');
            return;
        }
        form[type + '_file'] = file;
    }
};

const removeFile = (type) => {
    form[type + '_file'] = null;
};

const getStatusClass = (status) => {
    const s = status?.toLowerCase();
    switch (s) {
        case 'interview': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        case 'hired': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
        case 'rejected': return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400';
        default: return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400';
    }
};

const submitForm = () => {
    if (!form.first_name || !form.last_name || !form.email || !form.phone_number || !form.position_applied) {
        triggerToast('Please fill in all required fields');
        return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(form.email)) {
        triggerToast('Please enter a valid email address');
        return;
    }

    form.post(route('hrm.applicants.store'), {
        forceFormData: true,
        preserveScroll: true,
        preserveState: false, // 🔥 Force fresh component state after submission
        onSuccess: () => {
            isModalOpen.value = false;
            form.reset();
            triggerToast('Applicant registered successfully!');
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
            triggerToast('Failed to add applicant. Please check all fields.');
        }
    });
};

const submitSchedule = () => {
    if (!selectedApplicant.value) {
        triggerToast('No applicant selected');
        return;
    }

    if (!scheduleForm.scheduled_at || !scheduleForm.interview_type) {
        triggerToast('Please fill in all required fields');
        return;
    }

    const selectedDate = new Date(scheduleForm.scheduled_at);
    const now = new Date();
    if (selectedDate < now) {
        triggerToast('Interview date cannot be in the past');
        return;
    }

    scheduleForm.post(route('hrm.applicants.schedule', selectedApplicant.value.id), {
        preserveScroll: true,
        preserveState: false, // 🔥 Force fresh component state
        onSuccess: () => {
            closeScheduleModal();
            triggerToast('Interview scheduled successfully!');
            router.reload({ only: ['applicants'] }); // extra safety reload
        },
        onError: (errors) => {
            console.error('Schedule submission errors:', errors);
            triggerToast('Failed to schedule interview. Please check all fields.');
        }
    });
};

const formatNoticePeriod = (period) => {
    if (!period) return 'N/A';
    return period.replace('_', ' ').replace(/(^\w|\s\w)/g, m => m.toUpperCase());
};
</script>

<template>

    <Head title="Applicant Management" />

    <AuthenticatedLayout>
        <Transition name="toast">
            <div v-if="showToast"
                class="fixed top-6 right-6 z-[100] flex items-center gap-3 px-6 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl shadow-2xl border border-white/10">
                <div class="h-8 w-8 bg-emerald-500 rounded-full flex items-center justify-center">
                    <CheckCircle2 class="h-5 w-5 text-white" />
                </div>
                <p class="text-sm font-bold uppercase tracking-tight">{{ toastMessage }}</p>
            </div>
        </Transition>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                    Recruitment <span class="text-blue-600">Portal</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Manage and track new applications for
                    Monti Textile.</p>
            </div>
            <button @click="isModalOpen = true"
                class="flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-bold text-sm transition-all shadow-lg shadow-blue-500/20 active:scale-95">
                <UserPlus class="h-4 w-4" /> Add New Applicant
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="relative md:col-span-2">
                <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                <input v-model="searchQuery" type="text" placeholder="Search by name, email or position..."
                    class="w-full pl-12 pr-4 py-3 rounded-2xl bg-white dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-blue-600 text-sm" />
            </div>
            <button
                class="flex items-center justify-center gap-2 px-4 py-3 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 font-bold text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 transition-all">
                <Filter class="h-4 w-4" /> Advanced Filters
            </button>
        </div>

        <div
            class="bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden min-h-[400px] relative">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-900/50">
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                Applicant</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                Position</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                Notice Period</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                Status</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        <tr v-if="filteredApplicants.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center">
                                <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">No pending
                                    applicants found</p>
                            </td>
                        </tr>
                        <tr v-for="person in filteredApplicants" :key="person.id"
                            class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 font-bold text-xs">
                                        {{ person.name ? person.name.charAt(0) : '?' }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{ person.name }}
                                        </p>
                                        <p class="text-xs text-slate-400">{{ person.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-slate-600 dark:text-slate-300">{{
                                person.position }}</td>
                            <td class="px-6 py-4 text-sm text-slate-500 font-bold uppercase text-[10px] tracking-tight">
                                {{ formatNoticePeriod(person.notice_period) }}
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getStatusClass(person.status)"
                                    class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider">
                                    {{ person.status || 'Pending' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <button @click="openViewModal(person)"
                                        class="p-2 text-slate-400 hover:text-blue-600 rounded-lg transition-all"
                                        title="View Details">
                                        <Eye class="h-5 w-5" />
                                    </button>
                                    <button @click="openScheduleModal(person)"
                                        class="p-2 text-slate-400 hover:text-emerald-600 rounded-lg transition-all"
                                        title="Set Interview Schedule">
                                        <Calendar class="h-5 w-5" />
                                    </button>
                                    <button
                                        class="p-2 text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-lg transition-all">
                                        <MoreHorizontal class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Image Viewer Modal -->
        <div v-if="isImageViewerOpen" class="fixed inset-0 z-[80] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/90 backdrop-blur-md" @click="closeImageViewer"></div>
            <div class="relative w-full max-w-6xl max-h-[90vh] flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-white">
                        <p class="text-sm font-bold uppercase tracking-widest">Government ID Document</p>
                        <p class="text-xs text-slate-300">Full size preview</p>
                    </div>
                    <button @click="closeImageViewer"
                        class="p-3 bg-white/10 hover:bg-white/20 rounded-2xl text-white transition-colors group">
                        <X class="h-6 w-6 group-hover:rotate-90 transition-transform duration-300" />
                    </button>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-[2rem] overflow-hidden shadow-2xl flex-1 relative">
                    <div class="h-full w-full flex items-center justify-center p-4">
                        <img :src="currentImageUrl" alt="Government ID Document"
                            class="max-h-full max-w-full object-contain rounded-xl shadow-lg" />
                    </div>
                </div>
            </div>
        </div>

        <!-- View Details Modal -->
        <div v-if="isViewModalOpen" class="fixed inset-0 z-[70] flex items-center justify-center p-4 sm:p-6">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeViewModal"></div>
            <div
                class="relative w-full max-w-4xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                <div
                    class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white dark:bg-slate-900 z-20">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Applicant
                            <span class="text-blue-600">Details</span>
                        </h2>
                        <p class="text-xs text-slate-500 font-medium tracking-tight">Application ID: #{{
                            viewingApplicant?.id }}</p>
                    </div>
                    <button @click="closeViewModal"
                        class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors">
                        <X class="h-5 w-5 text-slate-400" />
                    </button>
                </div>
                <div class="overflow-y-auto p-8 custom-scrollbar space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-6">
                            <div>
                                <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-3">Personal
                                    Information</h4>
                                <div class="bg-slate-50 dark:bg-slate-800/50 p-6 rounded-3xl space-y-4">
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">Full
                                            Name</p>
                                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{
                                            viewingApplicant?.name }}</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                                                Email</p>
                                            <p class="text-xs font-medium text-slate-900 dark:text-white break-words">{{
                                                viewingApplicant?.email }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                                                Phone</p>
                                            <p class="text-xs font-medium text-slate-900 dark:text-white">{{
                                                viewingApplicant?.phone_number || 'N/A' }}</p>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                                            Address</p>
                                        <p class="text-xs font-medium text-slate-900 dark:text-white">
                                            {{ viewingApplicant?.street_address }}, {{ viewingApplicant?.city }}, {{
                                                viewingApplicant?.state_province }} {{ viewingApplicant?.postal_zip_code }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-6">
                            <div>
                                <h4 class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-3">
                                    Application Status</h4>
                                <div class="bg-slate-50 dark:bg-slate-800/50 p-6 rounded-3xl space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                                                Position</p>
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{
                                                viewingApplicant?.position }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                                                Notice Period</p>
                                            <p
                                                class="text-sm font-bold text-amber-600 uppercase text-[10px] tracking-tight">
                                                {{ formatNoticePeriod(viewingApplicant?.notice_period) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                                            Current Status</p>
                                        <span :class="getStatusClass(viewingApplicant?.status)"
                                            class="inline-block mt-1 px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider">
                                            {{ viewingApplicant?.status || 'Pending' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4">Submitted
                            Government IDs</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div v-for="type in ['sss', 'philhealth', 'pagibig']" :key="type" class="group">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">{{
                                    type.toUpperCase() }} Document</p>
                                <div
                                    class="relative bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-3xl p-4 overflow-hidden aspect-[4/3] flex flex-col items-center justify-center transition-all hover:shadow-xl hover:border-blue-500/50">
                                    <template v-if="viewingApplicant?.[type + '_file_url']">
                                        <img :src="viewingApplicant[type + '_file_url']"
                                            class="absolute inset-0 w-full h-full object-cover opacity-20"
                                            alt="ID preview">
                                        <div class="z-10 text-center">
                                            <FileCheck class="h-10 w-10 text-emerald-500 mb-2 mx-auto" />
                                            <p class="text-[10px] font-bold text-slate-900 dark:text-white uppercase">ID
                                                Uploaded</p>
                                            <button @click="openImageViewer(viewingApplicant[type + '_file_url'])"
                                                class="mt-3 flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-700 rounded-xl text-[10px] font-black uppercase tracking-tighter shadow-sm hover:bg-blue-600 hover:text-white transition-all">
                                                <Maximize2 class="h-3 w-3" /> View Full File
                                            </button>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <Info class="h-10 w-10 text-slate-200 mb-2" />
                                        <p class="text-[10px] font-bold text-slate-400 uppercase">No File Provided</p>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    class="p-8 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end gap-3">
                    <button @click="closeViewModal"
                        class="px-8 py-3.5 bg-slate-900 text-white rounded-2xl font-bold text-sm transition-all">Close
                        Details</button>
                </div>
            </div>
        </div>

        <!-- Schedule Interview Modal -->
        <div v-if="isScheduleModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeScheduleModal"></div>
            <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-2xl">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Set <span
                                class="text-blue-600">Interview</span></h2>
                        <p class="text-sm text-slate-500 font-medium">Candidate: {{ selectedApplicant?.name }}</p>
                    </div>
                    <button @click="closeScheduleModal" class="p-2 hover:bg-slate-100 rounded-full transition-colors">
                        <X class="h-5 w-5 text-slate-400" />
                    </button>
                </div>
                <form @submit.prevent="submitSchedule" class="space-y-6">
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Interview
                                Type *</label>
                            <select v-model="scheduleForm.interview_type" required
                                class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-emerald-500">
                                <option value="">Select type</option>
                                <option value="phone">Phone Screen</option>
                                <option value="technical">Technical Interview</option>
                                <option value="behavioral">Behavioral Interview</option>
                                <option value="onsite">On-site Interview</option>
                                <option value="video">Video Conference</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Date & Time
                                *</label>
                            <div class="relative">
                                <Clock class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                <input type="datetime-local" v-model="scheduleForm.scheduled_at" :min="minInterviewDate"
                                    required
                                    class="w-full pl-12 pr-4 py-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-blue-600" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Duration</label>
                                <select v-model="scheduleForm.duration"
                                    class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700">
                                    <option value="15">15m</option>
                                    <option value="30">30m</option>
                                    <option value="45">45m</option>
                                    <option value="60">1h</option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Location</label>
                                <input v-model="scheduleForm.location" type="text" placeholder="Office/Link"
                                    class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700">
                            </div>
                        </div>
                        <div>
                            <label
                                class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Interviewer(s)</label>
                            <input v-model="scheduleForm.interviewer" type="text" placeholder="Name of interviewers"
                                class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700">
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" @click="closeScheduleModal"
                            class="flex-1 px-6 py-3.5 rounded-2xl text-sm font-bold text-slate-500 hover:bg-slate-100 transition-all">Cancel</button>
                        <button type="submit" :disabled="scheduleForm.processing"
                            class="flex-1 bg-blue-600 text-white py-3.5 rounded-2xl font-bold text-sm shadow-xl shadow-blue-500/25 active:scale-95 disabled:opacity-50">
                            {{ scheduleForm.processing ? 'Scheduling...' : 'Confirm Schedule' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Applicant Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="isModalOpen = false"></div>
            <div
                class="relative w-full max-w-4xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                <div
                    class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white dark:bg-slate-900 z-20">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">New <span
                                class="text-blue-600">Applicant</span></h2>
                        <p class="text-xs text-slate-500 font-medium tracking-tight">Registration for Monti Textile
                            Recruitment.
                        </p>
                    </div>
                    <button @click="isModalOpen = false"
                        class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors">
                        <X class="h-5 w-5 text-slate-400" />
                    </button>
                </div>
                <div class="overflow-y-auto p-8 custom-scrollbar space-y-8">
                    <form @submit.prevent="submitForm" class="space-y-8">
                        <div
                            class="bg-blue-50/50 dark:bg-blue-900/10 p-6 rounded-[2rem] border border-blue-100 dark:border-blue-900/20">
                            <div class="flex items-center mb-6">
                                <div
                                    class="h-10 w-10 bg-blue-600 rounded-2xl flex items-center justify-center mr-4 shadow-lg shadow-blue-500/30">
                                    <User class="text-white h-5 w-5" />
                                </div>
                                <h4 class="text-lg font-black text-slate-800 dark:text-white">Full Name</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500 ml-1">First Name *</label>
                                    <input v-model="form.first_name" type="text" required
                                        class="w-full px-5 py-3.5 rounded-2xl bg-white dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 text-sm focus:ring-2 focus:ring-blue-600">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500 ml-1">Middle Name</label>
                                    <input v-model="form.middle_name" type="text"
                                        class="w-full px-5 py-3.5 rounded-2xl bg-white dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 text-sm focus:ring-2 focus:ring-blue-600">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500 ml-1">Last Name *</label>
                                    <input v-model="form.last_name" type="text" required
                                        class="w-full px-5 py-3.5 rounded-2xl bg-white dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 text-sm focus:ring-2 focus:ring-blue-600">
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-slate-50/50 dark:bg-slate-800/50 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 shadow-sm">
                            <div class="flex items-center mb-6">
                                <div class="h-10 w-10 bg-blue-500 rounded-2xl flex items-center justify-center mr-4">
                                    <Home class="text-white h-5 w-5" />
                                </div>
                                <h4 class="text-lg font-black text-slate-800 dark:text-white">Current Address</h4>
                            </div>
                            <div class="space-y-5">
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500 ml-1">Street Address *</label>
                                    <input v-model="form.street_address" type="text" required
                                        placeholder="Street Address"
                                        class="w-full px-5 py-3.5 rounded-2xl bg-white dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 text-sm">
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-slate-500 ml-1">City *</label>
                                        <input v-model="form.city" type="text" required placeholder="City"
                                            class="w-full px-5 py-3.5 rounded-2xl bg-white dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 text-sm">
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-slate-500 ml-1">State / Province *</label>
                                        <input v-model="form.state_province" type="text" required
                                            placeholder="State / Province"
                                            class="w-full px-5 py-3.5 rounded-2xl bg-white dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 text-sm">
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-slate-500 ml-1">Postal / Zip Code *</label>
                                        <input v-model="form.postal_zip_code" type="text" required
                                            placeholder="Postal / Zip Code"
                                            class="w-full px-5 py-3.5 rounded-2xl bg-white dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-blue-50/30 dark:bg-blue-900/5 p-6 rounded-[2rem] border border-blue-100 dark:border-blue-900/10 shadow-sm">
                            <div class="flex items-center mb-6">
                                <div class="h-10 w-10 bg-blue-600 rounded-2xl flex items-center justify-center mr-4">
                                    <Briefcase class="text-white h-5 w-5" />
                                </div>
                                <h4 class="text-lg font-black text-slate-800 dark:text-white">Professional Info</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500 ml-1">Email Address *</label>
                                    <input v-model="form.email" type="email" required placeholder="email@example.com"
                                        class="w-full px-5 py-3.5 rounded-2xl bg-white dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 text-sm focus:ring-2 focus:ring-blue-600">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500 ml-1">Phone Number *</label>
                                    <input v-model="form.phone_number" type="tel" required
                                        placeholder="+1 (555) 123-4567"
                                        class="w-full px-5 py-3.5 rounded-2xl bg-white dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 text-sm focus:ring-2 focus:ring-blue-600">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500 ml-1">Position Applied *</label>
                                    <input v-model="form.position_applied" type="text" required
                                        placeholder="e.g., Textile Engineer"
                                        class="w-full px-5 py-3.5 rounded-2xl bg-white dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 text-sm focus:ring-2 focus:ring-blue-600">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500 ml-1">Notice Period *</label>
                                    <select v-model="form.notice_period"
                                        class="w-full px-5 py-3.5 rounded-2xl bg-white dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 text-sm focus:ring-2 focus:ring-blue-600">
                                        <option value="immediate">Immediate</option>
                                        <option value="15_days">15 Days</option>
                                        <option value="30_days">30 Days</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-emerald-50/20 dark:bg-emerald-900/5 p-6 rounded-[2rem] border border-emerald-100 dark:border-emerald-900/10">
                            <div class="flex items-center mb-6">
                                <div class="h-10 w-10 bg-emerald-600 rounded-2xl flex items-center justify-center mr-4">
                                    <FileCheck class="text-white h-5 w-5" />
                                </div>
                                <h4 class="text-lg font-black text-slate-800 dark:text-white">Government IDs</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div v-for="type in ['sss', 'philhealth', 'pagibig']" :key="type" class="space-y-2">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">{{
                                        type.toUpperCase() }} ID</p>
                                    <div :class="form[type + '_file'] ? 'border-emerald-500 bg-emerald-50/50' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800'"
                                        class="relative h-32 rounded-3xl border-2 border-dashed flex flex-col items-center justify-center p-4 transition-all group">
                                        <template v-if="!form[type + '_file']">
                                            <Upload
                                                class="h-5 w-5 text-slate-300 group-hover:text-blue-500 transition-colors mb-2" />
                                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">
                                                Click to upload</p>
                                            <input type="file" @change="(e) => handleFileUpload(e, type)"
                                                class="absolute inset-0 opacity-0 cursor-pointer"
                                                accept=".jpg,.jpeg,.png">
                                        </template>
                                        <template v-else>
                                            <div class="flex flex-col items-center text-center">
                                                <FileCheck class="h-6 w-6 text-emerald-600 mb-1" />
                                                <p class="text-[10px] font-black text-emerald-800 truncate w-24">{{
                                                    form[type + '_file'].name }}</p>
                                                <button @click="removeFile(type)" type="button"
                                                    class="mt-2 p-1 bg-rose-100 text-rose-600 rounded-lg hover:bg-rose-200">
                                                    <Trash2 class="h-3 w-3" />
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex flex-col sm:flex-row justify-between items-center pt-8 border-t border-slate-100 dark:border-slate-800 sticky bottom-0 bg-white dark:bg-slate-900 z-10 py-4">
                            <div
                                class="flex items-center text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4 sm:mb-0">
                                <ShieldCheck class="h-4 w-4 text-emerald-500 mr-2" /> Verified & Encrypted
                            </div>
                            <div class="flex gap-3 w-full sm:w-auto">
                                <button type="button" @click="isModalOpen = false"
                                    class="flex-1 sm:flex-none px-8 py-3.5 rounded-2xl text-sm font-bold text-slate-500 hover:bg-slate-100 transition-all">Cancel</button>
                                <button type="submit" :disabled="form.processing"
                                    class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-10 py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-bold text-sm shadow-xl shadow-blue-500/25 active:scale-95 disabled:opacity-50">
                                    <Save class="h-4 w-4" />
                                    <span>{{ form.processing ? 'Saving...' : 'Save Applicant' }}</span>
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
.toast-enter-active,
.toast-leave-active {
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.toast-enter-from,
.toast-leave-to {
    transform: translateY(-20px) scale(0.9);
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