<script setup>
import { usePage, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js';
import { computed, ref } from 'vue'
import {
    LayoutDashboard,
    BarChart3,
    Package,
    LogOut,
    ChevronRight,
    CreditCard,
    UserPlus,
    Spool,
    ClipboardList,
    ChartNoAxesCombined,
    ShoppingBasket,
    HandCoins,
    FileUser,
    DoorOpen,
    BicepsFlexed,
    Truck,
    Wallet,
    Factory,
    Book,
    Boxes,
    ShoppingCart,
    Warehouse,
    Globe,
    Clock,
    CalendarDays,
    History,
    Users,
    Settings,
    Receipt,
    HelpCircle,
    ShieldCheck,
    Building2,
    RefreshCw,
    ClipboardCheck,
    FileText,
    Send,
    ShoppingBag,
    User,
    TrendingUp,
    XCircle
} from 'lucide-vue-next'

const page = usePage()

// Bulletproof authentication object parsing
const user = computed(() => page.props.auth.user)
const client = computed(() => page.props.auth.client)
const supplier = computed(() => page.props.auth.supplier || (page.props.auth.user?.business_name ? page.props.auth.user : null))
const currentUrl = computed(() => page.url)

// State for the Workforce dropdown (HRM specifically)
const isWorkforceOpen = ref(false)
const toggleWorkforce = () => {
    isWorkforceOpen.value = !isWorkforceOpen.value
}

// State for Logout Confirmation Modal
const showLogoutModal = ref(false)

const isEmployeePortal = computed(() => currentUrl.value.startsWith('/dashboard/employee-ui'))
const isClient = computed(() => !!client.value)
const isSupplier = computed(() => !!supplier.value || currentUrl.value.startsWith('/supplier'))

const navItems = computed(() => {
    // ─────────────────────────────────────────────────────────────────
    // 1. Supplier Navigation (B2B Vendor Hub)
    // ─────────────────────────────────────────────────────────────────
    if (isSupplier.value) {
        return [
            { label: 'Vendor Hub', href: route('supplier.dashboard'), icon: LayoutDashboard },
            { label: 'Purchase Orders', href: route('supplier.orders'), icon: ShoppingCart },
        ]
    }

    // ─────────────────────────────────────────────────────────────────
    // 2. Client Navigation (B2B Customer Portal)
    // ─────────────────────────────────────────────────────────────────
    if (isClient.value) {
        return [
            { label: 'Dashboard', href: route('client.dashboard'), icon: LayoutDashboard },
            { label: 'Products', href: route('client.products'), icon: ShoppingBag },
            { label: 'Orders', href: route('client.orders'), icon: ShoppingCart },
            { label: 'Invoices', href: route('client.invoices'), icon: Receipt },
            { label: 'Profile', href: route('client.profile.edit'), icon: User },
            { label: 'Support', href: route('client.support'), icon: HelpCircle },
        ]
    }

    // ─────────────────────────────────────────────────────────────────
    // 3. Unified Employee Portal Navigation (Self-Service)
    // ─────────────────────────────────────────────────────────────────
    if (isEmployeePortal.value) {
        return [
            { label: 'Employee Dashboard', href: route('employee.ui.dashboard'), icon: Clock },
            { label: 'My Attendance', href: route('employee.ui.clock'), icon: CalendarDays },
            { label: 'Leave Request', href: route('employee.ui.leave'), icon: History },
            { label: 'Payslip', href: route('employee.ui.payslip'), icon: HandCoins },
        ]
    }

    // ─────────────────────────────────────────────────────────────────
    // 4. Standard Enterprise ERP Navigation
    // ─────────────────────────────────────────────────────────────────
    const items = [
        { label: 'Main Dashboard', href: route('dashboard'), icon: LayoutDashboard },
    ]

    const userRole = user.value?.role?.toUpperCase()
    const userPosition = user.value?.position?.toLowerCase()
    const manufacturingRole = user.value?.manufacturing_role

    // --- Trainee Specific Routing ---
    if (userPosition === 'trainee') {
        items.push(
            { label: 'Time In/Out', href: route('trainee.timekeeping'), icon: Clock },
            { label: 'Attendance', href: route('trainee.attendance'), icon: CalendarDays },
            { label: 'Payslips', href: route('trainee.payslip'), icon: HandCoins }
        );
        return items; // Trainees cannot see normal module links
    }

    // --- Human Resources (HRM) ---
    if (userRole === 'HRM') {
        if (userPosition === 'manager') {
            items.push(
                { label: 'Onboarding', href: route('hrm.manager.onboarding'), icon: BarChart3 },
                {
                    label: 'Workforce Management',
                    icon: Users,
                    isDropdown: true,
                    children: [
                        { label: 'Attendance', href: route('hrm.employee.attendance'), icon: FileUser },
                        { label: 'Leave MGMT', href: route('hrm.employee.leave'), icon: DoorOpen },
                    ]
                },
                { label: 'Payroll', href: route('hrm.manager.payroll'), icon: HandCoins },
                { label: 'Analytics', href: route('hrm.manager.analytics'), icon: ChartNoAxesCombined }
            )
        } else if (userPosition === 'staff') {
            items.push(
                { label: 'Recruitment', href: route('hrm.applicants.index'), icon: UserPlus },
                { label: 'Interview', href: route('hrm.employee.interview'), icon: ClipboardList },
                { label: 'Training', href: route('hrm.employee.training'), icon: BicepsFlexed },
                {
                    label: 'Workforce Management',
                    icon: Users,
                    isDropdown: true,
                    children: [
                        { label: 'Attendance', href: route('hrm.employee.attendance'), icon: FileUser },
                        { label: 'Leave MGMT', href: route('hrm.employee.leave'), icon: DoorOpen },
                    ]
                },
                { label: 'Payroll', href: route('hrm.employee.hrmstaffpayroll'), icon: HandCoins }
            )
        }
    }

    // --- Supply Chain Management (SCM) ---
    if (userRole === 'SCM') {
        if (userPosition === 'manager') {
            items.push(
                { label: 'Sales Orders', href: route('scm.manager.sales-orders'), icon: ShoppingCart },
                { label: 'Payment Approval', href: route('scm.manager.payments'), icon: HandCoins },
                { label: 'Vendor Management', href: route('scm.manager.vendor'), icon: ChartNoAxesCombined },
                { label: 'Close', href: route('scm.manager.close'), icon: DoorOpen }
            )
        } else if (userPosition === 'staff') {
            items.push(
                { label: 'Inbound', href: route('scm.employee.inbound'), icon: Truck },
                { label: 'Receiving', href: route('scm.employee.recieving'), icon: Truck },
                { label: 'Inventory', href: route('scm.employee.inventory'), icon: Package },
                { label: 'Verifications', href: route('scm.employee.verification'), icon: HandCoins }
            )
        }
    }

    // --- Financial Operations (FIN) ---
    if (userRole === 'FIN') {
        items.push({ label: 'Finance', href: userPosition === 'manager' ? route('fin.manager.dashboard') : route('fin.employee.dashboard'), icon: Wallet })
    }

    // --- Manufacturing Plant (MAN) ---
    if (userRole === 'MAN') {
        if (userPosition === 'manager') {
            // Manager links
            items.push(
                { label: 'Manufacturing Dashboard', href: route('man.manager.dashboard'), icon: Factory },
                { label: 'Production Orders', href: route('man.manager.production'), icon: ClipboardList },
                { label: 'Rejected Items', href: route('man.manager.rejected'), icon: XCircle }
            );
        } else {
            // Staff: show links based on manufacturing_role
            const roleToRoutes = {
                knitting_yarn: {
                    dashboard: 'man.staff.knitting-yarn.dashboard',
                    work: 'man.staff.knitting-yarn.page',
                    reports: 'man.staff.knitting-yarn.reports'
                },
                dyeing_color: {
                    dashboard: 'man.staff.dyeing-color.dashboard',
                    work: 'man.staff.dyeing-color.page',
                    reports: 'man.staff.dyeing-color.reports'
                },
                dyeing_fabric_softener: {
                    dashboard: 'man.staff.dyeing-fabric-softener.dashboard',
                    work: 'man.staff.dyeing-fabric-softener.page',
                    reports: 'man.staff.dyeing-fabric-softener.reports'
                },
                dyeing_squeezer: {
                    dashboard: 'man.staff.dyeing-squeezer.dashboard',
                    work: 'man.staff.dyeing-squeezer.page',
                    reports: 'man.staff.dyeing-squeezer.reports'
                },
                dyeing_ironing: {
                    dashboard: 'man.staff.dyeing-ironing.dashboard',
                    work: 'man.staff.dyeing-ironing.page',
                    reports: 'man.staff.dyeing-ironing.reports'
                },
                dyeing_forming: {
                    dashboard: 'man.staff.dyeing-forming.dashboard',
                    work: 'man.staff.dyeing-forming.page',
                    reports: 'man.staff.dyeing-forming.reports'
                },
                dyeing_packaging: {
                    dashboard: 'man.staff.dyeing-packaging.dashboard',
                    work: 'man.staff.dyeing-packaging.page',
                    reports: null
                },
                maintenance_checker: {
                    dashboard: 'man.staff.maintenance-checker.dashboard',
                    work: 'man.staff.maintenance-checker.page',
                    reports: 'man.staff.maintenance-checker.reports'
                },
                checker_quality: {
                    dashboard: 'man.staff.checker-quality.dashboard',
                    work: 'man.staff.checker-quality.production',
                    reports: null
                }
            };

            const routes = roleToRoutes[manufacturingRole];
            if (routes) {
                items.push({ label: 'Manufacturing Dashboard', href: route(routes.dashboard), icon: Factory });
                if (routes.work) {
                    let workLabel = 'My Work';
                    if (manufacturingRole === 'dyeing_packaging') workLabel = 'Packaging';
                    if (manufacturingRole === 'checker_quality') workLabel = 'Production Control';
                    items.push({ label: workLabel, href: route(routes.work), icon: ClipboardList });
                }
                if (routes.reports) {
                    items.push({ label: 'Reports', href: route(routes.reports), icon: FileText });
                }
            }
            // If no role is assigned, do NOT add any link – the controller will show a message.
        }
    }

    // --- Inventory & Logistics (INV) ---
    if (userRole === 'INV') {
        items.push({ label: 'Inventory', href: userPosition === 'manager' ? route('inv.manager.inventory') : route('inv.employee.dashboard'), icon: Boxes })
        if (userPosition === 'manager') {
            items.push(
                { label: 'Production Planning', href: route('inv.manager.production-planning'), icon: TrendingUp },
                { label: 'Master Materials', href: route('inv.manager.material'), icon: Spool },
                { label: 'Master Products', href: route('inv.manager.product'), icon: Building2 }
            )
        }
    }

    // --- Order Fulfillment (ORD) ---
    if (userRole === 'ORD') {
        items.push({ label: 'Orders', href: userPosition === 'manager' ? route('ord.manager.dashboard') : route('ord.employee.dashboard'), icon: ShoppingCart })
    }

    // --- Warehouse Management (WAR) ---
    if (userRole === 'WAR') {
        items.push({ label: 'Warehouse', href: userPosition === 'manager' ? route('war.manager.dashboard') : route('war.employee.dashboard'), icon: Warehouse })
    }

    // --- Customer Relationship Management (CRM) ---
    if (userRole === 'CRM') {
        items.push(
            { label: 'Lead & Deals', href: route('crm.lead'), icon: FileUser },
            { label: 'Customer Profiles', href: route('crm.customerprofile'), icon: Users }
        );
        if (userPosition === 'manager') {
            items.push({ label: 'Approval Queue', href: route('crm.approval.queue'), icon: ClipboardCheck });
        }
    }

    // --- E-Commerce & B2B Portal (ECO) ---
    if (userRole === 'ECO') {
        if (userPosition === 'manager') {
            items.push(

                { label: 'Store', href: route('eco.manager.store'), icon: ShoppingBag },
                { label: 'Quotations', href: route('eco.manager.quotations'), icon: FileText },
                { label: 'Orders', href: route('eco.manager.orders'), icon: ShoppingCart },
                { label: 'Credit MGMT', href: route('eco.manager.credit'), icon: CreditCard },
                { label: 'Book MGMT', href: route('eco.manager.book'), icon: Book }
            )
        } else {
            items.push(
                { label: 'Online Store', href: route('eco.employee.products'), icon: Globe },
                { label: 'Order Management', href: route('eco.employee.ordermng'), icon: ShoppingBasket }
            )
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // NEW MODULES (PRO, PROJ, IT)
    // ─────────────────────────────────────────────────────────────────

    // --- Procurement Module (PRO) ---
    if (userRole === 'PRO') {
        if (userPosition === 'manager') {
            items.push(
                { label: 'Material Requests', href: route('pro.manager.material-requests'), icon: ClipboardList },
                { label: 'Supplier Quotations', href: route('pro.manager.supplier-quotations'), icon: FileText },
                { label: 'Receipt', href: route('pro.manager.receipt'), icon: Send }
            )
        } else if (userPosition === 'staff') {
            items.push({ label: 'Procurement Staff', href: route('pro.employee.dashboard'), icon: ShoppingCart })
        }
    }

    // --- Project Automation (PROJ) ---
    if (userRole === 'PROJ') {
        items.push({ label: 'Projects', href: userPosition === 'manager' ? route('proj.manager.dashboard') : route('proj.employee.dashboard'), icon: LayoutDashboard })
    }

    // --- IT & Systems Admin (IT) ---
    if (userRole === 'IT') {
        items.push({ label: 'IT & Systems', href: userPosition === 'manager' ? route('it.manager.dashboard') : route('it.employee.dashboard'), icon: Settings })
    }

    return items
})

const isActive = (href) => {
    if (href === '#') return false
    return currentUrl.value === href || currentUrl.value.startsWith(href + '/')
}

// --- Display Helpers ---
const displayName = computed(() => {
    if (isSupplier.value) return supplier.value?.representative_name
    if (isClient.value) return client.value?.company_name
    return user.value?.name
})

const displayInitial = computed(() => displayName.value?.charAt(0) ?? '?')

// Dynamically fetch and display actual uploaded user image if it exists
const userPhotoUrl = computed(() => {
    if (user.value?.profile_photo_path) return `/storage/${user.value.profile_photo_path}`;
    if (supplier.value?.profile_photo_path) return `/storage/${supplier.value.profile_photo_path}`;
    if (client.value?.profile_photo_path) return `/storage/${client.value.profile_photo_path}`;
    return null;
})

const displayDepartment = computed(() => {
    if (isSupplier.value) return 'Supplier'
    if (isClient.value) return client.value?.business_type
    return user.value?.role
})

const displayPosition = computed(() => {
    if (isSupplier.value) return supplier.value?.business_name ?? 'Vendor'
    if (isEmployeePortal.value) return user.value?.employee_id ?? 'Staff'
    if (isClient.value) return 'Partner'
    return user.value?.position
})

const sidebarLabel = computed(() => {
    if (isSupplier.value) return 'Vendor'
    if (isClient.value) return 'Partner'
    if (isEmployeePortal.value) return 'Employee'
    return 'System'
})

// Ensures the correct logout route is triggered for the specific Auth Guard
const logoutRoute = computed(() => {
    if (isClient.value) return route('client.logout')
    if (isSupplier.value) return route('supplier.logout')
    return route('logout')
})
</script>

<template>
    <aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 z-40 transition-all duration-300">
        <div
            class="flex flex-col flex-grow bg-slate-50 dark:bg-gray-950 border-r border-gray-200/60 dark:border-gray-800/60 shadow-xl">

            <div class="flex items-center h-20 flex-shrink-0 px-4">
                <div
                    class="flex items-center gap-2.5 p-2 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 w-full">
                    <div :class="isSupplier ? 'bg-emerald-600 shadow-emerald-500/20' : 'bg-blue-600 shadow-blue-500/20'"
                        class="h-9 w-9 flex-shrink-0 rounded-lg flex items-center justify-center shadow-lg">
                        <img src="/images/applogo.png" alt="Logo" class="h-6 w-6 object-contain brightness-0 invert" />
                    </div>
                    <div class="flex flex-col overflow-hidden">
                        <h2
                            class="text-[13px] font-black text-gray-900 dark:text-white leading-tight tracking-tight uppercase">
                            Monti <span :class="isSupplier ? 'text-emerald-600' : 'text-blue-600'">Textile</span>
                        </h2>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest truncate">
                            {{ sidebarLabel }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex-1 flex flex-col overflow-y-auto px-3 py-4 custom-scrollbar">
                <div class="mb-3 px-2">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.15em]">Main Menu</p>
                </div>
                <nav class="space-y-1">
                    <template v-for="item in navItems" :key="item.label">
                        <div v-if="item.isDropdown" class="space-y-1">
                            <button @click="toggleWorkforce" :class="[
                                isWorkforceOpen ? 'text-blue-600 bg-white/50 dark:bg-gray-900/50' : 'text-gray-500 dark:text-gray-400',
                                'group w-full flex items-center justify-between px-3 py-2.5 text-[13px] font-bold rounded-xl hover:bg-white/50 dark:hover:bg-gray-900/50 transition-all duration-300'
                            ]">
                                <div class="flex items-center">
                                    <div :class="[isWorkforceOpen ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600' : 'text-gray-400']"
                                        class="p-1.5 rounded-lg mr-2.5 transition-colors duration-300">
                                        <component :is="item.icon" class="h-4.5 w-4.5" />
                                    </div>
                                    <span class="truncate tracking-tight">{{ item.label }}</span>
                                </div>
                                <ChevronRight
                                    :class="['h-3.5 w-3.5 transition-transform duration-300', isWorkforceOpen ? 'rotate-90' : 'text-gray-400']" />
                            </button>

                            <div v-show="isWorkforceOpen" class="pl-10 space-y-1 mt-1 transition-all">
                                <Link v-for="subItem in item.children" :key="subItem.label" :href="subItem.href" :class="[
                                    isActive(subItem.href) ? 'text-blue-600' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'
                                ]" class="flex items-center py-2 text-[12px] font-bold transition-colors">
                                    <component :is="subItem.icon" class="h-3.5 w-3.5 mr-2.5" />
                                    {{ subItem.label }}
                                </Link>
                            </div>
                        </div>

                        <Link v-else :href="item.href" :class="[
                            isActive(item.href)
                                ? isSupplier
                                    ? 'bg-white dark:bg-gray-900 text-emerald-600 shadow-sm ring-1 ring-gray-200/50 dark:ring-gray-800'
                                    : 'bg-white dark:bg-gray-900 text-blue-600 shadow-sm ring-1 ring-gray-200/50 dark:ring-gray-800'
                                : 'text-gray-500 dark:text-gray-400 hover:bg-white/50 dark:hover:bg-gray-900/50 hover:text-gray-900 dark:hover:text-white'
                        ]"
                            class="group relative flex items-center justify-between px-3 py-2.5 text-[13px] font-bold rounded-xl transition-all duration-300">
                            <div v-if="isActive(item.href)" :class="isSupplier ? 'bg-emerald-600' : 'bg-blue-600'"
                                class="absolute left-0 top-1/4 bottom-1/4 w-0.5 rounded-r-full"></div>

                            <div class="flex items-center relative z-10">
                                <div :class="[
                                    isActive(item.href)
                                        ? isSupplier
                                            ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600'
                                            : 'bg-blue-50 dark:bg-blue-900/30 text-blue-600'
                                        : 'text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300'
                                ]" class="p-1.5 rounded-lg transition-colors duration-300 mr-2.5">
                                    <component :is="item.icon" class="h-4.5 w-4.5 flex-shrink-0" />
                                </div>
                                <span class="truncate tracking-tight">{{ item.label }}</span>
                            </div>
                            <ChevronRight v-if="isActive(item.href)"
                                :class="isSupplier ? 'text-emerald-600/40' : 'text-blue-600/40'" class="h-3.5 w-3.5" />
                        </Link>
                    </template>
                </nav>
            </div>

            <div class="p-3 mt-auto flex-shrink-0">
                <div
                    class="bg-white dark:bg-gray-900 rounded-2xl p-2.5 border border-gray-100 dark:border-gray-800 shadow-lg group">
                    <div class="flex items-center gap-2.5 relative z-10">

                        <div class="relative">
                            <img v-if="userPhotoUrl" :src="userPhotoUrl" alt="Profile"
                                class="h-9 w-9 rounded-xl object-cover shadow-lg"
                                :class="isSupplier ? 'shadow-emerald-500/30' : 'shadow-blue-500/30'" />
                            <div v-else :class="isSupplier
                                ? 'from-emerald-600 to-teal-700 shadow-emerald-500/30'
                                : 'from-blue-600 to-indigo-700 shadow-blue-500/30'"
                                class="h-9 w-9 rounded-xl bg-gradient-to-br flex items-center justify-center text-white text-xs font-black shadow-lg uppercase">
                                {{ displayInitial }}
                            </div>
                            <div
                                class="absolute -bottom-0.5 -right-0.5 h-3 w-3 bg-green-500 border-2 border-white dark:border-gray-900 rounded-full">
                            </div>
                        </div>

                        <div class="flex-1 min-w-0">
                            <p
                                class="text-[11px] font-black text-gray-900 dark:text-white truncate uppercase tracking-tighter">
                                {{ displayName }}
                            </p>
                            <div class="flex items-center gap-1 mb-0.5">
                                <Building2 class="h-2.5 w-2.5 text-gray-400" />
                                <span :class="isSupplier ? 'text-emerald-600' : 'text-blue-600'"
                                    class="text-[8px] font-black uppercase truncate">
                                    {{ displayDepartment }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1">
                                <ShieldCheck :class="isSupplier ? 'text-emerald-500' : 'text-blue-500'"
                                    class="h-2.5 w-2.5" />
                                <span class="text-[8px] font-black text-gray-400 uppercase truncate">
                                    {{ displayPosition }}
                                </span>
                            </div>
                        </div>

                        <button @click="showLogoutModal = true"
                            class="p-2 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all duration-300">
                            <LogOut class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <transition name="modal-fade">
                <div v-if="showLogoutModal"
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                    @click.self="showLogoutModal = false">
                    <div
                        class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 w-full max-w-sm p-6 flex flex-col items-center text-center">
                        <div
                            class="h-14 w-14 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4">
                            <LogOut class="h-6 w-6 text-red-600 dark:text-red-400 ml-1" />
                        </div>
                        <h3 class="text-xl font-black text-gray-900 dark:text-white mb-2">Sign Out</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 px-2">Are you sure you want to sign out
                            of your
                            account?</p>
                        <div class="flex gap-3 w-full">
                            <button @click="showLogoutModal = false"
                                class="flex-1 py-3 text-sm font-bold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                Cancel
                            </button>
                            <Link :href="logoutRoute" method="post" as="button"
                                class="flex-1 py-3 text-sm font-bold rounded-xl bg-red-600 text-white hover:bg-red-700 transition shadow-lg shadow-red-500/20">
                                Confirm Sign Out
                            </Link>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>
    </aside>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 3px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.2);
    border-radius: 10px;
}

/* Modal Entry/Exit Animations */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.modal-fade-enter-active .bg-white,
.modal-fade-leave-active .bg-white {
    transition: transform 0.2s ease;
}

.modal-fade-enter-from .bg-white,
.modal-fade-leave-to .bg-white {
    transform: scale(0.95) translateY(10px);
}
</style>