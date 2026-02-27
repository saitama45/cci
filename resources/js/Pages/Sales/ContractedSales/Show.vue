<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ArrowLeftIcon,
    CurrencyDollarIcon,
    CalendarDaysIcon,
    CheckCircleIcon,
    ClockIcon,
    HomeIcon,
    UserCircleIcon,
    DocumentChartBarIcon,
    ArrowsRightLeftIcon,
    BanknotesIcon,
    ChevronDownIcon,
    CalendarIcon,
    CalculatorIcon
} from '@heroicons/vue/24/outline';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    sale: Object
});

const { showSuccess, showError } = useToast();

const totalInterest = computed(() => {
    if (!props.sale.payment_schedules) return 0;
    return props.sale.payment_schedules.reduce((sum, s) => sum + (parseFloat(s.interest) || 0), 0);
});

const totalObligation = computed(() => {
    if (!props.sale.payment_schedules) return 0;
    return props.sale.payment_schedules.reduce((sum, s) => sum + (parseFloat(s.amount_due) || 0), 0);
});

const amortizationPaid = computed(() => {
    if (!props.sale.payment_schedules) return 0;
    return props.sale.payment_schedules.reduce((sum, s) => {
        if (s.type === 'Amortization') {
            return sum + (parseFloat(s.amount_paid) || 0);
        }
        return sum;
    }, 0);
});

const outstandingBalance = computed(() => {
    return totalObligation.value - amortizationPaid.value;
});

const amortizationPayments = computed(() => {
    if (!props.sale.payments) return [];
    return props.sale.payments.filter(p => p.payment_type === 'Amortization');
});

const yearsWithPayments = computed(() => {
    const years = new Set();
    if (!props.sale.payment_schedules) return years;

    const startDate = new Date(props.sale.start_date);

    props.sale.payment_schedules.forEach(s => {
        if (parseFloat(s.amount_paid) > 0 || s.status !== 'Pending') {
            const dueDate = new Date(s.due_date);
            const monthsDiff = (dueDate.getFullYear() - startDate.getFullYear()) * 12 + (dueDate.getMonth() - startDate.getMonth());
            const year = Math.floor(monthsDiff / 12) + 1;
            years.add(year);
        }
    });
    return years;
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(value || 0);
};

const formatDateDisplay = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};

const getStatusColor = (status) => {
    switch (status) {
        case 'Paid': return 'bg-emerald-50 text-emerald-700 border-emerald-100';
        case 'Pending': return 'bg-amber-50 text-amber-700 border-amber-100';
        case 'Overdue': return 'bg-rose-50 text-rose-700 border-rose-100';
        default: return 'bg-slate-50 text-slate-700 border-slate-100';
    }
};

const calculateFactorRate = (annualRate, terms) => {
    if (!annualRate || annualRate <= 0) return 1 / terms;
    const i = (annualRate / 100) / 12;
    const n = terms;
    return (i * Math.pow(1 + i, n)) / (Math.pow(1 + i, n) - 1);
};

const showRepriceModal = ref(false);
const showCollectionsModal = ref(false);
const showAmortizationModal = ref(false);

const interestRateSummary = computed(() => {
    if (!props.sale.repricing_config || props.sale.repricing_config.length === 0) {
        return `${parseFloat(props.sale.interest_rate)}%`;
    }

    const config = props.sale.repricing_config;
    let segments = [];
    let startYear = config[0].year;
    let currentRate = config[0].rate;

    for (let i = 1; i < config.length; i++) {
        if (parseFloat(config[i].rate) !== parseFloat(currentRate)) {
            if (config[i-1].year === startYear) {
                segments.push(`Yr ${startYear}: ${parseFloat(currentRate)}%`);
            } else {
                segments.push(`Yr ${startYear}-${config[i-1].year}: ${parseFloat(currentRate)}%`);
            }
            startYear = config[i].year;
            currentRate = config[i].rate;
        }
    }
    
    const lastYear = config[config.length - 1].year;
    if (lastYear === startYear) {
        segments.push(`Yr ${startYear}: ${parseFloat(currentRate)}%`);
    } else {
        segments.push(`Yr ${startYear}-${lastYear}: ${parseFloat(currentRate)}%`);
    }

    return segments.join(', ');
});

