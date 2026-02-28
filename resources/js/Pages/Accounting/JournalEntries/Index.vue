<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { usePagination } from '@/Composables/usePagination';
import { usePermission } from '@/Composables/usePermission';
import { 
    DocumentTextIcon,
    PlusIcon,
    MagnifyingGlassIcon,
    EyeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    journalEntries: Object,
});

const { hasPermission } = usePermission();
const pagination = usePagination(props.journalEntries, 'journal-entries.index');

onMounted(() => {
    pagination.updateData(props.journalEntries);
});

watch(() => props.journalEntries, (newData) => {
    pagination.updateData(newData);
}, { deep: true });

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value || 0);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-PH', {
        month: 'short',
        day: '2-digit',
        year: 'numeric'
    });
};
</script>

<template>
    <Head title="Journal Entries - Accounting" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Journal Entries</h2>
                    <p class="text-sm text-slate-500 mt-1">View and record all manual and automated ledger entries.</p>
                </div>
            </div>
        </template>

        <div class="py-6 space-y-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <DataTable
                        title="General Journal"
                        subtitle="Chronological record of transactions"
                        search-placeholder="Search description or reference..."
                        :search="pagination.search.value"
                        :data="pagination.data.value"
                        :current-page="pagination.currentPage.value"
                        :last-page="pagination.lastPage.value"
                        :per-page="pagination.perPage.value"
                        :showing-text="pagination.showingText.value"
                        :is-loading="pagination.isLoading.value"
                        @update:search="pagination.search.value = $event"
                        @go-to-page="pagination.goToPage"
                        @change-per-page="pagination.changePerPage"
                    >
                        <template #actions>
                            <Link
                                v-if="hasPermission('journal_entries.create')"
                                :href="route('journal-entries.create')"
                                class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition-all duration-200 flex items-center space-x-2 text-sm font-semibold shadow-lg shadow-blue-600/20"
                            >
                                <PlusIcon class="w-5 h-5" />
                                <span>Manual Entry</span>
                            </Link>
                        </template>

                        <template #header>
                            <tr class="bg-slate-50">
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Reference #</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Description</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Total Debit</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Total Credit</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Actions</th>
                            </tr>
                        </template>

                        <template #body="{ data }">
                            <tr v-for="entry in data" :key="entry.id" class="hover:bg-slate-50/50 transition-colors border-b border-slate-50 last:border-0">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-700">
                                    {{ formatDate(entry.transaction_date) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a 
                                        :href="entry.reference_url" 
                                        :target="entry.referenceable_type ? '_blank' : '_self'"
                                        class="text-xs font-black tracking-tighter bg-blue-50 px-2 py-1 rounded hover:bg-blue-100 transition-colors border border-blue-100 flex items-center w-fit"
                                        :class="entry.referenceable_type ? 'text-blue-600' : 'text-slate-600'"
                                        :title="entry.referenceable_type ? 'View original transaction: ' + (entry.reference_no || 'N/A') : 'View Journal Details'"
                                    >
                                        {{ entry.reference_no || 'Manual' }}
                                        <svg v-if="entry.referenceable_type" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        <EyeIcon v-else class="h-3 w-3 ml-1" />
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 font-medium">
                                    {{ entry.description }}
                                    <div class="text-[10px] text-slate-400 mt-0.5">Created by: {{ entry.user?.name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-black text-slate-900">
                                    {{ formatCurrency(entry.lines.reduce((sum, l) => sum + parseFloat(l.debit), 0)) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-black text-slate-900">
                                    {{ formatCurrency(entry.lines.reduce((sum, l) => sum + parseFloat(l.credit), 0)) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link :href="route('journal-entries.show', entry.id)" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                                        <EyeIcon class="w-5 h-5" />
                                    </Link>
                                </td>
                            </tr>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
