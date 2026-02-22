<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useInputRestriction } from '@/Composables/useInputRestriction';
import { useToast } from '@/Composables/useToast';
import { 
    UserGroupIcon,
    PlusIcon,
    PencilSquareIcon,
    BuildingOfficeIcon,
    PhoneIcon,
    EnvelopeIcon,
    TagIcon,
    BanknotesIcon,
    EyeIcon,
    CheckBadgeIcon,
    ExclamationTriangleIcon,
    DocumentTextIcon
} from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextArea from '@/Components/TextArea.vue';

const props = defineProps({
    vendors: Object,
    filters: Object,
    expenseAccounts: Array,
});

const { formatTinNo, isValidEmail } = useInputRestriction();
const { showError } = useToast();

const search = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category || '');
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    tin: '',
    address: '',
    contact_person: '',
    email: '',
    phone: '',
    bank_name: '',
    bank_account_no: '',
    bank_branch: '',
    category: 'Supplier',
    verification_status: 'Pending',
    payment_terms: '',
    default_expense_account_id: '',
    is_active: true,
    remarks: '',
});

// Watch for TIN formatting
watch(() => form.tin, (val) => {
    form.tin = formatTinNo(val);
});

// Reactive validation for email
watch(() => form.email, (val) => {
    if (val && !isValidEmail(val)) {
        form.setError('email', 'Please enter a valid email address.');
    } else {
        if (form.errors.email) {
            form.clearErrors('email');
        }
    }
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (vendor) => {
    isEditing.value = true;
    editingId.value = vendor.id;
    form.clearErrors();
    form.name = vendor.name;
    form.tin = vendor.tin;
    form.address = vendor.address;
    form.contact_person = vendor.contact_person;
    form.email = vendor.email;
    form.phone = vendor.phone;
    form.bank_name = vendor.bank_name;
    form.bank_account_no = vendor.bank_account_no;
    form.bank_branch = vendor.bank_branch;
    form.category = vendor.category;
    form.verification_status = vendor.verification_status;
    form.payment_terms = vendor.payment_terms;
    form.default_expense_account_id = vendor.default_expense_account_id || '';
    form.is_active = !!vendor.is_active;
    form.remarks = vendor.remarks;
    showModal.value = true;
};

const validateForm = () => {
    form.clearErrors();
    let isValid = true;

    if (!form.name || form.name.trim() === '') {
        form.setError('name', 'Vendor name is required.');
        isValid = false;
    }

    if (form.email && !isValidEmail(form.email)) {
        form.setError('email', 'Please enter a valid email address.');
        isValid = false;
    }

    if (!form.category) {
        form.setError('category', 'Please select a category.');
        isValid = false;
    }

    if (!isValid) {
        showError('Please check the required fields highlighted in red.');
    }

    return isValid;
};

const submit = () => {
    // Force validation check
    const isValid = validateForm();
    if (!isValid) return;

    if (isEditing.value) {
        form.put(route('vendors.update', editingId.value), {
            onSuccess: () => {
                showModal.value = false;
            },
            onError: () => {
                showError('Failed to update vendor. Please check the form.');
            }
        });
    } else {
        form.post(route('vendors.store'), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
            onError: () => {
                showError('Failed to save vendor. Please check the form.');
            }
        });
    }
};

watch([search, categoryFilter], () => {
    router.get(route('vendors.index'), { 
        search: search.value,
        category: categoryFilter.value 
    }, {
        preserveState: true,
        replace: true
    });
});

const categories = ['Utility', 'Contractor', 'Government', 'Broker', 'Supplier', 'Other'];
const verificationStatuses = ['Pending', 'Verified', 'Blacklisted'];

const getStatusClass = (status) => {
    switch (status) {
        case 'Verified': return 'bg-emerald-50 text-emerald-700 border-emerald-100';
        case 'Blacklisted': return 'bg-rose-50 text-rose-700 border-rose-100';
        default: return 'bg-amber-50 text-amber-700 border-amber-100';
    }
};
</script>

<template>
    <Head title="Vendor Management" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Vendor Management</h2>
                    <p class="text-sm text-slate-500 mt-1">Manage your suppliers, contractors, and service providers.</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <button 
                        @click="openCreateModal"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-xl font-semibold text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all shadow-sm shadow-indigo-200"
                    >
                        <PlusIcon class="w-5 h-5 mr-2" />
                        Add New Vendor
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Search & Filters -->
                <div class="mb-6 bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex flex-col md:flex-row md:items-center gap-4">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Search by name, contact, or TIN..." 
                            class="block w-full pl-10 pr-3 py-2 border border-slate-200 rounded-xl leading-5 bg-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-all"
                        >
                    </div>
                    <div class="w-full md:w-48">
                        <select v-model="categoryFilter" class="block w-full border-slate-200 rounded-xl py-2 pl-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                            <option value="">All Categories</option>
                            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                        </select>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Vendor & Category</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Verification</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Contact Details</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Account Settings</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="vendor in vendors.data" :key="vendor.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 flex-shrink-0 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                            <BuildingOfficeIcon class="w-6 h-6" />
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-semibold text-slate-900">{{ vendor.name }}</div>
                                            <div class="flex items-center space-x-2 mt-0.5">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-slate-100 text-slate-600 border border-slate-200">
                                                    {{ vendor.category }}
                                                </span>
                                                <span v-if="vendor.tin" class="text-[11px] text-slate-400">TIN: {{ vendor.tin }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getStatusClass(vendor.verification_status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border">
                                        <CheckBadgeIcon v-if="vendor.verification_status === 'Verified'" class="w-3.5 h-3.5 mr-1" />
                                        <ExclamationTriangleIcon v-if="vendor.verification_status === 'Blacklisted'" class="w-3.5 h-3.5 mr-1" />
                                        {{ vendor.verification_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-slate-900">{{ vendor.contact_person || 'No Contact' }}</span>
                                        <div class="flex items-center space-x-3 mt-1 text-xs text-slate-500">
                                            <span v-if="vendor.phone" class="flex items-center"><PhoneIcon class="w-3 h-3 mr-1" /> {{ vendor.phone }}</span>
                                            <span v-if="vendor.email" class="flex items-center"><EnvelopeIcon class="w-3 h-3 mr-1" /> {{ vendor.email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    <div class="flex flex-col text-xs">
                                        <span v-if="vendor.default_expense_account" class="flex items-center">
                                            <TagIcon class="w-3 h-3 mr-1 text-indigo-400" />
                                            {{ vendor.default_expense_account.name }}
                                        </span>
                                        <span v-if="vendor.payment_terms" class="flex items-center mt-1">
                                            <ClockIcon class="w-3 h-3 mr-1 text-slate-400" />
                                            {{ vendor.payment_terms }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-3">
                                        <Link :href="route('vendors.show', vendor.id)" class="text-slate-400 hover:text-indigo-600 transition-colors">
                                            <EyeIcon class="w-5 h-5" />
                                        </Link>
                                        <button @click="openEditModal(vendor)" class="text-slate-400 hover:text-indigo-600 transition-colors">
                                            <PencilSquareIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <Modal :show="showModal" @close="showModal = false" max-width="4xl">
            <div class="p-6">
                <h3 class="text-xl font-bold text-slate-900 mb-6 border-b border-slate-100 pb-4 flex items-center">
                    <BuildingOfficeIcon class="w-6 h-6 mr-2 text-indigo-600" />
                    {{ isEditing ? 'Refine Vendor Details' : 'Add New Vendor' }}
                </h3>

                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Column 1: Identity -->
                    <div class="space-y-5">
                        <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400 border-l-2 border-indigo-500 pl-3">Identity</h4>
                        <div>
                            <InputLabel for="name" value="Legal Vendor Name" />
                            <TextInput 
                                id="name" 
                                v-model="form.name" 
                                class="mt-1 block w-full"
                                :class="{'ring-2 ring-red-500 border-red-500': form.errors.name}"
                                required 
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="tin" value="TIN (Tax ID)" />
                            <TextInput id="tin" v-model="form.tin" placeholder="000-000-000-000" class="mt-1 block w-full" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel for="category" value="Category" />
                                <select 
                                    id="category" 
                                    v-model="form.category" 
                                    class="mt-1 block w-full border-slate-200 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500 shadow-sm"
                                    :class="{'ring-2 ring-red-500 border-red-500': form.errors.category}"
                                >
                                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                                </select>
                                <InputError :message="form.errors.category" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="verification_status" value="Verification" />
                                <select id="verification_status" v-model="form.verification_status" class="mt-1 block w-full border-slate-200 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                                    <option v-for="status in verificationStatuses" :key="status" :value="status">{{ status }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Column 2: Financial & Terms -->
                    <div class="space-y-5">
                        <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400 border-l-2 border-indigo-500 pl-3">Accounting & Payment</h4>
                        <div>
                            <InputLabel for="default_expense_account_id" value="Default Expense Account" />
                            <select id="default_expense_account_id" v-model="form.default_expense_account_id" class="mt-1 block w-full border-slate-200 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                                <option value="">Select Account...</option>
                                <option v-for="acc in expenseAccounts" :key="acc.id" :value="acc.id">
                                    {{ acc.code }} - {{ acc.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="payment_terms" value="Payment Terms" />
                            <TextInput id="payment_terms" v-model="form.payment_terms" placeholder="e.g., Net 30, Due on Receipt" class="mt-1 block w-full" />
                        </div>
                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <InputLabel for="bank_name" value="Bank Name" />
                                <TextInput id="bank_name" v-model="form.bank_name" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel for="bank_account_no" value="Account No." />
                                <TextInput id="bank_account_no" v-model="form.bank_account_no" class="mt-1 block w-full" />
                            </div>
                        </div>
                    </div>

                    <!-- Column 3: Contact -->
                    <div class="space-y-5">
                        <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400 border-l-2 border-indigo-500 pl-3">Communication</h4>
                        <div>
                            <InputLabel for="contact_person" value="Contact Person" />
                            <TextInput id="contact_person" v-model="form.contact_person" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel for="email" value="Email Address" />
                            <TextInput 
                                id="email" 
                                type="email" 
                                v-model="form.email" 
                                class="mt-1 block w-full" 
                                :class="{'ring-2 ring-red-500 border-red-500': form.errors.email}"
                            />
                            <InputError :message="form.errors.email" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="phone" value="Phone / Mobile" />
                            <TextInput id="phone" v-model="form.phone" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel for="address" value="Business Address" />
                            <TextArea id="address" v-model="form.address" rows="2" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <div class="md:col-span-3 mt-6 flex justify-end space-x-3 pt-6 border-t border-slate-100">
                        <SecondaryButton @click="showModal = false">Cancel</SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            {{ isEditing ? 'Update Vendor Profile' : 'Save Vendor Profile' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