// Advanced UI: Group schedules by contract year
const groupedSchedules = computed(() => {
    if (!props.sale.payment_schedules) return [];
    
    const groups = {};
    const startDate = new Date(props.sale.start_date);

    props.sale.payment_schedules.forEach(s => {
        const dueDate = new Date(s.due_date);
        const monthsDiff = (dueDate.getFullYear() - startDate.getFullYear()) * 12 + (dueDate.getMonth() - startDate.getMonth());
        const year = Math.floor(monthsDiff / 12) + 1;
        
        if (!groups[year]) {
            groups[year] = {
                year,
                items: [],
                totalPrincipal: 0,
                totalInterest: 0,
                totalDue: 0
            };
        }
        groups[year].items.push(s);
        groups[year].totalPrincipal += parseFloat(s.principal);
        groups[year].totalInterest += parseFloat(s.interest);
        groups[year].totalDue += parseFloat(s.amount_due);
    });

    return Object.values(groups);
});

const yearsCount = Math.ceil(props.sale.terms_month / 12);
const initialRepricingConfig = props.sale.repricing_config || Array.from({ length: yearsCount }, (_, i) => ({
    year: i + 1,
    rate: props.sale.interest_rate
}));

const repriceForm = useForm({
    new_interest_rate: props.sale.interest_rate,
    effective_date: new Date().toISOString().split('T')[0],
    repricing_config: initialRepricingConfig
});

const submitReprice = () => {
    repriceForm.post(route('contracted-sales.reprice', props.sale.id), {
        onSuccess: () => {
            showRepriceModal.value = false;
            showSuccess('Interest rate configuration updated.');
        },
        onError: (errors) => {
            showError('Failed to update configuration. ' + Object.values(errors).join(', '));
        }
    });
};
</script>

