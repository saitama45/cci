<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { usePagination } from '@/Composables/usePagination';
import { usePermission } from '@/Composables/usePermission';
import { useToast } from '@/Composables/useToast';
import { 
    PlusIcon,
    EyeIcon,
    PrinterIcon,
    CheckCircleIcon,
    ClockIcon,
    BanknotesIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    disbursements: Object,
    filters: Object
});

const { hasPermission } = usePermission();
const { showSuccess, showError } = useToast();

const statusFilter = ref(props.filters?.status || '');

const pagination = usePagination(props.disbursements, 'accounting.disbursements.index', () => ({
    status: statusFilter.value
}));

onMounted(() => {
    pagination.updateData(props.disbursements);
});

watch(statusFilter, () => {
    pagination.performSearch();
});

watch(() => props.disbursements, (newData) => {
    pagination.updateData(newData);
}, { deep: true });

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

const getStatusClass = (status) => {
    const baseClasses = 'px-2 py-0.5 rounded text-xs font-medium';
    switch (status) {
        case 'Draft': return `${baseClasses} bg-gray-100 text-gray-800`;
        case 'Approved': return `${baseClasses} bg-blue-100 text-blue-800`;
        case 'Paid': return `${baseClasses} bg-green-100 text-green-800`;
        case 'Cancelled': return `${baseClasses} bg-red-100 text-red-800`;
        default: return baseClasses;
    }
};

const printVoucher = (id) => {
    window.open(route('accounting.disbursements.print', id), '_blank');
};
</script>

<template>
    <Head title="Disbursements (Payment Vouchers) - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Disbursements (Payment Vouchers)
                </h2>
                <div class="flex space-x-2">
                    <Link
                        :href="route('accounting.disbursements.vault')"
                        class="inline-flex items-center px-4 py-2 bg-amber-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-600 focus:outline-none transition ease-in-out duration-150"
                    >
                        <ClockIcon class="w-4 h-4 mr-1" />
                        PDC Vault
                    </Link>
                    <Link
                        v-if="hasPermission('accounting.view')"
                        :href="route('accounting.disbursements.create')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none transition ease-in-out duration-150"
                    >
                        <PlusIcon class="w-4 h-4 mr-1" />
                        New Voucher
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                    <div class="p-6">
                        <DataTable
                            title="Payment Vouchers"
                            subtitle="Track your cash outflows and vendor payments"
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
                            <template #actions>
                                <select
                                    v-model="statusFilter"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm py-2"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="Draft">Draft</option>
                                    <option value="Approved">Approved</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </template>

                            <template #header>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Voucher #</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Vendor</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </template>

                            <template #body="{ data: items }">
                                <tr v-for="pv in items" :key="pv.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ pv.voucher_no }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">
                                        {{ pv.vendor?.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex items-center">
                                            <BanknotesIcon v-if="pv.payment_method === 'Cash'" class="w-4 h-4 mr-1 text-green-500" />
                                            <ClockIcon v-if="pv.payment_method === 'PDC'" class="w-4 h-4 mr-1 text-amber-500" />
                                            {{ pv.payment_method }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(pv.payment_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold text-right">
                                        {{ formatCurrency(pv.total_amount) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getStatusClass(pv.status)">
                                            {{ pv.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <Link
                                                :href="route('accounting.disbursements.show', pv.id)"
                                                class="text-indigo-600 hover:text-indigo-900"
                                                title="View Details"
                                            >
                                                <EyeIcon class="w-5 h-5" />
                                            </Link>
                                            <button
                                                @click="printVoucher(pv.id)"
                                                class="text-emerald-600 hover:text-emerald-900"
                                                title="Print PDF"
                                            >
                                                <PrinterIcon class="w-5 h-5" />
                                            </button>
                                        </div>
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
