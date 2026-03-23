<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, computed } from 'vue'
import {
    BookOpen,
    PlayCircle,
    CheckCircle2,
    Award,
    Clock,
    Search,
    Video,
    FileText,
    ArrowRight,
    UserCheck,
    GraduationCap,
    User,
    Shield,
    MoreHorizontal,
    Mail,
    LayoutDashboard,
    X,
    CheckCircle,
    BadgeCheck,
    Star
} from 'lucide-vue-next'

const props = defineProps({
    trainees: {
        type: Array,
        default: () => []
    }
})

/* -------------------------
   MODAL & TOAST STATE
--------------------------*/
const isConfirmModalOpen = ref(false)
const isGradeModalOpen = ref(false) // Modal for Coursework/Grading
const selectedTrainee = ref(null)
const showToast = ref(false)
const toastMessage = ref('')

const triggerToast = (msg) => {
    toastMessage.value = msg;
    showToast.value = true;
    setTimeout(() => showToast.value = false, 3000);
};

/* -------------------------
   GRADING / COURSEWORK LOGIC
--------------------------*/
const gradeForm = useForm({
    skills_performance: 0,
    behaviour: 0,
    technicals: 0,
    safety_awareness: 0,
    productivity: 0
})

const openGradeModal = (trainee) => {
    selectedTrainee.value = trainee;
    // Pre-fill stars if the trainee already has a grade record
    if (trainee.trainee_grade) {
        gradeForm.skills_performance = trainee.trainee_grade.skills_performance;
        gradeForm.behaviour = trainee.trainee_grade.behaviour;
        gradeForm.technicals = trainee.trainee_grade.technicals;
        gradeForm.safety_awareness = trainee.trainee_grade.safety_awareness;
        gradeForm.productivity = trainee.trainee_grade.productivity;
    } else {
        gradeForm.reset();
    }
    isGradeModalOpen.value = true;
}

const submitGrades = () => {
    gradeForm.post(route('hrm.training.grade', selectedTrainee.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            triggerToast(`Grades updated for ${selectedTrainee.value.name}`);
            isGradeModalOpen.value = false;
        }
    })
}

/* -------------------------
   PROMOTION SUGGESTION LOGIC
--------------------------*/
const openConfirmModal = (trainee) => {
    // Check if grade reaches 80% (Total stars / 25 * 100)
    const grade = trainee.trainee_grade ? trainee.trainee_grade.total_percentage : 0;

    if (grade < 80) {
        triggerToast(`Cannot suggest promotion. ${trainee.name} current grade is ${grade}%. (80% Required)`);
        return;
    }

    selectedTrainee.value = trainee;
    isConfirmModalOpen.value = true;
}

const confirmPromotion = () => {
    if (!selectedTrainee.value) return;

    router.post(route('hrm.training.suggest-promotion', selectedTrainee.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            triggerToast(`Promotion suggestion for ${selectedTrainee.value.name} sent to HR Manager!`);
            isConfirmModalOpen.value = false;
            selectedTrainee.value = null;
        },
        onError: () => {
            triggerToast('Failed to send suggestion. Please try again.');
        }
    })
}

const searchQuery = ref('')

const filteredTrainees = computed(() => {
    return props.trainees.filter(t => {
        const matchesSearch = t.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            t.role.toLowerCase().includes(searchQuery.value.toLowerCase());
        const isStillTrainee = t.position === 'trainee';
        const isNotSuggested = !t.promotion_suggested;

        return matchesSearch && isStillTrainee && isNotSuggested;
    })
})

const getRoleBadgeColor = (role) => {
    const colors = {
        'HRM': 'bg-pink-100 text-pink-600',
        'SCM': 'bg-blue-100 text-blue-600',
        'FIN': 'bg-emerald-100 text-emerald-600',
        'MAN': 'bg-purple-100 text-purple-600',
        'INV': 'bg-amber-100 text-amber-600',
        'ORD': 'bg-green-100 text-amber-600',
    }
    return colors[role] || 'bg-slate-100 text-slate-600'
}
</script>

