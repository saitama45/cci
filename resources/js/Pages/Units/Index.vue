<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useErrorHandler } from '@/Composables/useErrorHandler';
import { useToast } from '@/Composables/useToast';
import { usePagination } from '@/Composables/usePagination';
import { usePermission } from '@/Composables/usePermission';
import { 
    MapIcon,
    PencilSquareIcon, 
    TrashIcon,
    PlusIcon,
    EyeIcon,
    HomeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    units: Object,
    projects: Array,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingUnit = ref(null);
const { confirm } = useConfirm();
const { post, put, destroy } = useErrorHandler();
const { showSuccess, showError } = useToast();
const { hasPermission } = usePermission();

const pagination = usePagination(props.units, 'units.index');

onMounted(() => {
    pagination.updateData(props.units);
});

watch(() => props.units, (newUnits) => {
    pagination.updateData(newUnits);
}, { deep: true });

const createForm = useForm({
    project_id: '',
    block_num: '',
    lot_num: '',
    name: '',
    sqm_area: '',
    status: 'Available',
    svg_path: '',
});

const editForm = useForm({
    project_id: '',
    block_num: '',
    lot_num: '',
    name: '',
    sqm_area: '',
    status: 'Available',
    svg_path: '',
});

const createUnit = () => {
    post(route('units.store'), createForm.data(), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
            showSuccess('Unit created successfully')
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join(', ') || 'An error occurred'
            showError(errorMessage)
        }
    });
};

const editUnit = (unit) => {
    editingUnit.value = unit;
    editForm.project_id = unit.project_id;
    editForm.block_num = unit.block_num;
    editForm.lot_num = unit.lot_num;
    editForm.name = unit.name;
    editForm.sqm_area = unit.sqm_area;
    editForm.status = unit.status;
    editForm.svg_path = unit.svg_path;
    showEditModal.value = true;
};

const updateUnit = () => {
    put(route('units.update', editingUnit.value.id), editForm.data(), {
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
            editingUnit.value = null;
            showSuccess('Unit updated successfully')
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join(', ') || 'An error occurred'
            showError(errorMessage)
        }
    });
};

const deleteUnit = async (unit) => {
    const confirmed = await confirm({
        title: 'Delete Unit',
        message: `Are you sure you want to delete "${unit.name}"? This action cannot be undone.`
    })
    
    if (confirmed) {
        destroy(route('units.destroy', unit.id), {
            onSuccess: () => showSuccess('Unit deleted successfully'),
            onError: (errors) => {
                const errorMessage = Object.values(errors).flat().join(', ') || 'Cannot delete unit'
                showError(errorMessage)
            }
        });
    }
};

const statusColors = {
    'Available': 'bg-emerald-50 text-emerald-700 border-emerald-100',
    'Reserved': 'bg-blue-50 text-blue-700 border-blue-100',
    'Sold': 'bg-slate-50 text-slate-700 border-slate-200',
    'Blocked': 'bg-red-50 text-red-700 border-red-100',
};
</script>

