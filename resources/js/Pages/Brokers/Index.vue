<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { usePagination } from '@/Composables/usePagination';
import { usePermission } from '@/Composables/usePermission';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import { 
    UserGroupIcon,
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    IdentificationIcon,
    ReceiptPercentIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    brokers: Object,
    filters: Object
});

const { hasPermission } = usePermission();
const { showSuccess, showError } = useToast();
const { confirm } = useConfirm();
const pagination = usePagination(props.brokers, 'brokers.index');

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingBroker = ref(null);

onMounted(() => {
    pagination.updateData(props.brokers);
});

watch(() => props.brokers, (newData) => {
    pagination.updateData(newData);
}, { deep: true });

const createForm = useForm({
    name: '',
    commission_rate: '5.00',
    prc_license: '',
});

const editForm = useForm({
    name: '',
    commission_rate: '',
    prc_license: '',
});

const storeBroker = () => {
    createForm.post(route('brokers.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
            showSuccess('Broker created successfully.');
        }
    });
};

const editBroker = (broker) => {
    editingBroker.value = broker;
    editForm.name = broker.name;
    editForm.commission_rate = broker.commission_rate;
    editForm.prc_license = broker.prc_license;
    showEditModal.value = true;
};

const updateBroker = () => {
    editForm.put(route('brokers.update', editingBroker.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
            showSuccess('Broker updated successfully.');
        }
    });
};

const deleteBroker = async (broker) => {
    const confirmed = await confirm({
        title: 'Delete Broker',
        message: `Are you sure you want to delete ${broker.name}? This action cannot be undone.`
    });

    if (confirmed) {
        router.delete(route('brokers.destroy', broker.id), {
            onSuccess: () => showSuccess('Broker deleted successfully.'),
            onError: (errors) => showError(errors.error || 'Failed to delete broker.')
        });
    }
};
</script>

<template>
    <Head title="Brokers & Agents - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Brokers & Agents</h2>
                    <p class="text-sm text-slate-500 mt-1">Manage network of brokers and their commission structures.</p>
                </div>
            </div>
        </template>

        <div class="py-6 space-y-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <DataTable
                        title="Broker Network"
                        subtitle="List of authorized sales agents and brokers"
                        search-placeholder="Search by name or license..."
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
                            <button
                                v-if="hasPermission('brokers.create')"
                                @click="showCreateModal = true"
                                class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition-all duration-200 flex items-center space-x-2 text-sm font-semibold shadow-lg shadow-blue-600/20"
                            >
                                <PlusIcon class="w-5 h-5" />
                                <span>Add Broker</span>
                            </button>
                        </template>

                        <template #header>
                            <tr class="bg-slate-50">
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Broker Name</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">PRC License</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Comm. Rate</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Actions</th>
                            </tr>
                        </template>

                        <template #body="{ data }">
                            <tr v-for="broker in data" :key="broker.id" class="hover:bg-slate-50/50 transition-colors border-b border-slate-50 last:border-0">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 bg-indigo-50 rounded-full flex items-center justify-center border border-indigo-100">
                                            <UserGroupIcon class="w-6 h-6 text-indigo-500" />
                                        </div>
                                        <div class="ml-4 text-sm font-bold text-slate-900">{{ broker.name }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-2">
                                        <IdentificationIcon class="w-4 h-4 text-slate-400" />
                                        <span class="text-sm font-medium text-slate-600">{{ broker.prc_license || 'No License Record' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        <ReceiptPercentIcon class="w-3.5 h-3.5 mr-1" />
                                        {{ broker.commission_rate }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-1">
                                        <button v-if="hasPermission('brokers.edit')" @click="editBroker(broker)" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"><PencilSquareIcon class="w-5 h-5" /></button>
                                        <button v-if="hasPermission('brokers.delete')" @click="deleteBroker(broker)" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"><TrashIcon class="w-5 h-5" /></button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- Create Broker Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showCreateModal = false"></div>
            
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full relative animate-in fade-in zoom-in duration-200">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between rounded-t-3xl">
                    <h3 class="text-xl font-bold text-slate-900">New Broker</h3>
                </div>
                
                <form @submit.prevent="storeBroker" class="p-8 space-y-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Broker Name</label>
                            <input v-model="createForm.name" type="text" required placeholder="Full Name" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Commission Rate (%)</label>
                            <input v-model="createForm.commission_rate" type="number" step="0.01" min="0" max="100" required class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">PRC License #</label>
                            <input v-model="createForm.prc_license" type="text" placeholder="Optional" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="showCreateModal = false" class="px-6 py-3 text-slate-600 font-bold bg-slate-100 rounded-2xl hover:bg-slate-200 transition-colors">Discard</button>
                        <button type="submit" :disabled="createForm.processing" class="px-8 py-3 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-600/30 disabled:opacity-50 transition-all">
                            Save Broker
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Broker Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showEditModal = false"></div>
            
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full relative animate-in fade-in zoom-in duration-200">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between rounded-t-3xl">
                    <h3 class="text-xl font-bold text-slate-900">Edit Broker</h3>
                </div>
                
                <form @submit.prevent="updateBroker" class="p-8 space-y-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Broker Name</label>
                            <input v-model="editForm.name" type="text" required class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Commission Rate (%)</label>
                            <input v-model="editForm.commission_rate" type="number" step="0.01" min="0" max="100" required class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">PRC License #</label>
                            <input v-model="editForm.prc_license" type="text" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="showEditModal = false" class="px-6 py-3 text-slate-600 font-bold bg-slate-100 rounded-2xl hover:bg-slate-200 transition-colors">Discard</button>
                        <button type="submit" :disabled="editForm.processing" class="px-8 py-3 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-600/30 disabled:opacity-50 transition-all">
                            Update Broker
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
