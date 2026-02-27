<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon, ScaleIcon, CalendarIcon, CurrencyDollarIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    bankAccounts: Array
});

const form = useForm({
    chart_of_account_id: '',
    start_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0], // First day of current month
    statement_date: new Date().toISOString().split('T')[0],
    statement_ending_balance: 0
});

const submit = () => {
    form.post(route('accounting.reconciliations.store'));
};
</script>

<template>
    <Head title="Start Bank Reconciliation - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="route('accounting.reconciliations.index')" class="text-slate-500 hover:text-slate-700">
                    <ArrowLeftIcon class="w-6 h-6" />
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Start New Bank Reconciliation
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8">
                    
                    <div class="mb-8 flex items-center justify-center space-x-4 p-6 bg-slate-50 rounded-xl border border-slate-100">
                        <ScaleIcon class="w-12 h-12 text-indigo-300" />
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Match Your Books with the Bank</h3>
                            <p class="text-sm text-slate-500 mt-1">Enter the details from your official bank statement to begin.</p>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Select Bank Account</label>
                            <select v-model="form.chart_of_account_id" class="block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="" disabled>Select an account...</option>
                                <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                                    {{ account.code }} - {{ account.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.chart_of_account_id" class="text-red-500 text-xs mt-1">{{ form.errors.chart_of_account_id }}</div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center">
                                    <CalendarIcon class="w-4 h-4 mr-2 text-slate-400" />
                                    Statement Start Date
                                </label>
                                <input type="date" v-model="form.start_date" class="block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                <div v-if="form.errors.start_date" class="text-red-500 text-xs mt-1">{{ form.errors.start_date }}</div>
                                <p class="text-[10px] text-slate-500 mt-1">Transactions before this date will be ignored in this session.</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center">
                                    <CalendarIcon class="w-4 h-4 mr-2 text-slate-400" />
                                    Statement End Date
                                </label>
                                <input type="date" v-model="form.statement_date" class="block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                <div v-if="form.errors.statement_date" class="text-red-500 text-xs mt-1">{{ form.errors.statement_date }}</div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center">
                                <CurrencyDollarIcon class="w-4 h-4 mr-2 text-slate-400" />
                                Statement Ending Balance
                            </label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-slate-500 sm:text-sm">₱</span>
                                </div>
                                <input type="number" step="0.01" v-model="form.statement_ending_balance" class="pl-10 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-black text-lg" placeholder="0.00" />
                            </div>
                            <div v-if="form.errors.statement_ending_balance" class="text-red-500 text-xs mt-1">{{ form.errors.statement_ending_balance }}</div>
                        </div>

                        <div class="pt-6 border-t border-slate-100 flex justify-end">
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="inline-flex justify-center items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-lg font-bold text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50 transition duration-150 shadow-md"
                            >
                                {{ form.processing ? 'Fetching Transactions...' : 'Start Reconciliation' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
