<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ClockIcon,
    ArrowLeftIcon,
    BanknotesIcon,
    ChartBarIcon,
    CalendarIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    HandThumbUpIcon,
    ArrowDownLeftIcon,
    ArrowUpRightIcon
} from '@heroicons/vue/24/outline';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    pdcs: Object,
    forecast: Array,
    filters: Object
});

const { confirm } = useConfirm();
const { showSuccess, showError } = useToast();

const currentType = ref(props.filters?.type || 'Outward');

const switchTab = (type) => {
    currentType.value = type;
    router.get(route('accounting.disbursements.vault'), { type }, { preserveState: true, preserveScroll: true });
};

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

const getMaxAmount = () => {
    const values = props.forecast.flatMap(f => [parseFloat(f.total_inflow), parseFloat(f.total_outflow)]);
    return Math.max(...values, 1);
};

const bouncePdc = async (pdc) => {
    const confirmed = await confirm({
        title: 'Mark as Bounced',
        message: `Check #${pdc.check_no} from ${pdc.type === 'Inward' ? 'Customer' : 'Vendor'} will be marked as Bounced.`,
        confirmButtonText: 'Yes, Mark as Bounced',
        type: 'warning'
    });

    if (confirmed) {
        router.post(route('accounting.disbursements.pdc-bounce', pdc.id));
    }
};

// Summary Logic for Cards
const next7DaysInflow = computed(() => {
    const now = new Date();
    const next7 = new Date();
    next7.setDate(now.getDate() + 7);
    
    return props.forecast
        .filter(f => {
            const d = new Date(f.check_date);
            return d >= now && d <= next7;
        })
        .reduce((sum, f) => sum + parseFloat(f.total_inflow), 0);
});

const next7DaysOutflow = computed(() => {
    const now = new Date();
    const next7 = new Date();
    next7.setDate(now.getDate() + 7);
    
    return props.forecast
        .filter(f => {
            const d = new Date(f.check_date);
            return d >= now && d <= next7;
        })
        .reduce((sum, f) => sum + parseFloat(f.total_outflow), 0);
});
</script>