<template>

    <Head title="Trainee Management" />

    <AuthenticatedLayout>
        <Transition name="toast">
            <div v-if="showToast"
                class="fixed top-6 right-6 z-[100] flex items-center gap-3 px-6 py-4 bg-slate-900 text-white rounded-2xl shadow-2xl border border-white/10">
                <CheckCircle class="h-5 w-5 text-emerald-400" />
                <p class="text-sm font-bold uppercase tracking-tight">{{ toastMessage }}</p>
            </div>
        </Transition>

        <div v-if="isGradeModalOpen" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="isGradeModalOpen = false"></div>
            <div class="relative w-full max-w-md bg-white rounded-[2.5rem] p-8 shadow-2xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-black text-slate-900 uppercase">Coursework Grading</h3>
                    <button @click="isGradeModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <X class="h-6 w-6" />
                    </button>
                </div>

                <p class="text-sm text-slate-500 mb-6">Rate <b>{{ selectedTrainee?.name }}</b> based on their training
                    performance.</p>

                <div class="space-y-6">
                    <div v-for="criterion in [
                        { id: 'skills_performance', label: 'Skills Performance' },
                        { id: 'behaviour', label: 'Behaviour' },
                        { id: 'technicals', label: 'Technicals' },
                        { id: 'safety_awareness', label: 'Safety Awareness' },
                        { id: 'productivity', label: 'Productivity' }
                    ]" :key="criterion.id">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">{{
                            criterion.label }}</p>
                        <div class="flex gap-2">
                            <button v-for="star in 5" :key="star" @click="gradeForm[criterion.id] = star" type="button">
                                <Star
                                    :class="[gradeForm[criterion.id] >= star ? 'text-amber-400 fill-amber-400' : 'text-slate-200', 'h-6 w-6 transition-colors']" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex flex-col gap-3">
                    <button @click="submitGrades"
                        class="w-full bg-slate-900 text-white py-4 rounded-xl font-bold uppercase text-xs tracking-widest hover:bg-blue-600 transition-all">
                        Save Trainee Grades
                    </button>
                </div>
            </div>
        </div>

        <div v-if="isConfirmModalOpen" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="isConfirmModalOpen = false"></div>
            <div class="relative w-full max-w-sm bg-white rounded-[2rem] p-8 shadow-2xl text-center">
                <div class="h-20 w-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <UserCheck class="h-10 w-10 text-blue-600" />
                </div>
                <h3 class="text-xl font-black text-slate-900 uppercase">Suggest Promotion?</h3>
                <p class="text-slate-500 text-sm mt-2 mb-8 leading-relaxed">
                    Would you like to suggest <b>{{ selectedTrainee?.name }}</b> to the HR Manager for promotion to
                    official staff?
                </p>
                <div class="flex flex-col gap-3">
                    <button @click="confirmPromotion"
                        class="w-full bg-blue-600 text-white py-4 rounded-xl font-bold shadow-lg shadow-blue-500/20 active:scale-95 transition-all uppercase text-xs tracking-widest">
                        Send Suggestion
                    </button>
                    <button @click="isConfirmModalOpen = false"
                        class="w-full px-6 py-3 text-slate-400 font-bold hover:bg-slate-50 rounded-xl transition-all text-xs uppercase">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                    Trainee <span class="text-blue-600">Development</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Monitor and manage newly onboarded
                    staff progress.</p>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative">
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input v-model="searchQuery" type="text" placeholder="Search trainees..."
                        class="pl-11 pr-6 py-3 bg-white dark:bg-slate-800 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-600 shadow-sm w-64" />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white relative overflow-hidden shadow-xl">
                    <div class="relative z-10">
                        <p class="text-slate-400 text-[10px] font-black uppercase tracking-[0.2em] mb-2">Current Batch
                        </p>
                        <h2 class="text-4xl font-black mb-1">{{ filteredTrainees.length }}</h2>
                        <p class="text-blue-400 text-xs font-bold mb-6">Active Trainees</p>
                        <button
                            class="w-full py-3 bg-blue-600 hover:bg-blue-700 rounded-xl font-bold text-xs uppercase tracking-widest transition-all">
                            View Reports
                        </button>
                    </div>
                    <GraduationCap class="absolute -bottom-6 -right-6 h-32 w-32 text-white/5 -rotate-12" />
                </div>

                <div
                    class="bg-white dark:bg-slate-800 p-6 rounded-[2.5rem] border border-slate-100 dark:border-slate-700 shadow-sm">
                    <h3
                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <Shield class="h-3 w-3 text-blue-600" /> Department Distribution
                    </h3>
                    <div class="space-y-3">
                        <div v-for="role in ['HRM', 'SCM', 'FIN', 'MAN', 'INV', 'ORD', 'WAR', 'CRM', 'ECO']" :key="role"
                            class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-600">{{ role }}</span>
                            <span class="px-2 py-1 bg-slate-50 rounded-lg text-[10px] font-black text-slate-500">
                                {{trainees.filter(t => t.role === role && t.position === 'trainee').length}}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-3">
                <div v-if="filteredTrainees.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="trainee in filteredTrainees" :key="trainee.id"
                        class="bg-white dark:bg-slate-800 p-6 rounded-[2.5rem] border border-slate-100 dark:border-slate-700 shadow-sm hover:border-blue-400 transition-all group">

                        <div class="flex items-start justify-between mb-6">
                            <div class="flex items-center gap-4">
                                <div
                                    class="h-14 w-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-black">
                                    {{ trainee.name.charAt(0) }}
                                </div>
                                <div>
                                    <h4 class="text-sm font-black text-slate-900 dark:text-white">{{ trainee.name }}
                                    </h4>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span
                                            :class="[getRoleBadgeColor(trainee.role), 'px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-tighter']">
                                            {{ trainee.role }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-medium">ID: #{{ trainee.id
                                            }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-2xl">
                                <div class="flex justify-between items-center mb-2">
                                    <p class="text-[10px] font-black text-slate-400 uppercase">Trainee Grade</p>
                                    <p
                                        :class="[(trainee.trainee_grade?.total_percentage || 0) >= 80 ? 'text-emerald-500' : 'text-blue-600', 'text-[10px] font-black']">
                                        {{ trainee.trainee_grade ? trainee.trainee_grade.total_percentage : 0 }}%
                                    </p>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                    <div :class="[(trainee.trainee_grade?.total_percentage || 0) >= 80 ? 'bg-emerald-500' : 'bg-blue-600', 'h-full transition-all']"
                                        :style="{ width: (trainee.trainee_grade ? trainee.trainee_grade.total_percentage : 0) + '%' }">
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <button @click="openGradeModal(trainee)"
                                    class="flex-1 flex items-center justify-center gap-2 py-3 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all shadow-lg">
                                    <LayoutDashboard class="h-3.5 w-3.5" /> Coursework
                                </button>

                                <button @click="openConfirmModal(trainee)"
                                    class="px-4 py-3 bg-slate-50 text-slate-400 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition-all border border-transparent hover:border-blue-100"
                                    :title="(trainee.trainee_grade?.total_percentage || 0) < 80 ? 'Grade must be at least 80%' : 'Suggest Promotion'">
                                    <UserCheck
                                        :class="[(trainee.trainee_grade?.total_percentage || 0) >= 80 ? 'text-emerald-500' : '', 'h-4 w-4']" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else
                    class="flex flex-col items-center justify-center py-20 bg-slate-50 rounded-[3rem] border-2 border-dashed border-slate-200">
                    <div class="h-20 w-20 bg-white rounded-3xl flex items-center justify-center shadow-sm mb-4">
                        <User class="h-10 w-10 text-slate-200" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-400">No trainees found</h3>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>