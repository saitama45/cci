<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    CalendarDaysIcon,
    ArrowDownTrayIcon,
    TableCellsIcon,
    DocumentTextIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    CreditCardIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    ledger_lines: Array,
    accounts: Array,
    filters: Object
});

const accountId = ref(props.filters.account_id || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const applyFilters = () => {
    router.get(route('accounting.general-ledger'), {
        account_id: accountId.value,
        start_date: startDate.value,
        end_date: endDate.value
    }, { preserveState: true, preserveScroll: true });
};

const exportPdf = () => {
    const url = route('accounting.general-ledger.export', {
        account_id: accountId.value,
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

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-PH', {
        month: 'short',
        day: '2-digit',
        year: 'numeric'
    });
};

const clearFilters = () => {
    accountId.value = '';
    startDate.value = '';
    endDate.value = '';
    applyFilters();
};

// Group lines by account for a "Book" feel
const groupedLedger = computed(() => {
    const groups = {};
    props.ledger_lines.forEach(line => {
        const accountKey = `${line.chart_of_account.code} - ${line.chart_of_account.name}`;
        if (!groups[accountKey]) {
            groups[accountKey] = {
                account: line.chart_of_account,
                lines: [],
                total_debit: 0,
                total_credit: 0,
                running_balance: 0
            };
        }
        
        const debit = parseFloat(line.debit);
        const credit = parseFloat(line.credit);
        
        // Calculate running balance based on account type
        const isNormalDebit = ['asset', 'expense'].includes(line.chart_of_account.type);
        if (isNormalDebit) {
            groups[accountKey].running_balance += (debit - credit);
        } else {
            groups[accountKey].running_balance += (credit - debit);
        }
        
        groups[accountKey].lines.push({
            ...line,
            current_balance: groups[accountKey].running_balance
        });
        
        groups[accountKey].total_debit += debit;
        groups[accountKey].total_credit += credit;
    });
    return groups;
});
</script>

<template>
    <Head title="General Ledger - Accounting Reports" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">General Ledger</h2>
                    <p class="text-sm text-slate-500 mt-1">Detailed transaction history across all accounts.</p>
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
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex flex-col md:flex-row items-end space-y-4 md:space-y-0 md:space-x-4 mb-6 no-print transition-all hover:shadow-md">
                    <div class="flex-1 w-full">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Select Account</label>
                        <div class="relative">
                            <CreditCardIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <select v-model="accountId" class="pl-10 w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all text-sm font-bold text-slate-700">
                                <option value="">All Accounts</option>
                                <option v-for="account in accounts" :key="account.id" :value="account.id">
                                    {{ account.code }} - {{ account.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="flex-1 w-full">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Start Date</label>
                        <div class="relative">
                            <CalendarDaysIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input v-model="startDate" type="date" class="pl-10 w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all text-sm font-bold text-slate-700">
                        </div>
                    </div>
                    <div class="flex-1 w-full">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">End Date</label>
                        <div class="relative">
                            <CalendarDaysIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input v-model="endDate" type="date" class="pl-10 w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all text-sm font-bold text-slate-700">
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button @click="applyFilters" class="bg-blue-600 text-white px-6 py-2.5 rounded-2xl hover:bg-blue-700 transition-all font-black text-sm shadow-xl shadow-blue-600/20">Apply Filters</button>
                        <button @click="clearFilters" class="bg-slate-100 text-slate-600 px-4 py-2.5 rounded-2xl hover:bg-slate-200 transition-all font-black text-sm">Reset</button>
                    </div>
                </div>

                <!-- Ledger Sections -->
                <div v-if="Object.keys(groupedLedger).length === 0" class="bg-white rounded-3xl p-12 text-center border border-slate-100 shadow-sm">
                    <TableCellsIcon class="w-16 h-16 text-slate-200 mx-auto mb-4" />
                    <h3 class="text-xl font-bold text-slate-900">No Ledger Transactions Found</h3>
                    <p class="text-slate-500 mt-2">Adjust your filters or record some transactions to see data here.</p>
                </div>

                <div v-else class="space-y-8">
                    <div v-for="(group, accountName) in groupedLedger" :key="accountName" class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden transition-all hover:shadow-md">
                        <div class="px-8 py-5 border-b border-slate-50 bg-slate-50/50 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-blue-600 rounded-xl">
                                    <DocumentTextIcon class="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <h3 class="text-base font-black text-slate-900 tracking-tight">{{ accountName }}</h3>
                                    <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 tracking-tighter">{{ group.account.type }}</span>
                                </div>
                            </div>
                            <div class="flex space-x-8 text-sm">
                                <div class="flex flex-col items-end">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Activity</span>
                                    <div class="flex space-x-3 font-bold text-[11px]">
                                        <span class="text-blue-600">DR: {{ formatCurrency(group.total_debit) }}</span>
                                        <span class="text-rose-600">CR: {{ formatCurrency(group.total_credit) }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end border-l border-slate-200 pl-8">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Net Balance</span>
                                    <span class="text-lg font-black text-slate-900">{{ formatCurrency(group.running_balance) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50/30">
                                        <th class="px-8 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Date</th>
                                        <th class="px-8 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Ref #</th>
                                        <th class="px-8 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Description / Memo</th>
                                        <th class="px-8 py-3 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Debit</th>
                                        <th class="px-8 py-3 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Credit</th>
                                        <th class="px-8 py-3 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Balance</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="line in group.lines" :key="line.id" class="hover:bg-slate-50/20 transition-colors">
                                        <td class="px-8 py-4 text-xs font-bold text-slate-500 whitespace-nowrap">{{ formatDate(line.journal_entry.transaction_date) }}</td>
                                        <td class="px-8 py-4 text-xs font-black text-blue-600 tracking-tighter">{{ line.journal_entry.reference_no || '-' }}</td>
                                        <td class="px-8 py-4 text-sm text-slate-700">
                                            <div class="font-bold leading-tight">{{ line.journal_entry.description }}</div>
                                            <div class="text-[10px] text-slate-400 italic font-medium mt-0.5" v-if="line.memo">{{ line.memo }}</div>
                                        </td>
                                        <td class="px-8 py-4 text-sm text-right font-black text-slate-800">
                                            {{ line.debit > 0 ? formatCurrency(line.debit) : '-' }}
                                        </td>
                                        <td class="px-8 py-4 text-sm text-right font-black text-slate-800">
                                            {{ line.credit > 0 ? formatCurrency(line.credit) : '-' }}
                                        </td>
                                        <td class="px-8 py-4 text-sm text-right font-black text-blue-600 bg-blue-50/30">
                                            {{ formatCurrency(line.current_balance) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    .no-print { display: none !important; }
}
</style>
