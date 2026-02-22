<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    BuildingOfficeIcon,
    ArrowLeftIcon,
    DocumentArrowUpIcon,
    DocumentTextIcon,
    TrashIcon,
    CheckBadgeIcon,
    GlobeAltIcon,
    CreditCardIcon,
    TagIcon,
    UserCircleIcon,
    MapPinIcon
} from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    vendor: Object,
});

const showUploadModal = ref(false);

const uploadForm = useForm({
    name: '',
    category: 'Compliance',
    file: null,
});

const handleFileSelect = (event) => {
    uploadForm.file = event.target.files[0];
};

const submitUpload = () => {
    uploadForm.post(route('vendors.documents.upload', props.vendor.id), {
        onSuccess: () => {
            showUploadModal.value = false;
            uploadForm.reset();
        }
    });
};

const deleteDocument = (id) => {
    if (confirm('Are you sure you want to delete this document?')) {
        useForm({}).delete(route('vendors.documents.delete', id));
    }
};

const getStatusClass = (status) => {
    switch (status) {
        case 'Verified': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'Blacklisted': return 'bg-rose-50 text-rose-700 border-rose-200';
        default: return 'bg-amber-50 text-amber-700 border-amber-200';
    }
};
</script>

<template>
    <Head :title="'Vendor: ' + vendor.name" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link :href="route('vendors.index')" class="p-2 bg-white rounded-xl border border-slate-200 text-slate-400 hover:text-indigo-600 transition-colors shadow-sm shadow-slate-100">
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div>
                        <div class="flex items-center space-x-3">
                            <h2 class="font-bold text-2xl text-slate-800 leading-tight">{{ vendor.name }}</h2>
                            <span :class="getStatusClass(vendor.verification_status)" class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase border">
                                {{ vendor.verification_status }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500 mt-1">Vendor Registry & Compliance Profile</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Core Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Info Grid -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden p-8">
                    <h3 class="text-lg font-bold text-slate-900 mb-6 flex items-center">
                        <UserCircleIcon class="w-6 h-6 mr-2 text-indigo-600" />
                        Business Profile
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-6">
                            <div class="flex items-start space-x-4">
                                <div class="p-2 bg-slate-50 rounded-xl">
                                    <BuildingOfficeIcon class="w-5 h-5 text-slate-400" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Category</p>
                                    <p class="text-slate-900 font-semibold mt-0.5">{{ vendor.category }}</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <div class="p-2 bg-slate-50 rounded-xl">
                                    <GlobeAltIcon class="w-5 h-5 text-slate-400" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">TIN (Tax ID)</p>
                                    <p class="text-slate-900 font-semibold mt-0.5">{{ vendor.tin || 'Not Provided' }}</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <div class="p-2 bg-slate-50 rounded-xl">
                                    <TagIcon class="w-5 h-5 text-slate-400" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Default Expense Account</p>
                                    <p class="text-slate-900 font-semibold mt-0.5">
                                        {{ vendor.default_expense_account ? `${vendor.default_expense_account.code} - ${vendor.default_expense_account.name}` : 'No Default Account' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="flex items-start space-x-4">
                                <div class="p-2 bg-slate-50 rounded-xl">
                                    <CreditCardIcon class="w-5 h-5 text-slate-400" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Bank Details</p>
                                    <p class="text-slate-900 font-semibold mt-0.5">{{ vendor.bank_name || 'No Bank' }}</p>
                                    <p class="text-xs text-slate-500">{{ vendor.bank_account_no || '-' }}</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <div class="p-2 bg-slate-50 rounded-xl">
                                    <MapPinIcon class="w-5 h-5 text-slate-400" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Payment Terms</p>
                                    <p class="text-slate-900 font-semibold mt-0.5">{{ vendor.payment_terms || 'Due on Receipt' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-8 border-t border-slate-50">
                         <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Remarks / Notes</p>
                         <p class="text-slate-600 italic text-sm">{{ vendor.remarks || 'No internal notes available.' }}</p>
                    </div>
                </div>

                <!-- Document List -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-8 py-6 border-b border-slate-50 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900 flex items-center">
                            <DocumentTextIcon class="w-6 h-6 mr-2 text-indigo-600" />
                            Compliance Documents
                        </h3>
                        <button 
                            @click="showUploadModal = true"
                            class="inline-flex items-center px-4 py-2 bg-indigo-50 text-indigo-700 rounded-xl font-bold text-xs hover:bg-indigo-100 transition-colors"
                        >
                            <DocumentArrowUpIcon class="w-4 h-4 mr-2" />
                            Upload Document
                        </button>
                    </div>
                    
                    <div class="divide-y divide-slate-50">
                        <div v-for="doc in vendor.documents" :key="doc.id" class="px-8 py-4 flex items-center justify-between group hover:bg-slate-50 transition-colors">
                            <div class="flex items-center space-x-4">
                                <div class="p-2 bg-slate-100 rounded-lg group-hover:bg-white transition-colors">
                                    <DocumentTextIcon class="w-6 h-6 text-slate-400" />
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900">{{ doc.name }}</p>
                                    <p class="text-[10px] text-slate-400 uppercase tracking-widest">{{ doc.category }} • {{ new Date(doc.created_at).toLocaleDateString() }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a :href="'/storage/' + doc.file_path" target="_blank" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors">
                                    <GlobeAltIcon class="w-5 h-5" />
                                </a>
                                <button @click="deleteDocument(doc.id)" class="p-2 text-slate-400 hover:text-rose-600 transition-colors">
                                    <TrashIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                        <div v-if="vendor.documents.length === 0" class="px-8 py-12 text-center text-slate-400 italic text-sm">
                            No compliance documents uploaded yet.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Contact -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8">
                     <h3 class="text-lg font-bold text-slate-900 mb-6 border-b border-slate-50 pb-4">Contact Info</h3>
                     <div class="space-y-4">
                         <div>
                             <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Primary Contact</p>
                             <p class="text-slate-900 font-bold mt-0.5">{{ vendor.contact_person || 'N/A' }}</p>
                         </div>
                         <div>
                             <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Email</p>
                             <p class="text-slate-900 mt-0.5">{{ vendor.email || 'N/A' }}</p>
                         </div>
                         <div>
                             <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Phone</p>
                             <p class="text-slate-900 mt-0.5">{{ vendor.phone || 'N/A' }}</p>
                         </div>
                         <div>
                             <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Office Address</p>
                             <p class="text-slate-600 text-sm mt-1 leading-relaxed">{{ vendor.address || 'No address provided.' }}</p>
                         </div>
                     </div>
                </div>

                <div class="bg-indigo-900 rounded-2xl shadow-lg p-8 text-white">
                    <h3 class="text-lg font-bold mb-4">Verification Check</h3>
                    <p class="text-indigo-200 text-sm mb-6 leading-relaxed">
                        Ensure all compliance documents (BIR 2303, Permits) are uploaded before marking this vendor as <span class="text-white font-bold underline">Verified</span>.
                    </p>
                    <div class="flex items-center space-x-2 text-emerald-400" v-if="vendor.verification_status === 'Verified'">
                        <CheckBadgeIcon class="w-6 h-6" />
                        <span class="font-bold">Trust Verified Vendor</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Modal -->
        <Modal :show="showUploadModal" @close="showUploadModal = false">
            <div class="p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-6 border-b border-slate-100 pb-4">Upload Compliance Document</h3>
                <form @submit.prevent="submitUpload" class="space-y-4">
                    <div>
                        <InputLabel value="Document Name (e.g., BIR 2303, Business Permit)" />
                        <TextInput v-model="uploadForm.name" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <InputLabel value="Category" />
                        <select v-model="uploadForm.category" class="mt-1 block w-full border-slate-200 rounded-xl">
                            <option>Compliance</option>
                            <option>Financial</option>
                            <option>Contract</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="File (PDF or Image)" />
                        <input type="file" @change="handleFileSelect" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" required />
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <SecondaryButton @click="showUploadModal = false">Cancel</SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': uploadForm.processing }" :disabled="uploadForm.processing">
                            Start Upload
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
