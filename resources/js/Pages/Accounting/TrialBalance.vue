<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    CalendarDaysIcon,
    ArrowDownTrayIcon,
    ScaleIcon,
    CurrencyDollarIcon,
    FunnelIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    accounts: Array,
    filters: Object,
    totals: Object
});

const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const applyFilters = () => {
    router.get(route('accounting.trial-balance'), {
        start_date: startDate.value,
        end_date: endDate.value
    }, { preserveState: true, preserveScroll: true });
};

const exportPdf = () => {
    const url = route('accounting.trial-balance.export', {
        start_date: startDate.value,
        end_date: endDate.value
    });
    window.open(url, '_blank');
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value || 0);
};

const clearFilters = () => {
    startDate.value = '';
    endDate.value = '';
    applyFilters();
};
</script>

<template>
    <Head title="Trial Balance - Accounting Reports" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Trial Balance</h2>
                    <p class="text-sm text-slate-500 mt-1">Summary of all ledger balances as of period end.</p>
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
                <!-- Print Header (Only visible in print) -->
                <div class="hidden print-header p-8 border-b-4 border-slate-900 mb-8">
                    <div class="flex justify-between items-end">
                        <div>
                            <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tighter">Trial Balance Report</h1>
                            <p class="text-sm font-bold text-slate-500 mt-1 uppercase tracking-widest">Period: {{ filters.start_date }} to {{ filters.end_date }}</p>
                        </div>
                        <div class="text-right">
                            <div class="text-xl font-black text-blue-600 tracking-tighter">Horizon ERP</div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Generated: {{ new Date().toLocaleString() }}</div>
                        </div>
                    </div>
                </div>
                <!-- Filters Bar -->
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col md:flex-row items-end space-y-4 md:space-y-0 md:space-x-4 mb-6 no-print">
                    <div class="flex-1">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Start Date</label>
                        <div class="relative">
                            <CalendarDaysIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input v-model="startDate" type="date" class="pl-10 w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all text-sm font-semibold text-slate-700">
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">End Date</label>
                        <div class="relative">
                            <CalendarDaysIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input v-model="endDate" type="date" class="pl-10 w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all text-sm font-semibold text-slate-700">
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button @click="applyFilters" class="bg-blue-600 text-white px-6 py-2 rounded-xl hover:bg-blue-700 transition-all font-bold text-sm shadow-lg shadow-blue-600/20">Apply Filters</button>
                        <button @click="clearFilters" class="bg-slate-100 text-slate-600 px-4 py-2 rounded-xl hover:bg-slate-200 transition-all font-bold text-sm">Clear</button>
                    </div>
                </div>

                <!-- Trial Balance Table -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-8 py-6 border-b border-slate-50 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-indigo-50 rounded-xl">
                                <ScaleIcon class="w-6 h-6 text-indigo-600" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 leading-tight">Ledger Summary</h3>
                                <p class="text-xs text-slate-500 font-medium">Verified double-entry totals</p>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50">
                                    <th class="px-8 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100">Account Code</th>
                                    <th class="px-8 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100">Account Name</th>
                                    <th class="px-8 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100">Type</th>
                                    <th class="px-8 py-4 text-right text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100">Debit</th>
                                    <th class="px-8 py-4 text-right text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100">Credit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="account in accounts" :key="account.id" class="hover:bg-slate-50/30 transition-colors">
                                    <td class="px-8 py-4 text-sm font-black text-slate-700 tracking-tight">{{ account.code }}</td>
                                    <td class="px-8 py-4 text-sm font-bold text-slate-900">{{ account.name }}</td>
                                    <td class="px-8 py-4">
                                        <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-slate-100 text-slate-500 tracking-tighter">{{ account.type }}</span>
                                    </td>
                                    <td class="px-8 py-4 text-sm text-right font-bold text-slate-700">
                                        {{ account.total_debit > 0 ? formatCurrency(account.total_debit) : '-' }}
                                    </td>
                                    <td class="px-8 py-4 text-sm text-right font-bold text-slate-700">
                                        {{ account.total_credit > 0 ? formatCurrency(account.total_credit) : '-' }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-900 text-white">
                                    <td colspan="3" class="px-8 py-6 text-sm font-black uppercase tracking-widest">Grand Totals</td>
                                    <td class="px-8 py-6 text-base text-right font-black border-l border-white/10">
                                        {{ formatCurrency(totals.debit) }}
                                    </td>
                                    <td class="px-8 py-6 text-base text-right font-black border-l border-white/10">
                                        {{ formatCurrency(totals.credit) }}
                                    </td>
                                </tr>
                                <tr v-if="totals.debit !== totals.credit" class="bg-rose-600 text-white text-center">
                                    <td colspan="5" class="py-2 text-[10px] font-black uppercase tracking-widest animate-pulse">
                                        Warning: Ledger is out of balance by {{ formatCurrency(Math.abs(totals.debit - totals.credit)) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    .no-print { display: none !important; }
    .print-header { display: block !important; }
    body { background: white !important; margin: 0; padding: 0; }
    .bg-white { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
    .py-6 { padding-top: 0 !important; }
    .max-w-7xl { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
    .sm\:px-6, .lg\:px-8 { padding: 0 !important; }
    thead tr { background-color: #f8fafc !important; -webkit-print-color-adjust: exact; }
    tfoot tr { background-color: #0f172a !important; color: white !important; -webkit-print-color-adjust: exact; }
    @page { margin: 1cm; }
}
</style>
