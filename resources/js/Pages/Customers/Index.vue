<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, watch, computed, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Toggle from '@/Components/Toggle.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useErrorHandler } from '@/Composables/useErrorHandler';
import { useToast } from '@/Composables/useToast';
import { usePagination } from '@/Composables/usePagination';
import { usePermission } from '@/Composables/usePermission';
import { useInputRestriction } from '@/Composables/useInputRestriction';
import { 
    UserIcon, 
    PencilSquareIcon, 
    TrashIcon,
    PlusIcon,
    EyeIcon,
    EnvelopeIcon,
    PhoneIcon,
    IdentificationIcon,
    MapPinIcon,
    UserCircleIcon,
    PhotoIcon,
    CheckCircleIcon,
    XCircleIcon,
    DocumentIcon,
    FolderArrowDownIcon,
    ArrowUpTrayIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    customers: Object,
    requirements: Array,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDocumentsModal = ref(false);
const editingCustomerId = ref(null);

// HELPER TO FIND CUSTOMER IN LATEST DATA SOURCE
const editingCustomer = computed(() => {
    if (!editingCustomerId.value) return null;
    // Prioritize props.customers.data as it's the direct source from Inertia
    return props.customers.data.find(c => c.id == editingCustomerId.value) || 
           customerData.value.find(c => c.id == editingCustomerId.value) || 
           null;
});

// HELPER TO GET DOCUMENT FOR A REQUIREMENT
const getCustomerDocument = (requirementId) => {
    return editingCustomer.value?.documents?.find(d => d.document_requirement_id == requirementId);
};

const photoPreview = ref(null);

const { confirm } = useConfirm();
const { post, put, destroy } = useErrorHandler();
const { showSuccess, showError } = useToast();
const { hasPermission } = usePermission();
const { restrictNumeric, restrictAlphanumeric, restrictLetters, isValidEmail, formatContactNo, formatTinNo, validateFileSize } = useInputRestriction();

const {
    search: searchQuery,
    perPage: itemsPerPage,
    currentPage: activePage,
    data: customerData,
    lastPage: totalPages,
    isLoading: isTableLoading,
    showingText: paginationInfo,
    updateData: syncPagination,
    goToPage: navigateToPage,
    changePerPage: updateItemsPerPage
} = usePagination(props.customers, 'customers.index');

const openDocumentsModal = (customer) => {
    editingCustomerId.value = customer.id;
    showDocumentsModal.value = true;
};

const documentForm = useForm({
    customer_id: null,
    document_requirement_id: null,
    file: null,
});

const processingRequirementId = ref(null);

const uploadDocument = (requirementId, file) => {
    if (!editingCustomerId.value || !file) return;

    // Validate File Size (50MB)
    const validation = validateFileSize(file, 50);
    if (!validation.valid) {
        showError(validation.message);
        return;
    }

    processingRequirementId.value = requirementId;
    documentForm.customer_id = editingCustomerId.value;
    documentForm.document_requirement_id = requirementId;
    documentForm.file = file;

    documentForm.post(route('customer-documents.store'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            showSuccess('Document uploaded successfully');
            documentForm.reset();
            // RELOAD TO SYNC PROPS
            router.reload({
                only: ['customers'],
                onSuccess: (page) => {
                    syncPagination(page.props.customers);
                }
            });
        },
        onError: (errors) => {
            if (errors.file) showError(errors.file);
        },
        onFinish: () => {
            processingRequirementId.value = null;
        },
    });
};

const deleteDocument = async (document) => {
    const confirmed = await confirm({
        title: 'Remove Document',
        message: `Are you sure you want to remove the uploaded file "${document.file_name}"?`
    });

    if (confirmed) {
        // We use router.delete here instead of form.delete because we don't have a form instance for this specific document
        destroy(route('customer-documents.destroy', document.id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                showSuccess('Document removed successfully');
                router.reload({
                    only: ['customers'],
                    onSuccess: (page) => {
                        syncPagination(page.props.customers);
                    }
                });
            }
        });
    }
};

onMounted(() => {
    syncPagination(props.customers);
});

watch(() => props.customers, (newCustomers) => {
    syncPagination(newCustomers);
}, { deep: true });

const createForm = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    email: '',
    contact_no: '',
    tin: '',
    maceda_status: false,
    home_no_street: '',
    barangay: '',
    city: '',
    region: '',
    zip_code: '',
    gender: '',
    civil_status: '',
    profile_photo: null,
});

