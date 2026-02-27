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
    ShoppingCartIcon,
    BuildingOfficeIcon,
    CalendarIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    purchaseOrders: Object,
    filters: Object
});

const { hasPermission } = usePermission();

const statusFilter = ref(props.filters?.status || '');

const pagination = usePagination(props.purchaseOrders, 'accounting.purchase-orders.index', () => ({
    status: statusFilter.value
}));

onMounted(() => {
    pagination.updateData(props.purchaseOrders);
});

watch(statusFilter, () => {
    pagination.performSearch();
});

watch(() => props.purchaseOrders, (newData) => {
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
    const baseClasses = 'px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest border shadow-sm';
    switch (status) {
        case 'Draft': return `${baseClasses} bg-gray-50 text-gray-600 border-gray-200`;
        case 'Approved': return `${baseClasses} bg-blue-50 text-blue-600 border-blue-200`;
        case 'Billed': return `${baseClasses} bg-emerald-50 text-emerald-600 border-emerald-200`;
        case 'Closed': return `${baseClasses} bg-slate-100 text-slate-500 border-slate-300`;
        case 'Cancelled': return `${baseClasses} bg-red-50 text-red-600 border-red-200`;
        default: return baseClasses;
    }
};
</script>

<template>
    <Head title="Purchase Orders - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                    <ShoppingCartIcon class="w-6 h-6 mr-2 text-indigo-500" />
                    Purchase Orders
                </h2>
                <Link
                    v-if="hasPermission('purchase_orders.create')"
                    :href="route('accounting.purchase-orders.create')"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition ease-in-out duration-150 shadow-md shadow-indigo-100"
                >
                    <PlusIcon class="w-4 h-4 mr-1" />
                    New Purchase Order
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                    <div class="p-6">
                        <DataTable
                            title="PO Registry"
                            subtitle="Manage vendor commitments and procurement requests"
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
                                    <option value="Billed">Billed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </template>

                            <template #header>
                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">PO #</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Vendor</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Project</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                            </template>

                            <template #body="{ data: items }">
                                <tr v-for="po in items" :key="po.id" class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-slate-900">
                                        {{ po.po_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-slate-700">{{ po.vendor?.name }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ po.vendor?.tin }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                        <div class="flex items-center">
                                            <BuildingOfficeIcon class="w-4 h-4 mr-1.5 text-slate-300" />
                                            {{ po.project?.name || 'G&A / General' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                        {{ formatDate(po.po_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 font-black text-right">
                                        {{ formatCurrency(po.total_amount) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span :class="getStatusClass(po.status)">
                                            {{ po.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link
                                            :href="route('accounting.purchase-orders.show', po.id)"
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
