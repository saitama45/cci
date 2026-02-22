<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, watch, computed, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Autocomplete from '@/Components/Autocomplete.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useErrorHandler } from '@/Composables/useErrorHandler';
import { useToast } from '@/Composables/useToast';
import { usePagination } from '@/Composables/usePagination';
import { usePermission } from '@/Composables/usePermission';
import { useInputRestriction } from '@/Composables/useInputRestriction';
import { 
    ClipboardDocumentListIcon,
    PencilSquareIcon, 
    TrashIcon,
    PlusIcon,
    CalendarDaysIcon,
    CurrencyDollarIcon,
    UserCircleIcon,
    HomeIcon,
    ClockIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    reservations: Object,
    customers: Array,
    units: Array,
    brokers: Array,
    payment_methods: Array,
    stats: Object
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingReservation = ref(null);

const { confirm } = useConfirm();
const { post, put, destroy: inertiaDelete } = useErrorHandler();
const { showSuccess, showError } = useToast();
const { hasPermission } = usePermission();
const { restrictNumeric, formatNumberWithCommas, stripCommas, formatDateForInput, formatDateDisplay } = useInputRestriction();

const pagination = usePagination(props.reservations, 'reservations.index');

const filterByStatus = (status = null) => {
    router.get(route('reservations.index'), { 
        status: status,
        search: pagination.search.value,
        per_page: pagination.perPage.value
    }, { 
        preserveState: true,
        replace: true 
    });
};

onMounted(() => {
    pagination.updateData(props.reservations);
});

watch(() => props.reservations, (newReservations) => {
    pagination.updateData(newReservations);
}, { deep: true });

const createForm = useForm({
    customer_id: '',
    unit_id: '',
    broker_id: '',
    reservation_date: formatDateForInput(new Date()),
    expiry_date: formatDateForInput(new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)),
    fee: '25000',
    payment_method: 'Cash',
    reference_no: '',
});

const editForm = useForm({
    customer_id: '',
    unit_id: '',
    broker_id: '',
    reservation_date: '',
    expiry_date: '',
    fee: '',
});

const isEditingContracted = computed(() => {
    return editingReservation.value?.status === 'Contracted';
});

// Helper for reactive auto-comma input
const handleAmountInput = (e, form, field = 'fee') => {
    const input = e.target;
    const rawValue = stripCommas(input.value);
    const numericValue = restrictNumeric(rawValue, true);
    
    // Store clean numeric value in form state
    form[field] = numericValue;
    
    // Format display value with commas
    input.value = formatNumberWithCommas(numericValue);
};

const createReservation = () => {
    createForm.post(route('reservations.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
            showSuccess('Reservation created and accounting record generated.');
        },
    });
};

const editReservation = (reservation) => {
    // 1. Set the raw reservation data first
    editingReservation.value = reservation;
    
    // 2. Populate form fields using centralized date formatter
    editForm.customer_id = reservation.customer_id;
    editForm.unit_id = reservation.unit_id;
    editForm.broker_id = reservation.broker_id || '';
    editForm.reservation_date = formatDateForInput(reservation.reservation_date);
    editForm.expiry_date = formatDateForInput(reservation.expiry_date);
    
    // Truncate to 2 decimals to prevent .0000 display from database
    const rawFee = parseFloat(reservation.fee || 0);
    editForm.fee = rawFee.toFixed(2);
    
    // 3. Show modal
    showEditModal.value = true;
};

const updateReservation = () => {
    editForm.put(route('reservations.update', editingReservation.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
            editingReservation.value = null;
            showSuccess('Reservation updated successfully');
        },
    });
};

const showCancelModal = ref(false);
const cancellingReservation = ref(null);
const cancelForm = useForm({
    action: 'Forfeit',
    reference_no: '',
});

// NEW: Contracting Wizard
const showContractWizard = ref(false);
const contractingReservation = ref(null);
const contractForm = useForm({
    plan_type: 'Installment',
    amortization_terms: 12,
    interest_rate: 7,
    start_date: formatDateForInput(new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)),
});

// Watch for plan type changes to reset fields
watch(() => contractForm.plan_type, (newType) => {
    if (newType === 'Spot Cash') {
        contractForm.amortization_terms = 1;
        contractForm.interest_rate = 0;
    } else {
        contractForm.amortization_terms = 12;
        contractForm.interest_rate = 7;
    }
});

// NEW: Record Additional Payment (for DP)
const showPaymentModal = ref(false);
const payingReservation = ref(null);
const paymentForm = useForm({
    amount: '',
    payment_date: formatDateForInput(new Date()),
    payment_method: 'Cash',
    payment_type: 'Downpayment',
    reference_no: '',
});

