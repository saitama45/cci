<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ArrowLeftIcon,
    CurrencyDollarIcon,
    CalendarDaysIcon,
    CheckCircleIcon,
    UserCircleIcon,
    HomeIcon,
    ClipboardDocumentCheckIcon,
    BanknotesIcon,
    IdentificationIcon,
    InformationCircleIcon,
    CreditCardIcon,
    DocumentTextIcon,
    ClockIcon,
    ChevronRightIcon,
    LockClosedIcon,
    PlusIcon
} from '@heroicons/vue/24/outline';
import { useToast } from '@/Composables/useToast';
import { useInputRestriction } from '@/Composables/useInputRestriction';

const props = defineProps({
    contract: Object,
    payment_methods: Array,
    banks: Array
});

const showReceiptsModal = ref(false);

const { showSuccess, showError } = useToast();
const { formatDateDisplay, restrictNumeric, formatNumberWithCommas, stripCommas, formatDateForInput } = useInputRestriction();

const form = useForm({
    contracted_sale_id: props.contract.id,
    schedule_ids: [],
    amount: '0',
    payment_date: formatDateForInput(new Date()),
    payment_method: 'Cash',
    reference_no: '',
    notes: '',
    // PDC Fields
    bank_id: '',
    check_no: '',
    check_date: formatDateForInput(new Date()),
    starting_check_no: '',
});

const isPdcMethod = computed(() => ['Check', 'PDC'].includes(form.payment_method));
const isBulkPdcMethod = computed(() => form.payment_method === 'Bulk PDC');

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(value || 0);
};

const handleAmountInput = (e) => {
    const rawValue = stripCommas(e.target.value);
    const numericValue = restrictNumeric(rawValue, true);
    form.amount = numericValue;
    e.target.value = formatNumberWithCommas(numericValue);
};

// Selection logic
const toggleSchedule = (scheduleId) => {
    const schedule = props.contract.payment_schedules.find(s => s.id === scheduleId);
    if (!schedule || schedule.status === 'Paid') return;

    const index = form.schedule_ids.indexOf(scheduleId);
    if (index > -1) {
        form.schedule_ids.splice(index, 1);
    } else {
        form.schedule_ids.push(scheduleId);
    }
    
    updateAmount();
};

const updateAmount = () => {
    const totalDue = props.contract.payment_schedules
        .filter(s => form.schedule_ids.includes(s.id))
        .reduce((sum, s) => sum + (parseFloat(s.amount_due) - parseFloat(s.amount_paid)), 0);
    
    form.amount = totalDue.toFixed(2);
};

const isAllSelected = computed(() => {
    const pendingIds = props.contract.payment_schedules.filter(s => s.status !== 'Paid').map(s => s.id);
    return pendingIds.length > 0 && pendingIds.every(id => form.schedule_ids.includes(id));
});

const toggleAll = () => {
    const pendingIds = props.contract.payment_schedules.filter(s => s.status !== 'Paid').map(s => s.id);
    if (isAllSelected.value) {
        form.schedule_ids = [];
    } else {
        form.schedule_ids = [...pendingIds];
    }
    updateAmount();
};

const submitPayment = () => {
    if (form.schedule_ids.length === 0) {
        showError('Please select at least one installment to pay.');
        return;
    }
    
    form.post(route('payments.store'), {
        onSuccess: () => {
            showSuccess('Payment recorded successfully.');
        },
        onError: (errors) => {
            showError('Failed to record payment. ' + Object.values(errors).join(', '));
        }
    });
};

const getStatusColor = (status) => {
    switch (status) {
        case 'Paid': return 'bg-emerald-50 text-emerald-700 border-emerald-100';
        case 'Partially Paid': return 'bg-blue-50 text-blue-700 border-blue-100';
        case 'Pending': return 'bg-amber-50 text-amber-700 border-amber-100';
        case 'Overdue': return 'bg-rose-50 text-rose-700 border-rose-100';
        default: return 'bg-slate-50 text-slate-700 border-slate-100';
    }
};

