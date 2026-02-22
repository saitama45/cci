<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    CalendarDaysIcon,
    ChartPieIcon,
    ArrowTrendingUpIcon,
    UsersIcon,
    ScaleIcon,
    ArrowDownTrayIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    outstanding_report: Object,
    installment_report: Object,
    filters: Object,
    summary: Object
});

const asOfDate = ref(props.filters.as_of_date || new Date().toISOString().split('T')[0]);

const applyFilters = () => {
    router.get(route('accounting.overall-receivables'), {
        as_of_date: asOfDate.value
    }, { preserveState: true, preserveScroll: true });
};

const exportPdf = () => {
    const url = route('accounting.overall-receivables.export', {
        as_of_date: asOfDate.value
    });
    window.open(url, '_blank');
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value || 0);
};

const formatPercent = (value, total) => {
    if (!total) return '0.00%';
    return ((value / total) * 100).toFixed(2) + '%';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-PH', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
    });
};
</script>

<template>
    <Head title="Overall Receivables - Accounting" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Overall Receivables Summary</h2>
                    <p class="text-sm text-slate-500 mt-1">Consolidated view of outstanding balances and overdue installments.</p>
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
            <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8">
                
                <!-- Filters Bar -->
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex flex-col md:flex-row items-end space-y-4 md:space-y-0 md:space-x-4 mb-8 transition-all hover:shadow-md">
                    <div class="w-full md:w-64">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">As Of Date</label>
                        <div class="relative">
                            <CalendarDaysIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input v-model="asOfDate" type="date" class="pl-10 w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all text-sm font-bold text-slate-700">
                        </div>
                    </div>
                    <button @click="applyFilters" class="bg-blue-600 text-white px-6 py-2.5 rounded-2xl hover:bg-blue-700 transition-all font-black text-sm shadow-xl shadow-blue-600/20">Update Reports</button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- Report 1: Outstanding Receivables -->
                    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden flex flex-col">
                        <div class="p-8 border-b border-slate-50 bg-slate-50/30">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="p-2.5 bg-indigo-600 rounded-2xl">
                                    <ArrowTrendingUpIcon class="w-6 h-6 text-white" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-slate-900 tracking-tight uppercase">Overall Outstanding Receivables</h3>
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">for the month as of {{ formatDate(asOfDate) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex-grow overflow-x-auto custom-scrollbar">
                            <table class="w-full text-left min-w-[500px]">
                                <thead>
                                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                        <th class="px-6 py-4">Aging Bracket</th>
                                        <th class="px-6 py-4 text-right">Outstanding Balance</th>
                                        <th class="px-6 py-4 text-center"># of Accounts</th>
                                        <th class="px-6 py-4 text-right pr-8">%</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="(data, bracket) in outstanding_report" :key="bracket" class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-5 text-xs font-black text-slate-700 uppercase tracking-wide whitespace-nowrap">{{ bracket }}</td>
                                        <td class="px-6 py-5 text-sm text-right font-bold text-slate-900 whitespace-nowrap">{{ formatCurrency(data.amount) }}</td>
                                        <td class="px-6 py-5 text-sm text-center font-black text-slate-500">{{ data.count }}</td>
                                        <td class="px-6 py-5 text-xs text-right font-black text-blue-600 pr-8">{{ formatPercent(data.amount, summary.total_outstanding) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-slate-900 text-white">
                                    <tr class="font-black text-xs uppercase tracking-widest">
                                        <td class="px-6 py-6 whitespace-nowrap">Total Outstanding</td>
                                        <td class="px-6 py-6 text-right text-base whitespace-nowrap">{{ formatCurrency(summary.total_outstanding) }}</td>
                                        <td class="px-6 py-6 text-center">{{ summary.outstanding_accounts }}</td>
                                        <td class="px-6 py-6 text-right pr-8">100.00%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Report 2: Installment Due -->
                    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden flex flex-col">
                        <div class="p-8 border-b border-slate-50 bg-slate-50/30">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="p-2.5 bg-rose-600 rounded-2xl">
                                    <ScaleIcon class="w-6 h-6 text-white" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-slate-900 tracking-tight uppercase">Overall Installment Due</h3>
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">for the month as of {{ formatDate(asOfDate) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex-grow overflow-x-auto custom-scrollbar">
                            <table class="w-full text-left min-w-[500px]">
                                <thead>
                                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                        <th class="px-6 py-4">Aging Bracket</th>
                                        <th class="px-6 py-4 text-right">Installment Due</th>
                                        <th class="px-6 py-4 text-center"># of Items</th>
                                        <th class="px-6 py-4 text-right pr-8">%</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="(data, bracket) in installment_report" :key="bracket" class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-5 text-xs font-black text-slate-700 uppercase tracking-wide whitespace-nowrap">{{ bracket }}</td>
                                        <td class="px-6 py-5 text-sm text-right font-bold text-slate-900 whitespace-nowrap">{{ formatCurrency(data.amount) }}</td>
                                        <td class="px-6 py-5 text-sm text-center font-black text-slate-500">{{ data.count }}</td>
                                        <td class="px-6 py-5 text-xs text-right font-black text-rose-600 pr-8">{{ formatPercent(data.amount, summary.total_installment) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-slate-900 text-white">
                                    <tr class="font-black text-xs uppercase tracking-widest">
                                        <td class="px-6 py-6 whitespace-nowrap">Total Installment Due</td>
                                        <td class="px-6 py-6 text-right text-base whitespace-nowrap">{{ formatCurrency(summary.total_installment) }}</td>
                                        <td class="px-6 py-6 text-center">{{ summary.installment_accounts }}</td>
                                        <td class="px-6 py-6 text-right pr-8">100.00%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
