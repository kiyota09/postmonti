<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Plus, DollarSign, Calendar, X, GripVertical, CheckCircle2, AlertCircle,
    HelpCircle, ArrowRight, UserCheck, Building2, FileText, Upload, Clock,
    MessageSquare, Video, MapPin, Download, Eye, Trash2, ChevronDown, ChevronUp
} from 'lucide-vue-next';

const props = defineProps({
    leads: Array
});

// --- State Management ---
const showCreateModal = ref(false);
const showDropConfirm = ref(false);
const showClientConversionModal = ref(false);
const showNoteModal = ref(false);
const showInterviewModal = ref(false);
const showFileUploadModal = ref(false);
const showAcceptRejectModal = ref(false);
const currentLead = ref(null);
const draggingLeadId = ref(null);
const pendingMove = ref({ leadId: null, newStatus: null, companyName: '' });

// --- Forms ---
const form = useForm({
    company_name: '',
    contact_person: '',
    email: '',
    phone: '',
    interest_fabric: 'Cotton',
    estimated_value: '',
});

const conversionForm = useForm({
    lead_id: null,
    company_name: '',
    contact_person: '',
    email: '',
    phone: '',
    business_type: 'wholesaler',
    tin_number: '',
    company_address: '',
    password: 'password123',
});

const noteForm = useForm({
    note: '',
});

const interviewForm = useForm({
    scheduled_at: '',
    location: '',
    notes: '',
});

const fileForm = useForm({
    file: null,
});

const rejectForm = useForm({
    reject_reason: '',
});

const columns = [
    { title: 'New Inquiry', status: 'Inquiry' },
    { title: 'Negotiation', status: 'Negotiation' },
    { title: 'Approval Sent', status: 'Approval Sent' },
    { title: 'Closed-Won', status: 'Closed-Won' }
];

const getLeadsByStatus = (status) => {
    return props.leads?.filter(lead => lead.status === status && lead.status !== 'Converted') || [];
};

// --- Modals Control ---
const openNoteModal = (lead) => {
    currentLead.value = lead;
    noteForm.note = '';
    showNoteModal.value = true;
};

const openInterviewModal = (lead) => {
    currentLead.value = lead;
    interviewForm.reset();
    showInterviewModal.value = true;
};

const openFileUploadModal = (lead) => {
    currentLead.value = lead;
    fileForm.file = null;
    showFileUploadModal.value = true;
};

const openAcceptRejectModal = (lead) => {
    currentLead.value = lead;
    rejectForm.reject_reason = '';
    showAcceptRejectModal.value = true;
};

const closeAllModals = () => {
    showNoteModal.value = false;
    showInterviewModal.value = false;
    showFileUploadModal.value = false;
    showAcceptRejectModal.value = false;
    currentLead.value = null;
};

// --- API Calls ---
const addNote = () => {
    noteForm.post(route('crm.lead.add-note', currentLead.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeAllModals();
            noteForm.reset();
            router.reload({ only: ['leads'] });
        },
    });
};

const scheduleInterview = () => {
    interviewForm.post(route('crm.lead.schedule-interview', currentLead.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeAllModals();
            interviewForm.reset();
            router.reload({ only: ['leads'] });
        },
    });
};

const uploadFile = () => {
    fileForm.post(route('crm.lead.upload-file', currentLead.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeAllModals();
            fileForm.reset();
            router.reload({ only: ['leads'] });
        },
    });
};

const acceptLead = () => {
    router.post(route('crm.lead.accept', currentLead.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            closeAllModals();
            router.reload({ only: ['leads'] });
        },
    });
};

const rejectLead = () => {
    rejectForm.post(route('crm.lead.reject', currentLead.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeAllModals();
            rejectForm.reset();
            router.reload({ only: ['leads'] });
        },
    });
};

// --- Conversion Logic ---
const openConversionModal = (lead) => {
    conversionForm.lead_id = lead.id;
    conversionForm.company_name = lead.company_name;
    conversionForm.contact_person = lead.contact_person;
    conversionForm.email = lead.email;
    conversionForm.phone = lead.phone;
    showClientConversionModal.value = true;
};

const submitConversion = () => {
    conversionForm.post(route('crm.lead.convert'), {
        preserveScroll: true,
        onSuccess: () => {
            showClientConversionModal.value = false;
            conversionForm.reset();
            router.reload({ only: ['leads'] });
        },
    });
};

