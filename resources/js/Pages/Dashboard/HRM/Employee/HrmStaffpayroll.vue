<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    Wallet,
    Search,
    FileDown,
    Clock,
    AlertCircle,
    CheckCircle2,
    TrendingUp,
    Filter,
    Plus,
    Save,
    CalendarDays,
    ShieldCheck,
    X,
    MoreHorizontal,
    ChevronDown,
    HelpCircle
} from 'lucide-vue-next';

const props = defineProps({
    payroll_data: Array,
    holidays: Array,
    stats: Object,
    errors: Object, // Inertia passes validation errors
    flash: Object,  // For success/error messages
});

const searchQuery = ref('');
const isHolidayModalOpen = ref(false);
const isStatutoryModalOpen = ref(false);

/** * Confirmation States for Actions
 */
const isConfirmPayrollOpen = ref(false);
const isConfirmHolidayOpen = ref(false);

/**
 * Main Payroll Form
 */
const payrollForm = useForm({
    role: 'Staff',
    base_salary: '',
    overtime_hours: '',
    late_rate_min: '',
    sunday_restday_hours: '',
    night_rate: '',
    sss_loan: '',
    pf_loan: '',
});

/**
 * Holiday Management Form
 */
const holidayForm = useForm({
    holiday_date: '',
    holiday_name: '',
    holiday_type: 'Normal',
    premium_rate: 1.0
});

/**
 * Statutory Configuration Form (Visual Only)
 */
const statMatrixForm = useForm({
    sss_mode: 'bracket',
    sss_contribution: '',
    ph_mode: 'standard',
    philhealth_rate: '',
    pagibig_mode: 'threshold',
    pagibig_fixed: '',
    tax_bracket: 'train'
});

/**
 * Action Submissions
 */
const submitPayrollEntry = () => {
    isConfirmPayrollOpen.value = false;
    payrollForm.post(route('hrm.employee.payroll.store'), {
        preserveScroll: true,
        onSuccess: () => {
            payrollForm.reset();
        },
    });
};

const submitHolidayConfig = () => {
    isConfirmHolidayOpen.value = false;
    holidayForm.post(route('hrm.holidays.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isHolidayModalOpen.value = false;
            holidayForm.reset();
        },
    });
};

const approvePayroll = (id) => {
    router.patch(route('hrm.employee.payroll.approve', id));
};

const rejectPayroll = (id) => {
    router.patch(route('hrm.employee.payroll.reject', id));
};

const submitStatutoryMatrix = () => {
    statMatrixForm.post(route('hrm.statutory.update'), {
        preserveScroll: true,
        onSuccess: () => isStatutoryModalOpen.value = false,
    });
};

