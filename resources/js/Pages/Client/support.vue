<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    HelpCircle,
    MessageSquare,
    Phone,
    Mail,
    FileText,
    Search,
    ChevronRight,
    LifeBuoy,
    Clock,
    ShieldQuestion,
    ArrowUpRight
} from 'lucide-vue-next';

const page = usePage();
const client = computed(() => page.props.auth?.client);

// FAQ Categories
const faqs = ref([
    { question: 'How do I request a credit line increase?', category: 'Billing' },
    { question: 'What is the standard lead time for wholesale orders?', category: 'Shipping' },
    { question: 'How can I track my fabric production status?', category: 'Orders' },
    { question: 'Can I request a custom fabric GSM?', category: 'Technical' },
]);

const supportChannels = [
    {
        title: 'Submit Ticket',
        desc: 'Open a formal inquiry for technical or billing issues.',
        icon: MessageSquare,
        color: 'text-blue-600',
        bg: 'bg-blue-50 dark:bg-blue-900/20'
    },
    {
        title: 'Knowledge Base',
        desc: 'Browse documentation on fabric specifications and care.',
        icon: FileText,
        color: 'text-indigo-600',
        bg: 'bg-indigo-50 dark:bg-indigo-900/20'
    },
    {
        title: 'Account Manager',
        desc: 'Direct line for VIP wholesale partners.',
        icon: LifeBuoy,
        color: 'text-purple-600',
        bg: 'bg-purple-50 dark:bg-purple-900/20'
    }
];
</script>

<template>

    <Head title="Support - Partner Portal" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-10">

            <div
                class="relative overflow-hidden rounded-[3rem] bg-gray-900 p-10 text-white border border-gray-800 shadow-2xl">
                <div class="absolute -right-20 -top-20 h-80 w-80 bg-blue-600/20 rounded-full blur-3xl"></div>
                <div class="absolute -left-20 -bottom-20 h-64 w-64 bg-indigo-600/10 rounded-full blur-3xl"></div>

                <div class="relative z-10 text-center max-w-2xl mx-auto space-y-4">
                    <div
                        class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-4 py-1.5 rounded-full border border-white/10 text-[10px] font-black uppercase tracking-widest">
                        <HelpCircle class="h-3.5 w-3.5 text-blue-400" />
                        Partner Assistance Center
                    </div>
                    <h1 class="text-4xl md:text-5xl font-black tracking-tighter uppercase italic">How can we <span
                            class="text-blue-500">help you?</span></h1>
                    <p class="text-gray-400 font-medium text-sm">Dedicated support for our B2B partners. Search our
                        resources or contact your account manager.</p>

                    <div class="relative mt-8">
                        <Search class="absolute left-6 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                        <input type="text" placeholder="Search for fabric specs, shipping times, or billing..."
                            class="w-full pl-16 pr-6 py-5 rounded-3xl bg-white text-gray-900 border-none focus:ring-4 focus:ring-blue-500/50 text-sm font-bold shadow-2xl shadow-blue-500/10 transition-all">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div v-for="channel in supportChannels" :key="channel.title"
                    class="group p-8 rounded-[2.5rem] bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm transition-all hover:shadow-xl hover:-translate-y-2">
                    <div :class="[channel.bg, channel.color]"
                        class="h-14 w-14 rounded-2xl flex items-center justify-center mb-6 transition-transform group-hover:scale-110">
                        <component :is="channel.icon" class="h-7 w-7" />
                    </div>
                    <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight">{{
                        channel.title }}</h3>
                    <p class="mt-2 text-sm text-gray-500 font-medium leading-relaxed">{{ channel.desc }}</p>
                    <button
                        class="mt-6 flex items-center gap-2 text-xs font-black uppercase tracking-widest text-blue-600 group-hover:gap-4 transition-all">
                        Get Started
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">

                <div class="space-y-6">
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-gray-800 flex items-center justify-center text-gray-500">
                            <ShieldQuestion class="h-5 w-5" />
                        </div>
                        <h2 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Frequent
                            Inquiries</h2>
                    </div>

                    <div class="space-y-3">
                        <div v-for="faq in faqs" :key="faq.question"
                            class="flex items-center justify-between p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 group hover:border-blue-200 transition-all cursor-pointer">
                            <div class="flex items-center gap-4">
                                <span
                                    class="text-[9px] font-black text-blue-600 bg-blue-50 dark:bg-blue-900/30 px-2 py-1 rounded-lg uppercase w-16 text-center">{{
                                    faq.category }}</span>
                                <p class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ faq.question }}</p>
                            </div>
                            <ChevronRight class="h-4 w-4 text-gray-400 group-hover:text-blue-600" />
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-gray-800 flex items-center justify-center text-gray-500">
                            <Clock class="h-5 w-5" />
                        </div>
                        <h2 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Business
                            Support Hours</h2>
                    </div>

                    <div
                        class="rounded-[2.5rem] bg-gradient-to-br from-blue-600 to-indigo-700 p-8 text-white shadow-lg">
                        <div class="space-y-6">
                            <div class="flex items-start gap-4">
                                <Phone class="h-6 w-6 opacity-60" />
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80">Direct Line
                                    </p>
                                    <p class="text-lg font-black">+63 (2) 8888-MONTI</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <Mail class="h-6 w-6 opacity-60" />
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80">Enterprise
                                        Support</p>
                                    <p class="text-lg font-black">support@montitextile.ph</p>
                                </div>
                            </div>
                            <div class="pt-6 border-t border-white/10 flex justify-between items-center">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest opacity-80">Monday —
                                        Friday</p>
                                    <p class="text-sm font-bold">08:00 AM — 06:00 PM</p>
                                </div>
                                <div class="h-10 w-10 rounded-full bg-green-500 border-4 border-white/20 animate-pulse">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="flex items-center justify-between p-8 rounded-[2.5rem] bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm">
                <div class="flex items-center gap-5">
                    <div
                        class="h-14 w-14 rounded-2xl bg-slate-100 dark:bg-gray-800 flex items-center justify-center text-gray-900 dark:text-white">
                        <MessageSquare class="h-6 w-6" />
                    </div>
                    <div>
                        <h4 class="text-sm font-black uppercase tracking-tight text-gray-900 dark:text-white">Looking
                            for your open tickets?</h4>
                        <p class="text-xs font-medium text-gray-500">View the progress of your ongoing technical support
                            and billing inquiries.</p>
                    </div>
                </div>
                <Link href="#"
                    class="group flex items-center gap-2 px-6 py-3 rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-[10px] font-black uppercase tracking-widest transition-all hover:scale-105 active:scale-95">
                    View History
                    <ArrowUpRight class="h-4 w-4" />
                </Link>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.tracking-tighter {
    letter-spacing: -0.05em;
}
</style>