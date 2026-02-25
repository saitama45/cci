<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    CalendarDaysIcon,
    ArrowDownTrayIcon,
    ClockIcon,
    UserCircleIcon,
    ChevronRightIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    report_data: Array,
    filters: Object,
    totals: Object
});

const asOfDate = ref(props.filters.as_of_date || new Date().toISOString().split('T')[0]);

const applyFilters = () => {
    router.get(route('accounting.ar-aging'), {
        as_of_date: asOfDate.value
    }, { preserveState: true, preserveScroll: true });
};

const exportPdf = () => {
    const url = route('accounting.ar-aging.export', {
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

const formatDate = (date) => {
    if (!date || date === 'No Payment') return 'No Payment';
    return new Date(date).toLocaleDateString('en-PH', {
        month: 'short',
        day: '2-digit',
        year: 'numeric'
    });
};

const getBracketColor = (bracket) => {
    switch (bracket) {
        case 'Current': return 'bg-emerald-50 text-emerald-700';
        case '1-30 Days': return 'bg-amber-50 text-amber-700';
        case '31-60 Days': return 'bg-orange-50 text-orange-700';
        case '61-90 Days': return 'bg-rose-50 text-rose-700';
        default: return 'bg-red-50 text-red-700';
    }
};
</script>

<template>
    <Head title="Aging Report - Accounting" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Accounts Aging Report</h2>
                    <p class="text-sm text-slate-500 mt-1">Classification of receivables based on time overdue.</p>
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
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex flex-col md:flex-row items-end space-y-4 md:space-y-0 md:space-x-4 mb-6 transition-all hover:shadow-md">
                    <div class="w-full md:w-64">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">As Of Date</label>
                        <div class="relative">
                            <CalendarDaysIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input v-model="asOfDate" type="date" class="pl-10 w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all text-sm font-bold text-slate-700">
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button @click="applyFilters" class="bg-blue-600 text-white px-6 py-2.5 rounded-2xl hover:bg-blue-700 transition-all font-black text-sm shadow-xl shadow-blue-600/20">Generate Report</button>
                    </div>
                </div>

                <!-- Aging Summary Table -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50">
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">#</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Customer Name</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Unit / Lot</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Last Pay Date</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Not Yet Due</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">1-30</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">31-60</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">61-90</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">91-120</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">120 Over</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 bg-rose-50/30">Total Due</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Outstanding</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Aging Days</th>
                                    <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Bracket</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="(row, idx) in report_data" :key="row.contract_id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 text-[11px] font-bold text-slate-400">{{ idx + 1 }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="p-1.5 bg-blue-50 rounded-lg">
                                                <UserCircleIcon class="w-4 h-4 text-blue-500" />
                                            </div>
                                            <span class="text-xs font-black text-slate-900 tracking-tight">{{ row.customer_name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-[10px] font-bold text-slate-600 truncate max-w-[150px] block">{{ row.unit_name }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-[10px] font-bold text-slate-500 whitespace-nowrap">{{ formatDate(row.last_pay_date) }}</td>
                                    <td class="px-6 py-4 text-xs text-right font-bold text-slate-400">{{ formatCurrency(row.not_yet_due) }}</td>
                                    <td class="px-6 py-4 text-xs text-right font-black text-slate-700" :class="{'text-rose-600': row['1_30'] > 0}">{{ row['1_30'] > 0 ? formatCurrency(row['1_30']) : '-' }}</td>
                                    <td class="px-6 py-4 text-xs text-right font-black text-slate-700" :class="{'text-rose-600': row['31_60'] > 0}">{{ row['31_60'] > 0 ? formatCurrency(row['31_60']) : '-' }}</td>
                                    <td class="px-6 py-4 text-xs text-right font-black text-slate-700" :class="{'text-rose-600': row['61_90'] > 0}">{{ row['61_90'] > 0 ? formatCurrency(row['61_90']) : '-' }}</td>
                                    <td class="px-6 py-4 text-xs text-right font-black text-slate-700" :class="{'text-rose-600': row['91_120'] > 0}">{{ row['91_120'] > 0 ? formatCurrency(row['91_120']) : '-' }}</td>
                                    <td class="px-6 py-4 text-xs text-right font-black text-slate-700" :class="{'text-rose-600': row['120_over'] > 0}">{{ row['120_over'] > 0 ? formatCurrency(row['120_over']) : '-' }}</td>
                                    <td class="px-6 py-4 text-xs text-right font-black text-rose-600 bg-rose-50/20">{{ formatCurrency(row.total_due) }}</td>
                                    <td class="px-6 py-4 text-xs text-right font-black text-slate-900">{{ formatCurrency(row.outstanding_balance) }}</td>
                                    <td class="px-6 py-4 text-xs text-right font-black" :class="row.aging_days > 0 ? 'text-rose-600' : 'text-slate-400'">{{ Math.floor(row.aging_days) }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span :class="['px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-tighter border', getBracketColor(row.aging_bracket)]">
                                            {{ row.aging_bracket }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-900 text-white">
                                <tr class="font-black text-xs uppercase tracking-widest">
                                    <td colspan="4" class="px-6 py-6 text-right">Grand Totals</td>
                                    <td class="px-6 py-6 text-right border-l border-white/10">{{ formatCurrency(totals.not_yet_due) }}</td>
                                    <td class="px-6 py-6 text-right border-l border-white/10">{{ formatCurrency(totals['1_30']) }}</td>
                                    <td class="px-6 py-6 text-right border-l border-white/10">{{ formatCurrency(totals['31_60']) }}</td>
                                    <td class="px-6 py-6 text-right border-l border-white/10">{{ formatCurrency(totals['61_90']) }}</td>
                                    <td class="px-6 py-6 text-right border-l border-white/10">{{ formatCurrency(totals['91_120']) }}</td>
                                    <td class="px-6 py-6 text-right border-l border-white/10">{{ formatCurrency(totals['120_over']) }}</td>
                                    <td class="px-6 py-6 text-right border-l border-white/10 bg-rose-600">{{ formatCurrency(totals.total_due) }}</td>
                                    <td class="px-6 py-6 text-right border-l border-white/10">{{ formatCurrency(totals.outstanding_balance) }}</td>
                                    <td colspan="2" class="px-6 py-6"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div v-if="report_data.length === 0" class="bg-white rounded-3xl p-12 text-center border border-slate-100 shadow-sm">
                    <ClockIcon class="w-16 h-16 text-slate-200 mx-auto mb-4" />
                    <h3 class="text-xl font-bold text-slate-900">No Receivables Found</h3>
                    <p class="text-slate-500 mt-2">There are no active contracts with outstanding balances as of this date.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
