<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    BuildingOfficeIcon, 
    UserGroupIcon, 
    CurrencyDollarIcon, 
    ClipboardDocumentCheckIcon 
} from '@heroicons/vue/24/outline';

const page = usePage();
const user = computed(() => page.props.auth?.user || {});

const modules = [
    {
        name: 'Projects & Inventory',
        description: 'Manage physical assets, lots, and site plans.',
        icon: BuildingOfficeIcon,
        color: 'bg-blue-500',
        textColor: 'text-blue-500',
        bgLight: 'bg-blue-50',
        stats: '12 Active Projects'
    },
    {
        name: 'Sales & Reservations',
        description: 'Handle leads, customers, and reservation fees.',
        icon: UserGroupIcon,
        color: 'bg-indigo-500',
        textColor: 'text-indigo-500',
        bgLight: 'bg-indigo-50',
        stats: '45 New Leads'
    },
    {
        name: 'Collections',
        description: 'Track receivables, ledgers, and PDCs.',
        icon: CurrencyDollarIcon,
        color: 'bg-emerald-500',
        textColor: 'text-emerald-500',
        bgLight: 'bg-emerald-50',
        stats: '₱2.4M Collected'
    },
    {
        name: 'Accounting',
        description: 'General ledger, journal entries, and chart of accounts.',
        icon: ClipboardDocumentCheckIcon,
        color: 'bg-amber-500',
        textColor: 'text-amber-500',
        bgLight: 'bg-amber-50',
        stats: 'Audit Ready'
    }
];

const currentDate = new Date().toLocaleDateString('en-US', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
});
</script>

<template>
    <Head title="Dashboard - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Dashboard</h2>
                    <p class="text-sm text-slate-500 mt-1">Overview of system modules and status</p>
                </div>
                <div class="mt-4 md:mt-0 text-sm text-slate-500 bg-white px-4 py-2 rounded-lg shadow-sm border border-slate-200">
                    {{ currentDate }}
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Welcome Banner -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-6 md:p-10 text-white shadow-xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white opacity-10 rounded-full blur-2xl"></div>
                    <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-white opacity-10 rounded-full blur-2xl"></div>
                    
                    <div class="relative z-10">
                        <h3 class="text-3xl font-bold mb-2">Welcome back, {{ user.name }}!</h3>
                        <p class="text-blue-100 max-w-2xl text-lg">
                            You have logged into GalgaTech Horizon ERP. All systems are running optimally.
                        </p>
                    </div>
                </div>

                <!-- Module Overview Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="mod in modules" :key="mod.name" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 hover:shadow-md transition-shadow duration-200 group">
                        <div class="flex items-center justify-between mb-4">
                            <div :class="[mod.bgLight, 'p-3 rounded-lg group-hover:scale-110 transition-transform duration-200']">
                                <component :is="mod.icon" :class="[mod.textColor, 'w-6 h-6']" />
                            </div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active</span>
                        </div>
                        <h4 class="text-lg font-bold text-slate-800 mb-1">{{ mod.name }}</h4>
                        <p class="text-sm text-slate-500 mb-4 h-10">{{ mod.description }}</p>
                        
                        <div class="border-t border-slate-100 pt-4 flex items-center justify-between">
                            <span class="text-sm font-semibold text-slate-700">{{ mod.stats }}</span>
                            <span class="text-xs text-blue-600 hover:underline cursor-pointer">View Details &rarr;</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Placeholder -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                        <h4 class="text-lg font-bold text-slate-800">System Notices</h4>
                        <button class="text-sm text-blue-600 hover:underline">View All</button>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-start space-x-3 pb-4 border-b border-slate-50 last:border-0 last:pb-0">
                                <div class="w-2 h-2 mt-2 rounded-full bg-green-500"></div>
                                <div>
                                    <p class="text-sm font-medium text-slate-800">System Update 2.1.0 Live</p>
                                    <p class="text-xs text-slate-500 mt-0.5">The new collections module features are now available for all accounting roles.</p>
                                </div>
                                <span class="text-xs text-slate-400 ml-auto whitespace-nowrap">2h ago</span>
                            </div>
                            <div class="flex items-start space-x-3 pb-4 border-b border-slate-50 last:border-0 last:pb-0">
                                <div class="w-2 h-2 mt-2 rounded-full bg-amber-500"></div>
                                <div>
                                    <p class="text-sm font-medium text-slate-800">Maintenance Scheduled</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Scheduled database optimization this Sunday at 2:00 AM.</p>
                                </div>
                                <span class="text-xs text-slate-400 ml-auto whitespace-nowrap">1d ago</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>