const signContract = (reservation) => {
    // Validation: Check DP payment status
    const requiredDP = parseFloat(reservation.unit?.price_list?.downpayment_amount || 0);
    const totalPaid = parseFloat(reservation.total_paid || 0);

    if (totalPaid < requiredDP) {
        showError(`Cannot contract: Downpayment balance of ${formatCurrency(requiredDP - totalPaid)} is remaining.`);
        return;
    }

    contractingReservation.value = reservation;
    showContractWizard.value = true;
};

const submitContract = () => {
    contractForm.post(route('reservations.contract', contractingReservation.value.id), {
        onSuccess: () => {
            showContractWizard.value = false;
            contractForm.reset();
            contractingReservation.value = null;
            showSuccess('Contract signed and payment schedule generated.');
        },
    });
};

const openPaymentModal = (reservation) => {
    payingReservation.value = reservation;
    const requiredDP = parseFloat(reservation.unit?.price_list?.downpayment_amount || 0);
    const totalPaid = parseFloat(reservation.total_paid || 0);
    paymentForm.amount = Math.max(0, requiredDP - totalPaid).toString();
    showPaymentModal.value = true;
};

const submitPayment = () => {
    paymentForm.post(route('reservations.record-payment', payingReservation.value.id), {
        onSuccess: () => {
            showPaymentModal.value = false;
            paymentForm.reset();
            payingReservation.value = null;
            showSuccess('Payment recorded successfully.');
        },
    });
};

const showCancelDialog = (reservation) => {
    cancellingReservation.value = reservation;
    showCancelModal.value = true;
};

const submitCancellation = () => {
    cancelForm.post(route('reservations.cancel-accounting', cancellingReservation.value.id), {
        onSuccess: () => {
            showCancelModal.value = false;
            cancelForm.reset();
            cancellingReservation.value = null;
            showSuccess('Reservation cancelled and accounting reversal recorded.');
        },
    });
};

const deleteReservation = async (reservation) => {
    const confirmed = await confirm({
        title: 'Cancel Reservation',
        message: `Are you sure you want to cancel the reservation for ${reservation.customer?.first_name} ${reservation.customer?.last_name}? This will release the unit back to inventory.`,
        confirmButtonText: 'Cancel Reservation',
        type: 'danger'
    });
    
    if (confirmed) {
        inertiaDelete(route('reservations.destroy', reservation.id), {
            onSuccess: () => showSuccess('Reservation cancelled and unit released.'),
        });
    }
};

const isExpired = (expiryDate) => {
    if (!expiryDate) return false;
    return new Date(expiryDate) < new Date();
};

const isExpiringSoon = (expiryDate) => {
    if (!expiryDate) return false;
    const daysUntilExpiry = (new Date(expiryDate) - new Date()) / (1000 * 60 * 60 * 24);
    return daysUntilExpiry >= 0 && daysUntilExpiry <= 7;
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value || 0);
};

// Available units filter:
// For Create: Only 'Available'
// For Edit: 'Available' OR the current unit of the reservation being edited
const filteredUnits = computed(() => {
    // Map units to the format expected by Autocomplete
    const mapped = (props.units || []).map(u => ({
        id: u.id,
        status: u.status,
        name: `${u.name} - B${u.block_num} L${u.lot_num}` + (u.project?.name ? ` (${u.project.name})` : '')
    }));

    if (showCreateModal.value) {
        return mapped.filter(u => u.status === 'Available');
    }
    
    if (showEditModal.value && editingReservation.value) {
        const currentUnitId = editingReservation.value.unit_id;
        return mapped.filter(u => 
            u.status === 'Available' || String(u.id) === String(currentUnitId)
        );
    }
    
    return mapped;
});

const brokerOptions = computed(() => {
    return [
        { id: '', name: 'In-House / Direct' },
        ...(props.brokers || [])
    ];
});

</script>

