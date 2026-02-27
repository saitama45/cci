<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ArrowLeftIcon,
    ScaleIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    DocumentCheckIcon,
    PencilSquareIcon,
    CheckIcon
} from '@heroicons/vue/24/outline';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    reconciliation: Object,
    glBalance: [Number, String]
});

const { showSuccess, showError } = useToast();

const localLines = ref([...props.reconciliation.lines]);
const isEditingBalance = ref(false);
const balanceForm = useForm({
    statement_ending_balance: props.reconciliation.statement_ending_balance
});

const updateBalance = () => {
    balanceForm.put(route('accounting.reconciliations.update', props.reconciliation.id), {
        onSuccess: () => {
            isEditingBalance.value = false;
            // Success toast is handled globally by AppLayout from flash messages
        },
        preserveScroll: true
    });
};

// Track if all lines are cleared
const isAllCleared = computed(() => {
    return localLines.value.length > 0 && localLines.value.every(line => line.is_cleared);
});

// Calculate totals dynamically on the frontend
const totalCleared = computed(() => {
    return localLines.value
        .filter(line => line.is_cleared)
        .reduce((sum, line) => sum + parseFloat(line.amount), 0);
});

// Assuming Ending Balance = Beginning Balance (not fully tracked here, so we use GL Balance conceptually, 
// or simple comparison: target is Statement Ending Balance).
// Usually: Difference = (Statement Balance) - (GL Balance + Uncleared Checks - Uncleared Deposits)
// For a simplified view: Target = Statement Ending Balance. 
// Cleared Balance = Sum of all cleared items in this period.
// If the beginning balance was 0, Difference = Statement Balance - Cleared Balance.
// To make it functional for the demo:
const difference = computed(() => {
    // Round to 2 decimal places to avoid floating point precision artifacts like -0.00
    const diff = parseFloat(balanceForm.statement_ending_balance) - totalCleared.value;
    return Math.round((diff + Number.EPSILON) * 100) / 100;
});

