<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    CalendarDaysIcon,
    ArrowDownTrayIcon,
    BuildingOfficeIcon,
    PresentationChartLineIcon,
    BanknotesIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    ChartBarIcon,
    ShoppingCartIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    projects: Array,
    filters: Object,
    totals: Object
});

const budgetUtilization = computed(() => {
    if (!props.totals.budget || props.totals.budget === 0) return 0;
    const totalExposure = props.totals.expenses + props.totals.committed;
    return Math.min(100, (totalExposure / props.totals.budget) * 100);
});

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);

const applyFilters = () => {
    router.get(route('accounting.project-pl'), {
        start_date: startDate.value,
        end_date: endDate.value
    }, { preserveState: true, preserveScroll: true });
};

const exportPdf = () => {
    const url = route('accounting.project-pl.export', {
        start_date: startDate.value,
        end_date: endDate.value
    });
    window.open(url, '_blank');
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value || 0);
};
</script>

<template>
    <Head title="Project P&L Report - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Project Profit & Loss</h2>
                    <p class="text-sm text-slate-500 mt-1">Summary of revenue and expenses allocated per project.</p>
                </div>
                <div class="mt-4 md:mt-0 flex space-x-2 no-print">
                    <button @click="exportPdf" class="bg-white text-slate-700 border border-slate-200 px-4 py-2.5 rounded-xl hover:bg-slate-50 transition-all flex items-center space-x-2 text-sm font-bold shadow-sm">
                        <ArrowDownTrayIcon class="w-4 h-4" />
                        <span>Export PDF</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6 space-y-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Filters Bar -->
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex flex-col md:flex-row items-end space-y-4 md:space-y-0 md:space-x-4 mb-6">
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Start Date</label>
                            <div class="relative">
                                <CalendarDaysIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                <input v-model="startDate" type="date" class="pl-10 w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all text-sm font-bold text-slate-700">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">End Date</label>
                            <div class="relative">
                                <CalendarDaysIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                <input v-model="endDate" type="date" class="pl-10 w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all text-sm font-bold text-slate-700">
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button @click="applyFilters" class="bg-indigo-600 text-white px-6 py-2.5 rounded-2xl hover:bg-indigo-700 transition-all font-black text-sm shadow-xl shadow-indigo-600/20">Generate Report</button>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-2 bg-emerald-50 rounded-xl">
                                <ArrowTrendingUpIcon class="w-6 h-6 text-emerald-600" />
                            </div>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Revenue</span>
                        </div>
                        <p class="text-2xl font-black text-slate-900">{{ formatCurrency(totals.revenue) }}</p>
                    </div>

                    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-2 bg-rose-50 rounded-xl">
                                <ArrowTrendingDownIcon class="w-6 h-6 text-rose-600" />
                            </div>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Actual Spent</span>
                        </div>
                        <p class="text-2xl font-black text-slate-900">{{ formatCurrency(totals.expenses) }}</p>
                    </div>

                    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-2 bg-amber-50 rounded-xl">
                                <ShoppingCartIcon class="w-6 h-6 text-amber-600" />
                            </div>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Committed (POs)</span>
                        </div>
                        <p class="text-2xl font-black text-slate-900">{{ formatCurrency(totals.committed) }}</p>
                    </div>

                    <div class="bg-slate-900 p-6 rounded-3xl shadow-xl shadow-slate-900/20 text-white relative overflow-hidden">
                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2 bg-indigo-500/20 rounded-xl">
                                    <ChartBarIcon class="w-6 h-6 text-indigo-400" />
                                </div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Budget Health</span>
                            </div>
                            <p class="text-2xl font-black">{{ formatCurrency(totals.budget) }}</p>
                            <div class="mt-4">
                                <div class="flex justify-between text-[9px] font-black uppercase mb-1">
                                    <span>Utilization</span>
                                    <span>{{ budgetUtilization.toFixed(1) }}%</span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                    <div :style="{ width: `${budgetUtilization}%` }" :class="['h-full transition-all duration-1000', budgetUtilization > 90 ? 'bg-rose-500' : 'bg-indigo-500']"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- P&L Table -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50">
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Project Name</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Budget</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Revenue</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Spent (Actual)</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Committed (POs)</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 bg-slate-50">Variance</th>
                                    <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Margin (%)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="project in projects" :key="project.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="p-1.5 bg-indigo-50 rounded-lg">
                                                <BuildingOfficeIcon class="w-4 h-4 text-indigo-500" />
                                            </div>
                                            <span class="text-xs font-black text-slate-900 tracking-tight">{{ project.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-right font-bold text-slate-500">{{ formatCurrency(project.budget) }}</td>
                                    <td class="px-6 py-4 text-xs text-right font-black text-emerald-600">{{ formatCurrency(project.revenue) }}</td>
                                    <td class="px-6 py-4 text-xs text-right font-black text-rose-600">{{ formatCurrency(project.expenses) }}</td>
                                    <td class="px-6 py-4 text-xs text-right font-bold text-amber-600 italic">{{ formatCurrency(project.committed) }}</td>
                                    <td class="px-6 py-4 text-xs text-right font-black bg-slate-50/30">
                                        <span :class="project.variance < 0 ? 'text-rose-600' : 'text-indigo-600'">
                                            {{ formatCurrency(project.variance) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-[10px] font-bold text-slate-500">
                                            {{ project.revenue > 0 ? ((project.net_profit / project.revenue) * 100).toFixed(2) + '%' : '0.00%' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-900 text-white font-black text-[10px] uppercase tracking-widest">
                                <tr>
                                    <td class="px-6 py-6">Grand Totals</td>
                                    <td class="px-6 py-6 text-right border-l border-white/10 text-slate-400">{{ formatCurrency(totals.budget) }}</td>
                                    <td class="px-6 py-6 text-right border-l border-white/10 text-emerald-400">{{ formatCurrency(totals.revenue) }}</td>
                                    <td class="px-6 py-6 text-right border-l border-white/10 text-rose-400">{{ formatCurrency(totals.expenses) }}</td>
                                    <td class="px-6 py-6 text-right border-l border-white/10 text-amber-400">{{ formatCurrency(totals.committed) }}</td>
                                    <td class="px-6 py-6 text-right border-l border-white/10 bg-indigo-600">Profit: {{ formatCurrency(totals.net_profit) }}</td>
                                    <td class="px-6 py-6 text-center border-l border-white/10">
                                        {{ totals.revenue > 0 ? ((totals.net_profit / totals.revenue) * 100).toFixed(2) + '%' : '-' }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div v-if="projects.length === 0" class="bg-white rounded-3xl p-12 text-center border border-slate-100 shadow-sm">
                    <BuildingOfficeIcon class="w-16 h-16 text-slate-200 mx-auto mb-4" />
                    <h3 class="text-xl font-bold text-slate-900">No Projects Found</h3>
                    <p class="text-slate-500 mt-2">Active projects will appear here once they have accounting activity in the selected date range.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
