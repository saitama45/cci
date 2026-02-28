<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { usePagination } from '@/Composables/usePagination';
import { useInputRestriction } from '@/Composables/useInputRestriction';
import { 
    FingerPrintIcon, 
    UserIcon, 
    ClockIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    ComputerDesktopIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    logs: Object,
    filters: Object,
    users: Array,
    modules: Array,
    actions: Array
});

const { formatDateDisplay } = useInputRestriction();
const pagination = usePagination(props.logs, 'admin.activity-logs.index');

onMounted(() => {
    pagination.updateData(props.logs);
});

watch(() => props.logs, (newData) => {
    pagination.updateData(newData);
}, { deep: true });

const getActionColor = (action) => {
    switch (action.toLowerCase()) {
        case 'created': return 'bg-emerald-50 text-emerald-700 border-emerald-100';
        case 'updated': return 'bg-blue-50 text-blue-700 border-blue-100';
        case 'deleted': return 'bg-rose-50 text-rose-700 border-rose-100';
        case 'approved': return 'bg-indigo-50 text-indigo-700 border-indigo-100';
        case 'cancelled': return 'bg-amber-50 text-amber-700 border-amber-100';
        case 'repriced': return 'bg-purple-50 text-purple-700 border-purple-100';
        default: return 'bg-slate-50 text-slate-700 border-slate-100';
    }
};

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString('en-PH', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};
</script>

<template>
    <Head title="Audit Trail - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Audit Trail</h2>
                    <p class="text-sm text-slate-500 mt-1">System-wide activity logs and security history.</p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <DataTable
                        title="Activity Records"
                        subtitle="Chronological trail of user actions"
                        search-placeholder="Search description..."
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
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Timestamp</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">User</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Module</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Action</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Description</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Source</th>
                            </tr>
                        </template>

                        <template #body="{ data }">
                            <tr v-for="log in data" :key="log.id" class="hover:bg-slate-50/50 transition-colors border-b border-slate-50 last:border-0">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-black text-slate-900">{{ formatDateDisplay(log.created_at) }}</span>
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">{{ formatTime(log.created_at) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200">
                                            <UserIcon class="w-4 h-4 text-slate-500" />
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-bold text-slate-900">{{ log.user?.name || 'System' }}</div>
                                            <div class="text-[10px] text-slate-400">{{ log.user?.email || 'automated@system' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-widest rounded-md border border-slate-200">
                                        {{ log.module }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest border', getActionColor(log.action)]">
                                        {{ log.action }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 font-medium">
                                    {{ log.description }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-slate-400">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <ComputerDesktopIcon class="w-3.5 h-3.5" />
                                        <span class="text-[10px] font-bold font-mono">{{ log.ip_address || '0.0.0.0' }}</span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
