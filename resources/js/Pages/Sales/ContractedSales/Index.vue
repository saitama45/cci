<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { usePagination } from '@/Composables/usePagination';
import { 
    ClipboardDocumentCheckIcon,
    UserCircleIcon,
    HomeIcon,
    EyeIcon,
    CurrencyDollarIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    sales: Object,
    filters: Object
});

const pagination = usePagination(props.sales, 'contracted-sales.index');

onMounted(() => {
    pagination.updateData(props.sales);
});

watch(() => props.sales, (newData) => {
    pagination.updateData(newData);
}, { deep: true });

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
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

const viewLedger = (id) => {
    router.get(route('contracted-sales.show', id));
};
</script>

<template>
    <Head title="Contracted Sales - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Contracted Sales</h2>
                    <p class="text-sm text-slate-500 mt-1">Portfolio of sold units and active payment plans.</p>
                </div>
            </div>
        </template>

        <div class="py-6 space-y-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <DataTable
                        title="Sales Inventory"
                        subtitle="Converted contracts and ownership records"
                        search-placeholder="Search by customer or unit..."
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
                        <template #header>
                            <tr class="bg-slate-50">
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Contract #</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Customer</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Unit Details</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Total Contract Price</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Paid to Date</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Balance</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Actions</th>
                            </tr>
                        </template>

                        <template #body="{ data }">
                            <tr v-for="sale in data" :key="sale.id" class="hover:bg-slate-50/50 transition-colors border-b border-slate-50 last:border-0">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-blue-600">
                                    {{ sale.contract_no }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 bg-indigo-50 rounded-full flex items-center justify-center border border-indigo-100 text-indigo-500 font-bold">
                                            {{ sale.customer?.last_name?.charAt(0) }}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-slate-900">{{ sale.customer?.first_name }} {{ sale.customer?.last_name }}</div>
                                            <div class="text-[10px] text-slate-500 font-medium uppercase tracking-wider">Contracted: {{ formatDateDisplay(sale.start_date) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <div class="text-sm font-bold text-slate-800">{{ sale.unit?.name }}</div>
                                        <div class="text-xs text-blue-600 font-bold tracking-tight">{{ sale.unit?.project?.name }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-slate-900">
                                    {{ formatCurrency(sale.unit?.price_list?.tcp) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-emerald-600">{{ formatCurrency(sale.total_paid) }}</span>
                                        <div class="w-24 bg-slate-100 h-1 rounded-full mt-1 overflow-hidden">
                                            <div 
                                                class="bg-emerald-500 h-full transition-all duration-500" 
                                                :style="{ width: Math.min(100, (sale.total_paid / (sale.unit?.price_list?.tcp || 1)) * 100) + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-rose-600">
                                    {{ formatCurrency((sale.unit?.price_list?.tcp || 0) - sale.total_paid) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button 
                                        @click="viewLedger(sale.id)"
                                        class="inline-flex items-center p-2 text-blue-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                                        title="View Details & Ledger"
                                    >
                                        <EyeIcon class="w-5 h-5" />
                                        <span class="ml-1 text-xs font-bold">Ledger</span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
