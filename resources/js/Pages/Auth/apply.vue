<script setup>
import { onMounted, ref, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    FileCheck, Upload, Trash2, ShieldCheck, Save, CheckCircle2
} from 'lucide-vue-next';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const isLoaded = ref(false);
const showSuccessModal = ref(false);

const form = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    email: '',
    phone_country: '+63',
    phone_raw: '',
    phone_number: '',
    street_address: '',
    city: '',
    state_province: '',
    postal_zip_code: '',
    position_applied: '',
    expected_salary: '',
    notice_period: 'immediate',
    textile_experience: '',
    sss_file: null,
    philhealth_file: null,
    pagibig_file: null,
    status: 'pending'
});

const inputWarnings = ref({
    first_name: '',
    middle_name: '',
    last_name: '',
    email: '',
    phone_raw: '',
    street_address: '',
    city: '',
    state_province: '',
    postal_zip_code: '',
    expected_salary: ''
});

let warningTimeouts = {};

onMounted(() => {
    isLoaded.value = true;
});

const triggerWarning = (field, message) => {
    inputWarnings.value[field] = message;
    if (warningTimeouts[field]) clearTimeout(warningTimeouts[field]);
    warningTimeouts[field] = setTimeout(() => {
        inputWarnings.value[field] = '';
    }, 3000);
};

// --- 1. PHYSICAL KEYPRESS BLOCKS ---
const blockNumbersAndSpecial = (e, field) => {
    if (e.key.length === 1 && !/^[a-zA-Z\sñÑ-]$/.test(e.key)) {
        e.preventDefault();
        triggerWarning(field, 'Numbers and special characters are not allowed.');
    }
};

const blockNonNumeric = (e, field) => {
    if (e.key.length === 1 && !/^\d$/.test(e.key)) {
        e.preventDefault();
        triggerWarning(field, 'Letters and special characters are not allowed.');
    }
};