<template>
    <Head title="Units - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Unit Inventory</h2>
                    <p class="text-sm text-slate-500 mt-1">Manage subdivision lots and housing units.</p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Data Table Container -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <DataTable
                        title="Inventory List"
                        subtitle="Detailed list of all units"
                        search-placeholder="Search by unit name, status or project..."
                        empty-message="No units found. Add your first unit."
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
                                v-if="hasPermission('units.create')"
                                @click="showCreateModal = true"
                                class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition-all duration-200 flex items-center space-x-2 text-sm font-semibold shadow-lg shadow-blue-600/20"
                            >
                                <PlusIcon class="w-5 h-5" />
                                <span>Add Unit</span>
                            </button>
                        </template>

                        <template #header>
                            <tr class="bg-slate-50">
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Unit Details</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Project</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Location</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Area (sqm)</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Actions</th>
                            </tr>
                        </template>

                        <template #body="{ data }">
                            <tr v-for="unit in data" :key="unit.id" class="hover:bg-slate-50/50 transition-colors border-b border-slate-50 last:border-0">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-500 font-bold border border-slate-200">
                                            <HomeIcon class="w-5 h-5" />
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-slate-900">{{ unit.name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-slate-600 font-medium">{{ unit.project?.name || 'N/A' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-1 text-xs font-mono font-bold rounded-lg bg-slate-100 text-slate-700 border border-slate-200">
                                        B{{ unit.block_num }} L{{ unit.lot_num }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-bold text-slate-700">{{ unit.sqm_area }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        :class="[
                                            'inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg border',
                                            statusColors[unit.status] || 'bg-slate-50 text-slate-500 border-slate-200'
                                        ]"
                                    >
                                        {{ unit.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-1">
                                         <Link
                                            v-if="hasPermission('units.view')"
                                            :href="route('units.show', unit.id)"
                                            class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                                            title="View Details"
                                        >
                                            <EyeIcon class="w-5 h-5" />
                                        </Link>
                                        <button
                                            v-if="hasPermission('units.edit')"
                                            @click="editUnit(unit)"
                                            class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                                            title="Edit Unit"
                                        >
                                            <PencilSquareIcon class="w-5 h-5" />
                                        </button>
                                        <button
                                            v-if="hasPermission('units.delete')"
                                            @click="deleteUnit(unit)"
                                            class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                                            title="Delete Unit"
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

        <!-- Create Unit Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="showCreateModal = false"></div>
            
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full relative overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-xl font-bold text-slate-900">Add New Unit</h3>
                    <p class="text-sm text-slate-500">Create a new inventory item.</p>
                </div>
                
                <form @submit.prevent="createUnit" class="p-8 space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Project</label>
                        <select v-model="createForm.project_id" required class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                            <option value="" disabled>Select Project</option>
                            <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.name }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Block No.</label>
                            <input v-model="createForm.block_num" type="number" required min="1" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Lot No.</label>
                            <input v-model="createForm.lot_num" type="number" required min="1" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Unit Name / Model</label>
                        <input v-model="createForm.name" type="text" required placeholder="Ex. Premium Corner Lot" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Area (sqm)</label>
                            <input v-model="createForm.sqm_area" type="number" step="0.01" required min="1" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Status</label>
                            <select v-model="createForm.status" required class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                                <option value="Available">Available</option>
                                <option value="Reserved">Reserved</option>
                                <option value="Sold">Sold</option>
                                <option value="Blocked">Blocked</option>
                            </select>
                        </div>
                    </div>

                    <div>
                         <label class="block text-sm font-bold text-slate-700 mb-1">SVG Path Data</label>
                         <textarea v-model="createForm.svg_path" rows="3" placeholder="Paste SVG path data for map interaction..." class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-mono text-xs"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="showCreateModal = false" class="px-6 py-2.5 text-slate-600 font-bold bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">Cancel</button>
                        <button type="submit" :disabled="createForm.processing" class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-600/20 disabled:opacity-50 transition-all">Add Unit</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Unit Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="showEditModal = false"></div>
            
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full relative overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-xl font-bold text-slate-900">Modify Unit</h3>
                    <p class="text-sm text-slate-500">Update details for <strong>{{ editingUnit?.name }}</strong>.</p>
                </div>
                
                <form @submit.prevent="updateUnit" class="p-8 space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Project</label>
                        <select v-model="editForm.project_id" required class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                            <option value="" disabled>Select Project</option>
                            <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.name }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Block No.</label>
                            <input v-model="editForm.block_num" type="number" required min="1" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Lot No.</label>
                            <input v-model="editForm.lot_num" type="number" required min="1" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Unit Name / Model</label>
                        <input v-model="editForm.name" type="text" required class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Area (sqm)</label>
                            <input v-model="editForm.sqm_area" type="number" step="0.01" required min="1" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Status</label>
                            <select v-model="editForm.status" required class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                                <option value="Available">Available</option>
                                <option value="Reserved">Reserved</option>
                                <option value="Sold">Sold</option>
                                <option value="Blocked">Blocked</option>
                            </select>
                        </div>
                    </div>

                    <div>
                         <label class="block text-sm font-bold text-slate-700 mb-1">SVG Path Data</label>
                         <textarea v-model="editForm.svg_path" rows="3" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-mono text-xs"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="showEditModal = false" class="px-6 py-2.5 text-slate-600 font-bold bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">Cancel</button>
                        <button type="submit" :disabled="editForm.processing" class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-600/20 disabled:opacity-50 transition-all">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
