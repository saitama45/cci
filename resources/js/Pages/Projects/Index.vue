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
    BuildingOfficeIcon,
    PencilSquareIcon, 
    TrashIcon,
    PlusIcon,
    EyeIcon,
    MapIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    projects: Object,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingProject = ref(null);
const { confirm } = useConfirm();
const { post, put, destroy } = useErrorHandler();
const { showSuccess, showError } = useToast();
const { hasPermission } = usePermission();

const pagination = usePagination(props.projects, 'projects.index');

onMounted(() => {
    pagination.updateData(props.projects);
});

watch(() => props.projects, (newProjects) => {
    pagination.updateData(newProjects);
}, { deep: true });

const createForm = useForm({
    name: '',
    code: '',
    location: '',
    map_overlay: '',
    is_active: true,
});

const editForm = useForm({
    name: '',
    code: '',
    location: '',
    map_overlay: '',
    is_active: true,
});

const createProject = () => {
    post(route('projects.store'), createForm.data(), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
            showSuccess('Project created successfully')
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join(', ') || 'An error occurred'
            showError(errorMessage)
        }
    });
};

const editProject = (project) => {
    editingProject.value = project;
    editForm.name = project.name;
    editForm.code = project.code;
    editForm.location = project.location;
    editForm.map_overlay = project.map_overlay;
    editForm.is_active = !!project.is_active;
    showEditModal.value = true;
};

const updateProject = () => {
    put(route('projects.update', editingProject.value.id), editForm.data(), {
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
            editingProject.value = null;
            showSuccess('Project updated successfully')
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join(', ') || 'An error occurred'
            showError(errorMessage)
        }
    });
};

const deleteProject = async (project) => {
    const confirmed = await confirm({
        title: 'Delete Project',
        message: `Are you sure you want to delete "${project.name}"? This action cannot be undone.`
    })
    
    if (confirmed) {
        destroy(route('projects.destroy', project.id), {
            onSuccess: () => showSuccess('Project deleted successfully'),
            onError: (errors) => {
                const errorMessage = Object.values(errors).flat().join(', ') || 'Cannot delete project'
                showError(errorMessage)
            }
        });
    }
};
</script>

<template>
    <Head title="Projects - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Project Management</h2>
                    <p class="text-sm text-slate-500 mt-1">Manage development projects and inventory locations.</p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Data Table Container -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <DataTable
                        title="Projects Directory"
                        subtitle="List of active development projects"
                        search-placeholder="Search by name, code or location..."
                        empty-message="No projects found. Create your first project."
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
                                v-if="hasPermission('projects.create')"
                                @click="showCreateModal = true"
                                class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition-all duration-200 flex items-center space-x-2 text-sm font-semibold shadow-lg shadow-blue-600/20"
                            >
                                <PlusIcon class="w-5 h-5" />
                                <span>Create Project</span>
                            </button>
                        </template>

                        <template #header>
                            <tr class="bg-slate-50">
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Project Name</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Code</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Location</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Actions</th>
                            </tr>
                        </template>

                        <template #body="{ data }">
                            <tr v-for="project in data" :key="project.id" class="hover:bg-slate-50/50 transition-colors border-b border-slate-50 last:border-0">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-500 font-bold border border-slate-200">
                                            <BuildingOfficeIcon class="w-5 h-5" />
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-slate-900">{{ project.name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-1 text-xs font-mono font-bold rounded-lg bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ project.code }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-slate-500">
                                        <MapIcon class="w-4 h-4 mr-1.5" />
                                        <span class="truncate max-w-xs">{{ project.location }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        :class="[
                                            'inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg border',
                                            project.is_active 
                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-100' 
                                                : 'bg-slate-50 text-slate-500 border-slate-200'
                                        ]"
                                    >
                                        {{ project.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-1">
                                         <Link
                                            v-if="hasPermission('projects.view')"
                                            :href="route('projects.show', project.id)"
                                            class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                                            title="View Details"
                                        >
                                            <EyeIcon class="w-5 h-5" />
                                        </Link>
                                        <button
                                            v-if="hasPermission('projects.edit')"
                                            @click="editProject(project)"
                                            class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                                            title="Edit Project"
                                        >
                                            <PencilSquareIcon class="w-5 h-5" />
                                        </button>
                                        <button
                                            v-if="hasPermission('projects.delete')"
                                            @click="deleteProject(project)"
                                            class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                                            title="Delete Project"
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

        <!-- Create Project Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="showCreateModal = false"></div>
            
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full relative overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-xl font-bold text-slate-900">Create New Project</h3>
                    <p class="text-sm text-slate-500">Add a new development project to the system.</p>
                </div>
                
                <form @submit.prevent="createProject" class="p-8 space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Project Name</label>
                        <input v-model="createForm.name" type="text" required placeholder="Ex. Amiya Raya" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Project Code</label>
                        <input v-model="createForm.code" type="text" required placeholder="Ex. AR-RIZAL" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all uppercase">
                        <p class="text-xs text-slate-400 mt-1">Unique identifier (max 20 chars)</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Location</label>
                        <textarea v-model="createForm.location" rows="2" required placeholder="Physical address of the project..." class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"></textarea>
                    </div>

                    <div>
                         <label class="block text-sm font-bold text-slate-700 mb-1">Map Overlay (SVG/URL)</label>
                         <textarea v-model="createForm.map_overlay" rows="3" placeholder="Paste SVG code or URL for site plan..." class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-mono text-xs"></textarea>
                    </div>

                    <div class="flex items-center">
                        <input v-model="createForm.is_active" type="checkbox" id="create_active" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 transition-all">
                        <label for="create_active" class="ml-2 block text-sm font-medium text-slate-700">Active Status</label>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="showCreateModal = false" class="px-6 py-2.5 text-slate-600 font-bold bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">Cancel</button>
                        <button type="submit" :disabled="createForm.processing" class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-600/20 disabled:opacity-50 transition-all">Create Project</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Project Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="showEditModal = false"></div>
            
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full relative overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-xl font-bold text-slate-900">Modify Project</h3>
                    <p class="text-sm text-slate-500">Update details for <strong>{{ editingProject?.name }}</strong>.</p>
                </div>
                
                <form @submit.prevent="updateProject" class="p-8 space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Project Name</label>
                        <input v-model="editForm.name" type="text" required class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Project Code</label>
                        <input v-model="editForm.code" type="text" required class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all uppercase">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Location</label>
                        <textarea v-model="editForm.location" rows="2" required class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"></textarea>
                    </div>

                     <div>
                         <label class="block text-sm font-bold text-slate-700 mb-1">Map Overlay (SVG/URL)</label>
                         <textarea v-model="editForm.map_overlay" rows="3" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-mono text-xs"></textarea>
                    </div>

                    <div class="flex items-center">
                        <input v-model="editForm.is_active" type="checkbox" id="edit_active" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 transition-all">
                        <label for="edit_active" class="ml-2 block text-sm font-medium text-slate-700">Active Status</label>
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
