<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    CalendarDaysIcon,
    DocumentTextIcon,
    PlusIcon,
    TrashIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    accounts: Array,
});

const { showSuccess, showError } = useToast();

const form = useForm({
    transaction_date: new Date().toISOString().split('T')[0],
    reference_no: '',
    description: '',
    lines: [
        { chart_of_account_id: '', debit: 0, credit: 0, memo: '' },
        { chart_of_account_id: '', debit: 0, credit: 0, memo: '' }
    ]
});

const addLine = () => {
    form.lines.push({ chart_of_account_id: '', debit: 0, credit: 0, memo: '' });
};

const removeLine = (index) => {
    if (form.lines.length > 2) {
        form.lines.splice(index, 1);
    }
};

const totalDebit = computed(() => form.lines.reduce((sum, line) => sum + parseFloat(line.debit || 0), 0));
const totalCredit = computed(() => form.lines.reduce((sum, line) => sum + parseFloat(line.credit || 0), 0));
const difference = computed(() => Math.abs(totalDebit.value - totalCredit.value));
const isBalanced = computed(() => difference.value < 0.001);

const submit = () => {
    if (!isBalanced.value) {
        showError('The journal entry is unbalanced. Debits must equal credits.');
        return;
    }

    form.post(route('journal-entries.store'), {
        onSuccess: () => showSuccess('Journal entry recorded successfully.'),
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value || 0);
};
</script>

<template>
    <Head title="Manual Journal Entry - Accounting" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Manual Journal Entry</h2>
                    <p class="text-sm text-slate-500 mt-1">Record a new double-entry transaction in the ledger.</p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Header Info -->
                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Transaction Date</label>
                            <input v-model="form.transaction_date" type="date" required class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Reference No. (JE #)</label>
                            <input v-model="form.reference_no" type="text" placeholder="e.g. JE-001" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                        </div>
                        <div class="md:col-span-1">
                             <label class="block text-sm font-bold text-slate-700 mb-2">Description / Particulars</label>
                             <input v-model="form.description" type="text" required placeholder="Description of the entry..." class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                        </div>
                    </div>

                    <!-- Entry Lines -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="px-8 py-4 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
                            <h3 class="text-sm font-black text-slate-600 uppercase tracking-widest">Entry Lines</h3>
                            <div class="flex items-center space-x-2">
                                <span v-if="isBalanced" class="text-xs font-bold text-emerald-600 flex items-center bg-emerald-50 px-3 py-1 rounded-lg border border-emerald-100 uppercase tracking-wider">
                                    <CheckCircleIcon class="w-4 h-4 mr-1.5" /> Balanced
                                </span>
                                <span v-else class="text-xs font-bold text-rose-600 flex items-center bg-rose-50 px-3 py-1 rounded-lg border border-rose-100 uppercase tracking-wider">
                                    <ExclamationTriangleIcon class="w-4 h-4 mr-1.5" /> Unbalanced by {{ formatCurrency(difference) }}
                                </span>
                            </div>
                        </div>

                        <div class="p-8">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest text-left">
                                        <th class="pb-4 px-2">Account</th>
                                        <th class="pb-4 px-2 text-right" width="180">Debit</th>
                                        <th class="pb-4 px-2 text-right" width="180">Credit</th>
                                        <th class="pb-4 px-2">Memo</th>
                                        <th class="pb-4 text-center" width="50"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="(line, index) in form.lines" :key="index" class="py-2">
                                        <td class="py-4 px-2">
                                            <select v-model="line.chart_of_account_id" required class="w-full border-slate-200 rounded-xl bg-slate-50 text-sm font-bold text-slate-700 focus:ring-blue-500/10 focus:border-blue-500">
                                                <option value="">Select Account</option>
                                                <option v-for="account in accounts" :key="account.id" :value="account.id">
                                                    {{ account.code }} - {{ account.name }}
                                                </option>
                                            </select>
                                        </td>
                                        <td class="py-4 px-2">
                                            <input v-model="line.debit" type="number" step="0.01" class="w-full text-right border-slate-200 rounded-xl bg-emerald-50/20 text-sm font-black text-emerald-700 focus:ring-emerald-500/10 focus:border-emerald-500">
                                        </td>
                                        <td class="py-4 px-2">
                                            <input v-model="line.credit" type="number" step="0.01" class="w-full text-right border-slate-200 rounded-xl bg-rose-50/20 text-sm font-black text-rose-700 focus:ring-rose-500/10 focus:border-rose-500">
                                        </td>
                                        <td class="py-4 px-2">
                                            <input v-model="line.memo" type="text" placeholder="Optional memo..." class="w-full border-slate-200 rounded-xl bg-slate-50 text-sm text-slate-600 font-medium focus:ring-blue-500/10 focus:border-blue-500">
                                        </td>
                                        <td class="py-4 text-center">
                                            <button @click="removeLine(index)" type="button" class="text-slate-300 hover:text-red-500 transition-colors">
                                                <TrashIcon class="w-5 h-5" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-slate-900 text-white font-black text-sm">
                                        <td class="px-4 py-4 uppercase tracking-widest text-[10px]">Grand Totals</td>
                                        <td class="px-4 py-4 text-right border-l border-white/10">{{ formatCurrency(totalDebit) }}</td>
                                        <td class="px-4 py-4 text-right border-l border-white/10">{{ formatCurrency(totalCredit) }}</td>
                                        <td colspan="2" class="border-l border-white/10"></td>
                                    </tr>
                                </tfoot>
                            </table>

                            <div class="mt-6 flex justify-between items-center">
                                <button @click="addLine" type="button" class="flex items-center space-x-2 text-blue-600 font-black text-xs uppercase tracking-widest hover:text-blue-700">
                                    <PlusIcon class="w-5 h-5" />
                                    <span>Add Another Line</span>
                                </button>

                                <div class="flex space-x-3">
                                    <Link :href="route('journal-entries.index')" class="px-6 py-3 text-slate-600 font-bold bg-slate-100 rounded-2xl hover:bg-slate-200 transition-colors">Discard</Link>
                                    <button 
                                        type="submit" 
                                        :disabled="form.processing || !isBalanced" 
                                        class="px-8 py-3 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-600/30 disabled:opacity-50 transition-all"
                                    >
                                        Record Journal Entry
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
