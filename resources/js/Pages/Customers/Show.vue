<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    UserIcon, 
    ArrowLeftIcon,
    EnvelopeIcon,
    PhoneIcon,
    IdentificationIcon,
    MapPinIcon,
    UserCircleIcon,
    CheckCircleIcon,
    XCircleIcon,
    CalendarIcon,
    DocumentIcon,
    EyeIcon,
    ArrowDownTrayIcon,
    HomeIcon,
    ChevronRightIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    customer: Object,
    requirements: Array,
});

const getCustomerDocument = (requirementId) => {
    return props.customer.documents?.find(d => d.document_requirement_id == requirementId);
};
</script>

<template>
    <Head :title="`${customer.full_name} - Customer Profile`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="route('customers.index')" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all">
                    <ArrowLeftIcon class="w-6 h-6" />
                </Link>
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Customer Profile</h2>
                    <p class="text-sm text-slate-500 mt-1">Detailed view of {{ customer.full_name }}'s information.</p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Profile Header Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="h-36 bg-gradient-to-r from-blue-600 to-indigo-700"></div>
                    <div class="px-8 pb-8">
                        <!-- Header Content: Adjusted margins and alignment to fix overlap -->
                        <div class="relative flex flex-col md:flex-row md:items-end -mt-10 mb-8 space-y-4 md:space-y-0">
                            <div class="h-28 w-28 rounded-3xl overflow-hidden border-4 border-white bg-slate-100 shadow-lg shrink-0">
                                <img v-if="customer.profile_photo" :src="`/storage/${customer.profile_photo}`" class="h-full w-full object-cover">
                                <UserCircleIcon v-else class="h-full w-full text-slate-300" />
                            </div>
                            
                            <div class="md:ml-6 flex-1 flex flex-col md:flex-row md:items-end md:justify-between pb-1">
                                <div class="pt-4 md:pt-0">
                                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">{{ customer.full_name }}</h1>
                                    <div class="flex items-center space-x-3 mt-1">
                                        <p class="text-sm text-slate-500 font-bold">Account No: <span class="text-blue-600 font-mono">{{ customer.account_no }}</span></p>
                                        <span class="text-slate-300">|</span>
                                        <p class="text-sm text-slate-500 font-bold">ID: <span class="text-slate-700 font-mono">#{{ String(customer.id).padStart(5, '0') }}</span></p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-3 pt-4 md:pt-0">
                                    <span v-if="customer.maceda_status" class="inline-flex items-center px-4 py-1.5 text-xs font-bold rounded-xl bg-blue-50 text-blue-700 border border-blue-100 shadow-sm">
                                        <CheckCircleIcon class="w-4 h-4 mr-1.5" />
                                        Maceda Protected
                                    </span>
                                    <span v-else class="inline-flex items-center px-4 py-1.5 text-xs font-bold rounded-xl bg-slate-50 text-slate-500 border border-slate-200 shadow-sm">
                                        <XCircleIcon class="w-4 h-4 mr-1.5" />
                                        Standard Status
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 pt-6 border-t border-slate-100">
                            <!-- Contact Column -->
                            <div class="space-y-4">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Contact Information</h3>
                                <div class="space-y-3">
                                    <div class="flex items-center text-slate-600">
                                        <EnvelopeIcon class="w-5 h-5 mr-3 text-slate-400" />
                                        <span class="text-sm font-medium">{{ customer.email || 'No email provided' }}</span>
                                    </div>
                                    <div class="flex items-center text-slate-600">
                                        <PhoneIcon class="w-5 h-5 mr-3 text-slate-400" />
                                        <span class="text-sm font-medium">{{ customer.contact_no || 'No contact number' }}</span>
                                    </div>
                                    <div class="flex items-center text-slate-600">
                                        <IdentificationIcon class="w-5 h-5 mr-3 text-slate-400" />
                                        <span class="text-sm font-medium">TIN: {{ customer.tin || 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Details Column -->
                            <div class="space-y-4">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Personal Details</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs text-slate-400 font-bold uppercase">Gender</p>
                                        <p class="text-sm font-semibold text-slate-700">{{ customer.gender || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400 font-bold uppercase">Civil Status</p>
                                        <p class="text-sm font-semibold text-slate-700">{{ customer.civil_status || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400 font-bold uppercase">Created At</p>
                                        <div class="flex items-center text-sm font-semibold text-slate-700">
                                            <CalendarIcon class="w-4 h-4 mr-1 text-slate-400" />
                                            {{ new Date(customer.created_at).toLocaleDateString() }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Address Column -->
                            <div class="space-y-4">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Billing Address</h3>
                                <div class="flex items-start text-slate-600">
                                    <MapPinIcon class="w-5 h-5 mr-3 text-slate-400 shrink-0 mt-0.5" />
                                    <div class="text-sm font-medium leading-relaxed">
                                        <p>{{ customer.home_no_street }}</p>
                                        <p>{{ customer.barangay }}</p>
                                        <p>{{ customer.city }}, {{ customer.region }}</p>
                                        <p>{{ customer.zip_code }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Details & History -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Purchase History Section -->
                    <div class="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm flex flex-col">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold text-slate-800 flex items-center">
                                <HomeIcon class="w-5 h-5 mr-2 text-emerald-600" />
                                Purchase History
                            </h3>
                            <span class="px-2 py-0.5 bg-slate-100 text-[10px] font-black text-slate-500 rounded-md uppercase tracking-widest">
                                {{ (customer.contracted_sales?.length || 0) + (customer.reservations?.filter(r => r.status === 'Active').length || 0) }} Units
                            </span>
                        </div>
                        
                        <div class="space-y-4 flex-grow">
                            <!-- Show Contracted Sales First -->
                            <div v-for="sale in customer.contracted_sales" :key="'sale-'+sale.id" 
                                class="p-4 rounded-xl border border-emerald-100 bg-emerald-50/20 group hover:bg-emerald-50 transition-all">
                                <div class="flex justify-between items-start">
                                    <div class="min-w-0">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-black text-slate-900 truncate">{{ sale.unit?.name }}</span>
                                            <span class="px-1.5 py-0.5 bg-emerald-600 text-[8px] font-black text-white rounded uppercase tracking-tighter">Contracted</span>
                                        </div>
                                        <p class="text-[10px] text-emerald-600 font-bold uppercase tracking-tight">{{ sale.unit?.project?.name }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] text-slate-400 font-bold uppercase">Contract Price</p>
                                        <p class="text-sm font-black text-slate-900">{{ new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(sale.tcp) }}</p>
                                    </div>
                                </div>
                                <div class="mt-3 pt-3 border-t border-emerald-100/50 flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">No: {{ sale.contract_no }}</span>
                                    <Link :href="route('payments.show', sale.id)" class="text-[10px] font-black text-emerald-600 hover:text-emerald-700 flex items-center">
                                        View Ledger <ChevronRightIcon class="w-3 h-3 ml-1" />
                                    </Link>
                                </div>
                            </div>

                            <!-- Show Active Reservations -->
                            <div v-for="res in customer.reservations?.filter(r => r.status === 'Active')" :key="'res-'+res.id" 
                                class="p-4 rounded-xl border border-blue-100 bg-blue-50/20 group hover:bg-blue-50 transition-all">
                                <div class="flex justify-between items-start">
                                    <div class="min-w-0">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-black text-slate-900 truncate">{{ res.unit?.name }}</span>
                                            <span class="px-1.5 py-0.5 bg-blue-600 text-[8px] font-black text-white rounded uppercase tracking-tighter">Reserved</span>
                                        </div>
                                        <p class="text-[10px] text-blue-600 font-bold uppercase tracking-tight">{{ res.unit?.project?.name }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] text-slate-400 font-bold uppercase">Reservation Fee</p>
                                        <p class="text-sm font-black text-slate-900">{{ new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(res.fee) }}</p>
                                    </div>
                                </div>
                                <div class="mt-3 pt-3 border-t border-blue-100/50 flex items-center justify-between text-[10px]">
                                    <span class="font-bold text-slate-500 uppercase tracking-widest">Expiry: {{ new Date(res.expiry_date).toLocaleDateString() }}</span>
                                    <span class="font-black text-blue-600 uppercase tracking-widest">Pending Downpayment</span>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-if="(!customer.contracted_sales?.length) && (!customer.reservations?.filter(r => r.status === 'Active').length)" 
                                class="py-12 flex flex-col items-center justify-center text-slate-400">
                                <HomeIcon class="w-12 h-12 mb-2 opacity-20" />
                                <p class="text-sm font-medium">No active units or contracts.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Section -->
                    <div class="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center">
                            <DocumentIcon class="w-5 h-5 mr-2 text-blue-600" />
                            Uploaded Documents
                        </h3>
                        
                        <div class="space-y-4">
                            <div v-for="req in requirements" :key="req.id" 
                                class="flex items-center justify-between p-4 rounded-xl border border-slate-50 bg-slate-50/30 group hover:bg-slate-50 transition-colors">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center">
                                        <span class="text-sm font-bold text-slate-700 truncate">{{ req.name }}</span>
                                        <span v-if="req.is_required && !getCustomerDocument(req.id)" class="ml-2 text-[10px] bg-red-50 text-red-600 px-1.5 py-0.5 rounded font-bold uppercase tracking-wider shrink-0">Required</span>
                                    </div>
                                    <p class="text-xs text-slate-500 truncate">{{ req.description }}</p>
                                </div>

                                <div class="ml-4 shrink-0">
                                    <template v-if="getCustomerDocument(req.id)">
                                        <a 
                                            :href="`/storage/${getCustomerDocument(req.id).file_path}`" 
                                            target="_blank"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-blue-600 bg-white border border-blue-100 rounded-lg hover:bg-blue-600 hover:text-white hover:border-blue-600 shadow-sm transition-all"
                                        >
                                            <EyeIcon class="w-4 h-4 mr-1.5" />
                                            Preview
                                        </a>
                                    </template>
                                    <template v-else>
                                        <span class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-slate-400 bg-slate-100/50 rounded-lg italic">
                                            <XCircleIcon class="w-4 h-4 mr-1.5 opacity-50" />
                                            Missing
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <div v-if="requirements.length === 0" class="py-12 flex flex-col items-center justify-center text-slate-400">
                                <DocumentIcon class="w-12 h-12 mb-2 opacity-20" />
                                <p class="text-sm font-medium">No requirements configured.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