const selectNextPending = () => {
    const nextPending = props.contract.payment_schedules.find(s => s.status !== 'Paid' && !form.schedule_ids.includes(s.id));
    if (nextPending) toggleSchedule(nextPending.id);
};

const getPaymentReference = (schedule) => {
    if (schedule.status === 'Pending') return null;
    
    const sTime = new Date(schedule.updated_at).getTime();
    
    // 1. Primary: Time Proximity Matching (within 10 minutes)
    // Find the amortization payment that was created closest to this schedule's update time
    let closestPayment = null;
    let minDiff = Infinity;

    props.contract.payments?.forEach(p => {
        if (p.payment_type !== 'Amortization') return;
        
        const pTime = new Date(p.created_at).getTime();
        const diff = Math.abs(sTime - pTime);
        
        // 10 minute window (600,000 ms)
        if (diff < 600000 && diff < minDiff) {
            minDiff = diff;
            closestPayment = p;
        }
    });

    if (closestPayment) return closestPayment.reference_no;

    // 2. Fallback: Ordinal matching (N-th paid installment matches N-th amortization payment)
    const paidAmorts = props.contract.payment_schedules
        ?.filter(s => s.status !== 'Pending' && s.type === 'Amortization')
        .sort((a, b) => a.installment_no - b.installment_no) || [];
    
    const sIndex = paidAmorts.findIndex(s => s.id === schedule.id);
    
    const payments = props.contract.payments
        ?.filter(p => p.payment_type === 'Amortization')
        .sort((a, b) => new Date(a.created_at) - new Date(b.created_at)) || [];

    return payments[sIndex]?.reference_no;
};

// Check if a reference should be highlighted (from global search redirect)
const isHighlighted = (ref) => {
    if (!ref) return false;
    const urlParams = new URLSearchParams(window.location.search);
    const searchVal = urlParams.get('search');
    return searchVal && ref.toLowerCase() === searchVal.toLowerCase();
};

const totalOutstanding = computed(() => {
    if (!props.contract.payment_schedules) return 0;
    return props.contract.payment_schedules.reduce((sum, s) => {
        return sum + (parseFloat(s.amount_due) - parseFloat(s.amount_paid));
    }, 0);
});

const totalPaid = computed(() => {
    if (!props.contract.payments) return 0;
    return props.contract.payments
        .filter(p => p.payment_type === 'Amortization')
        .reduce((sum, p) => sum + parseFloat(p.amount), 0);
});

const groupedSchedules = computed(() => {
    if (!props.contract.payment_schedules) return [];
    
    const groups = {};
    const startDate = new Date(props.contract.start_date);

    props.contract.payment_schedules.forEach(s => {
        const dueDate = new Date(s.due_date);
        const monthsDiff = (dueDate.getFullYear() - startDate.getFullYear()) * 12 + (dueDate.getMonth() - startDate.getMonth());
        const year = Math.floor(monthsDiff / 12) + 1;
        
        if (!groups[year]) {
            groups[year] = {
                year,
                items: []
            };
        }
        groups[year].items.push(s);
    });

    return Object.values(groups);
});

</script>

