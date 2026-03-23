<script setup>
import { Head, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, onMounted, onUnmounted, computed } from 'vue'
import {
    Clock,
    Users,
    ChevronLeft,
    ChevronRight,
    Sunrise,
    Sunset,
    Moon,
    X,
    Calendar as CalendarIcon,
    CheckCircle2,
    Plus,
    ArrowRight,
    Wand2,
    CalendarCheck,
    ListChecks,
    AlertCircle,
    UserX,
    Trash2,
    Sparkles,
    Edit3,
    Lock,
    ShieldCheck,
    ThumbsUp,
    ThumbsDown
} from 'lucide-vue-next'

const props = defineProps({
    attendance_status: Object,
    employee_attendance: Array,
    monthly_shifts: Array,
    holidays: Array,
    pending_shifts: Array,     // Aligned with AttendanceController
    pending_holidays: Array,   // Aligned with AttendanceController
    is_manager: Boolean,       // Aligned with AttendanceController
    current_month: Number,     // Server-provided month for viewDate sync
    current_year: Number       // Server-provided year for viewDate sync
})

// --- UI STATE TOGGLE ---
const activeView = ref('scheduler')

// --- LIVE CLOCK ---
const currentTime = ref(new Date().toLocaleTimeString())
let timerInterval
onMounted(() => {
    timerInterval = setInterval(() => {
        currentTime.value = new Date().toLocaleTimeString()
    }, 1000)
})
onUnmounted(() => clearInterval(timerInterval))

// --- CALENDAR & MODAL LOGIC ---
const viewDate = ref(
    props.current_month && props.current_year
        ? new Date(props.current_year, props.current_month - 1, 1)
        : new Date()
)
const selectedDate = ref(null)
const isModalOpen = ref(false)
const isAutoScheduleModalOpen = ref(false)
const isHolidayModalOpen = ref(false)
const selectedDept = ref('HRM')
const autoScheduleDept = ref('HRM')
const activeSelectorShift = ref(null)
const activeMonthlySelectorShift = ref(null)
const showSuccessToast = ref(false)
const toastMessage = ref('Staff Assigned to Shift')
const toastIsError = ref(false)
const departments = ['HRM', 'SCM', 'FIN', 'MAN', 'INV', 'ORD', 'WAR', 'CRM', 'ECO']

// --- DATE RULES ---
const todayStr = computed(() => {
    const d = new Date()
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
})

const isPastDate = (date) => {
    if (!date) return false
    return date < todayStr.value
}

const isEntireMonthPast = computed(() => {
    const y = viewDate.value.getFullYear()
    const m = viewDate.value.getMonth() + 1
    const lastDay = new Date(y, m, 0).getDate()
    const lastDateStr = `${y}-${String(m).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`
    return lastDateStr < todayStr.value
})