const blockSpecialForAddress = (e) => {
    if (e.key.length === 1 && !/^[a-zA-Z0-9\sñÑ.,#-]$/.test(e.key)) {
        e.preventDefault();
        triggerWarning('street_address', 'Invalid character. Use alphanumeric and basic punctuation.');
    }
};

const blockSpecialForEmail = (e) => {
    if (e.key.length === 1 && !/^[a-zA-Z0-9@.\-]$/.test(e.key)) {
        e.preventDefault();
        triggerWarning('email', 'Invalid character. Only letters, numbers, @, ., and - are allowed.');
    }
};

// --- 2. PASTE SANITIZATION WATCHERS ---
const sanitizeName = (val, field) => {
    const filtered = val.replace(/[^a-zA-Z\sñÑ-]/g, '');
    if (val !== filtered) {
        form[field] = filtered;
        triggerWarning(field, 'Invalid characters removed.');
    }
};
watch(() => form.first_name, (val) => sanitizeName(val, 'first_name'));
watch(() => form.middle_name, (val) => sanitizeName(val, 'middle_name'));
watch(() => form.last_name, (val) => sanitizeName(val, 'last_name'));
watch(() => form.city, (val) => sanitizeName(val, 'city'));
watch(() => form.state_province, (val) => sanitizeName(val, 'state_province'));

watch(() => form.street_address, (val) => {
    const filtered = val.replace(/[^a-zA-Z0-9\sñÑ.,#-]/g, '');
    if (val !== filtered) form.street_address = filtered;
});

watch(() => form.email, (val) => {
    const filtered = val.replace(/[^a-zA-Z0-9@.\-]/g, '');
    if (val !== filtered) form.email = filtered;
});

watch(() => form.phone_raw, (val) => {
    const filtered = val.replace(/\D/g, '').substring(0, 12);
    if (val !== filtered) form.phone_raw = filtered;
});

watch(() => form.postal_zip_code, (val) => {
    const filtered = val.replace(/\D/g, '').substring(0, 4);
    if (val !== filtered) form.postal_zip_code = filtered;
});

// --- 3. HARD SUBMIT LOCKS & FILE HANDLERS ---
const handleFileUpload = (e, type) => {
    const file = e.target.files[0];
    if (file) {
        form[type + '_file'] = file;
    }
};

const removeFile = (type) => {
    form[type + '_file'] = null;
};

const submitForm = () => {
    if (!/^[a-zA-Z\sñÑ-]+$/.test(form.first_name) || !/^[a-zA-Z\sñÑ-]+$/.test(form.last_name)) {
        toast.error('First Name and Last Name are required and must only contain letters.');
        return;
    }

    if (!form.street_address || !form.city || !form.state_province) {
        toast.error('Complete residential details are required.');
        return;
    }

    if (!form.position_applied) {
        toast.error('Please select the position you are applying for.');
        return;
    }

    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!emailRegex.test(form.email)) {
        toast.error('Please enter a valid email address.');
        triggerWarning('email', 'Invalid email format.');
        return;
    }

    if (form.phone_raw.length !== 12 || !/^\d{12}$/.test(form.phone_raw)) {
        toast.error('Phone number must be exactly 12 digits.');
        triggerWarning('phone_raw', 'Must be exactly 12 digits.');
        return;
    }

    if (form.postal_zip_code.length !== 4) {
        toast.error('Postal/Zip code must be exactly 4 digits.');
        triggerWarning('postal_zip_code', 'Must be exactly 4 digits.');
        return;
    }

    form.phone_number = `${form.phone_country}${form.phone_raw}`;

    form.post(route('applicants.public.store'), {
        forceFormData: true,
        onSuccess: () => {
            showSuccessModal.value = true;
            setTimeout(() => {
                router.visit('/');
            }, 3000);
        },
        onError: (errors) => {
            const errorMsg = Object.values(errors)[0] || 'Application submission failed. Please check your inputs.';
            toast.error(errorMsg);
        }
    });
};
</script>

<template>

    <Head title="Join Our Team | Monti Corp Careers" />

    <div class="relative min-h-screen flex flex-col bg-cover bg-center bg-no-repeat"
        style="background-image: url('/images/threads.jpg');">

        <div class="absolute inset-0 bg-black/40"></div>

        <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="showSuccessModal"
                class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-black/60 backdrop-blur-md">
                <div
                    class="bg-slate-900/90 backdrop-blur-xl border border-white/20 rounded-[2rem] p-10 max-w-sm w-full shadow-2xl shadow-black text-center">
                    <div
                        class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-emerald-500/20 mb-6 ring-4 ring-emerald-500/10">
                        <CheckCircle2 class="w-10 h-10 text-emerald-400" />
                    </div>
                    <h3 class="text-2xl font-black text-white mb-2 tracking-tight">Application Received</h3>
                    <p class="text-slate-300 mb-8 leading-relaxed font-medium">Your professional profile has been
                        securely sent to our HR node. Redirecting you shortly...</p>
                    <Link href="/"
                        class="inline-flex items-center justify-center w-full py-4 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-2xl transition-all active:scale-95 shadow-lg shadow-blue-500/20 tracking-widest uppercase text-sm">
                        Return Home Now
                    </Link>
                </div>
            </div>
        </Transition>

        <nav class="relative z-30 px-6 py-5 flex items-center">
            <Link href="/" class="flex items-center gap-3 group">
                <div
                    class="size-10 sm:size-11 p-2.5 bg-white/90 backdrop-blur-sm rounded-xl shadow-md group-hover:scale-105 transition-transform duration-300">
                    <img src="/images/applogo.png" alt="Monti Textile Logo" class="h-full w-full object-contain" />
                </div>
                <span class="font-black text-2xl tracking-tight text-white drop-shadow-md">
                    Monti<span class="text-blue-300">Textile</span>
                </span>
            </Link>
        </nav>

        <div class="relative z-10 flex-grow flex items-center justify-center px-5 pb-12 pt-4">
            <div class="w-full max-w-4xl"
                :class="{ 'opacity-0 translate-y-8': !isLoaded, 'opacity-100 translate-y-0': isLoaded }"
                style="transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.1s;">

                <div
                    class="backdrop-blur-lg bg-white/10 border border-white/20 rounded-3xl shadow-2xl shadow-black/40 overflow-hidden">
                    <div class="p-8 md:p-10 space-y-10">

                        <div class="text-center mb-10">
                            <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight drop-shadow-lg">
                                Careers Application
                            </h1>
                            <p class="mt-3 text-slate-200 text-base max-w-2xl mx-auto">
                                Fill out the details below to apply for a position. All applications are securely
                                processed by our human resources department.
                            </p>
                        </div>

                        <form @submit.prevent="submitForm" class="space-y-10">

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="md:col-span-3">
                                    <h3
                                        class="text-sm font-black uppercase tracking-widest text-blue-300 border-b border-white/20 pb-2 mb-2">
                                        Personal Identity</h3>
                                </div>

                                <div>
                                    <InputLabel for="first_name" value="First Name"
                                        class="text-white/90 font-semibold" />
                                    <TextInput id="first_name" type="text"
                                        class="mt-1 block w-full py-3 px-4 bg-white/15 border border-white/30 text-white placeholder:text-slate-300 rounded-xl focus:border-blue-400 focus:ring-blue-400/40"
                                        v-model="form.first_name" required autofocus placeholder="Juan"
                                        @keypress="blockNumbersAndSpecial($event, 'first_name')" />
                                    <p v-if="inputWarnings.first_name"
                                        class="text-xs text-red-300 font-bold mt-1 ml-1 animate-pulse">{{
                                            inputWarnings.first_name }}</p>
                                    <InputError class="mt-1 text-red-300" :message="form.errors.first_name" />
                                </div>

                                <div>
                                    <InputLabel for="middle_name" value="Middle Name (Optional)"
                                        class="text-white/90 font-semibold" />
                                    <TextInput id="middle_name" type="text"
                                        class="mt-1 block w-full py-3 px-4 bg-white/15 border border-white/30 text-white placeholder:text-slate-300 rounded-xl focus:border-blue-400 focus:ring-blue-400/40"
                                        v-model="form.middle_name" placeholder="Santos"
                                        @keypress="blockNumbersAndSpecial($event, 'middle_name')" />
                                    <p v-if="inputWarnings.middle_name"
                                        class="text-xs text-red-300 font-bold mt-1 ml-1 animate-pulse">{{
                                            inputWarnings.middle_name }}</p>
                                    <InputError class="mt-1 text-red-300" :message="form.errors.middle_name" />
                                </div>

                                <div>
                                    <InputLabel for="last_name" value="Last Name" class="text-white/90 font-semibold" />
                                    <TextInput id="last_name" type="text"
                                        class="mt-1 block w-full py-3 px-4 bg-white/15 border border-white/30 text-white placeholder:text-slate-300 rounded-xl focus:border-blue-400 focus:ring-blue-400/40"
                                        v-model="form.last_name" required placeholder="Dela Cruz"
                                        @keypress="blockNumbersAndSpecial($event, 'last_name')" />
                                    <p v-if="inputWarnings.last_name"
                                        class="text-xs text-red-300 font-bold mt-1 ml-1 animate-pulse">{{
                                            inputWarnings.last_name }}</p>
                                    <InputError class="mt-1 text-red-300" :message="form.errors.last_name" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="md:col-span-3">
                                    <h3
                                        class="text-sm font-black uppercase tracking-widest text-blue-300 border-b border-white/20 pb-2 mb-2">
                                        Residential Details</h3>
                                </div>

                                <div class="md:col-span-3">
                                    <InputLabel for="street_address" value="Street Address"
                                        class="text-white/90 font-semibold" />
                                    <TextInput id="street_address" type="text"
                                        class="mt-1 block w-full py-3 px-4 bg-white/15 border border-white/30 text-white placeholder:text-slate-300 rounded-xl focus:border-blue-400 focus:ring-blue-400/40"
                                        v-model="form.street_address" required
                                        placeholder="123 Main St., Brgy. San Jose" @keypress="blockSpecialForAddress" />
                                    <p v-if="inputWarnings.street_address"
                                        class="text-xs text-red-300 font-bold mt-1 ml-1 animate-pulse">{{
                                            inputWarnings.street_address }}</p>
                                    <InputError class="mt-1 text-red-300" :message="form.errors.street_address" />
                                </div>

                                <div>
                                    <InputLabel for="city" value="City" class="text-white/90 font-semibold" />
                                    <TextInput id="city" type="text"
                                        class="mt-1 block w-full py-3 px-4 bg-white/15 border border-white/30 text-white placeholder:text-slate-300 rounded-xl focus:border-blue-400 focus:ring-blue-400/40"
                                        v-model="form.city" required placeholder="General Trias"
                                        @keypress="blockNumbersAndSpecial($event, 'city')" />
                                    <p v-if="inputWarnings.city"
                                        class="text-xs text-red-300 font-bold mt-1 ml-1 animate-pulse">{{
                                            inputWarnings.city }}</p>
                                    <InputError class="mt-1 text-red-300" :message="form.errors.city" />
                                </div>

                                <div>
                                    <InputLabel for="state_province" value="Province"
                                        class="text-white/90 font-semibold" />
                                    <TextInput id="state_province" type="text"
                                        class="mt-1 block w-full py-3 px-4 bg-white/15 border border-white/30 text-white placeholder:text-slate-300 rounded-xl focus:border-blue-400 focus:ring-blue-400/40"
                                        v-model="form.state_province" required placeholder="Cavite"
                                        @keypress="blockNumbersAndSpecial($event, 'state_province')" />
                                    <p v-if="inputWarnings.state_province"
                                        class="text-xs text-red-300 font-bold mt-1 ml-1 animate-pulse">{{
                                            inputWarnings.state_province }}</p>
                                    <InputError class="mt-1 text-red-300" :message="form.errors.state_province" />
                                </div>

                                <div>
                                    <InputLabel for="postal_zip_code" value="Postal Code"
                                        class="text-white/90 font-semibold" />
                                    <TextInput id="postal_zip_code" type="text" maxlength="4"
                                        class="mt-1 block w-full py-3 px-4 bg-white/15 border border-white/30 text-white placeholder:text-slate-300 rounded-xl focus:border-blue-400 focus:ring-blue-400/40 font-mono tracking-widest"
                                        v-model="form.postal_zip_code" required placeholder="4107"
                                        @keypress="blockNonNumeric($event, 'postal_zip_code')" />
                                    <p v-if="inputWarnings.postal_zip_code"
                                        class="text-xs text-red-300 font-bold mt-1 ml-1 animate-pulse">{{
                                            inputWarnings.postal_zip_code }}</p>
                                    <InputError class="mt-1 text-red-300" :message="form.errors.postal_zip_code" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <h3
                                        class="text-sm font-black uppercase tracking-widest text-blue-300 border-b border-white/20 pb-2 mb-2">
                                        Professional Profile</h3>
                                </div>

                                <div>
                                    <InputLabel for="email" value="Email Address" class="text-white/90 font-semibold" />
                                    <TextInput id="email" type="email"
                                        class="mt-1 block w-full py-3 px-4 bg-white/15 border border-white/30 text-white placeholder:text-slate-300 rounded-xl focus:border-blue-400 focus:ring-blue-400/40"
                                        v-model="form.email" required placeholder="applicant@email.com"
                                        @keypress="blockSpecialForEmail" />
                                    <p v-if="inputWarnings.email"
                                        class="text-xs text-red-300 font-bold mt-1 ml-1 animate-pulse">{{
                                            inputWarnings.email }}</p>
                                    <InputError class="mt-1 text-red-300" :message="form.errors.email" />
                                </div>

                                <div>
                                    <InputLabel for="phone_raw" value="Phone Number (12 Digits)"
                                        class="text-white/90 font-semibold" />
                                    <div class="flex gap-2 mt-1">
                                        <select v-model="form.phone_country"
                                            class="w-[35%] py-3 px-2 bg-white/15 border border-white/30 text-white rounded-xl focus:border-blue-400 focus:ring-blue-400/40 custom-select text-center">
                                            <option value="+63">+63 (PH)</option>
                                            <option value="+1">+1 (US/CA)</option>
                                            <option value="+44">+44 (UK)</option>
                                            <option value="+61">+61 (AU)</option>
                                            <option value="+81">+81 (JP)</option>
                                            <option value="+65">+65 (SG)</option>
                                            <option value="+971">+971 (AE)</option>
                                        </select>
                                        <TextInput id="phone_raw" type="text" maxlength="12"
                                            class="w-[65%] py-3 px-4 bg-white/15 border border-white/30 text-white placeholder:text-slate-300 rounded-xl focus:border-blue-400 focus:ring-blue-400/40 font-mono"
                                            v-model="form.phone_raw" required placeholder="912345678901"
                                            @keypress="blockNonNumeric($event, 'phone_raw')" />
                                    </div>
                                    <div class="flex justify-between items-center mt-1 ml-1 pr-1">
                                        <p v-if="inputWarnings.phone_raw"
                                            class="text-xs text-red-300 font-bold animate-pulse">{{
                                                inputWarnings.phone_raw }}</p>
                                        <p v-else class="text-xs text-slate-300 transition-opacity">Numbers only.</p>
                                        <p :class="form.phone_raw.length === 12 ? 'text-blue-300' : 'text-slate-300'"
                                            class="text-xs font-bold transition-colors">{{ form.phone_raw.length }} / 12
                                        </p>
                                    </div>
                                    <InputError class="mt-1 text-red-300" :message="form.errors.phone_number" />
                                </div>

                                <div>
                                    <InputLabel for="position_applied" value="Position Applied"
                                        class="text-white/90 font-semibold" />
                                    <select id="position_applied" v-model="form.position_applied"
                                        class="mt-1 block w-full py-3 px-4 bg-white/15 border border-white/30 text-white rounded-xl focus:border-blue-400 focus:ring-blue-400/40 custom-select"
                                        required>
                                        <option value="" disabled>Select Position...</option>
                                        <option value="Human Resource Staff">Human Resource Staff</option>
                                        <option value="Human Resource Manager">Human Resource Manager</option>
                                        <option value="Warehouse Staff">Warehouse Staff</option>
                                        <option value="Warehouse Manager">Warehouse Manager</option>
                                        <option value="Company Driver">Company Driver</option>
                                        <option value="Customer Relationship Staff">Customer Relationship Staff</option>
                                        <option value="Customer Relationship Manager">Customer Relationship Manager
                                        </option>
                                        <option value="Procurement Staff">Procurement Staff</option>
                                        <option value="Procurement Manager">Procurement Manager</option>
                                        <option value="Accounting Staff">Accounting Staff</option>
                                        <option value="Accounting Manager">Accounting Manager</option>
                                        <option value="Manufacturing Staff">Manufacturing Staff</option>
                                        <option value="Manufacturing Manager">Manufacturing Manager</option>
                                        <option value="Color Chemist">Color Chemist</option>
                                        <option value="I.T. Personel">I.T. Personel</option>
                                        <option value="Procurement Staff">Procurement Staff</option>
                                        <option value="Procurement Manager">Procurement Manager</option>
                                        <option value="Project Automation Staff">Project Automation Staff</option>
                                        <option value="Project Automation Manager">Project Automation Manager</option>
                                    </select>
                                    <InputError class="mt-1 text-red-300" :message="form.errors.position_applied" />
                                </div>

                                <div>
                                    <InputLabel for="expected_salary" value="Expected Monthly Salary (PHP)"
                                        class="text-white/90 font-semibold" />
                                    <TextInput id="expected_salary" type="number" step="0.01"
                                        class="mt-1 block w-full py-3 px-4 bg-white/15 border border-white/30 text-white placeholder:text-slate-300 rounded-xl focus:border-blue-400 focus:ring-blue-400/40 font-mono tracking-wider"
                                        v-model="form.expected_salary" required placeholder="0.00" />
                                    <InputError class="mt-1 text-red-300" :message="form.errors.expected_salary" />
                                </div>

                                <div>
                                    <InputLabel for="notice_period" value="Notice Period"
                                        class="text-white/90 font-semibold" />
                                    <select id="notice_period" v-model="form.notice_period"
                                        class="mt-1 block w-full py-3 px-4 bg-white/15 border border-white/30 text-white rounded-xl focus:border-blue-400 focus:ring-blue-400/40 custom-select"
                                        required>
                                        <option value="immediate">Immediate</option>
                                        <option value="15_days">15 Days</option>
                                        <option value="30_days">30 Days</option>
                                        <option value="60_days">60 Days</option>
                                    </select>
                                    <InputError class="mt-1 text-red-300" :message="form.errors.notice_period" />
                                </div>

                                <div>
                                    <InputLabel for="textile_experience" value="Textile Experience"
                                        class="text-white/90 font-semibold" />
                                    <select id="textile_experience" v-model="form.textile_experience"
                                        class="mt-1 block w-full py-3 px-4 bg-white/15 border border-white/30 text-white rounded-xl focus:border-blue-400 focus:ring-blue-400/40 custom-select"
                                        required>
                                        <option value="" disabled>Select Option...</option>
                                        <option value="yes">Yes, I have experience</option>
                                        <option value="no">No experience yet</option>
                                    </select>
                                    <InputError class="mt-1 text-red-300" :message="form.errors.textile_experience" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <h3
                                        class="text-sm font-black uppercase tracking-widest text-blue-300 border-b border-white/20 pb-2 mb-4">
                                        Compliance Documents</h3>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div v-for="type in ['sss', 'philhealth', 'pagibig']" :key="type" class="space-y-2">
                                        <p class="text-[10px] font-black text-white/70 uppercase tracking-[0.2em] ml-1">
                                            {{ type }} ID Upload</p>

                                        <div :class="form[type + '_file'] ? 'border-emerald-400/50 bg-emerald-500/10' : 'border-white/30 bg-white/5 hover:border-blue-400/50'"
                                            class="relative h-32 rounded-xl border-2 border-dashed flex flex-col items-center justify-center p-4 transition-all group overflow-hidden">

                                            <template v-if="!form[type + '_file']">
                                                <Upload
                                                    class="h-5 w-5 text-white/50 group-hover:text-blue-300 transition-colors mb-2" />
                                                <p
                                                    class="text-[10px] text-white/70 font-bold uppercase tracking-widest">
                                                    Select Image</p>
                                                <input type="file" @change="(e) => handleFileUpload(e, type)"
                                                    class="absolute inset-0 opacity-0 cursor-pointer"
                                                    accept=".jpg,.jpeg,.png,.pdf">
                                            </template>

                                            <template v-else>
                                                <div class="flex flex-col items-center text-center">
                                                    <div class="p-1.5 bg-emerald-500/20 rounded-full mb-1">
                                                        <FileCheck class="h-5 w-5 text-emerald-300" />
                                                    </div>
                                                    <p class="text-[10px] font-black text-emerald-200 truncate w-24">{{
                                                        form[type + '_file'].name }}</p>
                                                    <button @click="removeFile(type)" type="button"
                                                        class="mt-2 p-1.5 bg-red-500/20 text-red-300 rounded-lg hover:bg-red-500/40 transition-colors z-10 relative">
                                                        <Trash2 class="h-3 w-3" />
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="flex flex-col sm:flex-row items-center justify-between gap-6 pt-8 border-t border-white/10 mt-6">
                                <div
                                    class="flex items-center text-[10px] font-black uppercase tracking-widest text-white/70">
                                    <ShieldCheck class="h-5 w-5 text-blue-300 mr-2" /> Data Encryption Active
                                </div>

                                <PrimaryButton
                                    class="w-full sm:w-auto px-10 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl shadow-lg shadow-blue-700/30 transition-all duration-200"
                                    :class="{ 'opacity-60 cursor-wait': form.processing }" :disabled="form.processing">
                                    <span v-if="form.processing">Processing...</span>
                                    <span v-else class="flex items-center gap-2">
                                        <Save class="w-4 h-4" /> Submit Application
                                    </span>
                                </PrimaryButton>
                            </div>

                            <div class="text-center mt-4 border-t border-white/10 pt-6">
                                <Link href="/"
                                    class="text-sm font-semibold text-slate-300 hover:text-white transition-colors">
                                    &larr; Return to Home
                                </Link>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap');

.font-mono {
    font-family: 'JetBrains Mono', monospace;
}

input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0 30px rgba(255, 255, 255, 0.08) inset !important;
    -webkit-text-fill-color: white !important;
}

input,
select,
textarea {
    @apply transition-all duration-300 ease-in-out;
}

/* Ensure the <option> text is visible inside the transparent select field */
.custom-select option {
    background-color: #0f172a;
    color: white;
}
</style>