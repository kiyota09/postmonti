<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { UserCheck, Briefcase, Users, Calendar, CheckCircle, XCircle, Search } from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    businessPartners: Array,
    leads: Array,
    pendingRegistrations: Array,
    upcomingInterviews: Array,
    pendingApprovals: Array,
    userRole: String,
    userPosition: String,
});

// Tab state
const activeTab = ref('partners');

// For pending registrations
const approveClient = (clientId) => {
    router.patch(route('clients.status.update', clientId), { status: 'active' });
};

const rejectClient = (clientId) => {
    router.patch(route('clients.status.update', clientId), { status: 'rejected' });
};

// For business partners suspend/reactivate
const togglePartnerStatus = (client) => {
    const newStatus = client.status === 'active' ? 'suspended' : 'active';
    router.patch(route('clients.status.update', client.id), { status: newStatus });
};

// For leads terminate
const terminateLead = (leadId) => {
    if (confirm('Are you sure you want to terminate this lead? It will be archived.')) {
        router.patch(route('crm.lead.status', leadId), { status: 'Archived' });
    }
};

// Format date for interviews
const formatDateTime = (date) => {
    return new Date(date).toLocaleString();
};
</script>

<template>
    <AuthenticatedLayout title="CRM Dashboard">

        <Head title="CRM Dashboard" />

        <div class="p-6 max-w-7xl mx-auto">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-50 rounded-xl">
                            <Briefcase class="w-6 h-6 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-xs font-black text-gray-400 uppercase">Total Leads</p>
                            <h3 class="text-2xl font-black">{{ stats.totalLeads }}</h3>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-green-50 rounded-xl">
                            <UserCheck class="w-6 h-6 text-green-600" />
                        </div>
                        <div>
                            <p class="text-xs font-black text-gray-400 uppercase">Leads Won</p>
                            <h3 class="text-2xl font-black">{{ stats.leadsWon }}</h3>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-purple-50 rounded-xl">
                            <Users class="w-6 h-6 text-purple-600" />
                        </div>
                        <div>
                            <p class="text-xs font-black text-gray-400 uppercase">Total Business Partners</p>
                            <h3 class="text-2xl font-black">{{ stats.totalPartners }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="flex space-x-6">
                    <button @click="activeTab = 'partners'"
                        :class="activeTab === 'partners' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-2 px-1 border-b-2 font-medium text-sm uppercase tracking-wider">
                        Business Partners
                    </button>
                    <button @click="activeTab = 'leads'"
                        :class="activeTab === 'leads' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-2 px-1 border-b-2 font-medium text-sm uppercase tracking-wider">
                        Leads
                    </button>
                    <button @click="activeTab = 'pending'"
                        :class="activeTab === 'pending' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-2 px-1 border-b-2 font-medium text-sm uppercase tracking-wider">
                        Pending Registrations
                    </button>
                    <button @click="activeTab = 'calendar'"
                        :class="activeTab === 'calendar' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-2 px-1 border-b-2 font-medium text-sm uppercase tracking-wider">
                        <Calendar class="inline w-4 h-4 mr-1" /> Calendar
                    </button>
                </nav>
            </div>

            <!-- Business Partners Tab -->
            <div v-if="activeTab === 'partners'">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="text-sm font-black uppercase">Official Business Partners</h3>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <input type="text" placeholder="Search..." class="pl-9 pr-4 py-2 border rounded-lg text-sm">
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="partner in businessPartners" :key="partner.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ partner.company_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">{{ partner.contact_person }}<br>{{
                                            partner.email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="partner.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                            {{ partner.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <button @click="togglePartnerStatus(partner)"
                                            class="text-blue-600 hover:text-blue-900 text-sm">
                                            {{ partner.status === 'active' ? 'Suspend' : 'Reactivate' }}
                                        </button>
                                        <Link :href="route('crm.customerprofile', partner.id)"
                                            class="ml-4 text-gray-600 hover:text-gray-900 text-sm">View</Link>
                                    </td>
                                </tr>
                                <tr v-if="businessPartners.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">No business partners
                                        found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Leads Tab -->
            <div v-if="activeTab === 'leads'">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="text-sm font-black uppercase">All Leads</h3>
                        <Link :href="route('crm.lead')" class="text-blue-600 text-sm font-medium">Manage Pipeline →
                        </Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="lead in leads" :key="lead.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ lead.company_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">{{ lead.contact_person }}<br>{{ lead.email }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="{
                                            'bg-blue-100 text-blue-800': lead.status === 'Inquiry',
                                            'bg-yellow-100 text-yellow-800': lead.status === 'Negotiation',
                                            'bg-purple-100 text-purple-800': lead.status === 'Approval Sent',
                                            'bg-green-100 text-green-800': lead.status === 'Closed-Won',
                                            'bg-red-100 text-red-800': lead.status === 'Lost',
                                        }" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                            {{ lead.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <button @click="terminateLead(lead.id)"
                                            class="text-red-600 hover:text-red-900 text-sm">Terminate</button>
                                        <Link :href="route('crm.lead')"
                                            class="ml-4 text-blue-600 hover:text-blue-900 text-sm">View</Link>
                                    </td>
                                </tr>
                                <tr v-if="leads.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">No leads found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pending Registrations Tab -->
            <div v-if="activeTab === 'pending'">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <h3 class="text-sm font-black uppercase">Pending Business Registrations</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">TIN</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="reg in pendingRegistrations" :key="reg.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ reg.company_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">{{ reg.contact_person }}<br>{{ reg.email }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">{{ reg.tin_number }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <button @click="approveClient(reg.id)"
                                            class="text-green-600 hover:text-green-900 text-sm mr-4">Approve</button>
                                        <button @click="rejectClient(reg.id)"
                                            class="text-red-600 hover:text-red-900 text-sm">Reject</button>
                                    </td>
                                </tr>
                                <tr v-if="pendingRegistrations.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">No pending
                                        registrations.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Calendar Tab -->
            <div v-if="activeTab === 'calendar'">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm p-6">
                    <h3 class="text-sm font-black uppercase mb-4">Upcoming Interviews</h3>
                    <div class="space-y-4">
                        <div v-for="interview in upcomingInterviews" :key="interview.id"
                            class="border-b pb-4 last:border-0">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-semibold">{{ interview.lead.company_name }}</p>
                                    <p class="text-sm text-gray-600">{{ formatDateTime(interview.scheduled_at) }}</p>
                                    <p class="text-sm text-gray-500">Location: {{ interview.location || 'TBD' }}</p>
                                    <p class="text-sm text-gray-500">Notes: {{ interview.notes || 'No notes' }}</p>
                                </div>
                                <Link :href="route('crm.lead')" class="text-blue-600 text-sm">View Lead</Link>
                            </div>
                        </div>
                        <div v-if="upcomingInterviews.length === 0" class="text-center py-8 text-gray-500">
                            No upcoming interviews scheduled.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Approval Queue (only for managers) -->
            <div v-if="userPosition === 'manager' && pendingApprovals.length > 0"
                class="mt-8 bg-yellow-50 border border-yellow-200 rounded-2xl p-4">
                <h3 class="text-sm font-black uppercase text-yellow-800 mb-2">Pending Approvals ({{
                    pendingApprovals.length }})</h3>
                <Link :href="route('crm.approval.queue')" class="text-yellow-700 text-sm underline">Review Queue →
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>