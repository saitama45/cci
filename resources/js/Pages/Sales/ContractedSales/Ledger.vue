<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ChevronLeftIcon,
    ArrowDownTrayIcon,
    BuildingOfficeIcon,
    UserIcon,
    BanknotesIcon,
    CalendarIcon,
    DocumentTextIcon,
    ClockIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    sale: Object,
    ledger: Array,
    summary: Object
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const exportSOA = () => {
    window.open(route('contracted-sales.soa', props.sale.id), '_blank');
};
</script>

<template>
    <Head :title="`Ledger: ${sale.contract_no} - Horizon ERP`" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="flex items-center space-x-4">
                    <Link :href="route('contracted-sales.show', sale.id)" class="p-2 bg-white rounded-xl border border-slate-200 text-slate-400 hover:text-indigo-600 transition-all shadow-sm">
                        <ChevronLeftIcon class="w-5 h-5" />
                    </Link>
                    <div>
                        <h2 class="font-black text-2xl text-slate-900 leading-none tracking-tight">Customer Ledger</h2>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1.5">Full Transaction History for {{ sale.contract_no }}</p>
                    </div>
                </div>
                <div class="mt-4 md:mt-0 flex space-x-2">
                    <button @click="exportSOA" class="bg-slate-900 text-white px-6 py-2.5 rounded-xl hover:bg-slate-800 transition-all flex items-center space-x-2 text-xs font-black uppercase tracking-widest shadow-xl shadow-slate-200">
                        <ArrowDownTrayIcon class="w-4 h-4" />
                        <span>Download SOA</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Summary Header -->
                <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 p-8">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        <div class="col-span-2 md:col-span-3 lg:col-span-2 flex items-center space-x-4 mb-4 lg:mb-0">
                            <div class="w-16 h-16 bg-indigo-50 rounded-2xl flex items-center justify-center shrink-0">
                                <UserIcon class="w-8 h-8 text-indigo-600" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Customer Name</p>
                                <h3 class="text-xl font-black text-slate-900 truncate">{{ sale.customer.full_name }}</h3>
                                <div class="flex items-center space-x-2 mt-0.5">
                                    <span class="text-[10px] font-bold text-indigo-600 truncate">{{ sale.unit.project.name }}</span>
                                    <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                                    <span class="text-[10px] font-bold text-slate-500">Unit {{ sale.unit.name }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 flex flex-col justify-center">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Paid (Equity)</p>
                            <p class="text-sm font-black text-slate-900">{{ formatCurrency(summary.equity_paid) }}</p>
                        </div>
                        <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-100 flex flex-col justify-center">
                            <p class="text-[9px] font-black text-emerald-600 uppercase tracking-widest mb-1">Total Paid (Amort)</p>
                            <p class="text-sm font-black text-emerald-700">{{ formatCurrency(summary.amort_paid) }}</p>
                        </div>
                        <div class="bg-amber-50 rounded-2xl p-4 border border-amber-100 flex flex-col justify-center">
                            <p class="text-[9px] font-black text-amber-600 uppercase tracking-widest mb-1">In-Vault (PDC)</p>
                            <p class="text-sm font-black text-amber-700">{{ formatCurrency(summary.pending_pdcs) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Main Ledger Table -->
                <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-10 py-6 border-b border-slate-50 flex items-center justify-between">
                        <h3 class="font-black text-slate-900 uppercase tracking-tight text-xs">Ledger Timeline</h3>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-1.5">
                                <div class="w-2 h-2 rounded-full bg-slate-100 border border-slate-300"></div>
                                <span class="text-[9px] font-black text-slate-400 uppercase">Assessment</span>
                            </div>
                            <div class="flex items-center space-x-1.5">
                                <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-lg shadow-emerald-200"></div>
                                <span class="text-[9px] font-black text-slate-400 uppercase">Payment</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th class="px-10 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Date</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Description</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Debit (+)</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest text-emerald-600">Credit (-)</th>
                                    <th class="px-10 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest bg-slate-100">Balance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="(item, index) in ledger" :key="index" :class="[
                                    'group transition-all duration-300',
                                    item.credit > 0 ? 'bg-emerald-50/20 hover:bg-emerald-50/40' : 'hover:bg-slate-50/50'
                                ]">
                                    <td class="px-10 py-5 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-black text-slate-900">{{ formatDate(item.transaction_date) }}</span>
                                            <span class="text-[9px] text-slate-400 font-bold uppercase">{{ item.status || 'Posted' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex items-center space-x-3">
                                            <div :class="[
                                                'w-8 h-8 rounded-lg flex items-center justify-center transition-all shadow-sm',
                                                item.credit > 0 ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-400'
                                            ]">
                                                <BanknotesIcon v-if="item.credit > 0" class="w-4 h-4" />
                                                <DocumentTextIcon v-else class="w-4 h-4" />
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-slate-700">{{ item.description }}</span>
                                                <span v-if="item.is_pdc" class="text-[9px] font-black text-amber-600 uppercase tracking-tighter">PDC (Pending Maturity)</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 text-right whitespace-nowrap text-sm font-black text-slate-900">
                                        {{ item.debit > 0 ? formatCurrency(item.debit) : '-' }}
                                    </td>
                                    <td class="px-6 py-5 text-right whitespace-nowrap text-sm font-black text-emerald-600">
                                        {{ item.credit > 0 ? formatCurrency(item.credit) : '-' }}
                                    </td>
                                    <td class="px-10 py-5 text-right whitespace-nowrap text-sm font-black text-slate-900 bg-slate-50/50 group-hover:bg-slate-50 transition-colors">
                                        {{ formatCurrency(item.running_balance) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-900 text-white">
                                <tr>
                                    <td colspan="4" class="px-10 py-8 text-sm font-bold text-slate-400 uppercase tracking-widest text-right border-r border-white/5">
                                        Current Outstanding Exposure
                                    </td>
                                    <td class="px-10 py-8 text-2xl font-black text-right text-rose-400">
                                        {{ formatCurrency(summary.outstanding_balance) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Footer Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-8 rounded-[2rem] border border-slate-200 flex items-start space-x-4">
                        <InformationCircleIcon class="w-6 h-6 text-indigo-500 flex-shrink-0" />
                        <div>
                            <h4 class="text-xs font-black text-slate-900 uppercase tracking-widest mb-2">Ledger Logic</h4>
                            <p class="text-xs text-slate-500 leading-relaxed italic">The ledger tracks all assessments (Debits) and receipts (Credits). PDCs are listed as Credits but marked as "Pending" to provide a realistic view of the future balance. Finalized balance is achieved once all PDCs are cleared in Bank Reconciliation.</p>
                        </div>
                    </div>
                    
                    <div class="bg-indigo-600 p-8 rounded-[2rem] text-white shadow-xl shadow-indigo-200 flex items-center justify-between overflow-hidden relative">
                        <div class="relative z-10">
                            <h4 class="text-xs font-bold text-indigo-200 uppercase tracking-widest mb-1">Contract Health</h4>
                            <p class="text-2xl font-black">Reconciled & Active</p>
                        </div>
                        <CheckCircleIcon class="w-20 h-20 text-white/10 absolute right-[-10px] bottom-[-10px]" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
