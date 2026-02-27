<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ArrowLeftIcon,
    PrinterIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
    BanknotesIcon,
    ClockIcon,
    UserIcon,
    ShieldCheckIcon
} from '@heroicons/vue/24/outline';
import { usePermission } from '@/Composables/usePermission';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    disbursement: Object
});

const { hasPermission } = usePermission();
const { showSuccess, showError } = useToast();
const { confirm } = useConfirm();

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const approveVoucher = async () => {
    const confirmed = await confirm({
        title: 'Approve Payment Voucher',
        message: `Are you sure you want to approve and post voucher ${props.disbursement.voucher_no}? This will record the disbursement in the General Ledger and cannot be reversed easily.`,
        confirmButtonText: 'Yes, Post & Approve',
        type: 'warning'
    });

    if (confirmed) {
        router.post(route('accounting.disbursements.approve', props.disbursement.id), {}, {
            onSuccess: () => {
                // Success toast is handled globally by AppLayout from flash messages
            },
            onError: (errors) => showError(errors.error || 'Failed to approve voucher.')
        });
    }
};

const printVoucher = () => {
    window.open(route('accounting.disbursements.print', props.disbursement.id), '_blank');
};

const printCheck = () => {
    window.open(route('accounting.disbursements.print-check', props.disbursement.id), '_blank');
};

const getStatusClass = (status) => {
    const baseClasses = 'px-3 py-1 rounded-full text-sm font-bold uppercase tracking-wider shadow-sm';
    switch (status) {
        case 'Draft': return `${baseClasses} bg-gray-100 text-gray-800 border-2 border-gray-200`;
        case 'Approved': return `${baseClasses} bg-blue-100 text-blue-800 border-2 border-blue-200`;
        case 'Paid': return `${baseClasses} bg-green-100 text-green-800 border-2 border-green-200`;
        case 'Cancelled': return `${baseClasses} bg-red-100 text-red-800 border-2 border-red-200`;
        default: return baseClasses;
    }
};
</script>