const calendarDays = computed(() => {
    const year = viewDate.value.getFullYear()
    const month = viewDate.value.getMonth()
    const firstDayOfMonth = new Date(year, month, 1).getDay()
    const daysInMonth = new Date(year, month + 1, 0).getDate()
    const days = []
    for (let i = 0; i < firstDayOfMonth; i++) { days.push({ day: null, date: null }) }
    for (let d = 1; d <= daysInMonth; d++) {
        const fullDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`
        days.push({ day: d, date: fullDate })
    }
    return days
})

const monthName = computed(() => viewDate.value.toLocaleString('default', { month: 'long', year: 'numeric' }))

// --- Holiday helpers ---
const holidaysByDate = computed(() => {
    const map = {}
    props.holidays.forEach(h => {
        map[h.date] = h
    })
    return map
})

const isNonWorkingDay = (date) => {
    const h = holidaysByDate.value[date]
    if (!h) return false
    return h.type === 'regular' || h.type === 'special_non_working'
}

const getHolidayInfo = (date) => {
    return holidaysByDate.value[date] || null
}

// Navigation
const fetchMonthData = () => {
    router.get(route('hrm.employee.attendance'), {
        month: viewDate.value.getMonth() + 1,
        year: viewDate.value.getFullYear()
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['monthly_shifts', 'holidays', 'pending_shifts', 'pending_holidays']
    })
}

const nextMonth = () => {
    viewDate.value = new Date(viewDate.value.getFullYear(), viewDate.value.getMonth() + 1, 1)
    fetchMonthData()
}

const prevMonth = () => {
    viewDate.value = new Date(viewDate.value.getFullYear(), viewDate.value.getMonth() - 1, 1)
    fetchMonthData()
}

// --- SHIFT RANGE HELPER ---
const getShiftRange = (type) => {
    switch (type) {
        case 'Morning': return '08:00 AM - 05:00 PM';
        case 'Afternoon': return '04:00 PM - 01:00 AM';
        case 'Graveyard': return '12:00 AM - 09:00 AM';
        default: return '08:00 AM - 05:00 PM';
    }
}

// --- COMPUTED: shifts grouped by date for calendar display ---
const shiftsByDate = computed(() => {
    const map = {}
    // Approved shifts
    props.monthly_shifts.forEach(shift => {
        if (!map[shift.effective_date]) { map[shift.effective_date] = new Set() }
        map[shift.effective_date].add(shift.shift_type)
    })
    // Pending shifts (mark them as pending for visual differentiation)
    props.pending_shifts.forEach(shift => {
        if (!map[shift.effective_date]) { map[shift.effective_date] = new Set() }
        map[shift.effective_date].add(shift.shift_type + '_PENDING')
    })
    const result = {}
    Object.keys(map).forEach(date => {
        result[date] = Array.from(map[date])
    })
    return result
})

// --- FORMS ---
const form = useForm({
    user_id: null,
    shift_type: '',
    effective_date: '',
    dept_code: '',
    schedule_range: '',
    is_bulk: false,
    month: null,
    year: null
})

const holidayForm = useForm({
    holiday_date: '',
    holiday_name: '',
    holiday_type: 'regular',
    premium_rate: null
})

// --- MODAL ACTIONS ---
const openDayModal = (date) => {
    if (!date) return
    if (isPastDate(date)) {
        triggerToast('Cannot schedule past dates.', true)
        return
    }
    selectedDate.value = date
    isModalOpen.value = true
    activeSelectorShift.value = null
}

const openHolidayModal = (date = null) => {
    if (date) {
        const existing = getHolidayInfo(date)
        if (existing) {
            holidayForm.holiday_date = existing.date
            holidayForm.holiday_name = existing.name
            holidayForm.holiday_type = existing.type
            holidayForm.premium_rate = existing.premium_rate
        } else {
            holidayForm.reset()
            holidayForm.holiday_date = date
        }
    } else {
        holidayForm.reset()
    }
    isHolidayModalOpen.value = true
}

const closeModal = () => {
    isModalOpen.value = false
    isAutoScheduleModalOpen.value = false
    isHolidayModalOpen.value = false
    activeSelectorShift.value = null
    activeMonthlySelectorShift.value = null
    holidayForm.reset()
}

const triggerToast = (msg, isError = false) => {
    toastMessage.value = msg
    toastIsError.value = isError
    showSuccessToast.value = true
    setTimeout(() => showSuccessToast.value = false, 4000)
}

// --- CONTROLLER ALIGNMENT METHODS ---

const updateShift = (employeeId, type) => {
    if (isPastDate(selectedDate.value)) {
        triggerToast('Cannot assign shifts on past dates.', true)
        return
    }
    if (isNonWorkingDay(selectedDate.value)) {
        triggerToast('Cannot assign shift on a non-working holiday', true)
        return
    }
    form.is_bulk = false
    form.user_id = employeeId
    form.shift_type = type
    form.effective_date = selectedDate.value
    form.dept_code = selectedDept.value
    form.schedule_range = getShiftRange(type)

    form.post(route('hrm.employee.attendance.update-shift'), {
        preserveScroll: true,
        onSuccess: () => {
            triggerToast(props.is_manager ? 'Staff Assigned to Shift' : 'Shift Request Sent for Approval')
            activeSelectorShift.value = null
        }
    })
}

const setEmployeeMonthlySchedule = (employeeId, shiftType) => {
    if (isEntireMonthPast.value) {
        triggerToast('Cannot schedule for a past month.', true)
        return
    }
    form.is_bulk = true
    form.user_id = employeeId
    form.shift_type = shiftType
    form.dept_code = autoScheduleDept.value
    form.month = viewDate.value.getMonth() + 1
    form.year = viewDate.value.getFullYear()
    form.schedule_range = getShiftRange(shiftType)

    form.post(route('hrm.employee.attendance.update-shift'), {
        preserveScroll: true,
        onSuccess: () => {
            triggerToast(props.is_manager ? 'Employee scheduled for the whole month' : 'Monthly request sent for approval')
            activeMonthlySelectorShift.value = null
            fetchMonthData()
        }
    })
}

const handleApproval = (id, type, action) => {
    const routeName = action === 'approve'
        ? `hrm.employee.attendance.approve-${type}`
        : `hrm.employee.attendance.reject-${type}`;

    router.post(route(routeName), { id }, {
        preserveScroll: true,
        onSuccess: () => triggerToast(`${type.charAt(0).toUpperCase() + type.slice(1)} ${action}d`)
    })
}

const removeShift = (employeeId, date) => {
    if (confirm('Remove this shift assignment?')) {
        router.delete(route('hrm.employee.attendance.destroy-shift'), {
            data: { user_id: employeeId, effective_date: date },
            preserveScroll: true,
            onSuccess: () => triggerToast('Shift successfully removed')
        })
    }
}

const submitHoliday = () => {
    const existing = getHolidayInfo(holidayForm.holiday_date)
    if (existing) {
        holidayForm.put(route('hrm.employee.holidays.update', existing.id), {
            onSuccess: () => { triggerToast('Holiday updated'); closeModal(); fetchMonthData(); }
        })
    } else {
        holidayForm.post(route('hrm.employee.holidays.store'), {
            onSuccess: () => { triggerToast('Holiday saved'); closeModal(); fetchMonthData(); }
        })
    }
}

const deleteHoliday = (date) => {
    const holiday = getHolidayInfo(date)
    if (holiday && confirm(`Delete holiday "${holiday.name}"?`)) {
        router.delete(route('hrm.employee.holidays.destroy', holiday.id), {
            onSuccess: () => { triggerToast('Holiday removed'); closeModal(); fetchMonthData(); }
        })
    }
}

// --- COMPUTED HELPERS ---
const departmentStaff = computed(() => {
    return props.employee_attendance.filter(emp => {
        const empDept = emp.dept || emp.role || ''
        return empDept.toString().toUpperCase() === selectedDept.value.toUpperCase()
    })
})

const monthlyDeptStaff = computed(() => {
    return props.employee_attendance.filter(emp => {
        const empDept = emp.dept || emp.role || ''
        return empDept.toString().toUpperCase() === autoScheduleDept.value.toUpperCase()
    })
})

const getEmployeesInShift = (type) => {
    return props.monthly_shifts
        .filter(s => s.effective_date === selectedDate.value && s.shift_type === type)
        .map(s => props.employee_attendance.find(emp => emp.id === s.user_id))
        .filter(emp => emp && (emp.dept || emp.role || '').toString().toUpperCase() === selectedDept.value.toUpperCase())
}

const getEmployeesInMonthlyShift = (type) => {
    const m = viewDate.value.getMonth() + 1
    const y = viewDate.value.getFullYear()
    const assignedIds = props.monthly_shifts
        .filter(s => {
            const d = new Date(s.effective_date)
            return (d.getMonth() + 1) === m && d.getFullYear() === y && s.shift_type === type
        })
        .map(s => s.user_id)
    const uniqueIds = [...new Set(assignedIds)]
    return uniqueIds.map(id => props.employee_attendance.find(emp => emp.id === id))
        .filter(emp => emp && (emp.dept || emp.role || '').toString().toUpperCase() === autoScheduleDept.value.toUpperCase())
}

const shiftIcon = (type) => {
    switch (type.replace('_PENDING', '')) {
        case 'Morning': return Sunrise
        case 'Afternoon': return Sunset
        case 'Graveyard': return Moon
        default: return Clock
    }
}
</script>

<template>

    <Head title="Attendance & Scheduling" />

    <AuthenticatedLayout>
        <Transition enter-active-class="transform transition duration-300 ease-out"
            enter-from-class="translate-y-[-100%] opacity-0" enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="showSuccessToast" :class="toastIsError ? 'bg-rose-500' : 'bg-emerald-500'"
                class="fixed top-6 right-6 z-[100] text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-3">
                <CheckCircle2 v-if="!toastIsError" class="h-5 w-5" />
                <AlertCircle v-else class="h-5 w-5" />
                <span class="font-bold text-sm">{{ toastMessage }}</span>
            </div>
        </Transition>

        <div class="flex flex-col lg:flex-row justify-between items-center gap-6 mb-8">
            <div class="flex items-center gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Shift <span
                            class="text-blue-600">Scheduler</span></h1>
                    <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Manage departmental shift
                        pipelines and attendance.</p>
                </div>
                <div
                    class="flex items-center bg-slate-100 dark:bg-slate-800 p-1 rounded-2xl border border-slate-200 dark:border-slate-700">
                    <button
                        @click="isEntireMonthPast ? triggerToast('Cannot schedule for a past month.', true) : (isAutoScheduleModalOpen = true)"
                        :class="isEntireMonthPast ? 'opacity-50 cursor-not-allowed bg-slate-400' : 'bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-500/20'"
                        class="flex items-center gap-2 px-4 py-2.5 text-white rounded-xl font-black text-[10px] uppercase tracking-widest transition-all active:scale-95">
                        <Wand2 class="h-3.5 w-3.5" /> Set Monthly
                    </button>
                    <button @click="activeView = 'scheduler'"
                        :class="activeView === 'scheduler' ? 'bg-white dark:bg-slate-700 text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all active:scale-95 ml-1">
                        Calendar
                    </button>
                    <button @click="activeView = 'attendance'"
                        :class="activeView === 'attendance' ? 'bg-white dark:bg-slate-700 text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all active:scale-95">
                        Attendance
                    </button>
                    <button v-if="is_manager" @click="activeView = 'approvals'"
                        :class="activeView === 'approvals' ? 'bg-white dark:bg-slate-700 text-rose-600 shadow-sm' : 'text-slate-500'"
                        class="relative flex items-center gap-2 px-4 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all active:scale-95">
                        <ShieldCheck class="h-3.5 w-3.5" /> Approvals
                        <span v-if="pending_shifts.length > 0 || pending_holidays.length > 0"
                            class="absolute -top-1 -right-1 h-3 w-3 bg-rose-500 rounded-full border-2 border-white"></span>
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div
                    class="flex items-center bg-white dark:bg-slate-800 rounded-2xl p-1 shadow-sm border border-slate-100 dark:border-slate-700">
                    <button @click="prevMonth"
                        class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl text-slate-500 transition-colors">
                        <ChevronLeft class="h-5 w-5" />
                    </button>
                    <span
                        class="px-6 font-black text-[11px] uppercase tracking-widest text-slate-700 dark:text-white">{{
                            monthName }}</span>
                    <button @click="nextMonth"
                        class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl text-slate-500 transition-colors">
                        <ChevronRight class="h-5 w-5" />
                    </button>
                </div>
                <div
                    class="bg-white dark:bg-slate-800 px-6 py-3 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-xl shadow-blue-500/5 flex items-center gap-4">
                    <Clock class="h-5 w-5 text-blue-600" />
                    <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tighter">{{ currentTime }}
                    </h2>
                </div>
            </div>
        </div>

        <div
            class="pb-3 p-2 bg-blue-200 rounded-xl mb-5 text-blue-900 text-center text-sm font-bold uppercase tracking-widest">
            <h2>Montitextile Shift Schedule - Graveyard: 12AM to 9AM | Morning: 8AM to 5PM | Afternoon: 4PM to 1AM</h2>
        </div>

        <Transition mode="out-in" enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4">

            <div v-if="activeView === 'scheduler'" key="scheduler">
                <div
                    class="bg-white dark:bg-slate-950 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden p-6">
                    <div class="grid grid-cols-7 gap-3">
                        <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day"
                            class="text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] pb-6">{{
                                day }}</div>

                        <div v-for="(item, index) in calendarDays" :key="index" @click="openDayModal(item.date)" :class="[
                            item.date ? (isPastDate(item.date) ? 'cursor-not-allowed' : 'cursor-pointer') : '',
                            item.date === todayStr ? 'ring-2 ring-blue-500 shadow-lg shadow-blue-500/20' : '',
                            isPastDate(item.date) ? 'opacity-50' : ''
                        ]" class="min-h-[140px] p-4 rounded-[2rem] border border-slate-100 dark:border-slate-800 transition-all group relative overflow-hidden"
                            :style="item.date && isNonWorkingDay(item.date) ? 'background-color: #fee2e2; border-color: #f87171;' : (item.date && isPastDate(item.date) ? 'background-color: #f8fafc;' : '')">

                            <span v-if="item.day" class="text-lg font-black transition-colors"
                                :class="isPastDate(item.date) ? 'text-slate-300 dark:text-slate-700' : 'text-slate-300 dark:text-slate-600 group-hover:text-blue-600'">
                                {{ item.day }}
                            </span>
                            <Lock v-if="item.date && isPastDate(item.date)"
                                class="absolute top-2 right-2 h-3 w-3 text-slate-300 dark:text-slate-600" />

                            <div v-if="item.date && getHolidayInfo(item.date)"
                                class="mt-1 text-[8px] font-black uppercase truncate" :class="{
                                    'text-red-600': getHolidayInfo(item.date).type.includes('regular'),
                                    'text-amber-600': getHolidayInfo(item.date).type === 'special_non_working',
                                    'text-green-600': getHolidayInfo(item.date).type === 'special_working'
                                }">
                                {{ getHolidayInfo(item.date).name }}
                            </div>

                            <div v-if="item.date && shiftsByDate[item.date]" class="mt-2 flex flex-wrap gap-2">
                                <div v-for="shift in shiftsByDate[item.date]" :key="shift"
                                    class="flex items-center gap-1 px-2 py-1 rounded-full text-[8px] font-black uppercase tracking-wider transition-all"
                                    :class="[
                                        shift.includes('PENDING') ? 'border border-dashed border-amber-400 opacity-60' : '',
                                        shift.includes('Morning') ? 'bg-amber-100 text-amber-700' :
                                            shift.includes('Afternoon') ? 'bg-blue-100 text-blue-700' : 'bg-indigo-100 text-indigo-700'
                                    ]">
                                    <component :is="shiftIcon(shift)" class="h-3 w-3" />
                                    <span class="hidden sm:inline">{{ shift.replace('_PENDING', '') }}</span>
                                </div>
                            </div>

                            <button v-if="item.date && is_manager" @click.stop="openHolidayModal(item.date)"
                                class="absolute p-1 bg-white dark:bg-slate-800 rounded-full shadow opacity-0 group-hover:opacity-100 transition"
                                :class="isPastDate(item.date) ? 'top-5 right-1' : 'top-1 right-1'">
                                <Sparkles class="h-3 w-3 text-purple-500" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="activeView === 'attendance'" key="attendance" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-sm flex items-center gap-6">
                        <div
                            class="h-14 w-14 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 rounded-2xl flex items-center justify-center">
                            <CheckCircle2 class="h-7 w-7" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">On-Time Today</p>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{
                                employee_attendance.filter(e => e.status === 'On-Time').length}} Staff</h3>
                        </div>
                    </div>
                    <div
                        class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-sm flex items-center gap-6">
                        <div
                            class="h-14 w-14 bg-amber-100 dark:bg-amber-900/30 text-amber-600 rounded-2xl flex items-center justify-center">
                            <AlertCircle class="h-7 w-7" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Late Arrivals</p>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{
                                employee_attendance.filter(e => e.status === 'Late').length}} Staff</h3>
                        </div>
                    </div>
                    <div
                        class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-sm flex items-center gap-6">
                        <div
                            class="h-14 w-14 bg-rose-100 dark:bg-rose-900/30 text-rose-600 rounded-2xl flex items-center justify-center">
                            <UserX class="h-7 w-7" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Currently Absent
                            </p>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{
                                employee_attendance.filter(e => e.status === 'Absent').length}} Staff</h3>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden">
                    <div class="p-8 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                        <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">
                            Real-Time Attendance Log</h2>
                        <div class="flex gap-2">
                            <button v-for="dept in departments.slice(0, 9)" :key="dept" @click="selectedDept = dept"
                                :class="selectedDept === dept ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 dark:bg-slate-800 text-slate-500'"
                                class="px-4 py-2 rounded-xl text-[10px] font-black transition-all uppercase tracking-tighter">{{
                                    dept }}</button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-slate-50 dark:border-slate-800">
                                    <th
                                        class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        Employee</th>
                                    <th
                                        class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        Department</th>
                                    <th
                                        class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        Current Shift</th>
                                    <th
                                        class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        Clock In</th>
                                    <th
                                        class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                                <tr v-for="emp in departmentStaff" :key="emp.id"
                                    class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                                    <td class="px-8 py-5">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="h-9 w-9 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-[11px] font-bold text-blue-600">
                                                {{ emp.name.charAt(0) }}</div>
                                            <span
                                                class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-tight">{{
                                                    emp.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5"><span
                                            class="px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg text-[9px] font-black text-slate-500 uppercase tracking-tighter">{{
                                                emp.dept }}</span></td>
                                    <td class="px-8 py-5">
                                        <div class="flex flex-col"><span
                                                class="text-xs font-bold text-slate-700 dark:text-slate-200">{{
                                                    emp.shift }}</span><span
                                                class="text-[9px] font-bold text-slate-400 uppercase">{{
                                                    getShiftRange(emp.shift) }}</span></div>
                                    </td>
                                    <td class="px-8 py-5"><span class="text-xs font-mono font-bold"
                                            :class="emp.clock_in !== '---' ? 'text-blue-600' : 'text-slate-300'">{{
                                                emp.clock_in }}</span></td>
                                    <td class="px-8 py-5">
                                        <div class="flex items-center gap-2">
                                            <div class="h-1.5 w-1.5 rounded-full"
                                                :class="{ 'bg-emerald-500': emp.status === 'On-Time', 'bg-amber-500': emp.status === 'Late', 'bg-slate-300': emp.status === 'Absent' }">
                                            </div>
                                            <span class="text-[10px] font-black uppercase tracking-widest"
                                                :class="{ 'text-emerald-600': emp.status === 'On-Time', 'text-amber-600': emp.status === 'Late', 'text-slate-400': emp.status === 'Absent' }">{{
                                                    emp.status }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div v-else-if="activeView === 'approvals' && is_manager" key="approvals" class="space-y-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div
                        class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-sm p-10">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="p-3 bg-rose-100 text-rose-600 rounded-2xl">
                                <ShieldCheck class="h-6 w-6" />
                            </div>
                            <h2 class="text-lg font-black uppercase tracking-widest">Pending Shifts</h2>
                        </div>
                        <div class="space-y-4 max-h-[600px] overflow-y-auto custom-scroll pr-4">
                            <div v-if="pending_shifts.length === 0"
                                class="text-center py-20 opacity-30 font-black uppercase tracking-widest">No pending
                                shifts</div>
                            <div v-for="ps in pending_shifts" :key="ps.id"
                                class="p-6 border rounded-3xl bg-slate-50 dark:bg-slate-950 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-black text-blue-600 uppercase">{{ ps.shift_type }} Shift
                                    </p>
                                    <p class="font-black text-sm uppercase tracking-tighter">{{ ps.user?.name || 'Staff'
                                    }}</p>
                                    <p class="text-[10px] text-slate-400 font-bold">{{ ps.effective_date }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="handleApproval(ps.id, 'shift', 'approve')"
                                        class="p-3 bg-emerald-500 text-white rounded-xl hover:scale-105 transition">
                                        <ThumbsUp class="h-4 w-4" />
                                    </button>
                                    <button @click="handleApproval(ps.id, 'shift', 'reject')"
                                        class="p-3 bg-rose-500 text-white rounded-xl hover:scale-105 transition">
                                        <ThumbsDown class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-sm p-10">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="p-3 bg-purple-100 text-purple-600 rounded-2xl">
                                <Sparkles class="h-6 w-6" />
                            </div>
                            <h2 class="text-lg font-black uppercase tracking-widest">Pending Holidays</h2>
                        </div>
                        <div class="space-y-4">
                            <div v-if="pending_holidays.length === 0"
                                class="text-center py-20 opacity-30 font-black uppercase tracking-widest">No pending
                                holidays</div>
                            <div v-for="ph in pending_holidays" :key="ph.id"
                                class="p-6 border rounded-3xl bg-slate-50 dark:bg-slate-950 flex items-center justify-between">
                                <div>
                                    <p class="font-black text-sm uppercase tracking-tighter">{{ ph.holiday_name }}</p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase">{{ ph.holiday_date }} • {{
                                        ph.holiday_type.replace('_', ' ') }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="handleApproval(ph.id, 'holiday', 'approve')"
                                        class="p-3 bg-emerald-500 text-white rounded-xl hover:scale-105 transition">
                                        <ThumbsUp class="h-4 w-4" />
                                    </button>
                                    <button @click="handleApproval(ph.id, 'holiday', 'reject')"
                                        class="p-3 bg-rose-500 text-white rounded-xl hover:scale-105 transition">
                                        <ThumbsDown class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition enter-active-class="duration-300 ease-out" enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100" leave-active-class="duration-200 ease-in"
            leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="isHolidayModalOpen" class="fixed inset-0 z-[80] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/90 backdrop-blur-md" @click="closeModal"></div>
                <div
                    class="relative bg-white dark:bg-slate-900 w-full max-w-md rounded-[3rem] shadow-2xl overflow-hidden border border-white/10">
                    <div class="p-8 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                        <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                            {{ getHolidayInfo(holidayForm.holiday_date) ? 'Edit' : 'Add' }} Holiday</h2>
                        <button @click="closeModal" class="p-2 hover:bg-rose-50 rounded-full">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                    <form @submit.prevent="submitHoliday" class="p-8 space-y-6">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Date</label>
                            <input type="date" v-model="holidayForm.holiday_date" required
                                class="w-full mt-2 px-5 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-blue-600" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Holiday
                                Name</label>
                            <input type="text" v-model="holidayForm.holiday_name" required
                                placeholder="e.g., Christmas Day"
                                class="w-full mt-2 px-5 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800 border-none ring-1 ring-slate-200 focus:ring-2 focus:ring-blue-600 uppercase font-black" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Type</label>
                            <select v-model="holidayForm.holiday_type" required
                                class="w-full mt-2 px-5 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800 border-none ring-1 ring-slate-200 focus:ring-2 focus:ring-blue-600 font-bold">
                                <option value="regular">Regular Holiday (Non-working)</option>
                                <option value="special_non_working">Special Non-Working</option>
                                <option value="special_working">Special Working</option>
                            </select>
                        </div>
                        <div v-if="getHolidayInfo(holidayForm.holiday_date)" class="flex gap-3">
                            <button type="button" @click="deleteHoliday(holidayForm.holiday_date)"
                                class="flex-1 py-4 bg-rose-500 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-rose-600 transition">Delete</button>
                            <button type="submit" :disabled="holidayForm.processing"
                                class="flex-1 py-4 bg-blue-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-blue-700 transition shadow-lg disabled:opacity-50">Save</button>
                        </div>
                        <button v-else type="submit" :disabled="holidayForm.processing"
                            class="w-full py-4 bg-blue-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-blue-700 transition shadow-lg disabled:opacity-50">Save
                            Holiday</button>
                    </form>
                </div>
            </div>
        </Transition>

        <Transition enter-active-class="duration-300 ease-out" enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100" leave-active-class="duration-200 ease-in"
            leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="isAutoScheduleModalOpen" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/90 backdrop-blur-md" @click="closeModal"></div>
                <div
                    class="relative bg-white dark:bg-slate-900 w-full max-w-7xl rounded-[3.5rem] shadow-2xl overflow-hidden border border-white/10">
                    <div
                        class="p-10 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="p-2 bg-blue-600 rounded-xl text-white shadow-lg shadow-blue-500/30">
                                        <CalendarCheck class="h-5 w-5" />
                                    </div>
                                    <h2
                                        class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                        Monthly Pipeline: {{ monthName }}</h2>
                                </div>
                                <p class="text-xs text-slate-500 font-bold uppercase tracking-widest">Whole-Month
                                    Scheduler (Non-working holidays skipped automatically)</p>
                            </div>
                            <button @click="closeModal" class="p-3 hover:bg-rose-50 rounded-2xl transition-colors">
                                <X class="h-6 w-6" />
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-2 mt-8">
                            <button v-for="dept in departments" :key="dept" @click="autoScheduleDept = dept"
                                :class="autoScheduleDept === dept ? 'bg-blue-600 text-white shadow-xl shadow-blue-500/40' : 'bg-white dark:bg-slate-800 text-slate-500'"
                                class="px-6 py-2.5 rounded-xl text-[10px] font-black border border-slate-100 dark:border-slate-700 uppercase tracking-widest transition-all">{{
                                    dept }}</button>
                        </div>
                    </div>

                    <div
                        class="p-10 grid grid-cols-1 lg:grid-cols-3 gap-8 max-h-[55vh] overflow-y-auto custom-scroll relative">
                        <div v-for="shiftType in ['Graveyard', 'Morning', 'Afternoon']" :key="shiftType"
                            class="flex flex-col gap-4 relative">
                            <div :class="{ 'bg-amber-50 border-amber-100 dark:bg-amber-900/10': shiftType === 'Morning', 'bg-blue-50 border-blue-100 dark:bg-blue-900/10': shiftType === 'Afternoon', 'bg-indigo-50 border-indigo-100 dark:bg-indigo-900/10': shiftType === 'Graveyard' }"
                                class="flex items-center justify-between p-5 rounded-[2rem] border mb-4">
                                <div class="flex items-center gap-3">
                                    <div :class="{ 'bg-amber-500': shiftType === 'Morning', 'bg-blue-500': shiftType === 'Afternoon', 'bg-indigo-600': shiftType === 'Graveyard' }"
                                        class="p-2 rounded-xl text-white">
                                        <component
                                            :is="shiftType === 'Morning' ? Sunrise : (shiftType === 'Afternoon' ? Sunset : Moon)"
                                            class="h-4 w-4" />
                                    </div>
                                    <div class="flex flex-col"><span
                                            class="font-black text-xs uppercase tracking-widest">{{ shiftType
                                            }}</span><span
                                            class="text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase">{{
                                                getShiftRange(shiftType) }}</span></div>
                                </div>
                                <button
                                    @click="activeMonthlySelectorShift = activeMonthlySelectorShift === shiftType ? null : shiftType"
                                    class="p-2 bg-white dark:bg-slate-800 rounded-xl shadow-sm border hover:scale-110 transition-transform">
                                    <Plus class="h-4 w-4 text-blue-600" />
                                </button>
                            </div>

                            <div v-if="getEmployeesInMonthlyShift(shiftType).length > 0" class="space-y-3">
                                <div v-for="emp in getEmployeesInMonthlyShift(shiftType)" :key="emp.id"
                                    class="p-5 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 flex items-center gap-3 shadow-sm">
                                    <div
                                        class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-[10px] font-bold text-blue-600">
                                        {{ emp.name.charAt(0) }}</div>
                                    <div class="flex flex-col flex-1"><span
                                            class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-tight">{{
                                                emp.name }}</span></div>
                                    <button
                                        @click="removeShift(emp.id, selectedDate || new Date().toISOString().split('T')[0])"
                                        class="p-1 text-slate-400 hover:text-rose-600 transition">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                            <div v-else
                                class="flex flex-col items-center justify-center py-10 border-2 border-dashed border-slate-100 rounded-[2rem] opacity-40">
                                <Users class="h-8 w-8 text-slate-300 mb-2" />
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Empty
                                    Pipeline</p>
                            </div>

                            <Transition enter-active-class="duration-200 ease-out"
                                enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0">
                                <div v-if="activeMonthlySelectorShift === shiftType"
                                    class="absolute top-20 left-0 right-0 z-[70] bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-3xl shadow-2xl p-4 max-h-[300px] overflow-y-auto custom-scroll">
                                    <div v-if="monthlyDeptStaff.length > 0" class="space-y-1">
                                        <button v-for="emp in monthlyDeptStaff" :key="emp.id"
                                            @click="setEmployeeMonthlySchedule(emp.id, shiftType)"
                                            class="w-full flex items-center gap-3 p-3 hover:bg-slate-50 dark:hover:bg-slate-900 rounded-xl transition-colors group">
                                            <div
                                                class="h-7 w-7 rounded-lg bg-slate-100 dark:bg-slate-700 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center text-[9px] font-black">
                                                {{ emp.name.charAt(0) }}</div>
                                            <span
                                                class="text-xs font-bold text-slate-600 dark:text-slate-300 group-hover:text-blue-600 uppercase">{{
                                                    emp.name }}</span>
                                        </button>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                    <div
                        class="p-10 bg-slate-50 dark:bg-slate-950 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                        <div class="flex items-center gap-2 text-slate-400">
                            <CheckCircle2 class="h-4 w-4" /><span
                                class="text-[10px] font-bold uppercase tracking-widest">Database Sync Active</span>
                        </div>
                        <button @click="closeModal"
                            class="px-10 py-4 bg-slate-900 dark:bg-white dark:text-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-2xl transition-transform active:scale-95 flex items-center gap-2">Finish
                            Scheduling
                            <ArrowRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition enter-active-class="duration-300 ease-out" enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100" leave-active-class="duration-200 ease-in"
            leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="isModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-xl" @click="closeModal"></div>
                <div
                    class="relative bg-white dark:bg-slate-900 w-full max-w-7xl rounded-[3.5rem] shadow-2xl overflow-hidden border border-white/10">
                    <div
                        class="p-10 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="p-2 bg-blue-600 rounded-xl text-white shadow-lg shadow-blue-500/30">
                                        <CalendarIcon class="h-5 w-5" />
                                    </div>
                                    <h2
                                        class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                        Scheduled Shift: {{ selectedDate }}</h2>
                                </div>
                                <div v-if="getHolidayInfo(selectedDate)" class="mt-2 text-sm">
                                    <span class="font-bold uppercase"
                                        :class="{ 'text-red-600': getHolidayInfo(selectedDate).type.includes('regular'), 'text-amber-600': getHolidayInfo(selectedDate).type === 'special_non_working', 'text-green-600': getHolidayInfo(selectedDate).type === 'special_working' }">
                                        🎉 {{ getHolidayInfo(selectedDate).name }} ({{
                                            getHolidayInfo(selectedDate).type.replace('_', ' ') }})
                                    </span>
                                </div>
                            </div>
                            <button @click="closeModal" class="p-3 hover:bg-rose-50 rounded-2xl transition-colors">
                                <X class="h-6 w-6" />
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-2 mt-8">
                            <button v-for="dept in departments" :key="dept" @click="selectedDept = dept"
                                :class="selectedDept === dept ? 'bg-blue-600 text-white shadow-xl shadow-blue-500/40' : 'bg-white dark:bg-slate-800 text-slate-500'"
                                class="px-6 py-2.5 rounded-xl text-[10px] font-black border border-slate-100 dark:border-slate-700 uppercase tracking-widest transition-all">{{
                                    dept }}</button>
                        </div>
                    </div>

                    <div
                        class="p-10 grid grid-cols-1 lg:grid-cols-3 gap-8 max-h-[55vh] overflow-y-auto custom-scroll relative">
                        <div v-for="shiftType in ['Graveyard', 'Morning', 'Afternoon']" :key="shiftType"
                            class="flex flex-col gap-4 relative">
                            <div :class="{ 'bg-amber-50 border-amber-100 dark:bg-amber-900/10': shiftType === 'Morning', 'bg-blue-50 border-blue-100 dark:bg-blue-900/10': shiftType === 'Afternoon', 'bg-indigo-50 border-indigo-100 dark:bg-indigo-900/10': shiftType === 'Graveyard' }"
                                class="flex items-center justify-between p-5 rounded-[2rem] border mb-4">
                                <div class="flex items-center gap-3">
                                    <div :class="{ 'bg-amber-500': shiftType === 'Morning', 'bg-blue-500': shiftType === 'Afternoon', 'bg-indigo-600': shiftType === 'Graveyard' }"
                                        class="p-2 rounded-xl text-white">
                                        <component
                                            :is="shiftType === 'Morning' ? Sunrise : (shiftType === 'Afternoon' ? Sunset : Moon)"
                                            class="h-4 w-4" />
                                    </div>
                                    <div class="flex flex-col"><span
                                            class="font-black text-xs uppercase tracking-widest">{{ shiftType
                                            }}</span><span
                                            class="text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase">{{
                                                getShiftRange(shiftType) }}</span></div>
                                </div>
                                <button :disabled="isNonWorkingDay(selectedDate)"
                                    @click="activeSelectorShift = activeSelectorShift === shiftType ? null : shiftType"
                                    class="p-2 bg-white dark:bg-slate-800 rounded-xl shadow-sm border hover:scale-110 transition-transform"
                                    :class="{ 'opacity-50 cursor-not-allowed': isNonWorkingDay(selectedDate) }">
                                    <Plus class="h-4 w-4"
                                        :class="isNonWorkingDay(selectedDate) ? 'text-slate-300' : 'text-blue-600'" />
                                </button>
                            </div>
                            <div v-if="getEmployeesInShift(shiftType).length > 0" class="space-y-3">
                                <div v-for="emp in getEmployeesInShift(shiftType)" :key="emp.id"
                                    class="p-5 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 flex items-center gap-3 shadow-sm hover:border-blue-400 transition-colors">
                                    <div
                                        class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-[10px] font-bold text-blue-600">
                                        {{ emp.name.charAt(0) }}</div>
                                    <div class="flex flex-col flex-1"><span
                                            class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-tight">{{
                                                emp.name }}</span></div>
                                    <button @click="removeShift(emp.id, selectedDate)"
                                        class="p-1 text-slate-400 hover:text-rose-600 transition">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                            <div v-else
                                class="flex flex-col items-center justify-center py-10 border-2 border-dashed border-slate-100 rounded-[2rem] opacity-40">
                                <Users class="h-8 w-8 text-slate-300 mb-2" />
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Empty
                                    Pipeline</p>
                            </div>
                            <Transition enter-active-class="duration-200 ease-out"
                                enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0">
                                <div v-if="activeSelectorShift === shiftType && !isNonWorkingDay(selectedDate)"
                                    class="absolute top-20 left-0 right-0 z-[70] bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-3xl shadow-2xl p-4 max-h-[300px] overflow-y-auto custom-scroll">
                                    <div v-if="departmentStaff.length > 0" class="space-y-1">
                                        <button v-for="emp in departmentStaff" :key="emp.id"
                                            @click="updateShift(emp.id, shiftType)"
                                            class="w-full flex items-center gap-3 p-3 hover:bg-slate-50 dark:hover:bg-slate-900 rounded-xl transition-colors group">
                                            <div
                                                class="h-7 w-7 rounded-lg bg-slate-100 dark:bg-slate-700 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center text-[9px] font-black">
                                                {{ emp.name.charAt(0) }}</div>
                                            <span
                                                class="text-xs font-bold text-slate-600 dark:text-slate-300 group-hover:text-blue-600 uppercase">{{
                                                    emp.name }}</span>
                                        </button>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                    <div
                        class="p-10 bg-slate-50 dark:bg-slate-950 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                        <div class="flex items-center gap-2 text-slate-400">
                            <CheckCircle2 class="h-4 w-4" /><span
                                class="text-[10px] font-bold uppercase tracking-widest">Database Sync Active</span>
                        </div>
                        <button @click="closeModal"
                            class="px-10 py-4 bg-slate-900 dark:bg-white dark:text-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-2xl transition-transform active:scale-95 flex items-center gap-2">Finish
                            Scheduling
                            <ArrowRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scroll::-webkit-scrollbar {
    width: 6px;
}

.custom-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scroll::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

.dark .custom-scroll::-webkit-scrollbar-thumb {
    background: #1e293b;
}
</style>