<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Toggle from '@/Components/Toggle.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useErrorHandler } from '@/Composables/useErrorHandler';
import { useToast } from '@/Composables/useToast';
import { usePermission } from '@/Composables/usePermission';
import { 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon,
    ClipboardDocumentCheckIcon,
    Bars3Icon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    requirements: Array,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingRequirement = ref(null);

const { confirm } = useConfirm();
const { post, put, destroy } = useErrorHandler();
const { showSuccess } = useToast();
const { hasPermission } = usePermission();

const createForm = useForm({
    name: '',
    description: '',
    is_required: false,
    category: 'Basic',
    status: true,
    sort_order: 0,
});

const editForm = useForm({
    name: '',
    description: '',
    is_required: false,
    category: '',
    status: true,
    sort_order: 0,
});

const openEditModal = (req) => {
    editingRequirement.value = req;
    editForm.name = req.name;
    editForm.description = req.description;
    editForm.is_required = req.is_required;
    editForm.category = req.category;
    editForm.status = req.status;
    editForm.sort_order = req.sort_order;
    showEditModal.value = true;
};

const createRequirement = () => {
    post(route('document-requirements.store'), createForm, {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
            showSuccess('Requirement created successfully');
        },
    });
};

const updateRequirement = () => {
    put(route('document-requirements.update', editingRequirement.value.id), editForm, {
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
            editingRequirement.value = null;
            showSuccess('Requirement updated successfully');
        },
    });
};

const deleteRequirement = async (req) => {
    const confirmed = await confirm({
        title: 'Delete Requirement',
        message: `Are you sure you want to delete "${req.name}"? This may affect existing customer records.`
    });
    
    if (confirmed) {
        destroy(route('document-requirements.destroy', req.id), {
            onSuccess: () => showSuccess('Requirement deleted successfully'),
        });
    }
};

const categories = ['Identity', 'Income', 'Address', 'Civil Status', 'Special', 'Other'];
</script>

<template>
    <Head title="Document Checklist Config - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Document Checklist Configuration</h2>
                    <p class="text-sm text-slate-500 mt-1">Define and manage the documents required from customers.</p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <DataTable
                        title="Requirements"
                        subtitle="Document validation checklist"
                        :data="requirements"
                        :search-placeholder="null" 
                    >
                        <template #actions>
                            <button
                                v-if="hasPermission('document_requirements.create')"
                                @click="showCreateModal = true"
                                class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition-all duration-200 flex items-center space-x-2 text-sm font-semibold shadow-lg shadow-blue-600/20"
                            >
                                <PlusIcon class="w-5 h-5" />
                                <span>Add Requirement</span>
                            </button>
                        </template>

                        <template #header>
                            <tr class="bg-slate-50">
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Order</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Requirement Name</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Category</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Type</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Actions</th>
                            </tr>
                        </template>

                        <template #body="{ data }">
                            <tr v-for="req in data" :key="req.id" class="hover:bg-slate-50/50 transition-colors border-b border-slate-50 last:border-0">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400 font-mono">
                                    {{ req.sort_order }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-slate-900">{{ req.name }}</div>
                                    <div class="text-xs text-slate-500 max-w-xs truncate">{{ req.description }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-slate-100 text-slate-600">
                                        {{ req.category }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="req.is_required" class="text-xs font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded">Required</span>
                                    <span v-else class="text-xs font-bold text-slate-400 bg-slate-50 px-2 py-0.5 rounded">Optional</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="req.status" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">Active</span>
                                    <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-slate-50 text-slate-500 border border-slate-200">Inactive</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-1">
                                        <button v-if="hasPermission('document_requirements.edit')" @click="openEditModal(req)" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"><PencilSquareIcon class="w-5 h-5" /></button>
                                        <button v-if="hasPermission('document_requirements.delete')" @click="deleteRequirement(req)" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"><TrashIcon class="w-5 h-5" /></button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modals -->
        <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showCreateModal = showEditModal = false"></div>
            
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full relative overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-xl font-bold text-slate-900">{{ showCreateModal ? 'Add Requirement' : 'Edit Requirement' }}</h3>
                </div>
                
                <form @submit.prevent="showCreateModal ? createRequirement() : updateRequirement()" class="p-8 space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Requirement Name</label>
                        <input v-model="(showCreateModal ? createForm : editForm).name" type="text" required class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Category</label>
                        <select v-model="(showCreateModal ? createForm : editForm).category" required class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Description <span class="text-xs text-slate-400 font-normal">(Instructions for customer)</span></label>
                        <textarea v-model="(showCreateModal ? createForm : editForm).description" rows="3" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Sort Order</label>
                            <input v-model="(showCreateModal ? createForm : editForm).sort_order" type="number" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        </div>
                        <div class="flex items-center pt-7">
                            <Toggle v-model="(showCreateModal ? createForm : editForm).is_required" label="Required Document" />
                        </div>
                    </div>

                    <div class="flex items-center pt-2">
                        <Toggle v-model="(showCreateModal ? createForm : editForm).status" label="Active Status" />
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="showCreateModal = showEditModal = false" class="px-6 py-2.5 text-slate-600 font-bold bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">Discard</button>
                        <button type="submit" :disabled="createForm.processing || editForm.processing" class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-600/20 disabled:opacity-50 transition-all">
                            {{ showCreateModal ? 'Save' : 'Update' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