<template>
    <Head :title="`Collect - ${contract.contract_no}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link :href="route('payments.index')" class="p-2.5 bg-white rounded-xl border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-100 transition-all shadow-sm">
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div>
                        <div class="flex items-center space-x-2 mb-0.5">
                            <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 text-[10px] font-black uppercase tracking-widest rounded-md border border-indigo-100">Accounting / Collections</span>
                            <ChevronRightIcon class="w-3 h-3 text-slate-300" />
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">ID: {{ contract.id }}</span>
                        </div>
                        <h2 class="font-black text-2xl text-slate-900 leading-none">Record Payment</h2>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6 space-y-6">
            <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Quick Info Dashboard -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex items-center space-x-4">
                        <div class="p-3 bg-blue-50 rounded-2xl">
                            <UserCircleIcon class="w-6 h-6 text-blue-600" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Customer Name</p>
                            <h4 class="text-sm font-black text-slate-900 truncate">{{ contract.customer?.first_name }} {{ contract.customer?.last_name }}</h4>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex items-center space-x-4">
                        <div class="p-3 bg-emerald-50 rounded-2xl">
                            <HomeIcon class="w-6 h-6 text-emerald-600" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Unit Designation</p>
                            <h4 class="text-sm font-black text-slate-900 truncate">{{ contract.unit?.name }}</h4>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex items-center space-x-4">
                        <div class="p-3 bg-indigo-50 rounded-2xl">
                            <ClipboardDocumentCheckIcon class="w-6 h-6 text-indigo-600" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Contract Number</p>
                            <h4 class="text-sm font-black text-slate-900 truncate">{{ contract.contract_no }}</h4>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex items-center space-x-4">
                        <div class="p-3 bg-slate-900 rounded-2xl">
                            <CurrencyDollarIcon class="w-6 h-6 text-white" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Total Contract Price</p>
                            <h4 class="text-sm font-black text-slate-900 truncate">{{ formatCurrency(contract.tcp) }}</h4>
                        </div>
                    </div>

                    <div 
                        @click="showReceiptsModal = true"
                        class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex items-center space-x-4 cursor-pointer hover:border-emerald-200 hover:bg-emerald-50/10 transition-all group"
                    >
                        <div class="p-3 bg-emerald-600 rounded-2xl group-hover:scale-110 transition-transform">
                            <CheckCircleIcon class="w-6 h-6 text-white" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5 flex items-center">
                                Total Amort Paid
                                <span class="ml-2 text-[8px] bg-emerald-100 text-emerald-700 px-1 py-0.5 rounded uppercase">Click to view</span>
                            </p>
                            <h4 class="text-sm font-black text-emerald-600 truncate">{{ formatCurrency(totalPaid) }}</h4>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex items-center space-x-4">
                        <div class="p-3 bg-blue-500 rounded-2xl">
                            <IdentificationIcon class="w-6 h-6 text-white" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Principal Balance</p>
                            <h4 class="text-sm font-black text-blue-600 truncate">{{ formatCurrency(contract.current_balance) }}</h4>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex items-center space-x-4">
                        <div class="p-3 bg-rose-50 rounded-2xl">
                            <BanknotesIcon class="w-6 h-6 text-rose-600" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Total Outstanding (Dues)</p>
                            <h4 class="text-sm font-black text-rose-600 truncate">{{ formatCurrency(contract.total_dues) }}</h4>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
                    
                    <!-- Left: Installment List -->
                    <div class="xl:col-span-8 space-y-6">
                        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
                            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/30">
                                <div>
                                    <h3 class="font-black text-slate-900">Payment Schedule</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Select installments to apply this collection.</p>
                                </div>
                                <button @click="selectNextPending" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center bg-indigo-50 px-3 py-1.5 rounded-xl border border-indigo-100 transition-all">
                                    <PlusIcon class="w-3 h-3 mr-1" />
                                    Select Next Due
                                </button>
                            </div>

                            <div class="overflow-x-auto max-h-[600px] overflow-y-auto custom-scrollbar">
                                <table class="w-full text-left border-collapse">
                                    <thead class="sticky top-0 z-20 bg-white">
                                        <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                            <th class="px-8 py-4 w-10">
                                                <div 
                                                    @click="toggleAll"
                                                    class="w-5 h-5 rounded-lg border-2 flex items-center justify-center transition-all cursor-pointer"
                                                    :class="[
                                                        isAllSelected
                                                            ? 'bg-blue-600 border-blue-600 shadow-sm shadow-blue-200'
                                                            : 'border-slate-200 hover:border-slate-300 bg-white'
                                                    ]"
                                                >
                                                    <CheckCircleIcon v-if="isAllSelected" class="w-3.5 h-3.5 text-white" />
                                                </div>
                                            </th>
                                            <th class="px-4 py-4">Installment</th>
                                            <th class="px-4 py-4">Due Date</th>
                                            <th class="px-4 py-4 text-right">Amount Due</th>
                                            <th class="px-4 py-4 text-right">Paid</th>
                                            <th class="px-4 py-4 text-right">Balance</th>
                                            <th class="px-4 py-4 text-left">Reference #</th>
                                            <th class="px-8 py-4 text-right">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <template v-for="group in groupedSchedules" :key="group.year">
                                            <!-- Year Header Row -->
                                            <tr class="bg-slate-50/50 sticky top-12 z-10 backdrop-blur-md">
                                                <td colspan="7" class="px-8 py-2">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="px-2 py-0.5 bg-slate-900 text-white text-[9px] font-black rounded-md uppercase tracking-widest">Year {{ group.year }}</span>
                                                        <div class="h-[1px] flex-grow bg-slate-200"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr 
                                                v-for="s in group.items" 
                                                :key="s.id"
                                                @click="toggleSchedule(s.id)"
                                                class="group transition-all duration-150"
                                                :class="[
                                                    s.status === 'Paid' 
                                                        ? (isHighlighted(getPaymentReference(s)) ? 'bg-yellow-50/50' : 'bg-slate-50/30 opacity-60 grayscale cursor-not-allowed')
                                                        : 'cursor-pointer hover:bg-slate-50',
                                                    form.schedule_ids.includes(s.id) ? 'bg-blue-50/50' : ''
                                                ]"
                                            >
                                                <td class="px-8 py-4">
                                                    <div 
                                                        class="w-5 h-5 rounded-lg border-2 flex items-center justify-center transition-all"
                                                        :class="[
                                                            s.status === 'Paid' ? (isHighlighted(getPaymentReference(s)) ? 'bg-yellow-400 border-yellow-500 shadow-lg' : 'bg-slate-100 border-slate-200') :
                                                            (form.schedule_ids.includes(s.id)
                                                                ? 'bg-blue-600 border-blue-600 shadow-sm shadow-blue-200'
                                                                : 'border-slate-200 group-hover:border-slate-300 bg-white')
                                                        ]"
                                                    >
                                                        <CheckCircleIcon v-if="form.schedule_ids.includes(s.id) || isHighlighted(getPaymentReference(s))" class="w-3.5 h-3.5 text-white" />
                                                        <LockClosedIcon v-else-if="s.status === 'Paid'" class="w-3 h-3 text-slate-400" />
                                                    </div>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <span class="text-xs font-black text-slate-700">#{{ s.installment_no }}</span>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <span class="text-xs font-bold text-slate-500">{{ formatDateDisplay(s.due_date) }}</span>
                                                </td>
                                                <td class="px-4 py-4 text-right">
                                                    <span class="text-xs font-black text-slate-900">{{ formatCurrency(s.amount_due) }}</span>
                                                </td>
                                                <td class="px-4 py-4 text-right">
                                                    <span class="text-xs font-bold text-emerald-600">{{ formatCurrency(s.amount_paid) }}</span>
                                                </td>
                                                <td class="px-4 py-4 text-right">
                                                    <span class="text-xs font-bold text-rose-600">{{ formatCurrency(parseFloat(s.amount_due) - parseFloat(s.amount_paid)) }}</span>
                                                </td>
                                                <td class="px-4 py-4 text-left">
                                                    <span v-if="getPaymentReference(s)" 
                                                          :class="[
                                                              'text-[10px] font-black px-2 py-0.5 rounded border transition-all',
                                                              isHighlighted(getPaymentReference(s)) 
                                                                ? 'bg-yellow-400 text-slate-900 border-yellow-500 scale-125 shadow-md ring-2 ring-yellow-400/50' 
                                                                : 'bg-blue-50 text-blue-600 border-blue-100'
                                                          ]"
                                                    >
                                                        {{ getPaymentReference(s) }}
                                                    </span>
                                                    <span v-else class="text-[10px] text-slate-300 italic">—</span>
                                                </td>
                                                <td class="px-8 py-4 text-right">
                                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-tighter border', getStatusColor(s.status)]">
                                                        {{ s.status }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Sticky Receipt Panel -->
                    <div class="xl:col-span-4 sticky top-6">
                        <div class="bg-white rounded-[2rem] shadow-xl border border-slate-100 overflow-hidden">
                            <div class="p-8 bg-slate-900 text-white relative overflow-hidden">
                                <div class="relative z-10">
                                    <div class="flex items-center space-x-2 mb-4">
                                        <div class="p-2 bg-white/10 rounded-xl backdrop-blur-md">
                                            <CreditCardIcon class="w-5 h-5 text-indigo-300" />
                                        </div>
                                        <span class="text-xs font-bold text-indigo-300 uppercase tracking-widest">Receipt Summary</span>
                                    </div>
                                    <p class="text-[10px] font-bold text-white/50 uppercase tracking-widest mb-1">Total Collection Amount</p>
                                    <h3 class="text-2xl md:text-3xl xl:text-4xl font-black tracking-tight break-words">{{ formatCurrency(form.amount) }}</h3>
                                    
                                    <div class="mt-6 pt-6 border-t border-white/10 flex flex-wrap items-center justify-between gap-4">
                                        <div class="min-w-0">
                                            <p class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Items Selected</p>
                                            <p class="text-sm font-black truncate">{{ form.schedule_ids.length }} Installments</p>
                                        </div>
                                        <div class="text-right ml-auto">
                                            <p class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Type</p>
                                            <p class="text-sm font-black uppercase">Amort</p>
                                        </div>
                                    </div>
                                </div>
                                <BanknotesIcon class="absolute right-[-30px] top-[-30px] w-40 h-40 text-white/5 rotate-12 pointer-events-none" />
                            </div>

                            <form @submit.prevent="submitPayment" class="p-8 space-y-6">
                                <div class="space-y-4">
                                    <div v-if="!isBulkPdcMethod">
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Collection Date</label>
                                        <div class="relative group">
                                            <CalendarDaysIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
                                            <input v-model="form.payment_date" type="date" :required="!isBulkPdcMethod" class="block w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-bold text-slate-700 text-sm">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Method</label>
                                        <select v-model="form.payment_method" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-bold text-slate-700 text-sm">
                                            <option v-for="method in payment_methods" :key="method" :value="method">{{ method }}</option>
                                        </select>
                                    </div>

                                    <!-- PDC Specific Fields -->
                                    <div v-if="isPdcMethod || isBulkPdcMethod" class="space-y-4 p-4 bg-blue-50/50 rounded-3xl border border-blue-100 animate-in slide-in-from-top-4 duration-300">
                                        <div>
                                            <label class="block text-[10px] font-black text-blue-600 uppercase tracking-widest mb-2">Bank Issuer</label>
                                            <select v-model="form.bank_id" required class="block w-full px-4 py-2 bg-white border border-blue-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-bold text-slate-700 text-sm">
                                                <option value="">Select Issuer Bank</option>
                                                <option v-for="bank in banks" :key="bank.id" :value="bank.id">{{ bank.name }} - {{ bank.code }}</option>
                                            </select>
                                        </div>
                                        <div v-if="isPdcMethod" class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[10px] font-black text-blue-600 uppercase tracking-widest mb-2">Check #</label>
                                                <input v-model="form.check_no" type="text" placeholder="0000123" class="block w-full px-4 py-2 bg-white border border-blue-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-bold text-slate-900 text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-black text-blue-600 uppercase tracking-widest mb-2">Maturity Date</label>
                                                <input v-model="form.check_date" type="date" class="block w-full px-4 py-2 bg-white border border-blue-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-bold text-slate-900 text-sm">
                                            </div>
                                        </div>
                                        <div v-if="isBulkPdcMethod" class="space-y-2">
                                            <div>
                                                <label class="block text-[10px] font-black text-blue-600 uppercase tracking-widest mb-2">Starting Check #</label>
                                                <input v-model="form.starting_check_no" type="text" placeholder="0000101" required class="block w-full px-4 py-2 bg-white border border-blue-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-bold text-slate-900 text-sm">
                                            </div>
                                            <div class="flex items-start space-x-2 text-[10px] font-bold text-blue-500 bg-blue-100/50 p-2 rounded-lg">
                                                <InformationCircleIcon class="w-4 h-4 flex-shrink-0" />
                                                <p>Check numbers will auto-increment. Maturity dates will automatically align with the due date of each selected installment.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="!isBulkPdcMethod">
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">{{ isPdcMethod ? 'Reference / Deposit Slip' : 'Official Receipt / Reference #' }}</label>
                                        <div class="relative group">
                                            <DocumentTextIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
                                            <input v-model="form.reference_no" type="text" :required="!isBulkPdcMethod" placeholder="Ex: OR-99827" class="block w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-bold text-slate-900 text-sm placeholder:text-slate-300">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Notes / Memo</label>
                                        <textarea v-model="form.notes" rows="2" placeholder="Optional internal remarks..." class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-medium text-slate-600 text-sm placeholder:text-slate-300 resize-none"></textarea>
                                    </div>
                                </div>

                                <div class="pt-4">
                                    <button 
                                        type="submit" 
                                        :disabled="form.processing || form.schedule_ids.length === 0" 
                                        class="w-full py-4 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-600/30 disabled:opacity-50 disabled:grayscale transition-all flex items-center justify-center space-x-2 active:scale-95"
                                    >
                                        <CheckCircleIcon class="w-6 h-6" />
                                        <span>Post to Ledger</span>
                                    </button>
                                    <p class="text-center mt-4 text-[10px] font-bold text-slate-400 flex items-center justify-center">
                                        <InformationCircleIcon class="w-3 h-3 mr-1" />
                                        This action will generate accounting entries.
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Receipts Modal -->
        <div v-if="showReceiptsModal" class="fixed inset-0 z-[60] overflow-y-auto flex items-start sm:items-center justify-center p-4 pt-10 sm:pt-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showReceiptsModal = false"></div>
            
            <div class="bg-white rounded-[2.5rem] shadow-2xl max-w-2xl w-full relative animate-in fade-in zoom-in duration-200 my-auto overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-black text-slate-900">Payment History</h3>
                        <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-0.5">Summary of all collections for this contract</p>
                    </div>
                    <button @click="showReceiptsModal = false" class="p-2 hover:bg-slate-100 rounded-full transition-colors">
                        <ArrowLeftIcon class="w-5 h-5 text-slate-400 rotate-90" />
                    </button>
                </div>

                <div class="p-8 max-h-[60vh] overflow-y-auto custom-scrollbar">
                    <div v-if="!contract.payments?.filter(p => p.payment_type === 'Amortization').length" class="text-center py-12">
                        <BanknotesIcon class="w-16 h-16 text-slate-200 mx-auto mb-4" />
                        <p class="text-slate-400 font-bold">No amortization payments recorded yet.</p>
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="payment in contract.payments.filter(p => p.payment_type === 'Amortization')" :key="payment.id" class="p-5 rounded-3xl border border-slate-100 hover:border-emerald-200 transition-all flex items-center justify-between group">
                            <div class="flex items-center space-x-4">
                                <div class="p-3 bg-emerald-50 rounded-2xl group-hover:bg-emerald-600 transition-colors">
                                    <CheckCircleIcon class="w-5 h-5 text-emerald-600 group-hover:text-white" />
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-black text-slate-900">{{ payment.reference_no }}</span>
                                        <span class="px-1.5 py-0.5 bg-slate-100 text-[8px] font-black text-slate-500 rounded uppercase tracking-tighter">{{ payment.payment_method }}</span>
                                    </div>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">{{ formatDateDisplay(payment.payment_date) }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-black text-emerald-600">{{ formatCurrency(payment.amount) }}</div>
                                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">{{ payment.payment_type }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Lifetime Collections</p>
                        <h4 class="text-lg font-black text-slate-900">{{ formatCurrency(totalPaid) }}</h4>
                    </div>
                    <button @click="showReceiptsModal = false" class="px-6 py-2.5 bg-slate-900 text-white text-xs font-black rounded-xl hover:bg-slate-800 transition-all">Close History</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f8fafc;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
