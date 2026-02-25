<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import { usePermission } from '@/Composables/usePermission';
import { 
    ChevronLeftIcon,
    PrinterIcon,
    PencilSquareIcon,
    TrashIcon,
    CheckCircleIcon,
    XCircleIcon,
    DocumentPlusIcon,
    DocumentDuplicateIcon,
    ArrowRightIcon,
    UserCircleIcon,
    BuildingOfficeIcon,
    ReceiptPercentIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    bill: Object,
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

const getStatusClass = (status) => {
    const baseClasses = 'px-3 py-1 rounded-full text-sm font-bold uppercase tracking-wider';
    switch (status) {
        case 'Draft': return `${baseClasses} bg-gray-100 text-gray-800 border border-gray-300 shadow-sm`;
        case 'Approved': return `${baseClasses} bg-blue-100 text-blue-800 border border-blue-200 shadow-sm shadow-blue-50`;
        case 'Paid': return `${baseClasses} bg-green-100 text-green-800 border border-green-200 shadow-sm shadow-green-50`;
        case 'Partial': return `${baseClasses} bg-yellow-100 text-yellow-800 border border-yellow-200 shadow-sm shadow-yellow-50`;
        case 'Overdue': return `${baseClasses} bg-red-100 text-red-800 border border-red-200 shadow-sm shadow-red-50`;
        case 'Cancelled': return `${baseClasses} bg-gray-300 text-gray-500 border border-gray-400 shadow-sm`;
        default: return baseClasses;
    }
};

const updateStatus = async (status) => {
    const isApproval = status === 'Approved';
    const isCancellation = status === 'Cancelled';

    const confirmed = await confirm({
        title: `${status} ${props.bill.type}`,
        message: isCancellation 
            ? `Are you sure you want to void this ${props.bill.type.toLowerCase()}? This will create a REVERSING journal entry to ${props.bill.type === 'Debit Memo' ? 'RE-ESTABLISH the liability' : 'void the liability'}.`
            : `Are you sure you want to change the status of this ${props.bill.type.toLowerCase()} to ${status}?`,
        type: isApproval ? 'success' : (isCancellation ? 'danger' : 'warning'),
        confirmButtonText: isApproval ? 'Yes, Approve & Post' : (isCancellation ? `Yes, Void ${props.bill.type}` : 'Confirm'),
        cancelButtonText: 'No, Go Back'
    });

    if (confirmed) {
        router.put(route('accounting.bills.update', props.bill.id), { status }, {
            onSuccess: () => {
                // Success message handled globally
            },
            onError: (errors) => showError(errors.error || 'Failed to update bill status.')
        });
    }
};

const deleteBill = async () => {
    const confirmed = await confirm({
        title: 'Delete Bill',
        message: 'Are you sure you want to delete this draft bill? This action cannot be undone.'
    });

    if (confirmed) {
        router.delete(route('accounting.bills.destroy', props.bill.id), {
            onSuccess: () => {
                // Success message handled globally
            },
            onError: (errors) => showError('Failed to delete bill.')
        });
    }
};
</script>

<template>
    <Head :title="`Bill #${bill.bill_number} - Horizon ERP`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link :href="route('accounting.bills.index')" class="text-indigo-600 hover:text-indigo-900 transition-colors">
                        <ChevronLeftIcon class="w-6 h-6" />
                    </Link>
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            Bill Details
                        </h2>
                        <p class="text-xs text-gray-400 mt-0.5">Reference No: {{ bill.bill_number }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 transition ease-in-out duration-150">
                        <PrinterIcon class="w-4 h-4 mr-2" />
                        Print
                    </button>
                    
                    <template v-if="bill.status === 'Draft'">
                        <Link
                            v-if="hasPermission('bills.edit')"
                            :href="route('accounting.bills.edit', bill.id)"
                            class="inline-flex items-center px-4 py-2 bg-white border border-amber-300 rounded-md font-semibold text-xs text-amber-700 uppercase tracking-widest shadow-sm hover:bg-amber-50 transition ease-in-out duration-150"
                        >
                            <PencilSquareIcon class="w-4 h-4 mr-2" />
                            Edit
                        </Link>
                        <button
                            @click="deleteBill"
                            class="inline-flex items-center px-4 py-2 bg-white border border-red-300 rounded-md font-semibold text-xs text-red-700 uppercase tracking-widest shadow-sm hover:bg-red-50 transition ease-in-out duration-150"
                        >
                            <TrashIcon class="w-4 h-4 mr-2" />
                            Delete
                        </button>
                        <button
                            @click="updateStatus('Approved')"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 shadow-md transition ease-in-out duration-150"
                        >
                            <CheckCircleIcon class="w-4 h-4 mr-2" />
                            Approve & Post
                        </button>
                    </template>

                    <template v-if="bill.status === 'Approved'">
                        <Link
                            v-if="hasPermission('bills.debit_memo') && bill.type === 'Bill'"
                            :href="route('accounting.bills.create', { source_id: bill.id, type: 'Debit Memo' })"
                            class="inline-flex items-center px-4 py-2 bg-white border border-amber-300 rounded-md font-semibold text-xs text-amber-700 uppercase tracking-widest shadow-sm hover:bg-amber-50 transition ease-in-out duration-150"
                        >
                            <DocumentPlusIcon class="w-4 h-4 mr-2" />
                            Create Debit Memo
                        </Link>
                        <button
                            v-if="hasPermission('bills.cancel')"
                            @click="updateStatus('Cancelled')"
                            class="inline-flex items-center px-4 py-2 bg-white border border-red-300 rounded-md font-semibold text-xs text-red-700 uppercase tracking-widest shadow-sm hover:bg-red-50 transition ease-in-out duration-150"
                        >
                            <XCircleIcon class="w-4 h-4 mr-2" />
                            Void {{ bill.type }}
                        </button>
                    </template>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Status & High-Level Summary -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex flex-wrap justify-between items-center gap-6">
                    <div class="flex items-center gap-6">
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-widest mb-1 italic">Type</p>
                            <span :class="bill.type === 'Debit Memo' ? 'text-amber-700 font-black' : 'text-indigo-700 font-black'">
                                {{ bill.type.toUpperCase() }}
                            </span>
                        </div>
                        <div class="h-10 w-px bg-gray-100 hidden md:block"></div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-widest mb-1">Status</p>
                            <span :class="getStatusClass(bill.status)">{{ bill.status }}</span>
                        </div>
                        <div class="h-10 w-px bg-gray-100 hidden md:block"></div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-widest mb-1 italic">Total Amount</p>
                            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(bill.total_amount) }}</p>
                        </div>
                    </div>
                    
                    <div class="flex flex-col text-right">
                        <p class="text-xs text-gray-400 uppercase font-bold tracking-widest mb-1">Posted By</p>
                        <div class="flex items-center justify-end text-sm text-gray-700 font-medium">
                            <UserCircleIcon class="w-4 h-4 mr-1 text-gray-400" />
                            {{ bill.creator?.name || 'System' }}
                        </div>
                        <p class="text-[10px] text-gray-400 italic">on {{ new Date(bill.created_at).toLocaleDateString() }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Bill Information -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                            <div class="p-6 border-b border-gray-100 flex items-center">
                                <BuildingOfficeIcon class="w-5 h-5 mr-2 text-indigo-500" />
                                <h3 class="font-bold text-gray-900 uppercase tracking-wide">General Information</h3>
                            </div>
                            <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-10">
                                <div class="space-y-6">
                                    <div class="group">
                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-tighter mb-1">Vendor</p>
                                        <p class="text-lg font-bold text-indigo-700 underline decoration-indigo-200 decoration-2 underline-offset-4">{{ bill.vendor?.name }}</p>
                                        <p v-if="bill.vendor?.tin" class="text-xs text-gray-500 mt-1 italic">TIN: {{ bill.vendor.tin }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-tighter mb-1">Main Project Tag</p>
                                        <p class="text-sm font-medium text-gray-700 bg-gray-50 px-2 py-1 rounded-md border border-gray-100 inline-block">
                                            {{ bill.project?.name || 'No Project Tagged' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="space-y-6">
                                    <div class="grid grid-cols-2 gap-6">
                                        <div>
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-tighter mb-1 italic text-indigo-600">Bill Date</p>
                                            <p class="text-sm font-semibold text-gray-900 bg-indigo-50 px-2 py-1 rounded border border-indigo-100">{{ formatDate(bill.bill_date) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-tighter mb-1 italic text-red-600">Due Date</p>
                                            <p class="text-sm font-semibold text-gray-900 bg-red-50 px-2 py-1 rounded border border-red-100">{{ formatDate(bill.due_date) }}</p>
                                        </div>
                                    </div>
                                    <div v-if="bill.notes">
                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-tighter mb-1">Internal Notes</p>
                                        <div class="bg-yellow-50/50 p-3 rounded-lg border border-yellow-100 italic text-sm text-gray-600">
                                            "{{ bill.notes }}"
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bill Items Table -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                            <div class="p-6 border-b border-gray-100 flex items-center bg-gray-50/30">
                                <ReceiptPercentIcon class="w-5 h-5 mr-2 text-indigo-500" />
                                <h3 class="font-bold text-gray-900 uppercase tracking-wide">
                                    {{ bill.type === 'Debit Memo' ? 'Adjustment Distribution' : 'Expense Distribution' }}
                                </h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">Account / Code</th>
                                            <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">Description</th>
                                            <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">Project Tag</th>
                                            <th class="px-6 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-widest">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        <tr v-for="item in bill.items" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-700">
                                                <span class="text-xs text-gray-400 mr-1">[{{ item.chart_of_account?.code }}]</span>
                                                {{ item.chart_of_account?.name }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600 italic">
                                                {{ item.description }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-medium">
                                                {{ item.project?.name || '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">
                                                {{ formatCurrency(item.amount) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-gray-50/50">
                                        <tr>
                                            <td colspan="3" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-widest">
                                                {{ bill.type === 'Debit Memo' ? 'Total Reduction' : 'Grand Total' }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-xl font-black text-indigo-700">{{ formatCurrency(bill.total_amount) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Side Panel: Accounting Impact -->
                    <div class="space-y-6">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                                <div class="flex items-center">
                                    <InformationCircleIcon class="w-5 h-5 mr-2 text-indigo-500" />
                                    <h3 class="font-bold text-gray-900 uppercase tracking-wide">Ledger Impact</h3>
                                </div>
                                <Link 
                                    v-if="bill.journal_entry_id"
                                    :href="route('journal-entries.show', bill.journal_entry_id)"
                                    class="text-xs text-indigo-600 hover:underline font-bold uppercase"
                                >
                                    JE #{{ bill.journal_entry_id }}
                                </Link>
                            </div>
                            <div class="p-6">
                                <div v-if="bill.journal_entry" class="space-y-4">
                                    <div class="flex items-center justify-between text-xs text-gray-400 italic mb-2">
                                        <span>Journal Entry Lines</span>
                                        <span>T-Account View</span>
                                    </div>
                                    <div v-for="line in bill.journal_entry.lines" :key="line.id" class="border-b border-gray-50 pb-3 last:border-0">
                                        <p class="text-xs font-bold text-gray-800">{{ line.chart_of_account?.name }}</p>
                                        <div class="flex justify-between items-center mt-1">
                                            <span class="text-[10px] text-gray-400">{{ line.chart_of_account?.code }}</span>
                                            <div class="text-sm font-medium">
                                                <span v-if="line.debit > 0" class="text-blue-600 font-bold">DR {{ formatCurrency(line.debit) }}</span>
                                                <span v-else class="text-gray-600 font-bold ml-4 italic">CR {{ formatCurrency(line.credit) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-8">
                                    <div class="bg-gray-50 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3">
                                        <DocumentDuplicateIcon class="w-6 h-6 text-gray-300" />
                                    </div>
                                    <p class="text-sm text-gray-500 italic">No ledger entries yet.<br>Approve to post to ledger.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Status Panel -->
                        <div 
                            :class="[
                                'overflow-hidden shadow-lg sm:rounded-lg text-white',
                                bill.type === 'Debit Memo' ? 'bg-amber-700' : 'bg-indigo-900'
                            ]"
                        >
                            <div class="p-6">
                                <h3 class="font-bold uppercase tracking-widest text-white/70 text-xs mb-4">
                                    {{ bill.type === 'Debit Memo' ? 'Credit Status' : 'Payment Status' }}
                                </h3>
                                <div class="space-y-4">
                                    <template v-if="bill.type === 'Bill'">
                                        <div class="flex justify-between items-end border-b border-white/10 pb-3">
                                            <span class="text-xs text-white/60 uppercase">Paid to Date</span>
                                            <span class="text-lg font-bold">₱ 0.00</span>
                                        </div>
                                        <div class="flex justify-between items-end border-b border-white/10 pb-3">
                                            <span class="text-xs text-white/60 uppercase">Outstanding Balance</span>
                                            <span class="text-lg font-bold text-orange-400">{{ formatCurrency(bill.total_amount) }}</span>
                                        </div>
                                        <button 
                                            v-if="bill.status === 'Approved'"
                                            class="w-full mt-4 py-3 bg-white text-indigo-900 rounded-lg font-bold text-sm uppercase tracking-widest hover:bg-indigo-50 transition-colors shadow-xl shadow-indigo-950 flex items-center justify-center"
                                        >
                                            Record Payment
                                            <ArrowRightIcon class="w-4 h-4 ml-2" />
                                        </button>
                                    </template>
                                    <template v-else>
                                        <div class="flex justify-between items-end border-b border-white/10 pb-3">
                                            <span class="text-xs text-white/60 uppercase">Total Credit Value</span>
                                            <span class="text-lg font-bold">{{ formatCurrency(bill.total_amount) }}</span>
                                        </div>
                                        <div class="bg-white/10 p-3 rounded text-xs italic">
                                            This debit memo reduces your overall liability to <strong>{{ bill.vendor?.name }}</strong>.
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
