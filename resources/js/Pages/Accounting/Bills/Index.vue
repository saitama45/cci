<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { usePagination } from '@/Composables/usePagination';
import { usePermission } from '@/Composables/usePermission';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import { 
    DocumentTextIcon,
    PlusIcon,
    EyeIcon,
    PencilSquareIcon,
    TrashIcon,
    CalendarIcon,
    BanknotesIcon,
    BuildingOfficeIcon,
    ClockIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    bills: Object,
    filters: Object
});

const { hasPermission } = usePermission();
const { showSuccess, showError } = useToast();
const { confirm } = useConfirm();

const statusFilter = ref(props.filters?.status || '');

const pagination = usePagination(props.bills, 'accounting.bills.index', () => ({
    status: statusFilter.value
}));

onMounted(() => {
    pagination.updateData(props.bills);
});

watch(statusFilter, () => {
    pagination.performSearch();
});

watch(() => props.bills, (newData) => {
    pagination.updateData(newData);
}, { deep: true });

const deleteBill = async (bill) => {
    const confirmed = await confirm({
        title: 'Delete Bill',
        message: `Are you sure you want to delete bill #${bill.bill_number}? This action cannot be undone.`
    });

    if (confirmed) {
        router.delete(route('accounting.bills.destroy', bill.id), {
            onSuccess: () => {
                // Flash message handled globally
            },
            onError: (errors) => showError(errors.error || 'Failed to delete bill.')
        });
    }
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

const getStatusClass = (status) => {
    const baseClasses = 'px-2 py-0.5 rounded text-xs font-medium';
    switch (status) {
        case 'Draft': return `${baseClasses} bg-gray-100 text-gray-800`;
        case 'Approved': return `${baseClasses} bg-blue-100 text-blue-800`;
        case 'Paid': return `${baseClasses} bg-green-100 text-green-800`;
        case 'Partial': return `${baseClasses} bg-yellow-100 text-yellow-800`;
        case 'Overdue': return `${baseClasses} bg-red-100 text-red-800`;
        case 'Cancelled': return `${baseClasses} bg-gray-300 text-gray-500`;
        default: return baseClasses;
    }
};
</script>

<template>
    <Head title="Accounts Payable (Bills) - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Accounts Payable (Bills)
                </h2>
                <Link
                    v-if="hasPermission('bills.create')"
                    :href="route('accounting.bills.create')"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <PlusIcon class="w-4 h-4 mr-1" />
                    Record Bill
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                    <div class="p-6">
                        <!-- Data Table -->
                        <DataTable
                            title="Vendor Bills"
                            subtitle="Manage and track your accounts payable"
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
                                    <option value="Partial">Partial</option>
                                    <option value="Overdue">Overdue</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </template>

                            <template #header>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Bill #</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Vendor</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Due Date</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </template>

                            <template #body="{ data: items }">
                                <tr v-for="bill in items" :key="bill.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-xs font-bold uppercase tracking-wider">
                                        <span :class="bill.type === 'Debit Memo' ? 'text-amber-600 bg-amber-50 px-2 py-1 rounded' : 'text-indigo-600 bg-indigo-50 px-2 py-1 rounded'">
                                            {{ bill.type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ bill.bill_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">
                                        <div class="flex flex-col">
                                            <span>{{ bill.vendor?.name }}</span>
                                            <span v-if="bill.project" class="text-xs text-gray-400 font-normal">
                                                {{ bill.project.name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(bill.bill_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(bill.due_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold text-right">
                                        {{ formatCurrency(bill.total_amount) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getStatusClass(bill.status)">
                                            {{ bill.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <Link
                                                :href="route('accounting.bills.show', bill.id)"
                                                class="text-indigo-600 hover:text-indigo-900"
                                                title="View Details"
                                            >
                                                <EyeIcon class="w-5 h-5" />
                                            </Link>
                                            <Link
                                                v-if="bill.status === 'Draft' && hasPermission('bills.edit')"
                                                :href="route('accounting.bills.edit', bill.id)"
                                                class="text-amber-600 hover:text-amber-900"
                                                title="Edit Draft"
                                            >
                                                <PencilSquareIcon class="w-5 h-5" />
                                            </Link>
                                            <button
                                                v-if="bill.status === 'Draft' && hasPermission('bills.delete')"
                                                @click="deleteBill(bill)"
                                                class="text-red-600 hover:text-red-900"
                                                title="Delete Draft"
                                            >
                                                <TrashIcon class="w-5 h-5" />
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
