<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    BuildingLibraryIcon,
    Cog6ToothIcon
} from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    banks: Array
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const selectedBank = ref(null);

const form = useForm({
    name: '',
    code: '',
    branch: '',
    is_active: true,
    cheque_config: {
        paper_width: 612, // 8.5 inch in pts
        paper_height: 252, // 3.5 inch in pts
        date_top: 40,
        date_right: 50,
        payee_top: 85,
        payee_left: 100,
        amount_top: 85,
        amount_right: 50,
        words_top: 115,
        words_left: 50
    }
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (bank) => {
    isEditing.value = true;
    selectedBank.value = bank;
    form.name = bank.name;
    form.code = bank.code;
    form.branch = bank.branch;
    form.is_active = bank.is_active;
    if (bank.cheque_config) {
        form.cheque_config = { ...form.cheque_config, ...bank.cheque_config };
    }
    isModalOpen.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('accounting.banks.update', selectedBank.value.id), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('accounting.banks.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const closeModal = () => {
    isModalOpen.value = false;
};
</script>

<template>
    <Head title="Bank Management - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                    <BuildingLibraryIcon class="w-6 h-6 mr-2 text-indigo-500" />
                    Bank Management
                </h2>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition ease-in-out duration-150 shadow-md"
                >
                    <PlusIcon class="w-4 h-4 mr-1" />
                    Add Bank
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="bank in banks" :key="bank.id" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 bg-indigo-50 rounded-lg flex items-center justify-center text-indigo-600">
                                        <BuildingLibraryIcon class="w-8 h-8" />
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-900">{{ bank.name }}</h3>
                                        <p class="text-xs font-black text-indigo-500 uppercase tracking-widest">{{ bank.code }}</p>
                                    </div>
                                </div>
                                <span :class="[
                                    'px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest border',
                                    bank.is_active ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-slate-50 text-slate-400 border-slate-100'
                                ]">
                                    {{ bank.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            
                            <div class="mt-6 space-y-2">
                                <p class="text-xs text-slate-500 flex items-center">
                                    <BuildingLibraryIcon class="w-3.5 h-3.5 mr-1.5" />
                                    Branch: {{ bank.branch || 'Head Office' }}
                                </p>
                                <p class="text-[10px] text-slate-400 italic">Alignment Config: {{ bank.cheque_config ? 'Customized' : 'System Default' }}</p>
                            </div>

                            <div class="mt-6 pt-4 border-t border-slate-50 flex justify-end space-x-2">
                                <button @click="openEditModal(bank)" class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all">
                                    <Cog6ToothIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bank Configuration Modal -->
        <Modal :show="isModalOpen" @close="closeModal" max-width="2xl">
            <div class="p-6">
                <h3 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-4 mb-6">
                    {{ isEditing ? 'Configure Bank: ' + form.code : 'Add New Bank' }}
                </h3>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700">Bank Name</label>
                            <input type="text" v-model="form.name" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g. Banco de Oro" />
                            <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700">Bank Code (For Template)</label>
                            <input type="text" v-model="form.code" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g. BDO" />
                            <div v-if="form.errors.code" class="text-red-500 text-xs mt-1">{{ form.errors.code }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700">Branch (Optional)</label>
                            <input type="text" v-model="form.branch" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                            <div v-if="form.errors.branch" class="text-red-500 text-xs mt-1">{{ form.errors.branch }}</div>
                        </div>
                        <div v-if="isEditing">
                            <label class="block text-sm font-bold text-slate-700">Status</label>
                            <select v-model="form.is_active" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option :value="true">Active</option>
                                <option :value="false">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-6 border border-slate-200">
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4 flex items-center">
                            <Cog6ToothIcon class="w-4 h-4 mr-2" />
                            Cheque Alignment Settings (in Points)
                        </h4>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div>
                                <label class="text-[10px] font-bold text-slate-500">Date Top</label>
                                <input type="number" v-model="form.cheque_config.date_top" class="mt-1 block w-full rounded border-slate-300 text-sm" />
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-500">Date Right</label>
                                <input type="number" v-model="form.cheque_config.date_right" class="mt-1 block w-full rounded border-slate-300 text-sm" />
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-500">Payee Top</label>
                                <input type="number" v-model="form.cheque_config.payee_top" class="mt-1 block w-full rounded border-slate-300 text-sm" />
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-500">Payee Left</label>
                                <input type="number" v-model="form.cheque_config.payee_left" class="mt-1 block w-full rounded border-slate-300 text-sm" />
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-500">Amount Top</label>
                                <input type="number" v-model="form.cheque_config.amount_top" class="mt-1 block w-full rounded border-slate-300 text-sm" />
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-500">Amount Right</label>
                                <input type="number" v-model="form.cheque_config.amount_right" class="mt-1 block w-full rounded border-slate-300 text-sm" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-3 mt-8">
                        <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 rounded-md hover:bg-indigo-700 shadow-md">
                            {{ isEditing ? 'Update Bank' : 'Save Bank' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