const formatCurrency = (value) => {
    // Force -0 to 0 to remove the negative sign in display
    const sanitizedValue = Math.abs(value) < 0.001 ? 0 : value;
    
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(sanitizedValue);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const toggleLine = async (index) => {
    if (props.reconciliation.status !== 'Draft') return;

    const line = localLines.value[index];
    const newStatus = !line.is_cleared;
    
    // Optimistic UI update
    line.is_cleared = newStatus;

    try {
        await axios.post(route('accounting.reconciliations.toggle-line', [props.reconciliation.id, line.id]), {
            is_cleared: newStatus
        });
    } catch (error) {
        // Revert on failure
        line.is_cleared = !newStatus;
        showError('Failed to update line status.');
    }
};

const toggleAll = async () => {
    if (props.reconciliation.status !== 'Draft') return;

    const newStatus = !isAllCleared.value;
    
    // Optimistic UI update
    localLines.value.forEach(line => line.is_cleared = newStatus);

    try {
        await axios.post(route('accounting.reconciliations.bulk-toggle', props.reconciliation.id), {
            is_cleared: newStatus
        });
        // Success message removed to rely on global flash if any, or silence for better UX
    } catch (error) {
        // Simple reload on critical failure to sync state
        router.reload();
        showError('Failed to update all transactions.');
    }
};

const completeReconciliation = () => {
    if (Math.abs(difference.value) > 0.01) {
        showError('Difference must be zero before completing reconciliation.');
        return;
    }

    router.post(route('accounting.reconciliations.complete', props.reconciliation.id), {
        cleared_balance: totalCleared.value,
        difference: difference.value
    }, {
        onSuccess: () => {
            // Success toast is handled globally by AppLayout from flash messages
        },
        onError: (errors) => showError(errors.error || 'Failed to complete reconciliation.')
    });
};
</script>

<template>
    <Head :title="`Reconciliation ${reconciliation.account.code} - Horizon ERP`" />

    <AppLayout :fluid="true">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link :href="route('accounting.reconciliations.index')" class="text-slate-500 hover:text-slate-700">
                        <ArrowLeftIcon class="w-6 h-6" />
                    </Link>
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            {{ reconciliation.account.name }}
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">Statement Date: {{ formatDate(reconciliation.statement_date) }}</p>
                    </div>
                </div>
                
                <div class="flex items-center">
                    <span v-if="reconciliation.status === 'Completed'" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-100 text-emerald-800 font-bold text-sm border border-emerald-200 shadow-sm">
                        <CheckCircleIcon class="w-5 h-5 mr-2" />
                        Completed & Locked
                    </span>
                    <button 
                        v-else
                        @click="completeReconciliation"
                        :disabled="Math.abs(difference) > 0.01"
                        :class="[
                            'inline-flex items-center px-6 py-2 border border-transparent rounded-lg font-bold text-sm uppercase tracking-widest transition-all shadow-md',
                            Math.abs(difference) <= 0.01 ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-slate-200 text-slate-400 cursor-not-allowed'
                        ]"
                    >
                        <DocumentCheckIcon class="w-5 h-5 mr-2" />
                        Complete Reconciliation
                    </button>
                </div>
            </div>
        </template>

        <div class="h-[calc(100vh-140px)] flex flex-col md:flex-row overflow-hidden border-t border-slate-200">
            
            <!-- Left: Transactions Worksheet -->
            <div class="flex-1 bg-white overflow-auto custom-scrollbar border-r border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 relative table-fixed">
                    <thead class="bg-slate-50 sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th class="px-2 py-3 text-center text-[10px] font-black text-slate-500 uppercase w-16">
                                <div class="flex flex-col items-center">
                                    <span class="mb-1">Cleared</span>
                                    <button 
                                        @click="toggleAll"
                                        :disabled="reconciliation.status === 'Completed' || localLines.length === 0"
                                        class="focus:outline-none disabled:opacity-50"
                                        title="Select/Deselect All"
                                    >
                                        <div :class="[
                                            'w-5 h-5 rounded border flex items-center justify-center transition-colors',
                                            isAllCleared ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-400 text-transparent hover:border-indigo-500'
                                        ]">
                                            <CheckCircleIcon class="w-4 h-4" />
                                        </div>
                                    </button>
                                </div>
                            </th>
                            <th class="px-3 py-3 text-left text-[10px] font-black text-slate-500 uppercase w-28">Date</th>
                            <th class="px-3 py-3 text-left text-[10px] font-black text-slate-500 uppercase w-32">Type / Ref</th>
                            <th class="px-3 py-3 text-left text-[10px] font-black text-slate-500 uppercase">Description</th>
                            <th class="px-4 py-3 text-right text-[10px] font-black text-slate-500 uppercase w-36">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="localLines.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">No transactions found for this period.</td>
                        </tr>
                        <tr v-for="(line, index) in localLines" :key="line.id" 
                            :class="[
                                'transition-colors hover:bg-slate-50',
                                line.is_cleared ? 'bg-indigo-50/30' : ''
                            ]">
                            <td class="px-2 py-3 text-center">
                                <button 
                                    @click="toggleLine(index)"
                                    :disabled="reconciliation.status === 'Completed'"
                                    class="focus:outline-none disabled:opacity-50"
                                >
                                    <div :class="[
                                        'w-6 h-6 rounded-md border flex items-center justify-center transition-colors',
                                        line.is_cleared ? 'bg-indigo-500 border-indigo-500 text-white shadow-inner' : 'bg-white border-slate-300 text-transparent hover:border-indigo-400'
                                    ]">
                                        <CheckCircleIcon class="w-5 h-5" />
                                    </div>
                                </button>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-xs text-slate-600 font-bold">
                                {{ formatDate(line.transaction_date) }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="text-[9px] font-black px-1.5 py-0.5 rounded uppercase tracking-tighter bg-slate-100 text-slate-500 block w-max mb-1 border border-slate-200">{{ line.type }}</span>
                                <span class="text-[10px] font-mono text-slate-400 truncate block">{{ line.reference_no || 'N/A' }}</span>
                            </td>
                            <td class="px-3 py-3 text-xs text-slate-700">
                                <div class="line-clamp-2" :title="line.description">{{ line.description }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-black text-right">
                                <span :class="parseFloat(line.amount) > 0 ? 'text-emerald-600' : 'text-slate-900'">
                                    {{ formatCurrency(line.amount) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Right: Summary Dashboard -->
            <div class="w-full md:w-96 bg-slate-900 text-white flex flex-col flex-shrink-0 z-20 shadow-2xl relative overflow-hidden">
                <!-- Background Decoration -->
                <div class="absolute inset-0 bg-gradient-to-b from-indigo-900/20 to-transparent pointer-events-none"></div>

                <div class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-8 relative z-10">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest">Statement Ending Balance</h3>
                            <button 
                                v-if="reconciliation.status === 'Draft' && !isEditingBalance" 
                                @click="isEditingBalance = true"
                                class="text-slate-500 hover:text-white transition-colors"
                            >
                                <PencilSquareIcon class="w-4 h-4" />
                            </button>
                        </div>
                        
                        <div v-if="isEditingBalance" class="flex items-center space-x-2">
                            <div class="relative flex-1">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">₱</span>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    v-model="balanceForm.statement_ending_balance" 
                                    class="w-full bg-slate-800 border-indigo-500 rounded text-white pl-7 py-1 text-2xl font-black focus:ring-indigo-500"
                                    @keyup.enter="updateBalance"
                                />
                            </div>
                            <button @click="updateBalance" class="p-2 bg-emerald-600 rounded-md hover:bg-emerald-500">
                                <CheckIcon class="w-5 h-5 text-white" />
                            </button>
                            <button @click="isEditingBalance = false" class="p-2 bg-slate-700 rounded-md hover:bg-slate-600 text-xs">Esc</button>
                        </div>
                        <p v-else class="text-3xl font-black text-white">{{ formatCurrency(reconciliation.statement_ending_balance) }}</p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-center border-b border-slate-700 pb-2">
                            <span class="text-sm text-slate-400">Cleared Balance (Calculated)</span>
                            <span class="text-lg font-bold text-emerald-400">{{ formatCurrency(totalCleared) }}</span>
                        </div>
                        <div class="flex justify-between items-center border-b border-slate-700 pb-2">
                            <span class="text-sm text-slate-400">Current GL Balance</span>
                            <span class="text-lg font-bold text-slate-200">{{ formatCurrency(glBalance) }}</span>
                        </div>
                    </div>

                    <div :class="[
                        'p-6 rounded-xl border-2 transition-colors duration-500 flex flex-col items-center justify-center space-y-2 shadow-2xl',
                        Math.abs(difference) <= 0.01 ? 'bg-emerald-900/50 border-emerald-500/50' : 'bg-red-900/30 border-red-500/50'
                    ]">
                        <h3 class="text-xs font-black uppercase tracking-widest" 
                            :class="Math.abs(difference) <= 0.01 ? 'text-emerald-400' : 'text-red-400'">
                            Difference
                        </h3>
                        <p class="text-4xl font-black" :class="Math.abs(difference) <= 0.01 ? 'text-emerald-400' : 'text-red-400'">
                            {{ formatCurrency(difference) }}
                        </p>
                        <div v-if="Math.abs(difference) <= 0.01" class="text-emerald-500 flex items-center text-xs font-bold mt-2 bg-emerald-900/50 px-3 py-1 rounded-full">
                            <CheckCircleIcon class="w-4 h-4 mr-1" /> Reconciled
                        </div>
                        <div v-else class="text-red-400 flex items-center text-xs font-bold mt-2 bg-red-900/50 px-3 py-1 rounded-full">
                            <ExclamationTriangleIcon class="w-4 h-4 mr-1" /> Needs adjustment
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-slate-950 border-t border-slate-800 text-xs text-slate-500 z-10 flex-shrink-0">
                    <p><strong>Instructions:</strong> Check off the transactions that appear on your bank statement. The difference must be zero before you can complete this reconciliation.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