// --- Drag and Drop ---
const onDragStart = (lead) => {
    draggingLeadId.value = lead.id;
    pendingMove.value.companyName = lead.company_name;
};

const onDrop = (newStatus) => {
    if (!draggingLeadId.value) return;
    const lead = props.leads.find(l => l.id === draggingLeadId.value);
    const currentIndex = columns.findIndex(c => c.status === lead.status);
    const newIndex = columns.findIndex(c => c.status === newStatus);

    if (newIndex - currentIndex !== 1) {
        draggingLeadId.value = null;
        return;
    }

    pendingMove.value.leadId = draggingLeadId.value;
    pendingMove.value.newStatus = newStatus;
    showDropConfirm.value = true;
};

const confirmMove = () => {
    router.patch(route('crm.lead.status', pendingMove.value.leadId), {
        status: pendingMove.value.newStatus
    }, {
        preserveScroll: true,
        onSuccess: () => {
            closeConfirm();
        }
    });
};

const closeConfirm = () => {
    showDropConfirm.value = false;
    draggingLeadId.value = null;
    pendingMove.value = { leadId: null, newStatus: null, companyName: '' };
};

const submit = () => {
    form.post(route('crm.lead.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value || 0);
};

const formatDateTime = (date) => {
    return new Date(date).toLocaleString();
};

// Toggle visibility of notes/interviews/files within a lead card
const showNotes = ref({});
const showInterviews = ref({});
const showFiles = ref({});

const toggleNotes = (leadId) => {
    showNotes.value[leadId] = !showNotes.value[leadId];
};
const toggleInterviews = (leadId) => {
    showInterviews.value[leadId] = !showInterviews.value[leadId];
};
const toggleFiles = (leadId) => {
    showFiles.value[leadId] = !showFiles.value[leadId];
};
</script>

<template>
    <AuthenticatedLayout title="Lead & Deal Workspace">
        <div class="p-4 md:p-6 space-y-6 bg-slate-50/50 dark:bg-zinc-950 min-h-screen">
            <!-- Header -->
            <div
                class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-200 dark:border-zinc-800 pb-6">
                <div>
                    <h1 class="text-xl font-black tracking-tight text-blue-600 uppercase">
                        Linear <span class="text-gray-900 dark:text-white">Deal Pipeline</span>
                    </h1>
                </div>
                <button @click="showCreateModal = true"
                    class="flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-xl text-[10px] font-black uppercase shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all">
                    <Plus class="w-4 h-4" /> Create Deal
                </button>
            </div>

            <!-- Columns -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pb-6">
                <div v-for="(column, index) in columns" :key="column.status" class="flex flex-col group"
                    @dragover.prevent @drop="onDrop(column.status)">
                    <div class="flex items-center justify-between mb-3 px-2">
                        <div class="flex items-center gap-2">
                            <span
                                class="text-[8px] font-black bg-gray-200 dark:bg-zinc-800 text-gray-500 rounded-full h-4 w-4 flex items-center justify-center">
                                {{ index + 1 }}
                            </span>
                            <h3
                                class="text-[9px] font-black uppercase tracking-wider text-gray-500 group-hover:text-blue-600 transition-colors">
                                {{ column.title }}
                            </h3>
                        </div>
                        <span
                            class="text-[9px] font-bold bg-blue-50 text-blue-600 dark:bg-zinc-800 px-2 py-0.5 rounded-md">
                            {{ getLeadsByStatus(column.status).length }}
                        </span>
                    </div>

                    <div
                        class="space-y-3 min-h-[calc(100vh-220px)] p-2 bg-gray-100/50 dark:bg-zinc-900/50 rounded-[1.5rem] border-2 border-transparent transition-all group-hover:border-blue-100 dark:group-hover:border-zinc-800">
                        <div v-for="lead in getLeadsByStatus(column.status)" :key="lead.id" draggable="true"
                            @dragstart="onDragStart(lead)"
                            class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 p-4 rounded-xl shadow-sm hover:shadow-md hover:scale-[1.01] cursor-grab active:cursor-grabbing transition-all group/card relative">
                            <div
                                class="absolute right-3 top-3 opacity-0 group-hover/card:opacity-100 transition-opacity">
                                <GripVertical class="w-3 h-3 text-gray-300" />
                            </div>

                            <p
                                class="text-[10px] font-black uppercase text-gray-900 dark:text-white mb-2 pr-4 italic truncate">
                                {{ lead.company_name }}
                            </p>

                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2 text-[9px] font-bold text-gray-500 uppercase">
                                    <div class="p-1 rounded bg-green-50 text-green-600">
                                        <DollarSign class="w-2.5 h-2.5" />
                                    </div>
                                    {{ formatCurrency(lead.estimated_value) }}
                                </div>
                                <div class="flex items-center gap-2 text-[9px] font-bold text-gray-500 uppercase">
                                    <div class="p-1 rounded bg-blue-50 text-blue-600">
                                        <Calendar class="w-2.5 h-2.5" />
                                    </div>
                                    {{ lead.interest_fabric }}
                                </div>
                            </div>

                            <!-- New Inquiry: Add Note & Show Notes -->
                            <div v-if="lead.status === 'Inquiry'" class="mt-3">
                                <button @click.stop="openNoteModal(lead)"
                                    class="text-[9px] text-blue-600 flex items-center gap-1 mb-2">
                                    <MessageSquare class="w-3 h-3" /> Add Note
                                </button>
                                <div v-if="lead.notes && lead.notes.length" class="border-t pt-2 text-[9px]">
                                    <div class="flex justify-between items-center cursor-pointer"
                                        @click="toggleNotes(lead.id)">
                                        <span class="font-bold text-gray-500 uppercase">Notes ({{ lead.notes.length
                                            }})</span>
                                        <ChevronDown v-if="!showNotes[lead.id]" class="w-3 h-3" />
                                        <ChevronUp v-else class="w-3 h-3" />
                                    </div>
                                    <div v-if="showNotes[lead.id]" class="mt-2 space-y-2 max-h-40 overflow-y-auto">
                                        <div v-for="note in lead.notes" :key="note.id" class="bg-gray-50 p-2 rounded">
                                            <p class="text-gray-700">{{ note.note }}</p>
                                            <p class="text-gray-400 text-[8px] mt-1">{{ note.user.name }} - {{ new
                                                Date(note.created_at).toLocaleString() }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Negotiation: Schedule Interview & Show Interviews -->
                            <div v-if="lead.status === 'Negotiation'" class="mt-3">
                                <button @click.stop="openInterviewModal(lead)"
                                    class="text-[9px] text-blue-600 flex items-center gap-1 mb-2">
                                    <Video class="w-3 h-3" /> Schedule Interview
                                </button>
                                <div v-if="lead.interviews && lead.interviews.length" class="border-t pt-2 text-[9px]">
                                    <div class="flex justify-between items-center cursor-pointer"
                                        @click="toggleInterviews(lead.id)">
                                        <span class="font-bold text-gray-500 uppercase">Interviews ({{
                                            lead.interviews.length }})</span>
                                        <ChevronDown v-if="!showInterviews[lead.id]" class="w-3 h-3" />
                                        <ChevronUp v-else class="w-3 h-3" />
                                    </div>
                                    <div v-if="showInterviews[lead.id]" class="mt-2 space-y-2 max-h-40 overflow-y-auto">
                                        <div v-for="interview in lead.interviews" :key="interview.id"
                                            class="bg-gray-50 p-2 rounded">
                                            <div class="flex items-center gap-1 text-gray-700">
                                                <Calendar class="w-3 h-3" /> {{ formatDateTime(interview.scheduled_at)
                                                }}
                                            </div>
                                            <div v-if="interview.location"
                                                class="flex items-center gap-1 text-gray-600 mt-1">
                                                <MapPin class="w-3 h-3" /> {{ interview.location }}
                                            </div>
                                            <div v-if="interview.notes" class="mt-1 text-gray-600 italic">{{
                                                interview.notes }}</div>
                                            <p class="text-gray-400 text-[8px] mt-1">{{ interview.user.name }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Approval Sent: File Upload, Accept/Reject & Show Files -->
                            <div v-if="lead.status === 'Approval Sent'" class="mt-3">
                                <div class="flex gap-2 mb-2">
                                    <button @click.stop="openFileUploadModal(lead)"
                                        class="text-[9px] text-blue-600 flex items-center gap-1">
                                        <Upload class="w-3 h-3" /> Upload File
                                    </button>
                                    <button @click.stop="openAcceptRejectModal(lead)"
                                        class="text-[9px] text-blue-600 flex items-center gap-1">
                                        <CheckCircle2 class="w-3 h-3" /> Accept/Reject
                                    </button>
                                </div>
                                <div v-if="lead.approval_files && lead.approval_files.length"
                                    class="border-t pt-2 text-[9px]">
                                    <div class="flex justify-between items-center cursor-pointer"
                                        @click="toggleFiles(lead.id)">
                                        <span class="font-bold text-gray-500 uppercase">Files ({{
                                            lead.approval_files.length }})</span>
                                        <ChevronDown v-if="!showFiles[lead.id]" class="w-3 h-3" />
                                        <ChevronUp v-else class="w-3 h-3" />
                                    </div>
                                    <div v-if="showFiles[lead.id]" class="mt-2 space-y-2 max-h-40 overflow-y-auto">
                                        <div v-for="file in lead.approval_files" :key="file.id"
                                            class="bg-gray-50 p-2 rounded flex justify-between items-center">
                                            <span class="truncate max-w-[120px]">{{ file.original_name }}</span>
                                            <a :href="'/storage/' + file.file_path" target="_blank"
                                                class="text-blue-600 hover:underline flex items-center gap-1">
                                                <Eye class="w-3 h-3" /> View
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="mt-3 pt-3 border-t border-gray-50 dark:border-zinc-800 flex justify-between items-center">
                                <span class="text-[8px] font-black uppercase text-gray-400 italic">#{{ lead.id }}</span>
                                <div v-if="lead.status === 'Closed-Won'" class="flex items-center gap-1.5">
                                    <button @click.stop="openConversionModal(lead)"
                                        class="flex items-center gap-1 px-2 py-1 bg-green-600 text-white rounded-md text-[8px] font-black uppercase hover:bg-green-700 transition-colors shadow-md">
                                        <UserCheck class="w-2.5 h-2.5" /> client account create
                                    </button>
                                    <CheckCircle2 class="w-3 h-3 text-green-500" />
                                </div>
                            </div>
                        </div>

                        <div v-if="getLeadsByStatus(column.status).length === 0"
                            class="h-24 flex flex-col items-center justify-center border-2 border-dashed border-gray-200 dark:border-zinc-800 rounded-xl">
                            <AlertCircle class="w-4 h-4 text-gray-300 mb-1" />
                            <p class="text-[8px] font-black text-gray-300 uppercase italic">Empty</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modals -->
            <!-- Note Modal -->
            <div v-if="showNoteModal"
                class="fixed inset-0 z-[130] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
                <div class="bg-white dark:bg-zinc-900 w-full max-w-md rounded-[2rem] shadow-2xl overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="text-sm font-black uppercase">Add Note</h3>
                        <button @click="closeAllModals" class="hover:rotate-90 transition">
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                    <form @submit.prevent="addNote" class="p-6 space-y-4">
                        <textarea v-model="noteForm.note" rows="4" placeholder="Write your notes here..." required
                            class="w-full border rounded-xl p-3 text-sm"></textarea>
                        <button type="submit" :disabled="noteForm.processing"
                            class="w-full py-3 bg-blue-600 text-white rounded-xl text-sm font-black uppercase">
                            {{ noteForm.processing ? 'Saving...' : 'Save Note' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Interview Modal -->
            <div v-if="showInterviewModal"
                class="fixed inset-0 z-[130] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
                <div class="bg-white dark:bg-zinc-900 w-full max-w-md rounded-[2rem] shadow-2xl overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="text-sm font-black uppercase">Schedule Interview</h3>
                        <button @click="closeAllModals" class="hover:rotate-90 transition">
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                    <form @submit.prevent="scheduleInterview" class="p-6 space-y-4">
                        <input type="datetime-local" v-model="interviewForm.scheduled_at" required
                            class="w-full border rounded-xl p-3 text-sm" />
                        <input type="text" v-model="interviewForm.location" placeholder="Location (optional)"
                            class="w-full border rounded-xl p-3 text-sm" />
                        <textarea v-model="interviewForm.notes" rows="3" placeholder="Notes (optional)"
                            class="w-full border rounded-xl p-3 text-sm"></textarea>
                        <button type="submit" :disabled="interviewForm.processing"
                            class="w-full py-3 bg-blue-600 text-white rounded-xl text-sm font-black uppercase">
                            {{ interviewForm.processing ? 'Scheduling...' : 'Schedule' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- File Upload Modal -->
            <div v-if="showFileUploadModal"
                class="fixed inset-0 z-[130] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
                <div class="bg-white dark:bg-zinc-900 w-full max-w-md rounded-[2rem] shadow-2xl overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="text-sm font-black uppercase">Upload Approval File</h3>
                        <button @click="closeAllModals" class="hover:rotate-90 transition">
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                    <form @submit.prevent="uploadFile" class="p-6 space-y-4" enctype="multipart/form-data">
                        <input type="file" @input="fileForm.file = $event.target.files[0]" required
                            class="w-full border rounded-xl p-2 text-sm" accept="image/*,application/pdf" />
                        <button type="submit" :disabled="fileForm.processing"
                            class="w-full py-3 bg-blue-600 text-white rounded-xl text-sm font-black uppercase">
                            {{ fileForm.processing ? 'Uploading...' : 'Upload' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Accept/Reject Modal -->
            <div v-if="showAcceptRejectModal"
                class="fixed inset-0 z-[130] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
                <div class="bg-white dark:bg-zinc-900 w-full max-w-md rounded-[2rem] shadow-2xl overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="text-sm font-black uppercase">Decision Required</h3>
                        <button @click="closeAllModals" class="hover:rotate-90 transition">
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <button @click="acceptLead"
                            class="w-full py-3 bg-green-600 text-white rounded-xl text-sm font-black uppercase flex items-center justify-center gap-2">
                            <CheckCircle2 class="w-4 h-4" /> Accept Lead
                        </button>
                        <form @submit.prevent="rejectLead" class="space-y-3">
                            <textarea v-model="rejectForm.reject_reason" rows="2" placeholder="Reason for rejection"
                                required class="w-full border rounded-xl p-3 text-sm"></textarea>
                            <button type="submit" :disabled="rejectForm.processing"
                                class="w-full py-3 bg-red-600 text-white rounded-xl text-sm font-black uppercase flex items-center justify-center gap-2">
                                <X class="w-4 h-4" /> Reject Lead
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Conversion Modal (unchanged) -->
            <div v-if="showClientConversionModal"
                class="fixed inset-0 z-[130] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
                <div
                    class="bg-white dark:bg-zinc-900 w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden border border-gray-200 dark:border-zinc-800">
                    <div
                        class="p-8 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-green-50/50 dark:bg-green-900/10">
                        <div>
                            <h3 class="text-sm font-black uppercase tracking-widest italic text-green-700">Promote to
                                Business Client</h3>
                            <p class="text-[9px] font-bold text-gray-400 uppercase mt-1 italic">Finalizing Partnership
                                for {{ conversionForm.company_name }}</p>
                        </div>
                        <button @click="showClientConversionModal = false" class="hover:rotate-90 transition-transform">
                            <X class="w-6 h-6 text-gray-400" />
                        </button>
                    </div>
                    <form @submit.prevent="submitConversion" class="p-8 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-[9px] font-black text-gray-400 uppercase ml-2">Business Type</label>
                                <select v-model="conversionForm.business_type"
                                    class="w-full bg-gray-50 dark:bg-zinc-800 border-none rounded-xl text-[10px] font-black uppercase p-4">
                                    <option value="wholesaler">Wholesaler</option>
                                    <option value="retailer">Retailer</option>
                                    <option value="manufacturer">Manufacturer</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[9px] font-black text-gray-400 uppercase ml-2">TIN Number</label>
                                <input v-model="conversionForm.tin_number" placeholder="000-000-000" type="text"
                                    class="w-full bg-gray-50 dark:bg-zinc-800 border-none rounded-xl text-[10px] font-black p-4"
                                    required />
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] font-black text-gray-400 uppercase ml-2">Official Company
                                Address</label>
                            <textarea v-model="conversionForm.company_address" rows="3"
                                placeholder="COMPLETE BUSINESS ADDRESS"
                                class="w-full bg-gray-50 dark:bg-zinc-800 border-none rounded-xl text-[10px] font-black p-4"
                                required></textarea>
                        </div>
                        <button type="submit" :disabled="conversionForm.processing"
                            class="w-full py-4 bg-green-600 text-white rounded-2xl text-[10px] font-black uppercase shadow-xl hover:brightness-110 transition-all flex items-center justify-center gap-2">
                            <Building2 class="w-4 h-4" />
                            {{ conversionForm.processing ? 'Converting...' : 'Finalize Official Client' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Create Deal Modal (unchanged) -->
            <div v-if="showCreateModal"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
                <div
                    class="bg-white dark:bg-zinc-900 w-full max-w-md rounded-[2.5rem] shadow-2xl overflow-hidden border border-gray-200 dark:border-zinc-800">
                    <div
                        class="p-8 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50 dark:bg-zinc-800/50">
                        <h3 class="text-sm font-black uppercase tracking-widest italic">Initiate New Textile Deal</h3>
                        <button @click="showCreateModal = false" class="hover:rotate-90 transition-transform">
                            <X class="w-6 h-6 text-gray-400" />
                        </button>
                    </div>
                    <form @submit.prevent="submit" class="p-8 space-y-5">
                        <div class="space-y-4">
                            <input v-model="form.company_name" placeholder="COMPANY NAME" type="text"
                                class="w-full bg-gray-50 dark:bg-zinc-800 border-none rounded-xl text-xs font-black uppercase p-4"
                                required />
                            <input v-model="form.contact_person" placeholder="CONTACT PERSON" type="text"
                                class="w-full bg-gray-50 dark:bg-zinc-800 border-none rounded-xl text-xs font-black uppercase p-4"
                                required />
                            <div class="grid grid-cols-2 gap-4">
                                <input v-model="form.email" placeholder="EMAIL" type="email"
                                    class="w-full bg-gray-50 dark:bg-zinc-800 border-none rounded-xl text-[10px] font-black uppercase p-4"
                                    required />
                                <input v-model="form.phone" placeholder="PHONE" type="text"
                                    class="w-full bg-gray-50 dark:bg-zinc-800 border-none rounded-xl text-[10px] font-black uppercase p-4"
                                    required />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <input v-model="form.estimated_value" placeholder="VALUE (₱)" type="number"
                                    class="w-full bg-gray-50 dark:bg-zinc-800 border-none rounded-xl text-xs font-black uppercase p-4"
                                    required />
                                <select v-model="form.interest_fabric"
                                    class="w-full bg-gray-50 dark:bg-zinc-800 border-none rounded-xl text-xs font-black uppercase p-4">
                                    <option>Cotton</option>
                                    <option>Wool</option>
                                    <option>Nylon</option>
                                    <option>Polyester</option>
                                    <option>Silk</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" :disabled="form.processing"
                            class="w-full py-4 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase shadow-xl hover:brightness-110 transition-all">
                            {{ form.processing ? 'Syncing Pipeline...' : 'Confirm & Initiate Deal' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Drop Confirm Modal (unchanged) -->
            <div v-if="showDropConfirm"
                class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div
                    class="bg-white dark:bg-zinc-900 w-full max-w-sm rounded-[2.5rem] shadow-2xl p-8 text-center border border-gray-100 dark:border-zinc-800">
                    <div
                        class="h-16 w-16 rounded-3xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 mx-auto mb-6">
                        <HelpCircle class="h-8 w-8" />
                    </div>
                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter mb-2 italic">
                        Confirm Progress?</h3>
                    <p class="text-[11px] text-gray-500 mb-8 font-bold uppercase leading-relaxed">
                        Advance <span class="text-blue-600">{{ pendingMove.companyName }}</span> to <span
                            class="text-blue-600">{{ pendingMove.newStatus }}</span>?
                    </p>
                    <div class="flex gap-3">
                        <button @click="closeConfirm"
                            class="flex-1 py-4 rounded-2xl bg-gray-50 dark:bg-zinc-800 text-[10px] font-black uppercase text-gray-400">Cancel</button>
                        <button @click="confirmMove"
                            class="flex-1 py-4 rounded-2xl bg-blue-600 text-white text-[10px] font-black uppercase shadow-lg shadow-blue-500/20">Verify
                            & Move</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    height: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>