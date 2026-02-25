<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    CalendarDaysIcon,
    ArrowDownTrayIcon,
    ClockIcon,
    BuildingOfficeIcon,
    DocumentTextIcon,
    BanknotesIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    report_data: Array,
    filters: Object,
    totals: Object
});

const asOfDate = ref(props.filters.as_of_date || new Date().toISOString().split('T')[0]);

const applyFilters = () => {
    router.get(route('accounting.ap-aging'), {
        as_of_date: asOfDate.value
    }, { preserveState: true, preserveScroll: true });
};

const exportPdf = () => {
    const url = route('accounting.ap-aging.export', {
        as_of_date: asOfDate.value
    });
    window.open(url, '_blank');
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value || 0);
};

const formatDate = (date) => {
    if (!date || date === 'N/A') return 'N/A';
    return new Date(date).toLocaleDateString('en-PH', {
        month: 'short',
        day: '2-digit',
        year: 'numeric'
    });
};

const getAgingColor = (days) => {
    if (days <= 0) return 'text-emerald-600';
    if (days <= 30) return 'text-amber-600';
    if (days <= 60) return 'text-orange-600';
    if (days <= 90) return 'text-rose-600';
    return 'text-red-700';
};
</script>

<template>
    <Head title="AP Aging Report - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Accounts Payable (AP) Aging</h2>
                    <p class="text-sm text-slate-500 mt-1">Classification of unpaid vendor bills based on due dates.</p>
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
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex flex-col md:flex-row items-end space-y-4 md:space-y-0 md:space-x-4 mb-6">
                    <div class="w-full md:w-64">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">As Of Date</label>
                        <div class="relative">
                            <CalendarDaysIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input v-model="asOfDate" type="date" class="pl-10 w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all text-sm font-bold text-slate-700">
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button @click="applyFilters" class="bg-indigo-600 text-white px-6 py-2.5 rounded-2xl hover:bg-indigo-700 transition-all font-black text-sm shadow-xl shadow-indigo-600/20">Generate Report</button>
                    </div>
                </div>

                <!-- Aging Table -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50">
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Vendor</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Bill #</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Project</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 text-center">Due Date</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Current</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">1-30 Days</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">31-60 Days</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">61-90 Days</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">91+ Over</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 bg-slate-100/50">Total Unpaid</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="row in report_data" :key="row.bill_id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="text-xs font-black text-slate-900">{{ row.vendor_name }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <Link :href="route('accounting.bills.show', row.bill_id)" class="text-[11px] font-bold text-indigo-600 hover:underline">
                                            {{ row.bill_number }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-[10px] font-bold text-slate-500">{{ row.project_name }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex flex-col">
                                            <span class="text-[10px] font-bold text-slate-700">{{ formatDate(row.due_date) }}</span>
                                            <span v-if="row.days_overdue > 0" class="text-[9px] font-black text-rose-600 uppercase">{{ row.days_overdue }} Days Overdue</span>
                                            <span v-else class="text-[9px] font-black text-emerald-600 uppercase">Not Due</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-right font-bold text-slate-400">{{ row.current > 0 ? formatCurrency(row.current) : '-' }}</td>
                                    <td class="px-6 py-4 text-xs text-right font-black" :class="getAgingColor(row.days_overdue)">{{ row['1_30'] > 0 ? formatCurrency(row['1_30']) : '-' }}</td>
                                    <td class="px-6 py-4 text-xs text-right font-black" :class="getAgingColor(row.days_overdue)">{{ row['31_60'] > 0 ? formatCurrency(row['31_60']) : '-' }}</td>
                                    <td class="px-6 py-4 text-xs text-right font-black" :class="getAgingColor(row.days_overdue)">{{ row['61_90'] > 0 ? formatCurrency(row['61_90']) : '-' }}</td>
                                    <td class="px-6 py-4 text-xs text-right font-black" :class="getAgingColor(row.days_overdue)">{{ row['91_over'] > 0 ? formatCurrency(row['91_over']) : '-' }}</td>
                                    <td class="px-6 py-4 text-xs text-right font-black text-slate-900 bg-slate-50/50">{{ formatCurrency(row.unpaid_amount) }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-900 text-white">
                                <tr class="font-black text-xs uppercase tracking-widest text-right">
                                    <td colspan="4" class="px-6 py-6">Grand Totals</td>
                                    <td class="px-6 py-6 border-l border-white/10">{{ formatCurrency(totals.current) }}</td>
                                    <td class="px-6 py-6 border-l border-white/10">{{ formatCurrency(totals['1_30']) }}</td>
                                    <td class="px-6 py-6 border-l border-white/10">{{ formatCurrency(totals['31_60']) }}</td>
                                    <td class="px-6 py-6 border-l border-white/10">{{ formatCurrency(totals['61_90']) }}</td>
                                    <td class="px-6 py-6 border-l border-white/10">{{ formatCurrency(totals['91_over']) }}</td>
                                    <td class="px-6 py-6 border-l border-white/10 bg-indigo-600 font-black text-sm">{{ formatCurrency(totals.total) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div v-if="report_data.length === 0" class="bg-white rounded-3xl p-12 text-center border border-slate-100 shadow-sm">
                    <DocumentTextIcon class="w-16 h-16 text-slate-200 mx-auto mb-4" />
                    <h3 class="text-xl font-bold text-slate-900">No Payables Found</h3>
                    <p class="text-slate-500 mt-2">All approved bills are currently paid or there are no outstanding liabilities.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