<template>
    <Head title="PDC Vault & Liquidity Intelligence - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="route('accounting.disbursements.index')" class="text-slate-500 hover:text-slate-700">
                    <ArrowLeftIcon class="w-6 h-6" />
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    PDC Vault & Liquidity Intelligence
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Liquidity Heatmap / Forecast -->
                <div class="bg-slate-900 rounded-2xl shadow-2xl p-8 border border-slate-800 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/10 blur-[100px] rounded-full -mr-32 -mt-32"></div>
                    
                    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 space-y-4 md:space-y-0">
                        <div>
                            <h3 class="text-xl font-black text-white flex items-center">
                                <ChartBarIcon class="w-6 h-6 mr-2 text-blue-400" />
                                Liquidity Heatmap (Cash Flow Projection)
                            </h3>
                            <p class="text-sm text-slate-400 mt-1">Projected Inflows vs Outflows based on pending PDCs</p>
                        </div>
                        <div class="flex space-x-4">
                            <div class="bg-emerald-900/30 rounded-lg p-3 border border-emerald-500/20">
                                <p class="text-[10px] text-emerald-500 font-black uppercase tracking-widest">Total Inward (AR)</p>
                                <p class="text-xl font-black text-emerald-400">{{ formatCurrency(forecast.reduce((a, b) => a + parseFloat(b.total_inflow), 0)) }}</p>
                            </div>
                            <div class="bg-rose-900/30 rounded-lg p-3 border border-rose-500/20">
                                <p class="text-[10px] text-rose-500 font-black uppercase tracking-widest">Total Outward (AP)</p>
                                <p class="text-xl font-black text-rose-400">{{ formatCurrency(forecast.reduce((a, b) => a + parseFloat(b.total_outflow), 0)) }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="forecast.length === 0" class="text-center py-20 bg-slate-800/30 rounded-xl border-2 border-dashed border-slate-700">
                        <p class="text-slate-500 italic">No future cash flows currently scheduled.</p>
                    </div>

                    <div v-else class="flex items-end justify-between space-x-4 h-64 mt-12 pb-8 overflow-x-auto custom-scrollbar">
                        <div v-for="item in forecast" :key="item.check_date" class="flex-1 flex flex-col items-center group relative min-w-[80px]">
                            <!-- Tooltip -->
                            <div class="absolute bottom-full mb-2 bg-slate-800 text-white text-[10px] font-black px-3 py-2 rounded shadow-2xl opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-20 border border-slate-700">
                                <div class="text-emerald-400">In: {{ formatCurrency(item.total_inflow) }}</div>
                                <div class="text-rose-400">Out: {{ formatCurrency(item.total_outflow) }}</div>
                                <div class="mt-1 pt-1 border-t border-slate-600 font-black">Net: {{ formatCurrency(item.total_inflow - item.total_outflow) }}</div>
                            </div>
                            
                            <!-- Split Bars -->
                            <div class="flex items-end space-x-1 w-full h-full">
                                <div 
                                    :style="{ height: `${(parseFloat(item.total_inflow) / getMaxAmount()) * 100}%` }" 
                                    class="flex-1 bg-emerald-500/20 group-hover:bg-emerald-500 transition-all duration-500 rounded-t-sm border-t border-emerald-500/30 min-h-[2px]"
                                ></div>
                                <div 
                                    :style="{ height: `${(parseFloat(item.total_outflow) / getMaxAmount()) * 100}%` }" 
                                    class="flex-1 bg-rose-500/20 group-hover:bg-rose-500 transition-all duration-500 rounded-t-sm border-t border-rose-500/30 min-h-[2px]"
                                ></div>
                            </div>
                            
                            <!-- Label -->
                            <div class="mt-4 flex flex-col items-center">
                                <span class="text-[10px] text-slate-400 font-bold whitespace-nowrap">{{ formatDate(item.check_date) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-blue-600 rounded-2xl p-6 text-white shadow-xl shadow-blue-200 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold uppercase opacity-80 tracking-widest">Next 7 Days Inflow (AR)</h4>
                            <p class="text-3xl font-black mt-1">{{ formatCurrency(next7DaysInflow) }}</p>
                        </div>
                        <div class="bg-blue-500 rounded-full p-4 shadow-inner">
                            <HandThumbUpIcon class="w-10 h-10 text-white" />
                        </div>
                    </div>
                    
                    <div class="bg-rose-600 rounded-2xl p-6 text-white shadow-xl shadow-rose-200 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold uppercase opacity-80 tracking-widest">Next 7 Days Outflow (AP)</h4>
                            <p class="text-3xl font-black mt-1">{{ formatCurrency(next7DaysOutflow) }}</p>
                        </div>
                        <div class="bg-rose-500 rounded-full p-4 shadow-inner">
                            <BanknotesIcon class="w-10 h-10 text-white" />
                        </div>
                    </div>
                </div>

                <!-- PDC Tabbed Inventory -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-2 bg-slate-100/50 border-b border-slate-200">
                        <div class="flex items-center justify-between px-4">
                            <div class="flex p-1 bg-slate-200/50 rounded-2xl space-x-1">
                                <button 
                                    @click="switchTab('Outward')"
                                    :class="[
                                        'flex items-center px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-300',
                                        currentType === 'Outward' 
                                            ? 'bg-rose-600 text-white shadow-lg shadow-rose-200' 
                                            : 'text-slate-500 hover:text-slate-700 hover:bg-white/50'
                                    ]"
                                >
                                    <ArrowUpRightIcon class="w-4 h-4 mr-2" />
                                    Issued Checks (AP)
                                </button>
                                <button 
                                    @click="switchTab('Inward')"
                                    :class="[
                                        'flex items-center px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-300',
                                        currentType === 'Inward' 
                                            ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-200' 
                                            : 'text-slate-500 hover:text-slate-700 hover:bg-white/50'
                                    ]"
                                >
                                    <ArrowDownLeftIcon class="w-4 h-4 mr-2" />
                                    Collected Checks (AR)
                                </button>
                            </div>
                            
                            <div class="flex items-center space-x-4">
                                <div class="h-8 w-[1px] bg-slate-200"></div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    Total: {{ pdcs.total }} Checks
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">Check Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">{{ currentType === 'Inward' ? 'Customer' : 'Vendor' }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">Maturity Date</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase w-32">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="pdcs.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">No checks found in this category.</td>
                                </tr>
                                <tr v-for="pdc in pdcs.data" :key="pdc.id" class="hover:bg-slate-50">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-900">{{ pdc.check_no }}</span>
                                            <span class="text-xs text-slate-500 font-mono">{{ pdc.bank?.name || pdc.bank_name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600 font-medium">
                                        {{ currentType === 'Inward' ? (pdc.payment?.customer?.name || pdc.customer?.name) : (pdc.disbursement?.vendor?.name || pdc.vendor?.name) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-900">
                                        <div class="flex items-center">
                                            <CalendarIcon class="w-4 h-4 mr-2 text-slate-400" />
                                            {{ formatDate(pdc.check_date) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-black text-slate-900 text-right">
                                        {{ formatCurrency(pdc.amount) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span :class="[
                                            'px-2 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border shadow-sm',
                                            pdc.status === 'Pending' ? 'bg-amber-50 text-amber-600 border-amber-200' : 
                                            pdc.status === 'Cleared' ? 'bg-emerald-50 text-emerald-600 border-emerald-200' :
                                            'bg-red-50 text-red-600 border-red-200'
                                        ]">
                                            {{ pdc.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div v-if="pdc.status === 'Pending'" class="flex justify-end space-x-2">
                                            <button @click="bouncePdc(pdc)" class="p-1.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors" title="Mark as Bounced">
                                                <ExclamationTriangleIcon class="w-4 h-4" />
                                            </button>
                                        </div>
                                        <div v-else class="text-[10px] font-bold text-slate-400 italic">
                                            Reconciled
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 6px;
}
</style>