const editForm = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    email: '',
    contact_no: '',
    tin: '',
    maceda_status: false,
    home_no_street: '',
    barangay: '',
    city: '',
    region: '',
    zip_code: '',
    gender: '',
    civil_status: '',
    profile_photo: null,
    _method: 'PUT' // For file uploads via POST
});

const isCreateEmailValid = computed(() => !createForm.email || isValidEmail(createForm.email));
const isEditEmailValid = computed(() => !editForm.email || isValidEmail(editForm.email));

const handleContactInput = (e, form, field) => {
    const input = e.target;
    const start = input.selectionStart;
    const oldVal = input.value;
    const restricted = formatContactNo(oldVal);
    input.value = restricted;
    form[field] = restricted;
    nextTick(() => {
        const newPos = Math.max(0, start + (restricted.length - oldVal.length));
        input.setSelectionRange(newPos, newPos);
    });
};

const handleTinInput = (e, form, field) => {
    const input = e.target;
    const start = input.selectionStart;
    const oldVal = input.value;
    const restricted = formatTinNo(oldVal);
    input.value = restricted;
    form[field] = restricted;
    nextTick(() => {
        const newPos = Math.max(0, start + (restricted.length - oldVal.length));
        input.setSelectionRange(newPos, newPos);
    });
};

const handleLetterInput = (e, form, field) => {
    const input = e.target;
    const start = input.selectionStart;
    const oldVal = input.value;
    
    // STRIP EVERYTHING EXCEPT LETTERS AND SPACES, THEN UPPERCASE
    const cleaned = oldVal.replace(/[^a-zA-Z\s]/g, '').toUpperCase();
    
    // UPDATE FORM STATE
    form[field] = cleaned;
    
    // FORCE BROWSER INPUT TO MATCH CLEANED VALUE IMMEDIATELY
    input.value = cleaned;
    
    // MAINTAIN CURSOR POSITION
    const diff = cleaned.length - oldVal.length;
    const newPos = Math.max(0, start + diff);
    
    nextTick(() => {
        input.setSelectionRange(newPos, newPos);
    });
};

const handleNumericInput = (e, form, field, allowDecimal = false) => {
    const input = e.target;
    const start = input.selectionStart;
    const oldVal = input.value;
    const restricted = restrictNumeric(oldVal, allowDecimal, false);
    input.value = restricted;
    form[field] = restricted;
    nextTick(() => {
        const newPos = Math.max(0, start + (restricted.length - oldVal.length));
        input.setSelectionRange(newPos, newPos);
    });
};

const handleAlphanumericInput = (e, form, field) => {
    const input = e.target;
    const restricted = input.value.replace(/[^a-zA-Z0-9\s]/g, '');
    input.value = restricted;
    form[field] = restricted;
};

const onPhotoChange = (e, form) => {
    const file = e.target.files[0];
    if (file) {
        form.profile_photo = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            photoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const createCustomer = () => {
    post(route('customers.store'), createForm, {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
            photoPreview.value = null;
            showSuccess('Customer created successfully');
        },
    });
};

const editCustomer = (customer) => {
    editingCustomerId.value = customer.id;
    editForm.first_name = customer.first_name;
    editForm.middle_name = customer.middle_name;
    editForm.last_name = customer.last_name;
    editForm.email = customer.email;
    editForm.contact_no = customer.contact_no;
    editForm.tin = customer.tin;
    editForm.maceda_status = customer.maceda_status;
    editForm.home_no_street = customer.home_no_street;
    editForm.barangay = customer.barangay;
    editForm.city = customer.city;
    editForm.region = customer.region;
    editForm.zip_code = customer.zip_code;
    editForm.gender = customer.gender;
    editForm.civil_status = customer.civil_status;
    editForm.profile_photo = null;
    photoPreview.value = customer.profile_photo ? `/storage/${customer.profile_photo}` : null;
    showEditModal.value = true;
};

const updateCustomer = () => {
    // We use POST with _method=PUT because PHP doesn't handle multipart/form-data via PUT natively
    post(route('customers.update', editingCustomerId.value), editForm, {
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
            editingCustomerId.value = null;
            photoPreview.value = null;
            showSuccess('Customer updated successfully');
        },
    });
};