<template>
    <Head title="Reservations - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Reservations</h2>
                    <p class="text-sm text-slate-500 mt-1">Manage unit reservations and sales pipeline.</p>
                </div>
            </div>
        </template>

        <div class="py-6 space-y-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Compact Horizontal Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <button 
                        @click="filterByStatus(null)"
                        class="bg-white px-6 py-5 rounded-2xl border border-slate-100 shadow-sm flex items-center space-x-4 hover:border-blue-200 hover:bg-blue-50/10 transition-all text-left group"
                    >
                        <div class="p-3 bg-blue-50 rounded-xl shrink-0 group-hover:scale-110 transition-transform">
                            <ClipboardDocumentListIcon class="w-6 h-6 text-blue-600" />
                        </div>
                        <div>
                            <div class="text-2xl font-black text-slate-900 leading-none">{{ stats.total }}</div>
                            <div class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Total Reservations</div>
                        </div>
                    </button>

                    <button 
                        @click="filterByStatus('Ongoing DP')"
                        class="bg-white px-6 py-5 rounded-2xl border border-slate-100 shadow-sm flex items-center space-x-4 hover:border-emerald-200 hover:bg-emerald-50/10 transition-all text-left group"
                    >
                        <div class="p-3 bg-emerald-50 rounded-xl shrink-0 group-hover:scale-110 transition-transform">
                            <ClockIcon class="w-6 h-6 text-emerald-600" />
                        </div>
                        <div>
                            <div class="text-2xl font-black text-slate-900 leading-none">{{ stats.ongoing_dp }}</div>
                            <div class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Ongoing DP</div>
                        </div>
                    </button>

                    <button 
                        @click="filterByStatus('Ready for Contract')"
                        class="bg-white px-6 py-5 rounded-2xl border border-slate-100 shadow-sm flex items-center space-x-4 hover:border-green-200 hover:bg-green-50/10 transition-all text-left group"
                    >
                        <div class="p-3 bg-green-50 rounded-xl shrink-0 group-hover:scale-110 transition-transform">
                            <CheckCircleIcon class="w-6 h-6 text-green-600" />
                        </div>
                        <div>
                            <div class="text-2xl font-black text-slate-900 leading-none">{{ stats.ready_for_contract }}</div>
                            <div class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">DP Fully Paid</div>
                        </div>
                    </button>

                    <button 
                        @click="filterByStatus('Contracted')"
                        class="bg-white px-6 py-5 rounded-2xl border border-slate-100 shadow-sm flex items-center space-x-4 hover:border-indigo-200 hover:bg-indigo-50/10 transition-all text-left group"
                    >
                        <div class="p-3 bg-indigo-50 rounded-xl shrink-0 group-hover:scale-110 transition-transform">
                            <ClipboardDocumentCheckIcon class="w-6 h-6 text-indigo-600" />
                        </div>
                        <div>
                            <div class="text-2xl font-black text-slate-900 leading-none">{{ stats.contracted }}</div>
                            <div class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Contracted (Sold)</div>
                        </div>
                    </button>

                    <button 
                        @click="filterByStatus('Expiring Soon')"
                        class="bg-white px-6 py-5 rounded-2xl border border-slate-100 shadow-sm flex items-center space-x-4 hover:border-amber-200 hover:bg-amber-50/10 transition-all text-left group"
                    >
                        <div class="p-3 bg-amber-50 rounded-xl shrink-0 group-hover:scale-110 transition-transform">
                            <ClockIcon class="w-6 h-6 text-amber-600" />
                        </div>
                        <div>
                            <div class="text-2xl font-black text-slate-900 leading-none">{{ stats.expiring_soon }}</div>
                            <div class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Expiring Soon</div>
                        </div>
                    </button>

                    <div class="bg-white px-6 py-5 rounded-2xl border border-slate-100 shadow-sm flex items-center space-x-4">
                        <div class="p-3 bg-slate-50 rounded-xl shrink-0">
                            <CurrencyDollarIcon class="w-6 h-6 text-slate-600" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-xl font-black text-slate-900 leading-none truncate" :title="formatCurrency(stats.total_collected)">{{ formatCurrency(stats.total_collected) }}</div>
                            <div class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Total Collected</div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <DataTable
                        title="Reservation Pipeline"
                        subtitle="Detailed view of all active reservations"
                        search-placeholder="Search by customer or unit name..."
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
                                v-if="hasPermission('reservations.create')"
                                @click="showCreateModal = true"
                                class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition-all duration-200 flex items-center space-x-2 text-sm font-semibold shadow-lg shadow-blue-600/20"
                            >
                                <PlusIcon class="w-5 h-5" />
                                <span>Add Reservation</span>
                            </button>
                        </template>

                        <template #header>
                            <tr class="bg-slate-50">
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Customer</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Unit Info</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Reserved / Expiry</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">DP Paid</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Actions</th>
                            </tr>
                        </template>

                        <template #body="{ data }">
                            <tr v-for="reservation in data" :key="reservation.id" class="hover:bg-slate-50/50 transition-colors border-b border-slate-50 last:border-0">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 bg-blue-50 rounded-full flex items-center justify-center border border-blue-100">
                                            <UserCircleIcon class="w-6 h-6 text-blue-500" />
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-slate-900">{{ reservation.customer?.first_name }} {{ reservation.customer?.last_name }}</div>
                                            <div class="text-[10px] text-slate-500 font-medium uppercase tracking-wider">Broker: {{ reservation.broker?.name || 'In-House' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <div class="text-sm font-bold text-slate-800">{{ reservation.unit?.name }}</div>
                                        <div class="text-xs text-blue-600 font-bold tracking-tight">{{ reservation.unit?.project?.name }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex flex-col">
                                        <div class="flex items-center space-x-1">
                                            <span class="text-[10px] text-slate-400 font-bold uppercase w-12">Reserved:</span>
                                            <span class="text-slate-700 font-semibold">{{ formatDateDisplay(reservation.reservation_date) }}</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-[10px] text-slate-400 font-bold uppercase w-12">Expiry:</span>
                                            <span 
                                                :class="[
                                                    'font-semibold',
                                                    isExpired(reservation.expiry_date) ? 'text-red-600' : 
                                                    isExpiringSoon(reservation.expiry_date) ? 'text-amber-600' : 'text-slate-700'
                                                ]"
                                            >
                                                {{ formatDateDisplay(reservation.expiry_date) }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-emerald-600">{{ formatCurrency(reservation.total_paid) }}</span>
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">of {{ formatCurrency(reservation.unit?.price_list?.downpayment_amount) }} DP</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="reservation.status === 'Contracted'" class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        <CheckCircleIcon class="w-3.5 h-3.5 mr-1" />
                                        Contracted
                                    </span>
                                    <span v-else-if="reservation.status === 'Cancelled'" class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg bg-slate-50 text-slate-700 border border-slate-100">
                                        Forfeited
                                    </span>
                                    <span v-else-if="reservation.status === 'Refunded'" class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg bg-amber-50 text-amber-700 border border-amber-100">
                                        Refunded
                                    </span>
                                    <span v-else-if="isExpired(reservation.expiry_date)" class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg bg-red-50 text-red-700 border border-red-100">
                                        <ExclamationTriangleIcon class="w-3.5 h-3.5 mr-1" />
                                        Expired
                                    </span>
                                    <span v-else-if="isExpiringSoon(reservation.expiry_date)" class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg bg-amber-50 text-amber-700 border border-amber-100">
                                        <ClockIcon class="w-3.5 h-3.5 mr-1" />
                                        Expiring Soon
                                    </span>
                                    <span v-else class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        <CheckCircleIcon class="w-3.5 h-3.5 mr-1" />
                                        Active
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-1">
                                        <template v-if="reservation.status === 'Active'">
                                            <button 
                                                v-if="!reservation.is_dp_fully_paid"
                                                @click="openPaymentModal(reservation)" 
                                                class="p-2 text-emerald-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" 
                                                title="Collect Downpayment"
                                            >
                                                <CurrencyDollarIcon class="w-5 h-5" />
                                            </button>
                                            
                                            <button 
                                                @click="signContract(reservation)" 
                                                class="p-2 text-indigo-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all" 
                                                :class="{'opacity-50 cursor-not-allowed': !reservation.is_dp_fully_paid}"
                                                :title="reservation.is_dp_fully_paid ? 'Sign Contract' : 'Complete Downpayment first'"
                                            >
                                                <ClipboardDocumentListIcon class="w-5 h-5" />
                                            </button>
                                            
                                            <button @click="showCancelDialog(reservation)" class="p-2 text-amber-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-all" title="Cancel/Refund"><ExclamationTriangleIcon class="w-5 h-5" /></button>
                                        </template>
                                        <button v-if="hasPermission('reservations.edit') && reservation.status !== 'Contracted'" @click="editReservation(reservation)" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Edit Reservation"><PencilSquareIcon class="w-5 h-5" /></button>
                                        <button v-if="hasPermission('reservations.delete')" @click="deleteReservation(reservation)" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Delete Reservation"><TrashIcon class="w-5 h-5" /></button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- Create Reservation Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto flex items-start sm:items-center justify-center p-4 pt-10 sm:pt-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showCreateModal = false"></div>
            
            <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full relative animate-in fade-in zoom-in duration-200 my-auto">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between rounded-t-3xl">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">New Reservation</h3>
                        <p class="text-sm text-slate-500 font-medium">Link a customer to an available unit.</p>
                    </div>
                    <div class="p-2 bg-blue-50 rounded-xl">
                        <HomeIcon class="w-6 h-6 text-blue-600" />
                    </div>
                </div>
                
                <form @submit.prevent="createReservation" class="p-8 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center">
                                <UserCircleIcon class="w-4 h-4 mr-2 text-blue-500" />
                                Select Customer
                            </label>
                            <Autocomplete 
                                v-model="createForm.customer_id"
                                :options="customers"
                                label-key="name"
                                value-key="id"
                                placeholder="Search or select customer..."
                                :class="{'ring-2 ring-red-500 border-red-500': createForm.errors.customer_id}"
                            />
                            <p v-if="createForm.errors.customer_id" class="text-xs text-red-500 mt-1 font-bold">{{ createForm.errors.customer_id }}</p>
                        </div>

                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center">
                                <HomeIcon class="w-4 h-4 mr-2 text-emerald-500" />
                                Available Unit
                            </label>
                            <Autocomplete 
                                v-model="createForm.unit_id"
                                :options="filteredUnits"
                                label-key="name"
                                value-key="id"
                                placeholder="Select unit..."
                                :class="{'ring-2 ring-red-500 border-red-500': createForm.errors.unit_id}"
                            />
                            <p v-if="createForm.errors.unit_id" class="text-xs text-red-500 mt-1 font-bold">{{ createForm.errors.unit_id }}</p>
                        </div>

                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center">
                                <UserCircleIcon class="w-4 h-4 mr-2 text-amber-500" />
                                Referral / Broker
                            </label>
                            <Autocomplete 
                                v-model="createForm.broker_id"
                                :options="brokerOptions"
                                label-key="name"
                                value-key="id"
                                placeholder="In-House / Direct"
                                :class="{'ring-2 ring-red-500 border-red-500': createForm.errors.broker_id}"
                            />
                            <p v-if="createForm.errors.broker_id" class="text-xs text-red-500 mt-1 font-bold">{{ createForm.errors.broker_id }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center">
                                <CalendarDaysIcon class="w-4 h-4 mr-2 text-indigo-500" />
                                Reservation Date
                            </label>
                            <input v-model="createForm.reservation_date" type="date" required :class="{'ring-2 ring-red-500 border-red-500': createForm.errors.reservation_date}" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                            <p v-if="createForm.errors.reservation_date" class="text-xs text-red-500 mt-1 font-bold">{{ createForm.errors.reservation_date }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center">
                                <ClockIcon class="w-4 h-4 mr-2 text-rose-500" />
                                Expiry Date
                            </label>
                            <input v-model="createForm.expiry_date" type="date" required :class="{'ring-2 ring-red-500 border-red-500': createForm.errors.expiry_date}" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                            <p v-if="createForm.errors.expiry_date" class="text-xs text-red-500 mt-1 font-bold">{{ createForm.errors.expiry_date }}</p>
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center">
                                <CurrencyDollarIcon class="w-4 h-4 mr-2 text-emerald-600" />
                                Reservation Fee (PHP)
                            </label>
                            <div class="relative">
                                <input 
                                    :value="formatNumberWithCommas(createForm.fee)" 
                                    @input="handleAmountInput($event, createForm)"
                                    type="text" 
                                    required 
                                    :class="{'ring-2 ring-red-500 border-red-500': createForm.errors.fee}"
                                    class="block w-full px-4 py-3 bg-emerald-50/30 border border-emerald-100 rounded-2xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all font-black text-emerald-700 text-lg"
                                >
                            </div>
                            <p v-if="createForm.errors.fee" class="text-xs text-red-500 mt-1 font-bold">{{ createForm.errors.fee }}</p>
                        </div>

                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Payment Method</label>
                            <select v-model="createForm.payment_method" :class="{'ring-2 ring-red-500 border-red-500': createForm.errors.payment_method}" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                                <option v-for="method in payment_methods" :key="method" :value="method">{{ method }}</option>
                            </select>
                            <p v-if="createForm.errors.payment_method" class="text-xs text-red-500 mt-1 font-bold">{{ createForm.errors.payment_method }}</p>
                        </div>

                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Reference No. (OR# / Trans#)</label>
                            <input v-model="createForm.reference_no" type="text" required placeholder="e.g. OR-12345" :class="{'ring-2 ring-red-500 border-red-500': createForm.errors.reference_no}" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                            <p v-if="createForm.errors.reference_no" class="text-xs text-red-500 mt-1 font-bold">{{ createForm.errors.reference_no }}</p>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="showCreateModal = false" class="px-6 py-3 text-slate-600 font-bold bg-slate-100 rounded-2xl hover:bg-slate-200 transition-colors">Discard</button>
                        <button type="submit" :disabled="createForm.processing" class="px-8 py-3 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-600/30 disabled:opacity-50 transition-all">
                            Confirm Reservation
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Reservation Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto flex items-start sm:items-center justify-center p-4 pt-10 sm:pt-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showEditModal = false"></div>
            
            <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full relative animate-in fade-in zoom-in duration-200 my-auto">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between rounded-t-3xl">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Modify Reservation</h3>
                        <p class="text-sm text-slate-500 font-medium">Update reservation record details.</p>
                    </div>
                    <div class="p-2 bg-blue-50 rounded-xl">
                        <HomeIcon class="w-6 h-6 text-blue-600" />
                    </div>
                </div>

                <!-- Contracted Warning -->
                <div v-if="isEditingContracted" class="mx-8 mt-6 p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start space-x-3">
                    <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                    <div>
                        <p class="text-sm font-bold text-amber-800">Contracted Record Warning</p>
                        <p class="text-xs text-amber-700 leading-relaxed">This reservation is already <strong>Contracted</strong>. Modifying the fee or details will automatically synchronize and update the associated accounting journal entries and revenue recognition records.</p>
                    </div>
                </div>
                
                <form @submit.prevent="updateReservation" class="p-8 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center">
                                <UserCircleIcon class="w-4 h-4 mr-2 text-blue-500" />
                                Select Customer
                            </label>
                            <Autocomplete 
                                v-model="editForm.customer_id"
                                :options="customers"
                                label-key="name"
                                value-key="id"
                                placeholder="Search or select customer..."
                                :class="{'ring-2 ring-red-500 border-red-500': editForm.errors.customer_id}"
                            />
                            <p v-if="editForm.errors.customer_id" class="text-xs text-red-500 mt-1 font-bold">{{ editForm.errors.customer_id }}</p>
                        </div>

                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center">
                                <HomeIcon class="w-4 h-4 mr-2 text-emerald-500" />
                                Available Unit
                            </label>
                            <Autocomplete 
                                v-model="editForm.unit_id"
                                :options="filteredUnits"
                                label-key="name"
                                value-key="id"
                                placeholder="Select unit..."
                                :class="{'ring-2 ring-red-500 border-red-500': editForm.errors.unit_id}"
                            />
                            <p v-if="editForm.errors.unit_id" class="text-xs text-red-500 mt-1 font-bold">{{ editForm.errors.unit_id }}</p>
                        </div>

                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center">
                                <UserCircleIcon class="w-4 h-4 mr-2 text-amber-500" />
                                Referral / Broker
                            </label>
                            <Autocomplete 
                                v-model="editForm.broker_id"
                                :options="brokerOptions"
                                label-key="name"
                                value-key="id"
                                placeholder="In-House / Direct"
                                :class="{'ring-2 ring-red-500 border-red-500': editForm.errors.broker_id}"
                            />
                            <p v-if="editForm.errors.broker_id" class="text-xs text-red-500 mt-1 font-bold">{{ editForm.errors.broker_id }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center">
                                <CalendarDaysIcon class="w-4 h-4 mr-2 text-indigo-500" />
                                Reservation Date
                            </label>
                            <input v-model="editForm.reservation_date" type="date" required :class="{'ring-2 ring-red-500 border-red-500': editForm.errors.reservation_date}" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                            <p v-if="editForm.errors.reservation_date" class="text-xs text-red-500 mt-1 font-bold">{{ editForm.errors.reservation_date }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center">
                                <ClockIcon class="w-4 h-4 mr-2 text-rose-500" />
                                Expiry Date
                            </label>
                            <input v-model="editForm.expiry_date" type="date" required :class="{'ring-2 ring-red-500 border-red-500': editForm.errors.expiry_date}" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                            <p v-if="editForm.errors.expiry_date" class="text-xs text-red-500 mt-1 font-bold">{{ editForm.errors.expiry_date }}</p>
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center">
                                <CurrencyDollarIcon class="w-4 h-4 mr-2 text-emerald-600" />
                                Reservation Fee (PHP)
                            </label>
                            <div class="relative">
                                <input 
                                    :value="formatNumberWithCommas(editForm.fee)" 
                                    @input="handleAmountInput($event, editForm)"
                                    type="text" 
                                    required 
                                    :class="{'ring-2 ring-red-500 border-red-500': editForm.errors.fee}"
                                    class="block w-full px-4 py-3 bg-emerald-50/30 border border-emerald-100 rounded-2xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all font-black text-emerald-700 text-lg"
                                >
                            </div>
                            <p v-if="editForm.errors.fee" class="text-xs text-red-500 mt-1 font-bold">{{ editForm.errors.fee }}</p>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="showEditModal = false" class="px-6 py-3 text-slate-600 font-bold bg-slate-100 rounded-2xl hover:bg-slate-200 transition-colors">Discard</button>
                        <button type="submit" :disabled="editForm.processing" class="px-8 py-3 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-600/30 disabled:opacity-50 transition-all">
                            Update Record
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cancel/Refund Modal -->
        <div v-if="showCancelModal" class="fixed inset-0 z-50 overflow-y-auto flex items-start sm:items-center justify-center p-4 pt-10 sm:pt-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showCancelModal = false"></div>
            
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full relative animate-in fade-in zoom-in duration-200 my-auto">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between rounded-t-3xl">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Cancel Reservation</h3>
                        <p class="text-sm text-slate-500 font-medium">Select accounting treatment.</p>
                    </div>
                    <div class="p-2 bg-amber-50 rounded-xl">
                        <ExclamationTriangleIcon class="w-6 h-6 text-amber-600" />
                    </div>
                </div>
                
                <form @submit.prevent="submitCancellation" class="p-8 space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">How should we handle the fee?</label>
                        <div class="grid grid-cols-2 gap-4">
                            <button 
                                type="button" 
                                @click="cancelForm.action = 'Refund'"
                                :class="[
                                    'px-4 py-3 rounded-2xl font-bold text-sm transition-all border-2',
                                    cancelForm.action === 'Refund' ? 'bg-amber-50 border-amber-500 text-amber-700' : 'bg-slate-50 border-transparent text-slate-500 hover:bg-slate-100'
                                ]"
                            >
                                Refund to Customer
                            </button>
                            <button 
                                type="button" 
                                @click="cancelForm.action = 'Forfeit'"
                                :class="[
                                    'px-4 py-3 rounded-2xl font-bold text-sm transition-all border-2',
                                    cancelForm.action === 'Forfeit' ? 'bg-rose-50 border-rose-500 text-rose-700' : 'bg-slate-50 border-transparent text-slate-500 hover:bg-slate-100'
                                ]"
                            >
                                Forfeit to Income
                            </button>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-2 italic">
                            {{ cancelForm.action === 'Refund' ? 'Debit Liability, Credit Cash (Returns money to customer)' : 'Debit Liability, Credit Other Income (Company keeps the money)' }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Reference No. (Optional)</label>
                        <input v-model="cancelForm.reference_no" type="text" placeholder="Check # or Memo #" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="showCancelModal = false" class="px-6 py-3 text-slate-600 font-bold bg-slate-100 rounded-2xl hover:bg-slate-200 transition-colors">Back</button>
                        <button type="submit" :disabled="cancelForm.processing" class="px-8 py-3 bg-rose-600 text-white font-black rounded-2xl hover:bg-rose-700 shadow-xl shadow-rose-600/30 disabled:opacity-50 transition-all">
                            Confirm Cancellation
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Record Payment Modal (For DP Balance) -->
        <div v-if="showPaymentModal" class="fixed inset-0 z-50 overflow-y-auto flex items-start sm:items-center justify-center p-4 pt-10 sm:pt-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showPaymentModal = false"></div>
            
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full relative animate-in fade-in zoom-in duration-200 my-auto">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between rounded-t-3xl">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Collect Downpayment</h3>
                        <p class="text-sm text-slate-500 font-medium">Record additional payment for this unit.</p>
                    </div>
                    <div class="p-2 bg-emerald-50 rounded-xl">
                        <CurrencyDollarIcon class="w-6 h-6 text-emerald-600" />
                    </div>
                </div>
                
                <form @submit.prevent="submitPayment" class="p-8 space-y-5">
                    <!-- Payment Breakdown Summary -->
                    <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 font-bold uppercase text-[10px] tracking-widest">Required DP</span>
                            <span class="text-slate-900 font-black text-sm">{{ formatCurrency(payingReservation?.unit?.price_list?.downpayment_amount) }}</span>
                        </div>
                        
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500 font-bold uppercase text-[10px] tracking-widest">Total Paid</span>
                                <span class="text-emerald-600 font-black text-sm">{{ formatCurrency(payingReservation?.total_paid) }}</span>
                            </div>
                            
                            <!-- Scrollable History -->
                            <div v-if="payingReservation?.payments?.length" class="pl-3 border-l-2 border-slate-200 space-y-1.5 max-h-24 overflow-y-auto custom-scrollbar">
                                <div v-for="payment in payingReservation.payments" :key="payment.id" class="flex justify-between items-center text-[10px]">
                                    <div class="flex flex-col">
                                        <span class="text-slate-600 font-bold">{{ formatDateDisplay(payment.payment_date) }}</span>
                                        <span class="text-slate-400 font-medium">{{ payment.reference_no || 'No Ref' }} • {{ payment.payment_method }}</span>
                                    </div>
                                    <span class="text-slate-700 font-black">{{ formatCurrency(payment.amount) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-200 flex justify-between items-center">
                            <span class="text-slate-500 font-bold uppercase text-[10px] tracking-widest">Outstanding Balance</span>
                            <span class="text-rose-600 font-black text-lg">{{ formatCurrency(Math.max(0, parseFloat(payingReservation?.unit?.price_list?.downpayment_amount || 0) - parseFloat(payingReservation?.total_paid || 0))) }}</span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Amount to Pay (PHP)</label>
                        <input 
                            :value="formatNumberWithCommas(paymentForm.amount)" 
                            @input="handleAmountInput($event, paymentForm, 'amount')"
                            type="text" 
                            required 
                            class="block w-full px-4 py-3 bg-emerald-50/30 border border-emerald-100 rounded-2xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all font-black text-emerald-700 text-lg"
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Date</label>
                            <input v-model="paymentForm.payment_date" type="date" required class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Method</label>
                            <select v-model="paymentForm.payment_method" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                                <option v-for="method in payment_methods" :key="method" :value="method">{{ method }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Reference No.</label>
                        <input v-model="paymentForm.reference_no" type="text" required placeholder="OR# / Trans#" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="showPaymentModal = false" class="px-6 py-3 text-slate-600 font-bold bg-slate-100 rounded-2xl hover:bg-slate-200 transition-colors">Cancel</button>
                        <button type="submit" :disabled="paymentForm.processing" class="px-8 py-3 bg-emerald-600 text-white font-black rounded-2xl hover:bg-emerald-700 shadow-xl shadow-emerald-600/30 disabled:opacity-50 transition-all">
                            Post Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Contracting Wizard Modal -->
        <div v-if="showContractWizard" class="fixed inset-0 z-50 overflow-y-auto flex items-start sm:items-center justify-center p-4 pt-10 sm:pt-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showContractWizard = false"></div>
            
            <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full relative animate-in fade-in zoom-in duration-200 my-auto">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between rounded-t-3xl">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Sign Sales Contract</h3>
                        <p class="text-sm text-slate-500 font-medium">Select payment plan and generate schedule.</p>
                    </div>
                    <div class="p-2 bg-indigo-50 rounded-xl">
                        <ClipboardDocumentListIcon class="w-6 h-6 text-indigo-600" />
                    </div>
                </div>
                
                <form @submit.prevent="submitContract" class="p-8 space-y-6">
                    <div class="space-y-4">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Choose Payment Plan</label>
                        <div class="grid grid-cols-2 gap-4">
                            <button 
                                type="button" 
                                @click="contractForm.plan_type = 'Spot Cash'"
                                :class="[
                                    'p-4 rounded-2xl border-2 text-left transition-all',
                                    contractForm.plan_type === 'Spot Cash' ? 'bg-blue-50 border-blue-500' : 'bg-slate-50 border-transparent hover:bg-slate-100'
                                ]"
                            >
                                <div class="font-black text-slate-900">Spot Cash</div>
                                <div class="text-[10px] text-slate-500 font-medium mt-1">Full payment of remaining balance.</div>
                            </button>
                            <button 
                                type="button" 
                                @click="contractForm.plan_type = 'Installment'"
                                :class="[
                                    'p-4 rounded-2xl border-2 text-left transition-all',
                                    contractForm.plan_type === 'Installment' ? 'bg-blue-50 border-blue-500' : 'bg-slate-50 border-transparent hover:bg-slate-100'
                                ]"
                            >
                                <div class="font-black text-slate-900">Installment</div>
                                <div class="text-[10px] text-slate-500 font-medium mt-1">Monthly amortization over fixed terms.</div>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div v-if="contractForm.plan_type === 'Installment'">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Amortization Terms</label>
                            <div class="relative">
                                <select v-model="contractForm.amortization_terms" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                                    <option :value="12">12 Months (1 yr)</option>
                                    <option :value="24">24 Months (2 yrs)</option>
                                    <option :value="36">36 Months (3 yrs)</option>
                                    <option :value="60">60 Months (5 yrs)</option>
                                    <option :value="120">120 Months (10 yrs)</option>
                                </select>
                            </div>
                        </div>
                        <div v-if="contractForm.plan_type === 'Installment'">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Interest Rate (Annual %)</label>
                            <input v-model="contractForm.interest_rate" type="number" step="0.01" required class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                        </div>
                        <div :class="contractForm.plan_type === 'Spot Cash' ? 'col-span-2' : 'col-span-2 md:col-span-2'">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Payment Start Date</label>
                            <input v-model="contractForm.start_date" type="date" required class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                        </div>
                    </div>

                    <div class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-100">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-indigo-600 font-bold">Total Contract Price</span>
                            <span class="text-slate-900 font-black">{{ formatCurrency(contractingReservation?.unit?.price_list?.tcp) }}</span>
                        </div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-indigo-600 font-bold">Total Already Paid</span>
                            <span class="text-emerald-600 font-black">- {{ formatCurrency(contractingReservation?.total_paid) }}</span>
                        </div>
                        <div class="h-[1px] bg-indigo-100 my-2"></div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500 font-bold">Balance to Schedule</span>
                            <span class="text-slate-900 font-black">{{ formatCurrency(parseFloat(contractingReservation?.unit?.price_list?.tcp || 0) - parseFloat(contractingReservation?.total_paid || 0)) }}</span>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="showContractWizard = false" class="px-6 py-3 text-slate-600 font-bold bg-slate-100 rounded-2xl hover:bg-slate-200 transition-colors">Back</button>
                        <button type="submit" :disabled="contractForm.processing" class="px-8 py-3 bg-indigo-600 text-white font-black rounded-2xl hover:bg-indigo-700 shadow-xl shadow-indigo-600/30 disabled:opacity-50 transition-all">
                            Generate Schedule & Sign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>