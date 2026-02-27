<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { usePagination } from '@/Composables/usePagination';
import { usePermission } from '@/Composables/usePermission';
import { 
    PlusIcon,
    EyeIcon,
    ScaleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    reconciliations: Object,
    filters: Object
});

const { hasPermission } = usePermission();

const pagination = usePagination(props.reconciliations, 'accounting.reconciliations.index');

onMounted(() => {
    pagination.updateData(props.reconciliations);
});

watch(() => props.reconciliations, (newData) => {
    pagination.updateData(newData);
}, { deep: true });

const formatCurrency = (value) => {
    // Round to 2 decimal places first to clear tiny precision artifacts
    const rounded = Math.round((parseFloat(value) + Number.EPSILON) * 100) / 100;
    // Force -0 to 0 to remove the negative sign in display
    const sanitizedValue = Math.abs(rounded) < 0.01 ? 0 : rounded;

    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(sanitizedValue);
};

const isZero = (value) => {
    return Math.abs(parseFloat(value)) < 0.01;
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getStatusClass = (status) => {
    const baseClasses = 'px-2 py-0.5 rounded text-xs font-medium';
    switch (status) {
        case 'Draft': return `${baseClasses} bg-gray-100 text-gray-800`;
        case 'Completed': return `${baseClasses} bg-emerald-100 text-emerald-800`;
        default: return baseClasses;
    }
};
</script>

<template>
    <Head title="Bank Reconciliations - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                    <ScaleIcon class="w-6 h-6 mr-2 text-indigo-500" />
                    Bank Reconciliations
                </h2>
                <Link
                    v-if="hasPermission('accounting.view')"
                    :href="route('accounting.reconciliations.create')"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none transition ease-in-out duration-150"
                >
                    <PlusIcon class="w-4 h-4 mr-1" />
                    New Reconciliation
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                    <div class="p-6">
                        <DataTable
                            title="Reconciliation Statements"
                            subtitle="Align your GL cash balances with official bank statements"
                            :data="pagination.data.value"
                            :search="pagination.search.value"
                            @update:search="pagination.search.value = $event"
                            :current-page="pagination.currentPage.value"
                            :last-page="pagination.lastPage.value"
                            :per-page="pagination.perPage.value"
                            :showing-text="pagination.showingText.value"
                            :is-loading="pagination.isLoading.value"
                            @go-to-page="pagination.goToPage"
                            @change-per-page="pagination.changePerPage"
                        >
                            <template #header>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Account</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Statement Date</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Statement Balance</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Difference</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </template>

                            <template #body="{ data: items }">
                                <tr v-for="recon in items" :key="recon.id" class="hover:bg-slate-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-slate-900">{{ recon.account?.name }}</div>
                                        <div class="text-xs text-slate-500 font-mono">{{ recon.account?.code }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 font-medium">
                                        {{ formatDate(recon.statement_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 font-bold text-right">
                                        {{ formatCurrency(recon.statement_ending_balance) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-right">
                                        <span :class="isZero(recon.difference) ? 'text-emerald-500' : 'text-red-500'">
                                            {{ formatCurrency(recon.difference) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span :class="getStatusClass(recon.status)">
                                            {{ recon.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link
                                            :href="route('accounting.reconciliations.show', recon.id)"
                                            class="text-indigo-600 hover:text-indigo-900"
                                            title="View Details"
                                        >
                                            <EyeIcon class="w-5 h-5 ml-auto" />
                                        </Link>
                                    </td>
                                </tr>
                            </template>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