const filteredPayroll = computed(() => {
    return props.payroll_data?.filter(item =>
        item.employee_id?.toString().includes(searchQuery.value) ||
        (item.employee_name && item.employee_name.toLowerCase().includes(searchQuery.value.toLowerCase()))
    );
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(value || 0);
};
</script>

<template>

    <Head title="Payroll Processing" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 bg-[#f8faff] min-h-screen">
            <div class="max-w-[1600px] mx-auto space-y-8">

                <!-- Flash Messages -->
                <div v-if="$page.props.flash?.success"
                    class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-6 py-4 rounded-2xl text-sm font-bold">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash?.error"
                    class="bg-rose-50 border border-rose-200 text-rose-800 px-6 py-4 rounded-2xl text-sm font-bold">
                    {{ $page.props.flash.error }}
                </div>
                <div v-if="$page.props.flash?.warning"
                    class="bg-amber-50 border border-amber-200 text-amber-800 px-6 py-4 rounded-2xl text-sm font-bold">
                    {{ $page.props.flash.warning }}
                </div>

                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 uppercase tracking-tight italic">
                            Payroll <span class="text-blue-600 font-light">Processor</span>
                        </h1>
                        <p class="text-[10px] sm:text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">
                            HRM Staff Terminal | Role-Based Entry Module
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3 w-full md:w-auto">
                        <button @click="isHolidayModalOpen = true"
                            class="flex-1 md:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-wider hover:bg-slate-50 transition-all">
                            <CalendarDays class="size-4 text-rose-500" /> Set Holidays
                        </button>
                        <button @click="isStatutoryModalOpen = true"
                            class="flex-1 md:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-wider hover:bg-slate-50 transition-all">
                            <ShieldCheck class="size-4 text-blue-600" /> Statutory Rates
                        </button>
                        <button
                            class="flex-1 md:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-wider shadow-lg hover:bg-slate-800 transition-all">
                            <FileDown class="size-4" /> Export CSV
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-blue-600 p-6 rounded-[2rem] shadow-xl shadow-blue-200 text-white">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-white/20 rounded-2xl">
                                <Wallet class="size-6" />
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-blue-100 uppercase tracking-widest">Gross Payout
                                    Approved</p>
                                <p class="text-xl font-black italic">{{ formatCurrency(stats?.total_payout || 0) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm text-slate-800">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl">
                                <Clock class="size-6" />
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Pending Audit
                                </p>
                                <p class="text-xl font-black italic">{{ stats?.pending_approvals || 0 }} Requests</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm text-slate-800">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl">
                                <TrendingUp class="size-6" />
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Process
                                    Efficiency</p>
                                <p class="text-xl font-black italic">98.4%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[2rem] p-6 sm:p-8 shadow-sm border border-slate-100">
                    <div class="flex items-center gap-3 mb-6">
                        <Plus class="size-5 text-blue-600" />
                        <h3 class="text-xs font-black text-slate-800 uppercase tracking-[0.2em]">Quick Entry (Apply to
                            Role)</h3>
                    </div>

                    <!-- Validation Errors -->
                    <div v-if="payrollForm.errors && Object.keys(payrollForm.errors).length"
                        class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-2xl">
                        <p class="text-[9px] font-black text-rose-600 uppercase tracking-widest mb-2">Please fix the
                            following errors:</p>
                        <ul class="list-disc list-inside text-xs text-rose-700">
                            <li v-for="(error, field) in payrollForm.errors" :key="field">{{ error }}</li>
                        </ul>
                    </div>

                    <form @submit.prevent="isConfirmPayrollOpen = true" class="space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="relative">
                                <label
                                    class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Apply
                                    Suggestions To:</label>
                                <div class="relative">
                                    <select v-model="payrollForm.role"
                                        class="w-full bg-blue-50/50 border-none rounded-xl text-xs font-bold p-3 appearance-none focus:ring-2 ring-blue-500/20 cursor-pointer">
                                        <option value="Staff">All Staff Members</option>
                                        <option value="Manager">All Managerial Staff</option>
                                    </select>
                                    <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                                        <ChevronDown class="size-4 text-slate-400" />
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label
                                    class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Base
                                    Monthly Salary</label>
                                <input v-model="payrollForm.base_salary" type="number" step="0.01"
                                    class="w-full bg-slate-50 border-none rounded-xl text-xs font-bold p-3"
                                    placeholder="0.00">
                            </div>
                            <div>
                                <label
                                    class="text-[9px] font-black text-blue-500 uppercase tracking-widest mb-2 block">Late
                                    Rate/m</label>
                                <input v-model="payrollForm.late_rate_min" type="number" step="0.01"
                                    class="w-full bg-blue-50/50 border-none rounded-xl text-xs font-bold p-3 focus:ring-2 ring-blue-500/20"
                                    placeholder="0.00">
                            </div>
                            <div>
                                <label
                                    class="text-[9px] font-black text-emerald-500 uppercase tracking-widest mb-2 block">Sunday/Restday
                                    Hr</label>
                                <input v-model="payrollForm.sunday_restday_hours" type="number" step="0.01"
                                    class="w-full bg-emerald-50/50 border-none rounded-xl text-xs font-bold p-3 focus:ring-2 ring-emerald-500/20"
                                    placeholder="0.00">
                            </div>
                            <div>
                                <label
                                    class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Night
                                    Diff Amount</label>
                                <input v-model="payrollForm.night_rate" type="number" step="0.01"
                                    class="w-full bg-slate-50 border-none rounded-xl text-xs font-bold p-3"
                                    placeholder="0.00">
                            </div>
                            <div>
                                <label
                                    class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">OT
                                    Hours</label>
                                <input v-model="payrollForm.overtime_hours" type="number" step="0.5"
                                    class="w-full bg-slate-50 border-none rounded-xl text-xs font-bold p-3"
                                    placeholder="0">
                            </div>
                            <div>
                                <label
                                    class="text-[9px] font-black text-rose-500 uppercase tracking-widest mb-2 block">SSS
                                    Loan Deduction</label>
                                <input v-model="payrollForm.sss_loan" type="number" step="0.01"
                                    class="w-full bg-rose-50/50 border-none rounded-xl text-xs font-bold p-3"
                                    placeholder="0.00">
                            </div>
                            <div>
                                <label
                                    class="text-[9px] font-black text-rose-500 uppercase tracking-widest mb-2 block">PF
                                    Loan Deduction</label>
                                <input v-model="payrollForm.pf_loan" type="number" step="0.01"
                                    class="w-full bg-rose-50/50 border-none rounded-xl text-xs font-bold p-3"
                                    placeholder="0.00">
                            </div>
                        </div>

                        <div class="flex justify-between items-center pt-2">
                            <p class="text-[9px] text-slate-400 font-bold uppercase italic">*Statutory rates (SSS,
                                PhilHealth, Pag-IBIG, BIR Tax) are automatically calculated based on salary.</p>
                            <button :disabled="payrollForm.processing"
                                class="bg-blue-600 text-white px-10 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] flex items-center gap-2 hover:bg-blue-700 transition-all shadow-xl shadow-blue-100 active:scale-95">
                                <Save class="size-4" /> Suggest Role-Wide Payroll
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                    <div
                        class="p-6 sm:p-8 border-b border-slate-50 flex flex-col md:flex-row justify-between items-center gap-4 bg-slate-50/20">
                        <div class="relative w-full md:w-96">
                            <Search class="absolute left-4 top-1/2 -translate-y-1/2 size-4 text-slate-400" />
                            <input v-model="searchQuery" type="text" placeholder="SEARCH EMPLOYEE REGISTRY..."
                                class="w-full pl-12 pr-4 py-3 bg-white border-none rounded-2xl text-[10px] font-bold tracking-widest focus:ring-2 ring-blue-500/20 shadow-sm" />
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[2200px]">
                            <thead>
                                <tr
                                    class="text-[9px] font-black text-slate-400 uppercase tracking-[0.15em] bg-slate-50/80 sticky top-0 z-10">
                                    <th
                                        class="px-8 py-5 border-b border-slate-100 whitespace-nowrap sticky left-0 bg-slate-50/80 z-20">
                                        Employee Name</th>
                                    <th class="px-8 py-5 border-b border-slate-100">days</th>
                                    <th class="px-8 py-5 border-b border-slate-100 text-center">Rate</th>
                                    <th class="px-8 py-5 border-b border-slate-100">Total</th>
                                    <th class="px-8 py-5 border-b border-slate-100 text-right bg-blue-50/30">Night</th>
                                    <th class="px-8 py-5 border-b border-slate-100 bg-blue-50/30">Rate</th>
                                    <th
                                        class="px-8 py-5 border-b border-slate-100 text-right bg-blue-50/30 text-blue-600">
                                        Amount</th>
                                    <th class="px-8 py-5 border-b border-slate-100">Overtime</th>
                                    <th class="px-8 py-5 border-b border-slate-100 text-right">Rate</th>
                                    <th class="px-8 py-5 border-b border-slate-100">Amount</th>
                                    <th class="px-8 py-5 border-b border-slate-100 text-right bg-rose-50/30">Sun/sp</th>
                                    <th class="px-8 py-5 border-b border-slate-100 bg-rose-50/30">Rate</th>
                                    <th
                                        class="px-8 py-5 border-b border-slate-100 text-right bg-rose-50/30 text-rose-600">
                                        Amount</th>
                                    <th class="px-8 py-5 border-b border-slate-100">Holiday</th>
                                    <th class="px-8 py-5 border-b border-slate-100 text-right">Late(min)</th>
                                    <th class="px-8 py-5 border-b border-slate-100">Rate/min</th>
                                    <th class="px-8 py-5 border-b border-slate-100 text-right">Total</th>
                                    <th class="px-8 py-5 border-b border-slate-100 font-black text-slate-900">Gross</th>
                                    <th class="px-8 py-5 border-b border-slate-100 text-right text-blue-600">SSS</th>
                                    <th class="px-8 py-5 border-b border-slate-100 text-emerald-600">Philhealth</th>
                                    <th class="px-8 py-5 border-b border-slate-100 text-right text-amber-600">Pagibig
                                    </th>
                                    <th class="px-8 py-5 border-b border-slate-100">SSS loan</th>
                                    <th class="px-8 py-5 border-b border-slate-100 text-right">PF Loan</th>
                                    <th
                                        class="px-8 py-5 border-b border-slate-100 font-black text-blue-700 bg-blue-50/20">
                                        Net</th>
                                    <th class="px-8 py-5 border-b border-slate-100 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="item in filteredPayroll" :key="item.id"
                                    class="hover:bg-slate-50/50 transition-colors group">
                                    <td
                                        class="px-8 py-4 sticky left-0 bg-white group-hover:bg-slate-50 z-10 shadow-[4px_0_24px_-12px_rgba(0,0,0,0.1)]">
                                        <div class="flex flex-col min-w-[150px]">
                                            <span class="text-xs font-black text-slate-800 uppercase italic">{{
                                                item.employee_name || 'N/A' }}</span>
                                            <span class="text-[9px] text-slate-400 font-bold uppercase">{{ item.role }}
                                                | {{ item.employee_id }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-4 text-[10px] font-bold text-slate-600">{{ item.days_worked || 0
                                    }}</td>
                                    <td class="px-8 py-4 text-[10px] font-bold text-center text-slate-600">{{
                                        formatCurrency(item.daily_rate) }}</td>
                                    <td class="px-8 py-4 text-[10px] font-black text-slate-800">{{
                                        formatCurrency(item.total_days_amt) }}</td>
                                    <td class="px-8 py-4 text-right text-[10px] font-bold text-slate-500 bg-blue-50/10">
                                        {{ item.night_hours || 0 }}</td>
                                    <td class="px-8 py-4 text-[10px] font-bold text-slate-500 bg-blue-50/10">{{
                                        formatCurrency(item.night_rate) }}</td>
                                    <td class="px-8 py-4 text-right text-[10px] font-black text-blue-600 bg-blue-50/10">
                                        {{ formatCurrency(item.night_amt) }}</td>
                                    <td class="px-8 py-4 text-[10px] font-bold text-slate-500">{{ item.overtime_hours ||
                                        0 }}</td>
                                    <td class="px-8 py-4 text-right text-[10px] font-bold text-slate-500">{{
                                        formatCurrency(item.ot_rate) }}</td>
                                    <td class="px-8 py-4 text-[10px] font-black text-slate-800">{{
                                        formatCurrency(item.ot_amt) }}</td>
                                    <td class="px-8 py-4 text-right text-[10px] font-bold text-slate-500 bg-rose-50/10">
                                        {{ item.sunday_restday_hours || 0 }}</td>
                                    <td class="px-8 py-4 text-[10px] font-bold text-slate-500 bg-rose-50/10">{{
                                        formatCurrency(item.sun_sp_rate) }}</td>
                                    <td class="px-8 py-4 text-right text-[10px] font-black text-rose-600 bg-rose-50/10">
                                        {{ formatCurrency(item.sun_sp_amt) }}</td>
                                    <td class="px-8 py-4 text-[10px] font-bold text-slate-600">{{
                                        formatCurrency(item.holiday_amt) }}</td>
                                    <td class="px-8 py-4 text-right text-[10px] font-bold text-rose-500">{{
                                        item.late_minutes || 0 }}</td>
                                    <td class="px-8 py-4 text-[10px] font-bold text-slate-600">{{
                                        formatCurrency(item.late_rate_min) }}</td>
                                    <td class="px-8 py-4 text-right text-[10px] font-black text-rose-600">{{
                                        formatCurrency(item.late_total_deduction) }}</td>
                                    <td class="px-8 py-4 text-[11px] font-black text-slate-900 italic">{{
                                        formatCurrency(item.gross_pay) }}</td>
                                    <td class="px-8 py-4 text-right text-[10px] font-bold text-blue-600">{{
                                        formatCurrency(item.sss_deduction) }}</td>
                                    <td class="px-8 py-4 text-[10px] font-bold text-emerald-600">{{
                                        formatCurrency(item.philhealth_deduction) }}</td>
                                    <td class="px-8 py-4 text-right text-[10px] font-bold text-amber-600">{{
                                        formatCurrency(item.pagibig_deduction) }}</td>
                                    <td class="px-8 py-4 text-[10px] font-bold text-slate-500">{{
                                        formatCurrency(item.sss_loan) }}</td>
                                    <td class="px-8 py-4 text-right text-[10px] font-bold text-slate-500">{{
                                        formatCurrency(item.pf_loan) }}</td>
                                    <td class="px-8 py-4 text-sm font-black text-blue-700 bg-blue-50/10 italic">{{
                                        formatCurrency(item.net_pay) }}</td>
                                    <td class="px-8 py-4 text-center">
                                        <div class="flex gap-2 justify-center" v-if="item.status === 'pending'">
                                            <button @click="approvePayroll(item.id)"
                                                class="p-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition-colors">
                                                <CheckCircle2 class="size-4" />
                                            </button>
                                            <button @click="rejectPayroll(item.id)"
                                                class="p-2 bg-rose-50 text-rose-600 rounded-lg hover:bg-rose-100 transition-colors">
                                                <X class="size-4" />
                                            </button>
                                        </div>
                                        <span v-else
                                            :class="item.status === 'approved' ? 'text-emerald-500' : 'text-rose-500'"
                                            class="text-[9px] font-black uppercase tracking-tighter">
                                            {{ item.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isHolidayModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="isHolidayModalOpen = false"></div>
            <div
                class="relative bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
                <div class="p-8 border-b border-slate-50 flex justify-between items-center">
                    <h2 class="text-xl font-black text-slate-900 uppercase italic">Holiday <span
                            class="text-rose-500 font-light">Calendar</span></h2>
                    <button @click="isHolidayModalOpen = false"
                        class="p-2 hover:bg-slate-50 rounded-xl transition-colors">
                        <X class="size-5 text-slate-400" />
                    </button>
                </div>
                <div class="p-8 space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label
                                class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Holiday
                                Name</label>
                            <input v-model="holidayForm.holiday_name" type="text"
                                class="w-full bg-slate-50 border-none rounded-xl text-xs font-bold p-3 focus:ring-2 ring-rose-500/20"
                                placeholder="e.g., Independence Day">
                        </div>
                        <div>
                            <label
                                class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Date</label>
                            <input v-model="holidayForm.holiday_date" type="date"
                                class="w-full bg-slate-50 border-none rounded-xl text-xs font-bold p-3 focus:ring-2 ring-rose-500/20">
                        </div>
                        <div>
                            <label
                                class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Type</label>
                            <select v-model="holidayForm.holiday_type"
                                class="w-full bg-slate-50 border-none rounded-xl text-xs font-bold p-3 focus:ring-2 ring-rose-500/20 appearance-none">
                                <option value="Normal">Normal Holiday</option>
                                <option value="Special">Special Holiday</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label
                                class="text-[9px] font-black text-rose-500 uppercase tracking-widest mb-2 block">Manual
                                Holiday Multiplier</label>
                            <input v-model="holidayForm.premium_rate" type="number" step="0.1"
                                class="w-full bg-rose-50/50 border-none rounded-xl text-xs font-bold p-3"
                                placeholder="e.g., 2.0">
                        </div>
                    </div>
                    <button @click="isConfirmHolidayOpen = true"
                        class="w-full bg-slate-900 text-white py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:scale-[1.02] active:scale-95 transition-all shadow-lg shadow-slate-200">Update
                        Holiday Schedule</button>
                </div>
            </div>
        </div>

        <div v-if="isConfirmPayrollOpen" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md"></div>
            <div
                class="relative bg-white w-full max-w-sm rounded-[2.5rem] shadow-2xl p-10 text-center animate-in fade-in zoom-in duration-200">
                <div class="mx-auto flex items-center justify-center size-16 bg-blue-50 text-blue-600 rounded-2xl mb-6">
                    <HelpCircle class="size-8" />
                </div>
                <h3 class="text-lg font-black text-slate-900 uppercase italic mb-2">Confirm Bulk Suggestion?</h3>
                <p class="text-xs text-slate-500 font-bold mb-8 leading-relaxed">
                    This will apply these payroll settings to ALL employees tagged as <span
                        class="text-blue-600 font-black">{{ payrollForm.role }}</span>. Do you want to proceed?
                </p>
                <div class="grid grid-cols-2 gap-4">
                    <button @click="isConfirmPayrollOpen = false"
                        class="px-6 py-4 bg-slate-100 text-slate-400 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition-all">Cancel</button>
                    <button @click="submitPayrollEntry"
                        class="px-6 py-4 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-100 hover:scale-[1.02] transition-all">Yes,
                        Suggest</button>
                </div>
            </div>
        </div>

        <div v-if="isConfirmHolidayOpen" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md"></div>
            <div
                class="relative bg-white w-full max-w-sm rounded-[2.5rem] shadow-2xl p-10 text-center animate-in fade-in zoom-in duration-200">
                <div class="mx-auto flex items-center justify-center size-16 bg-rose-50 text-rose-600 rounded-2xl mb-6">
                    <CalendarDays class="size-8" />
                </div>
                <h3 class="text-lg font-black text-slate-900 uppercase italic mb-2">Update Calendar?</h3>
                <p class="text-xs text-slate-500 font-bold mb-8 leading-relaxed">
                    This will register <span class="text-rose-600 font-black">{{ holidayForm.holiday_name }}</span> into
                    the official payroll holiday schedule.
                </p>
                <div class="grid grid-cols-2 gap-4">
                    <button @click="isConfirmHolidayOpen = false"
                        class="px-6 py-4 bg-slate-100 text-slate-400 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition-all">Back</button>
                    <button @click="submitHolidayConfig"
                        class="px-6 py-4 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl hover:scale-[1.02] transition-all">Confirm
                        Date</button>
                </div>
            </div>
        </div>

        <div v-if="isStatutoryModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="isStatutoryModalOpen = false"></div>
            <div
                class="relative bg-white w-full max-w-xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
                <div class="p-8 border-b border-slate-50 flex justify-between items-center">
                    <h2 class="text-xl font-black text-slate-900 uppercase italic">Statutory <span
                            class="text-blue-600 font-light">Matrix</span></h2>
                    <button @click="isStatutoryModalOpen = false"
                        class="p-2 hover:bg-slate-50 rounded-xl transition-colors">
                        <X class="size-5 text-slate-400" />
                    </button>
                </div>
                <div class="p-8 space-y-6">
                    <div class="p-4 bg-blue-50/50 rounded-2xl border border-blue-100/50">
                        <p
                            class="text-[9px] font-black text-blue-600 uppercase tracking-widest mb-1 flex items-center gap-2">
                            <ShieldCheck class="size-3" /> PH Labor Standard 2026
                        </p>
                        <p class="text-[10px] text-slate-500 font-bold italic leading-tight">Automatic deduction scaling
                            based on Monthly Salary Credit (MSC) and income brackets.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                            <label class="text-[8px] font-black text-blue-600 uppercase tracking-widest block mb-2">SSS
                                Contribution</label>
                            <p class="text-xs font-black text-slate-800">Auto-calculated (4.5% EE share)</p>
                        </div>
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                            <label
                                class="text-[8px] font-black text-emerald-600 uppercase tracking-widest block mb-2">PhilHealth
                                Rate</label>
                            <p class="text-xs font-black text-slate-800">Standard 5% (2.5% EE share)</p>
                        </div>
                    </div>
                    <button @click="isStatutoryModalOpen = false"
                        class="w-full bg-blue-600 text-white py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-100 hover:scale-[1.02] active:scale-95 transition-all">Update
                        Global Statutory Matrix</button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style scoped>
.font-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

::-webkit-scrollbar {
    height: 6px;
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f5f9;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

button:active {
    transform: scale(0.98);
}
</style>