<template>
    <Head :title="`Ledger - ${sale.customer?.last_name}`" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="flex items-center space-x-4">
                    <Link :href="route('contracted-sales.index')" class="p-2 bg-white rounded-xl border border-slate-200 text-slate-400 hover:text-blue-600 transition-colors shadow-sm">
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div>
                        <h2 class="font-bold text-2xl text-slate-800 leading-tight">Sales Ledger</h2>
                        <p class="text-sm text-slate-500 mt-1">Contract: <span class="text-blue-600 font-black">{{ sale.contract_no }}</span></p>
                    </div>
                </div>
                <div class="mt-4 md:mt-0 flex space-x-3">
                    <Link
                        :href="route('contracted-sales.ledger', sale.id)"
                        class="inline-flex items-center px-4 py-2 bg-white text-indigo-600 border border-indigo-100 rounded-xl text-sm font-bold shadow-sm hover:bg-indigo-50 transition-all"
                    >
                        <DocumentChartBarIcon class="w-4 h-4 mr-2" />
                        Customer Ledger
                    </Link>
                    <button 
                        @click="showRepriceModal = true"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-600/20 hover:bg-indigo-700 transition-all"
                    >
                        <ArrowsRightLeftIcon class="w-4 h-4 mr-2" />
                        Configure Repricing
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6 space-y-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Financial Overview - Multi-Row Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Customer & Unit -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-start space-x-4 min-w-0">
                        <div class="p-3 bg-indigo-50 rounded-xl shrink-0">
                            <UserCircleIcon class="w-6 h-6 text-indigo-600" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Ownership</p>
                            <h3 class="text-lg font-black text-slate-900 truncate">{{ sale.customer?.last_name }}, {{ sale.customer?.first_name }}</h3>
                            <p class="text-sm font-bold text-blue-600 truncate">{{ sale.unit?.name }} • {{ sale.unit?.project?.name }}</p>
                        </div>
                    </div>

                    <!-- TCP Card -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-start space-x-4 min-w-0">
                        <div class="p-3 bg-slate-50 rounded-xl shrink-0">
                            <BanknotesIcon class="w-6 h-6 text-slate-600" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Contract Price (TCP)</p>
                            <h3 class="text-xl font-black text-slate-900">{{ formatCurrency(sale.tcp) }}</h3>
                            <p class="text-xs text-slate-500 font-medium mt-1">Full Value of Sale</p>
                        </div>
                    </div>

                    <!-- Equity/DP Box (Clickable) -->
                    <button 
                        @click="showCollectionsModal = true"
                        class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-start space-x-4 min-w-0 hover:border-blue-200 hover:bg-blue-50/10 transition-all text-left group"
                    >
                        <div class="p-3 bg-blue-50 rounded-xl shrink-0 group-hover:scale-110 transition-transform">
                            <CheckCircleIcon class="w-6 h-6 text-blue-600" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Equity / DP Paid</p>
                            <h3 class="text-xl font-black text-slate-900">{{ formatCurrency(sale.total_dp_paid) }}</h3>
                            <p class="text-xs text-blue-600 font-bold mt-1 group-hover:underline">View Receipt Records →</p>
                        </div>
                    </button>

                    <!-- Loan Information -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-start space-x-4 min-w-0">
                        <div class="p-3 bg-emerald-50 rounded-xl shrink-0">
                            <CurrencyDollarIcon class="w-6 h-6 text-emerald-600" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Loan Principal</p>
                            <h3 class="text-xl font-black text-slate-900">{{ formatCurrency(sale.loanable_amount) }}</h3>
                            <div class="flex flex-col text-[10px] mt-1 space-y-1">
                                <div class="text-emerald-600 font-bold">
                                    <span class="text-slate-400 font-medium">Rates:</span> {{ interestRateSummary }}
                                </div>
                                <div class="text-slate-500 font-bold">
                                    <span class="text-slate-400 font-medium italic">Monthly Amortization:</span> {{ formatCurrency(sale.monthly_amortization) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Outstanding Balance (Clickable) -->
                    <button 
                        @click="showAmortizationModal = true"
                        class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-start space-x-4 min-w-0 hover:border-rose-200 hover:bg-rose-50/10 transition-all text-left group"
                    >
                        <div class="p-3 bg-rose-50 rounded-xl shrink-0 group-hover:scale-110 transition-transform">
                            <DocumentChartBarIcon class="w-6 h-6 text-rose-600" />
                        </div>
                        <div class="min-w-0 w-full">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Outstanding Balance</p>
                            <h3 class="text-xl font-black text-rose-600">{{ formatCurrency(outstandingBalance) }}</h3>
                            <div class="w-full bg-slate-100 h-2 rounded-full mt-3 overflow-hidden">
                                <div class="bg-emerald-500 h-full transition-all duration-1000" :style="{ width: Math.min(100, (amortizationPaid / (parseFloat(totalObligation) || 1)) * 100) + '%' }"></div>
                            </div>
                            <p class="text-[10px] text-slate-400 font-bold mt-1 uppercase tracking-tighter">Paid (Amort): {{ formatCurrency(amortizationPaid) }}</p>
                        </div>
                    </button>

                    <!-- Total Obligations -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-start space-x-4 min-w-0">
                        <div class="p-3 bg-blue-50 rounded-xl shrink-0">
                            <CalculatorIcon class="w-6 h-6 text-blue-600" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Obligations</p>
                            <h3 class="text-xl font-black text-slate-900">{{ formatCurrency(totalObligation) }}</h3>
                            <p class="text-xs text-slate-500 font-medium mt-1">Sum of all due amounts</p>
                        </div>
                    </div>
                </div>

                <!-- Detailed Payment History & Schedule -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/30">
                        <div class="flex items-center space-x-3">
                            <div class="p-2.5 bg-blue-600 rounded-xl shadow-lg shadow-blue-600/20">
                                <CalendarIcon class="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <h3 class="font-black text-slate-900">Amortization Schedule</h3>
                                <p class="text-xs text-slate-500">Staggered principal and interest breakdown by year.</p>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto max-h-[800px] overflow-y-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <tbody class="divide-y divide-slate-100">
                                <template v-for="group in groupedSchedules" :key="group.year">
                                    <!-- Year Header Row -->
                                    <tr class="bg-slate-50/80 sticky top-0 z-10 backdrop-blur-md">
                                        <td colspan="7" class="px-8 py-4">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <span class="px-3 py-1 bg-slate-900 text-white text-xs font-black rounded-lg uppercase tracking-widest">Year #{{ group.year }}</span>
                                                    <div class="h-4 w-[1px] bg-slate-300 mx-2"></div>
                                                    <span class="text-xs font-bold text-slate-500 uppercase">Annual Totals:</span>
                                                    <span class="text-xs font-black text-slate-700">Prin: {{ formatCurrency(group.totalPrincipal) }}</span>
                                                    <span class="text-xs font-black text-slate-700">Int: {{ formatCurrency(group.totalInterest) }}</span>
                                                </div>
                                                <div class="text-right">
                                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">Yearly Due</span>
                                                    <span class="text-sm font-black text-slate-900">{{ formatCurrency(group.totalDue) }}</span>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Table Header for items -->
                                    <tr class="bg-white/50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                        <th class="px-8 py-3">Installment</th>
                                        <th class="px-8 py-3 text-center">Due Date</th>
                                        <th class="px-8 py-3 text-right">Principal</th>
                                        <th class="px-8 py-3 text-right">Interest</th>
                                        <th class="px-8 py-3 text-right text-slate-900">Total Due</th>
                                        <th class="px-8 py-3 text-right">Balance</th>
                                        <th class="px-8 py-3 text-right">Status</th>
                                    </tr>
                                    <!-- Data Rows -->
                                    <tr v-for="s in group.items" :key="s.id" class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-8 py-4">
                                            <span class="text-sm font-bold text-slate-700">#{{ s.installment_no }}</span>
                                        </td>
                                        <td class="px-8 py-4 text-center">
                                            <span class="text-sm font-bold text-slate-600">{{ formatDateDisplay(s.due_date) }}</span>
                                        </td>
                                        <td class="px-8 py-4 text-right text-sm font-medium text-slate-500">
                                            {{ formatCurrency(s.principal) }}
                                        </td>
                                        <td class="px-8 py-4 text-right text-sm font-medium text-slate-500">
                                            {{ formatCurrency(s.interest) }}
                                        </td>
                                        <td class="px-8 py-4 text-right text-sm font-black text-slate-900">
                                            {{ formatCurrency(s.amount_due) }}
                                        </td>
                                        <td class="px-8 py-4 text-right text-sm font-medium text-slate-400 font-mono">
                                            {{ formatCurrency(s.remaining_balance) }}
                                        </td>
                                        <td class="px-8 py-4 text-right">
                                            <span :class="['inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-tighter border', getStatusColor(s.status)]">
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
        </div>

        <!-- Collection History Modal -->
        <div v-if="showCollectionsModal" class="fixed inset-0 z-50 overflow-y-auto flex items-start sm:items-center justify-center p-4 pt-10 sm:pt-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showCollectionsModal = false"></div>
            
            <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full relative animate-in fade-in zoom-in duration-200 my-auto">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between rounded-t-3xl">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Equity & DP History</h3>
                        <p class="text-sm text-slate-500 font-medium">Receipts for Reservation and Downpayments.</p>
                    </div>
                    <button @click="showCollectionsModal = false" class="p-2 text-slate-400 hover:text-slate-600 transition-colors">
                        <ArrowLeftIcon class="w-6 h-6 rotate-180" />
                    </button>
                </div>
                
                <div class="p-0">
                    <div class="overflow-x-auto max-h-[400px] overflow-y-auto custom-scrollbar">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-8 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Date</th>
                                    <th class="px-8 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Type</th>
                                    <th class="px-8 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Ref #</th>
                                    <th class="px-8 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="payment in sale.payments" :key="payment.id" class="hover:bg-slate-50/50 transition-colors text-sm">
                                    <td class="px-8 py-4 font-bold text-slate-700">{{ formatDateDisplay(payment.payment_date) }}</td>
                                    <td class="px-8 py-4">
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200 uppercase">{{ payment.payment_type }}</span>
                                    </td>
                                    <td class="px-8 py-4 font-black text-slate-500 text-xs">{{ payment.reference_no }}</td>
                                    <td class="px-8 py-4 text-right font-black text-emerald-600">{{ formatCurrency(payment.amount) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="px-8 py-6 border-t border-slate-100 bg-slate-50/50 flex justify-between items-center rounded-b-3xl">
                    <span class="text-sm font-bold text-slate-500 uppercase tracking-widest">Total Equity Paid</span>
                    <span class="text-xl font-black text-blue-600">{{ formatCurrency(sale.total_dp_paid) }}</span>
                </div>
            </div>
        </div>

        <!-- Reprice Modal -->
        <div v-if="showRepriceModal" class="fixed inset-0 z-50 overflow-y-auto flex items-start sm:items-center justify-center p-4 pt-10 sm:pt-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showRepriceModal = false"></div>
            
            <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full relative animate-in fade-in zoom-in duration-200 my-auto">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between rounded-t-3xl">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Interest Configuration</h3>
                        <p class="text-sm text-slate-500 font-medium">Configure yearly interest rates in advance.</p>
                    </div>
                    <div class="p-2 bg-indigo-50 rounded-xl">
                        <ArrowsRightLeftIcon class="w-6 h-6 text-indigo-600" />
                    </div>
                </div>
                
                <form @submit.prevent="submitReprice" class="p-8 space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-4 uppercase tracking-widest">Yearly Rate Schedule</label>
                        <div class="space-y-3 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                            <div v-for="(config, index) in repriceForm.repricing_config" :key="config.year" class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100 transition-all" :class="{ 'opacity-60 bg-slate-100': yearsWithPayments.has(config.year) }">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-bold text-slate-600">Year {{ config.year }}</span>
                                    <span v-if="yearsWithPayments.has(config.year)" class="px-2 py-0.5 bg-rose-50 text-rose-600 border border-rose-100 rounded text-[9px] font-black uppercase tracking-tighter">Locked</span>
                                </div>
                                <div class="relative w-32">
                                    <input 
                                        v-model="config.rate" 
                                        type="number" 
                                        step="0.01" 
                                        :readonly="yearsWithPayments.has(config.year)"
                                        :disabled="yearsWithPayments.has(config.year)"
                                        class="block w-full pl-4 pr-8 py-2 bg-white border border-slate-200 rounded-xl text-sm font-black text-slate-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all disabled:bg-slate-50 disabled:cursor-not-allowed"
                                    >
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Immediate Reprice (Optional)</label>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Target Rate (%)</p>
                                <input v-model="repriceForm.new_interest_rate" type="number" step="0.01" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold">
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Effective Date</p>
                                <input v-model="repriceForm.effective_date" type="date" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold">
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-50 rounded-2xl p-4 border border-blue-100 flex items-start space-x-3">
                        <ClockIcon class="w-5 h-5 text-blue-600 mt-0.5" />
                        <div class="text-[11px] text-blue-700 leading-relaxed">
                            <p class="font-bold mb-1">How it works:</p>
                            <p>1. The schedule above stores future rates for reference.</p>
                            <p>2. "Immediate Reprice" will instantly recalculate all pending schedules from the selected date using the Target Rate.</p>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" @click="showRepriceModal = false" class="px-6 py-3 text-slate-600 font-bold bg-slate-100 rounded-2xl hover:bg-slate-200 transition-colors">Cancel</button>
                        <button type="submit" :disabled="repriceForm.processing" class="px-8 py-3 bg-indigo-600 text-white font-black rounded-2xl hover:bg-indigo-700 shadow-xl shadow-indigo-600/30 disabled:opacity-50 transition-all text-sm">
                            Save Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Amortization History Modal -->
        <div v-if="showAmortizationModal" class="fixed inset-0 z-50 overflow-y-auto flex items-start sm:items-center justify-center p-4 pt-10 sm:pt-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showAmortizationModal = false"></div>
            
            <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full relative animate-in fade-in zoom-in duration-200 my-auto">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between rounded-t-3xl">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Amortization History</h3>
                        <p class="text-sm text-slate-500 font-medium">Receipts for monthly amortization payments.</p>
                    </div>
                    <button @click="showAmortizationModal = false" class="p-2 text-slate-400 hover:text-slate-600 transition-colors">
                        <ArrowLeftIcon class="w-6 h-6 rotate-180" />
                    </button>
                </div>
                
                <div class="p-0">
                    <div class="overflow-x-auto max-h-[400px] overflow-y-auto custom-scrollbar">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-8 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Date</th>
                                    <th class="px-8 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Type</th>
                                    <th class="px-8 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Ref #</th>
                                    <th class="px-8 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="payment in amortizationPayments" :key="payment.id" class="hover:bg-slate-50/50 transition-colors text-sm">
                                    <td class="px-8 py-4 font-bold text-slate-700">{{ formatDateDisplay(payment.payment_date) }}</td>
                                    <td class="px-8 py-4">
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-600 border border-emerald-200 uppercase">{{ payment.payment_type }}</span>
                                    </td>
                                    <td class="px-8 py-4 font-black text-slate-500 text-xs">{{ payment.reference_no }}</td>
                                    <td class="px-8 py-4 text-right font-black text-emerald-600">{{ formatCurrency(payment.amount) }}</td>
                                </tr>
                                <tr v-if="amortizationPayments.length === 0">
                                    <td colspan="4" class="px-8 py-12 text-center text-slate-400 font-medium italic">No amortization receipts found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="px-8 py-6 border-t border-slate-100 bg-slate-50/50 flex justify-between items-center rounded-b-3xl">
                    <span class="text-sm font-bold text-slate-500 uppercase tracking-widest">Total Amortization Paid</span>
                    <span class="text-xl font-black text-rose-600">{{ formatCurrency(amortizationPaid) }}</span>
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
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>
