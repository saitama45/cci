<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { usePagination } from '@/Composables/usePagination';
import { usePermission } from '@/Composables/usePermission';
import { useToast } from '@/Composables/useToast';
import { 
    MapIcon,
    PlusIcon,
    PencilSquareIcon,
    CheckCircleIcon,
    XCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    accounts: Object,
    types: Array,
});

const { hasPermission } = usePermission();
const { showSuccess, showError } = useToast();
const pagination = usePagination(props.accounts, 'chart-of-accounts.index');

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingAccount = ref(null);

onMounted(() => {
    pagination.updateData(props.accounts);
});

watch(() => props.accounts, (newData) => {
    pagination.updateData(newData);
}, { deep: true });

const createForm = useForm({
    code: '',
    name: '',
    type: 'asset',
    category: '',
    description: '',
});

const editForm = useForm({
    name: '',
    type: '',
    category: '',
    description: '',
    is_active: true,
});

const storeAccount = () => {
    createForm.post(route('chart-of-accounts.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
            showSuccess('New account added to Chart of Accounts.');
        }
    });
};

const editAccount = (account) => {
    editingAccount.value = account;
    editForm.name = account.name;
    editForm.type = account.type;
    editForm.category = account.category;
    editForm.description = account.description;
    editForm.is_active = !!account.is_active;
    showEditModal.value = true;
};

const updateAccount = () => {
    editForm.put(route('chart-of-accounts.update', editingAccount.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
            showSuccess('Account updated successfully.');
        }
    });
};
</script>

<template>
    <Head title="Chart of Accounts - Accounting" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Chart of Accounts</h2>
                    <p class="text-sm text-slate-500 mt-1">Manage your company's accounting codes and categories.</p>
                </div>
            </div>
        </template>

        <div class="py-6 space-y-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <DataTable
                        title="Master List of Accounts"
                        subtitle="Full structure of the general ledger"
                        search-placeholder="Search code or name..."
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
                                v-if="hasPermission('chart_of_accounts.create')"
                                @click="showCreateModal = true"
                                class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition-all duration-200 flex items-center space-x-2 text-sm font-semibold shadow-lg shadow-blue-600/20"
                            >
                                <PlusIcon class="w-5 h-5" />
                                <span>Add Account</span>
                            </button>
                        </template>

                        <template #header>
                            <tr class="bg-slate-50">
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Code</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Name</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Type</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Category</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Actions</th>
                            </tr>
                        </template>

                        <template #body="{ data }">
                            <tr v-for="account in data" :key="account.id" class="hover:bg-slate-50/50 transition-colors border-b border-slate-50 last:border-0">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-slate-900 tracking-tight">
                                    {{ account.code }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-700">
                                    {{ account.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-slate-100 text-slate-500 tracking-tighter">{{ account.type }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500 font-medium">
                                    {{ account.category || 'Uncategorized' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span v-if="account.is_active" class="text-emerald-500"><CheckCircleIcon class="w-5 h-5 mx-auto" /></span>
                                    <span v-else class="text-slate-300"><XCircleIcon class="w-5 h-5 mx-auto" /></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button v-if="hasPermission('chart_of_accounts.edit')" @click="editAccount(account)" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                                        <PencilSquareIcon class="w-5 h-5" />
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- Create Account Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showCreateModal = false"></div>
            
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full relative animate-in fade-in zoom-in duration-200">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between rounded-t-3xl">
                    <h3 class="text-xl font-bold text-slate-900">Add Account</h3>
                </div>
                
                <form @submit.prevent="storeAccount" class="p-8 space-y-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Account Code</label>
                            <input v-model="createForm.code" type="text" required placeholder="e.g. 1010" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Account Name</label>
                            <input v-model="createForm.name" type="text" required placeholder="Cash in Bank" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Account Type</label>
                            <select v-model="createForm.type" required class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                                <option v-for="t in types" :key="t" :value="t" style="text-transform: capitalize;">{{ t }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Category (Sub-type)</label>
                            <input v-model="createForm.category" type="text" placeholder="e.g. Current Asset" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="showCreateModal = false" class="px-6 py-3 text-slate-600 font-bold bg-slate-100 rounded-2xl hover:bg-slate-200 transition-colors">Discard</button>
                        <button type="submit" :disabled="createForm.processing" class="px-8 py-3 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-600/30 disabled:opacity-50 transition-all">
                            Save Account
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Account Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showEditModal = false"></div>
            
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full relative animate-in fade-in zoom-in duration-200">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between rounded-t-3xl">
                    <h3 class="text-xl font-bold text-slate-900">Edit Account: {{ editingAccount?.code }}</h3>
                </div>
                
                <form @submit.prevent="updateAccount" class="p-8 space-y-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Account Name</label>
                            <input v-model="editForm.name" type="text" required class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Account Type</label>
                            <select v-model="editForm.type" required class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                                <option v-for="t in types" :key="t" :value="t" style="text-transform: capitalize;">{{ t }}</option>
                            </select>
                        </div>
                        <div class="flex items-center space-x-3 py-2">
                             <input v-model="editForm.is_active" type="checkbox" id="is_active" class="w-5 h-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500/10">
                             <label for="is_active" class="text-sm font-bold text-slate-700">This account is active</label>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="showEditModal = false" class="px-6 py-3 text-slate-600 font-bold bg-slate-100 rounded-2xl hover:bg-slate-200 transition-colors">Discard</button>
                        <button type="submit" :disabled="editForm.processing" class="px-8 py-3 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-600/30 disabled:opacity-50 transition-all">
                            Update Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
