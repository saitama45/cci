<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { usePagination } from '@/Composables/usePagination';
import { useInputRestriction } from '@/Composables/useInputRestriction';
import { 
    UserCircleIcon,
    HomeIcon,
    EyeIcon,
    ClipboardDocumentCheckIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    contracts: Object,
    filters: Object,
});

const { formatDateDisplay } = useInputRestriction();
const pagination = usePagination(props.contracts, 'payments.index');

onMounted(() => {
    pagination.updateData(props.contracts);
});

watch(() => props.contracts, (newData) => {
    pagination.updateData(newData);
}, { deep: true });

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(value || 0);
};

</script>

<template>
    <Head title="Collections - Select Contract" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Collections</h2>
                    <p class="text-sm text-slate-500 mt-1">Select an active contract to record a payment.</p>
                </div>
            </div>
        </template>

        <div class="py-6 space-y-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <DataTable
                        title="Active Contracts"
                        subtitle="List of contracts ready for collection"
                        search-placeholder="Search contract # or customer..."
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
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Unit Info</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Balance</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Action</th>
                            </tr>
                        </template>

                        <template #body="{ data }">
                            <tr v-for="contract in data" :key="contract.id" class="hover:bg-slate-50/50 transition-colors border-b border-slate-50 last:border-0">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-2">
                                        <ClipboardDocumentCheckIcon class="w-4 h-4 text-indigo-500" />
                                        <span class="text-sm font-black text-slate-900">{{ contract.contract_no }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 bg-blue-50 rounded-full flex items-center justify-center border border-blue-100">
                                            <UserCircleIcon class="w-5 h-5 text-blue-500" />
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-bold text-slate-900">{{ contract.customer?.first_name }} {{ contract.customer?.last_name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <div class="text-sm font-bold text-slate-800">{{ contract.unit?.name }}</div>
                                        <div class="text-[10px] text-blue-600 font-bold uppercase tracking-tight">{{ contract.unit?.project?.name }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-black text-rose-600">
                                    {{ formatCurrency(contract.total_dues) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <Link 
                                        :href="route('payments.show', contract.id)"
                                        class="inline-flex items-center p-2 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition-all shadow-sm group"
                                        title="View Collections"
                                    >
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
