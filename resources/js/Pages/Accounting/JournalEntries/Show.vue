<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ArrowLeftIcon,
    DocumentTextIcon,
    CalendarDaysIcon,
    UserIcon,
    HashtagIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    journalEntry: Object,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value || 0);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-PH', {
        month: 'long',
        day: '2-digit',
        year: 'numeric'
    });
};
</script>

<template>
    <Head title="View Journal Entry - Accounting" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="route('journal-entries.index')" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-all">
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Journal Entry Detail</h2>
                    <p class="text-sm text-slate-500 mt-1">Full transaction details for the ledger entry.</p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <!-- Header -->
                    <div class="p-8 border-b border-slate-50 bg-slate-50/30 grid grid-cols-2 md:grid-cols-4 gap-6">
                        <div>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Transaction Date</span>
                            <div class="flex items-center text-sm font-bold text-slate-700">
                                <CalendarDaysIcon class="w-4 h-4 mr-2 text-slate-400" />
                                {{ formatDate(journalEntry.transaction_date) }}
                            </div>
                        </div>
                        <div>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Reference #</span>
                            <div class="flex items-center text-sm font-black text-blue-600">
                                <HashtagIcon class="w-4 h-4 mr-1 text-blue-400" />
                                {{ journalEntry.reference_no || 'N/A' }}
                            </div>
                        </div>
                        <div class="col-span-2">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Description</span>
                            <div class="text-sm font-bold text-slate-800">
                                {{ journalEntry.description }}
                            </div>
                        </div>
                    </div>

                    <!-- Lines -->
                    <div class="p-8">
                        <table class="w-full">
                            <thead>
                                <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest text-left">
                                    <th class="pb-4 px-2">Account</th>
                                    <th class="pb-4 px-2 text-right" width="150">Debit</th>
                                    <th class="pb-4 px-2 text-right" width="150">Credit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="line in journalEntry.lines" :key="line.id">
                                    <td class="py-4 px-2">
                                        <div class="flex flex-col">
                                            <span :class="['text-sm font-bold text-slate-900', line.credit > 0 ? 'ml-8' : '']">
                                                {{ line.chart_of_account.code }} - {{ line.chart_of_account.name }}
                                            </span>
                                            <span v-if="line.memo" :class="['text-[10px] text-slate-400 italic mt-0.5', line.credit > 0 ? 'ml-8' : '']">
                                                {{ line.memo }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-2 text-right text-sm font-black text-slate-700">
                                        {{ line.debit > 0 ? formatCurrency(line.debit) : '' }}
                                    </td>
                                    <td class="py-4 px-2 text-right text-sm font-black text-slate-700">
                                        {{ line.credit > 0 ? formatCurrency(line.credit) : '' }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-900 text-white font-black">
                                    <td class="px-4 py-4 uppercase tracking-widest text-[10px]">Balanced Total</td>
                                    <td class="px-4 py-4 text-right text-sm border-l border-white/10">
                                        {{ formatCurrency(journalEntry.lines.reduce((sum, l) => sum + parseFloat(l.debit), 0)) }}
                                    </td>
                                    <td class="px-4 py-4 text-right text-sm border-l border-white/10">
                                        {{ formatCurrency(journalEntry.lines.reduce((sum, l) => sum + parseFloat(l.credit), 0)) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
