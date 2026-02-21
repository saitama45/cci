<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { usePagination } from '@/Composables/usePagination';
import { usePermission } from '@/Composables/usePermission';
import { useToast } from '@/Composables/useToast';
import { useInputRestriction } from '@/Composables/useInputRestriction';
import { 
    CurrencyDollarIcon,
    PlusIcon,
    CalendarDaysIcon,
    UserCircleIcon,
    IdentificationIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    payments: Object,
    customers: Array,
    reservations: Array,
    payment_methods: Array,
});

const { hasPermission } = usePermission();
const { showSuccess, showError } = useToast();
const { restrictNumeric, formatNumberWithCommas, stripCommas, formatDateForInput, formatDateDisplay } = useInputRestriction();
const pagination = usePagination(props.payments, 'payments.index');

const showCreateModal = ref(false);

onMounted(() => {
    pagination.updateData(props.payments);
});

watch(() => props.payments, (newData) => {
    pagination.updateData(newData);
}, { deep: true });

const createForm = useForm({
    customer_id: '',
    reservation_id: '',
    amount: '',
    payment_date: formatDateForInput(new Date()),
    payment_method: 'Cash',
    reference_no: '',
    notes: '',
});

const handleAmountInput = (e) => {
    const raw = stripCommas(e.target.value);
    createForm.amount = restrictNumeric(raw, true);
    e.target.value = formatNumberWithCommas(createForm.amount);
};

const storePayment = () => {
    createForm.post(route('payments.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
            showSuccess('Payment recorded and ledger updated.');
        }
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value || 0);
};

</script>

<template>
    <Head title="Collections & Payments - Accounting" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Collections</h2>
                    <p class="text-sm text-slate-500 mt-1">Manage and record all customer receipts and payments.</p>
                </div>
            </div>
        </template>

        <div class="py-6 space-y-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <DataTable
                        title="Collection History"
                        subtitle="Detailed record of all money received"
                        search-placeholder="Search reference or customer..."
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
                                v-if="hasPermission('payments.create')"
                                @click="showCreateModal = true"
                                class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl hover:bg-emerald-700 transition-all duration-200 flex items-center space-x-2 text-sm font-semibold shadow-lg shadow-emerald-600/20"
                            >
                                <PlusIcon class="w-5 h-5" />
                                <span>Record Receipt</span>
                            </button>
                        </template>

                        <template #header>
                            <tr class="bg-slate-50">
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Customer</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Method</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Reference #</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Amount</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">JE Status</th>
                            </tr>
                        </template>

                        <template #body="{ data }">
                            <tr v-for="payment in data" :key="payment.id" class="hover:bg-slate-50/50 transition-colors border-b border-slate-50 last:border-0">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-700">
                                    {{ formatDateDisplay(payment.payment_date) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <div class="text-sm font-bold text-slate-900">{{ payment.customer?.first_name }} {{ payment.customer?.last_name }}</div>
                                        <div class="text-[10px] text-blue-500 font-bold uppercase tracking-wider" v-if="payment.reservation">Unit: {{ payment.reservation.unit?.name }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    {{ payment.payment_method }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs font-black text-slate-700 tracking-tighter">{{ payment.reference_no }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-black text-emerald-700">
                                    {{ formatCurrency(payment.amount) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span v-if="payment.journal_entry_id" class="inline-flex items-center px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-tighter bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        Recorded
                                    </span>
                                    <span v-else class="inline-flex items-center px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-tighter bg-slate-100 text-slate-400">
                                        Pending
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- Create Payment Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showCreateModal = false"></div>
            
            <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full relative animate-in fade-in zoom-in duration-200">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between rounded-t-3xl">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Record Payment</h3>
                        <p class="text-sm text-slate-500 font-medium">Capture a new collection receipt.</p>
                    </div>
                    <div class="p-2 bg-emerald-50 rounded-xl">
                        <CurrencyDollarIcon class="w-6 h-6 text-emerald-600" />
                    </div>
                </div>
                
                <form @submit.prevent="storePayment" class="p-8 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Customer</label>
                            <select v-model="createForm.customer_id" required class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                                <option value="">Select Customer</option>
                                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.first_name }} {{ c.last_name }}</option>
                            </select>
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Linked Reservation (Optional)</label>
                            <select v-model="createForm.reservation_id" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                                <option value="">None / General Collection</option>
                                <option v-for="r in reservations" :key="r.id" :value="r.id">
                                    #{{ r.id }} - {{ r.unit?.name }} ({{ r.customer?.last_name }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Amount (PHP)</label>
                            <input 
                                :value="formatNumberWithCommas(createForm.amount)" 
                                @input="handleAmountInput"
                                type="text" 
                                required 
                                class="block w-full px-4 py-3 bg-emerald-50/30 border border-emerald-100 rounded-2xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all font-black text-emerald-700 text-lg"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Payment Date</label>
                            <input v-model="createForm.payment_date" type="date" required class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Payment Method</label>
                            <select v-model="createForm.payment_method" required class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                                <option v-for="m in payment_methods" :key="m" :value="m">{{ m }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Reference / OR #</label>
                            <input v-model="createForm.reference_no" type="text" required placeholder="OR-001" class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-semibold text-slate-700 text-sm">
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="showCreateModal = false" class="px-6 py-3 text-slate-600 font-bold bg-slate-100 rounded-2xl hover:bg-slate-200 transition-colors">Discard</button>
                        <button type="submit" :disabled="createForm.processing" class="px-8 py-3 bg-emerald-600 text-white font-black rounded-2xl hover:bg-emerald-700 shadow-xl shadow-emerald-600/30 disabled:opacity-50 transition-all">
                            Record Receipt
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