<template>
    <Head :title="`PV ${disbursement.voucher_no} - Horizon ERP`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link :href="route('accounting.disbursements.index')" class="text-slate-500 hover:text-slate-700">
                        <ArrowLeftIcon class="w-6 h-6" />
                    </Link>
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            Payment Voucher: {{ disbursement.voucher_no }}
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">Disbursement ID: {{ disbursement.id }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button 
                        v-if="['Check', 'PDC'].includes(disbursement.payment_method)"
                        @click="printCheck"
                        class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50 transition ease-in-out duration-150"
                    >
                        <PrinterIcon class="w-4 h-4 mr-2" />
                        Print Check
                    </button>
                    <button 
                        @click="printVoucher"
                        class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50 transition ease-in-out duration-150"
                    >
                        <PrinterIcon class="w-4 h-4 mr-2" />
                        Print PDF
                    </button>
                    <button 
                        v-if="disbursement.status === 'Draft' && hasPermission('accounting.view')"
                        @click="approveVoucher"
                        class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 shadow-lg shadow-emerald-200 transition ease-in-out duration-150"
                    >
                        <CheckCircleIcon class="w-4 h-4 mr-2" />
                        Post & Approve
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Status Banner -->
                <div :class="[
                    'p-6 rounded-xl border flex items-center justify-between transition-all duration-500',
                    disbursement.status === 'Approved' ? 'bg-blue-50 border-blue-200' : 'bg-slate-50 border-slate-200'
                ]">
                    <div class="flex items-center space-x-4">
                        <div :class="[
                            'w-12 h-12 rounded-full flex items-center justify-center shadow-inner',
                            disbursement.status === 'Approved' ? 'bg-blue-100 text-blue-600' : 'bg-slate-100 text-slate-400'
                        ]">
                            <ShieldCheckIcon v-if="disbursement.status === 'Approved'" class="w-7 h-7" />
                            <ClockIcon v-else class="w-7 h-7" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500">Current Status</p>
                            <h3 class="text-2xl font-black text-slate-900">{{ disbursement.status }}</h3>
                        </div>
                    </div>
                    <div v-if="disbursement.status === 'Draft'" class="hidden md:flex items-center text-amber-600 bg-amber-50 px-4 py-2 rounded-lg border border-amber-100">
                        <ExclamationCircleIcon class="w-5 h-5 mr-2" />
                        <span class="text-xs font-bold uppercase">Awaiting Approval to Post to GL</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Main Content (2 cols) -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Details Section -->
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="p-6 border-b border-slate-100">
                                <h3 class="text-lg font-bold text-slate-800">Voucher Information</h3>
                            </div>
                            <div class="p-0">
                                <table class="min-w-full">
                                    <tbody class="divide-y divide-slate-100">
                                        <tr>
                                            <td class="px-6 py-4 text-sm font-medium text-slate-500 bg-slate-50 w-1/3">Payee / Vendor</td>
                                            <td class="px-6 py-4 text-sm font-bold text-slate-900">{{ disbursement.vendor?.name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 text-sm font-medium text-slate-500 bg-slate-50">Payment Date</td>
                                            <td class="px-6 py-4 text-sm text-slate-900">{{ formatDate(disbursement.payment_date) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 text-sm font-medium text-slate-500 bg-slate-50">Payment Method</td>
                                            <td class="px-6 py-4 text-sm text-slate-900">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-slate-100 text-slate-700">
                                                    {{ disbursement.payment_method }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 text-sm font-medium text-slate-500 bg-slate-50">Source Account</td>
                                            <td class="px-6 py-4 text-sm text-slate-900">
                                                {{ disbursement.bank_account?.code }} - {{ disbursement.bank_account?.name }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 text-sm font-medium text-slate-500 bg-slate-50">Notes</td>
                                            <td class="px-6 py-4 text-sm text-slate-600 italic">{{ disbursement.notes || 'No remarks provided.' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Applied Bills Section -->
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                                <h3 class="text-lg font-bold text-slate-800">Payment Breakdown</h3>
                                <span class="px-3 py-1 rounded bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-100">
                                    {{ disbursement.items?.length }} Bills Paid
                                </span>
                            </div>
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Bill Number</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Bill Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Amount Paid</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-100">
                                    <tr v-for="item in disbursement.items" :key="item.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                                            {{ item.bill?.bill_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-500">
                                                {{ item.bill?.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 font-bold text-right">
                                            {{ formatCurrency(item.amount) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-slate-900">
                                    <tr>
                                        <td colspan="2" class="px-6 py-4 text-sm font-bold text-slate-300 text-right uppercase">Total Voucher Amount</td>
                                        <td class="px-6 py-4 text-lg font-black text-white text-right">{{ formatCurrency(disbursement.total_amount) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- GL Entry Visualization (If Approved) -->
                        <div v-if="disbursement.journal_entry" class="bg-slate-900 rounded-xl shadow-2xl overflow-hidden border border-slate-800">
                            <div class="p-6 border-b border-slate-800 flex justify-between items-center">
                                <h3 class="text-lg font-bold text-white flex items-center">
                                    <ShieldCheckIcon class="w-5 h-5 mr-2 text-blue-400" />
                                    General Ledger Impact
                                </h3>
                                <span class="text-xs text-slate-400 font-mono">Reference: {{ disbursement.journal_entry.id }}</span>
                            </div>
                            <div class="p-0">
                                <table class="min-w-full">
                                    <thead class="bg-slate-800">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase">Account</th>
                                            <th class="px-6 py-3 text-right text-xs font-bold text-slate-400 uppercase">Debit</th>
                                            <th class="px-6 py-3 text-right text-xs font-bold text-slate-400 uppercase">Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800">
                                        <tr v-for="line in disbursement.journal_entry.lines" :key="line.id" class="hover:bg-slate-800/50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-bold text-slate-200">{{ line.chart_of_account?.name }}</span>
                                                    <span class="text-xs text-slate-500 font-mono">{{ line.chart_of_account?.code }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm font-bold text-emerald-400">
                                                {{ line.debit > 0 ? formatCurrency(line.debit) : '-' }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm font-bold text-red-400">
                                                {{ line.credit > 0 ? formatCurrency(line.credit) : '-' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar Info (1 col) -->
                    <div class="space-y-6">
                        
                        <!-- PDC Info Card -->
                        <div v-if="disbursement.payment_method === 'PDC' && disbursement.pdc_detail" class="bg-amber-50 border border-amber-200 rounded-xl p-6">
                            <h3 class="text-md font-bold text-amber-900 mb-4 flex items-center">
                                <ClockIcon class="w-5 h-5 mr-2" />
                                PDC Vault Details
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-[10px] text-amber-600 font-bold uppercase">Check Number</p>
                                    <p class="text-lg font-black text-amber-900">{{ disbursement.pdc_detail.check_no }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-[10px] text-amber-600 font-bold uppercase">Maturity Date</p>
                                        <p class="text-sm font-bold text-amber-900">{{ formatDate(disbursement.pdc_detail.check_date) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-amber-600 font-bold uppercase">Vault Status</p>
                                        <span class="px-2 py-0.5 rounded-full bg-amber-200 text-amber-800 text-[10px] font-black uppercase">
                                            {{ disbursement.pdc_detail.status }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[10px] text-amber-600 font-bold uppercase">Bank</p>
                                    <p class="text-sm font-bold text-amber-900">
                                        {{ disbursement.pdc_detail.bank_name }} 
                                        <span v-if="disbursement.pdc_detail.bank_branch" class="text-amber-700 font-normal">({{ disbursement.pdc_detail.bank_branch }})</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Audit Trail -->
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                            <h3 class="text-md font-bold text-slate-800 mb-4 uppercase tracking-wider text-xs">Internal Control</h3>
                            <div class="space-y-4">
                                <div class="flex items-start space-x-3">
                                    <div class="mt-1 w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">
                                        <UserIcon class="w-4 h-4 text-slate-500" />
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400">Prepared By</p>
                                        <p class="text-sm font-bold text-slate-700">{{ disbursement.prepared_by?.name || 'Unknown' }}</p>
                                        <p class="text-[10px] text-slate-400">{{ disbursement.created_at }}</p>
                                    </div>
                                </div>
                                
                                <div v-if="disbursement.approved_by" class="flex items-start space-x-3">
                                    <div class="mt-1 w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                        <ShieldCheckIcon class="w-4 h-4 text-blue-600" />
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400">Approved By</p>
                                        <p class="text-sm font-bold text-slate-700">{{ disbursement.approved_by?.name }}</p>
                                        <p class="text-[10px] text-slate-400">{{ disbursement.updated_at }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