const deleteCustomer = async (customer) => {
    const confirmed = await confirm({
        title: 'Delete Customer',
        message: `Are you sure you want to delete ${customer.full_name}? This will remove all their records.`
    });
    
    if (confirmed) {
        destroy(route('customers.destroy', customer.id), {
            onSuccess: () => showSuccess('Customer deleted successfully'),
        });
    }
};
</script>

<template>
    <Head title="Customer Directory - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Customer Directory</h2>
                    <p class="text-sm text-slate-500 mt-1">Manage buyer profiles and contact information.</p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <DataTable
                        title="Customer Profiles"
                        subtitle="Centralized buyer information"
                        search-placeholder="Search by name, email, contact or TIN..."
                        :search="searchQuery"
                        :data="customerData"
                        :current-page="activePage"
                        :last-page="totalPages"
                        :per-page="itemsPerPage"
                        :showing-text="paginationInfo"
                        :is-loading="isTableLoading"
                        @update:search="searchQuery = $event"
                        @go-to-page="navigateToPage"
                        @change-per-page="updateItemsPerPage"
                    >
                        <template #actions>
                            <button
                                v-if="hasPermission('customers.create')"
                                @click="showCreateModal = true"
                                class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition-all duration-200 flex items-center space-x-2 text-sm font-semibold shadow-lg shadow-blue-600/20"
                            >
                                <PlusIcon class="w-5 h-5" />
                                <span>Add Customer</span>
                            </button>
                        </template>

                        <template #header>
                            <tr class="bg-slate-50">
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Customer</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Contact Info</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Location</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Actions</th>
                            </tr>
                        </template>

                        <template #body="{ data }">
                            <tr v-for="customer in data" :key="customer.id" class="hover:bg-slate-50/50 transition-colors border-b border-slate-50 last:border-0">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full overflow-hidden border border-slate-200 bg-slate-100 flex items-center justify-center">
                                            <img v-if="customer.profile_photo" :src="`/storage/${customer.profile_photo}`" class="h-full w-full object-cover">
                                            <UserCircleIcon v-else class="h-6 w-6 text-slate-400" />
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-slate-900">{{ customer.full_name }}</div>
                                            <div class="flex items-center space-x-2">
                                                <div class="text-[10px] text-blue-600 font-bold bg-blue-50 px-1.5 py-0.5 rounded">{{ customer.account_no }}</div>
                                                <div class="text-xs text-slate-500 font-mono">TIN: {{ customer.tin || 'N/A' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col space-y-1">
                                        <div class="text-xs text-slate-600">
                                            {{ customer.email || 'No email' }}
                                        </div>
                                        <div class="text-xs text-slate-600">
                                            {{ customer.contact_no || 'No contact' }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 font-medium">
                                    {{ customer.city }}, {{ customer.region }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="customer.maceda_status" class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg bg-blue-50 text-blue-700 border border-blue-100">
                                        Maceda Protected
                                    </span>
                                    <span v-else class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg bg-slate-50 text-slate-500 border border-slate-200">
                                        Standard
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-1">
                                        <Link v-if="hasPermission('customers.show') && customer?.id" :href="route('customers.show', customer.id)" class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" title="View Profile"><EyeIcon class="w-5 h-5" /></Link>
                                        <button v-if="hasPermission('customers.upload_documents')" @click="openDocumentsModal(customer)" class="p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-all" title="Manage Documents"><DocumentIcon class="w-5 h-5" /></button>
                                        <button v-if="hasPermission('customers.edit')" @click="editCustomer(customer)" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Edit Profile"><PencilSquareIcon class="w-5 h-5" /></button>
                                        <button v-if="hasPermission('customers.delete')" @click="deleteCustomer(customer)" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Delete Profile"><TrashIcon class="w-5 h-5" /></button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modals (Unified Style) -->
        <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showCreateModal = showEditModal = false"></div>
            
            <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full relative overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-xl font-bold text-slate-900">{{ showCreateModal ? 'New Customer Profile' : 'Edit Customer Profile' }}</h3>
                    <p class="text-sm text-slate-500">Provide details for the customer record.</p>
                </div>
                
                <form @submit.prevent="showCreateModal ? createCustomer() : updateCustomer()" class="p-8 space-y-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
                    <!-- Personal Info Section -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-bold text-blue-600 uppercase tracking-widest border-b border-blue-100 pb-2">Personal Information</h4>
                        
                        <div class="flex items-start space-x-6">
                            <div class="flex-shrink-0 relative group mt-1">
                                <div class="w-24 h-24 rounded-2xl bg-slate-100 border-2 border-dashed border-slate-300 flex items-center justify-center overflow-hidden transition-colors group-hover:border-blue-400 group-hover:bg-blue-50">
                                    <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover">
                                    <div v-else class="flex flex-col items-center">
                                        <PlusIcon class="w-6 h-6 text-slate-400 group-hover:text-blue-500" />
                                        <span class="text-[10px] font-bold text-slate-400 mt-1 uppercase tracking-wider group-hover:text-blue-500">Photo</span>
                                    </div>
                                </div>
                                <input type="file" @change="onPhotoChange($event, showCreateModal ? createForm : editForm)" class="absolute inset-0 opacity-0 cursor-pointer">
                            </div>
                            
                            <div class="flex-1 grid grid-cols-3 gap-4">
                                <div class="col-span-3 sm:col-span-1">
                                    <label class="block text-sm font-bold text-slate-700 mb-1">First Name</label>
                                    <input @input="handleLetterInput($event, showCreateModal ? createForm : editForm, 'first_name')" :value="showCreateModal ? createForm.first_name : editForm.first_name" type="text" required class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all uppercase font-semibold">
                                </div>
                                <div class="col-span-3 sm:col-span-1">
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Middle Name <span class="text-[10px] text-slate-400 font-normal">(Optional)</span></label>
                                    <input @input="handleLetterInput($event, showCreateModal ? createForm : editForm, 'middle_name')" :value="showCreateModal ? createForm.middle_name : editForm.middle_name" type="text" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all uppercase font-semibold">
                                </div>
                                <div class="col-span-3 sm:col-span-1">
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Last Name</label>
                                    <input @input="handleLetterInput($event, showCreateModal ? createForm : editForm, 'last_name')" :value="showCreateModal ? createForm.last_name : editForm.last_name" type="text" required class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all uppercase font-semibold">
                                </div>
                                <div class="col-span-3 sm:col-span-1">
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Gender</label>
                                    <select v-model="(showCreateModal ? createForm : editForm).gender" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="col-span-3 sm:col-span-2">
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Civil Status</label>
                                    <select v-model="(showCreateModal ? createForm : editForm).civil_status" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                                        <option value="">Select Status</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Divorced">Divorced</option>
                                        <option value="Widowed">Widowed</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact & ID Section -->
                    <div class="space-y-4 pt-4">
                        <h4 class="text-xs font-bold text-emerald-600 uppercase tracking-widest border-b border-emerald-100 pb-2">Identification & Contact</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2 sm:col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Email Address</label>
                                <input v-model="(showCreateModal ? createForm : editForm).email" type="email" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" placeholder="name@example.com">
                                <p v-if="(showCreateModal ? createForm : editForm).email && !(showCreateModal ? isCreateEmailValid : isEditEmailValid)" class="text-[10px] text-red-500 mt-1 font-bold">Invalid email format.</p>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Contact No.</label>
                                <input @input="handleContactInput($event, showCreateModal ? createForm : editForm, 'contact_no')" :value="showCreateModal ? createForm.contact_no : editForm.contact_no" type="text" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" placeholder="09XX XXX XXXX" maxlength="13">
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Tin No.</label>
                                <input @input="handleTinInput($event, showCreateModal ? createForm : editForm, 'tin')" :value="showCreateModal ? createForm.tin : editForm.tin" type="text" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" placeholder="XXX-XXX-XXX-XXX" maxlength="15">
                            </div>
                            <div class="col-span-2 sm:col-span-1 flex items-center pt-4 sm:pt-7">
                                <Toggle 
                                    v-model="(showCreateModal ? createForm : editForm).maceda_status" 
                                    label="Maceda Law Protection" 
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Address Section -->
                    <div class="space-y-4 pt-4">
                        <h4 class="text-xs font-bold text-amber-600 uppercase tracking-widest border-b border-amber-100 pb-2">Billing Address</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-1">House No. / Street / Village</label>
                                <input v-model="(showCreateModal ? createForm : editForm).home_no_street" type="text" class="block w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Barangay</label>
                                <input v-model="(showCreateModal ? createForm : editForm).barangay" type="text" class="block w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">City / Municipality</label>
                                <input v-model="(showCreateModal ? createForm : editForm).city" type="text" class="block w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Region</label>
                                <input v-model="(showCreateModal ? createForm : editForm).region" type="text" class="block w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Zip Code</label>
                                <input @input="handleNumericInput($event, showCreateModal ? createForm : editForm, 'zip_code', false)" :value="showCreateModal ? createForm.zip_code : editForm.zip_code" type="text" class="block w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="showCreateModal = showEditModal = false" class="px-6 py-2.5 text-slate-600 font-bold bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">Discard</button>
                        <button type="submit" :disabled="createForm.processing || editForm.processing" class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-600/20 disabled:opacity-50 transition-all">
                            {{ showCreateModal ? 'Save' : 'Update Profile' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Documents Management Modal -->
        <div v-if="showDocumentsModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showDocumentsModal = false"></div>
            
            <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full relative overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Document Management</h3>
                        <p class="text-sm text-slate-500 font-medium">{{ editingCustomer?.full_name }}</p>
                    </div>
                    <button @click="showDocumentsModal = false" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-all"><XCircleIcon class="w-6 h-6" /></button>
                </div>
                
                <div class="p-8 max-h-[70vh] overflow-y-auto custom-scrollbar">
                    <div class="space-y-6">
                        <div v-for="category in [...new Set(props.requirements.map(r => r.category))]" :key="category" class="space-y-3">
                            <h4 class="text-xs font-bold text-blue-600 uppercase tracking-widest border-b border-blue-50 pb-2">{{ category }} Documents</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="req in props.requirements.filter(r => r.category === category)" :key="req.id" 
                                    class="p-4 rounded-xl border border-slate-100 bg-slate-50/50 hover:bg-slate-50 transition-colors group">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center">
                                                <span class="text-sm font-bold text-slate-800">{{ req.name }}</span>
                                                <span v-if="req.is_required" class="ml-2 text-[10px] bg-red-50 text-red-600 px-1.5 py-0.5 rounded font-bold uppercase tracking-wider">Required</span>
                                            </div>
                                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ req.description }}</p>
                                        </div>
                                        
                                        <div class="ml-4">
                                            <div class="relative group/upload">
                                                <template v-if="processingRequirementId === req.id">
                                                    <div class="p-2">
                                                        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                                                    </div>
                                                </template>
                                                <template v-else>
                                                    <input 
                                                        type="file" 
                                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                                        @change="(e) => uploadDocument(req.id, e.target.files[0])"
                                                        accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                                                    >
                                                    <div class="bg-white border border-slate-200 p-2 rounded-lg text-slate-400 group-hover/upload:text-blue-600 group-hover/upload:border-blue-200 group-hover/upload:shadow-sm transition-all">
                                                        <ArrowUpTrayIcon class="w-5 h-5" />
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status Indicator & File Info -->
                                    <div class="mt-3 flex items-center justify-between">
                                        <div class="flex items-center text-[10px] font-bold">
                                            <template v-if="getCustomerDocument(req.id)">
                                                <div class="text-emerald-600 flex items-center">
                                                    <CheckCircleIcon class="w-3.5 h-3.5 mr-1" />
                                                    Uploaded
                                                </div>
                                            </template>
                                            <template v-else>
                                                <div class="text-slate-400 italic flex items-center">
                                                    <FolderArrowDownIcon class="w-3.5 h-3.5 mr-1" />
                                                    Ready for upload
                                                </div>
                                            </template>
                                        </div>

                                        <!-- View/Delete Uploaded File -->
                                        <div v-if="getCustomerDocument(req.id)" class="flex items-center space-x-2">
                                            <a 
                                                :href="`/storage/${getCustomerDocument(req.id).file_path}`" 
                                                target="_blank"
                                                class="text-[10px] font-bold text-blue-600 hover:text-blue-700 bg-blue-50 px-2 py-0.5 rounded transition-colors flex items-center"
                                            >
                                                <EyeIcon class="w-3 h-3 mr-1" />
                                                Preview
                                            </a>
                                            <button 
                                                v-if="hasPermission('customers.delete_documents')"
                                                @click="deleteDocument(getCustomerDocument(req.id))"
                                                class="text-red-400 hover:text-red-600 transition-colors p-1"
                                                title="Remove Document"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                    <button @click="showDocumentsModal = false" class="px-6 py-2 text-slate-600 font-bold hover:text-slate-800 transition-colors">Close</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f8fafc;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
