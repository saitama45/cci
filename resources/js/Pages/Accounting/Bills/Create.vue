<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Autocomplete from '@/Components/Autocomplete.vue';
import { useToast } from '@/Composables/useToast';
import { 
    PlusIcon, 
    TrashIcon, 
    ChevronLeftIcon,
    BanknotesIcon,
    CalendarIcon,
    UserIcon,
    IdentificationIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    vendors: Array,
    accounts: Array,
    projects: Array,
    bill: Object,
    isEditing: Boolean,
    prePopulated: Object,
});

const { showSuccess, showError } = useToast();

// Helper to format date to YYYY-MM-DD for HTML5 date input
const formatDate = (dateString) => {
    if (!dateString) return '';
    return dateString.split(' ')[0].split('T')[0];
};

const form = useForm({
    vendor_id: props.bill?.vendor_id || props.prePopulated?.vendor_id || '',
    purchase_order_id: props.bill?.purchase_order_id || props.prePopulated?.purchase_order_id || null,
    type: props.bill?.type || props.prePopulated?.type || 'Bill',
    bill_number: props.bill?.bill_number || '',
    bill_date: formatDate(props.bill?.bill_date) || new Date().toISOString().substr(0, 10),
    due_date: formatDate(props.bill?.due_date) || '',
    project_id: props.bill?.project_id || props.prePopulated?.project_id || '',
    notes: props.bill?.notes || props.prePopulated?.notes || '',
    status: props.bill?.status || 'Draft',
    tax_type: props.bill?.tax_type || 'VAT Exclusive',
    ewt_rate: props.bill?.ewt_rate || 0,
    items: props.bill?.items?.map(item => ({
        chart_of_account_id: item.chart_of_account_id,
        description: item.description,
        amount: item.amount,
        project_id: item.project_id || '',
    })) || props.prePopulated?.items?.map(item => ({
        ...item,
        project_id: item.project_id || '',
    })) || [
        { chart_of_account_id: '', description: '', amount: 0, project_id: '' }
    ]
});

// Tax Calculations
const grossAmount = computed(() => {
    return form.items.reduce((sum, item) => sum + parseFloat(item.amount || 0), 0);
});

const vatAmount = computed(() => {
    if (form.tax_type === 'VAT Inclusive') {
        const net = grossAmount.value / 1.12;
        return grossAmount.value - net;
    } else if (form.tax_type === 'VAT Exclusive') {
        return grossAmount.value * 0.12;
    }
    return 0;
});

const totalWithVat = computed(() => {
    return form.tax_type === 'VAT Exclusive' ? grossAmount.value + vatAmount.value : grossAmount.value;
});

const ewtAmount = computed(() => {
    const netOfVat = form.tax_type === 'VAT Inclusive' ? (grossAmount.value / 1.12) : grossAmount.value;
    return netOfVat * (form.ewt_rate / 100);
});

const netAmount = computed(() => {
    return totalWithVat.value - ewtAmount.value;
});

const isDebitMemo = computed(() => form.type === 'Debit Memo');

// Watch for prop changes (e.g., when navigating between different bills)
watch(() => props.bill, (newBill) => {
    if (newBill && props.isEditing) {
        form.vendor_id = newBill.vendor_id;
        form.type = newBill.type || 'Bill';
        form.bill_number = newBill.bill_number;
        form.bill_date = formatDate(newBill.bill_date);
        form.due_date = formatDate(newBill.due_date);
        form.project_id = newBill.project_id || '';
        form.notes = newBill.notes || '';
        form.status = newBill.status;
        form.items = newBill.items?.map(item => ({
            chart_of_account_id: item.chart_of_account_id,
            description: item.description,
            amount: item.amount,
            project_id: item.project_id || '',
        })) || [{ chart_of_account_id: '', description: '', amount: 0, project_id: '' }];
    }
}, { immediate: true });

const addItem = () => {
    form.items.push({ chart_of_account_id: '', description: '', amount: 0, project_id: '' });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const totalAmount = computed(() => {
    return form.items.reduce((sum, item) => sum + parseFloat(item.amount || 0), 0);
});

const submit = (status = 'Draft') => {
    form.status = status;
    const method = props.isEditing ? 'put' : 'post';
    const routeName = props.isEditing ? 'accounting.bills.update' : 'accounting.bills.store';
    const routeParams = props.isEditing ? props.bill.id : null;

    form[method](route(routeName, routeParams), {
        onSuccess: () => {
            // Flash handled globally
        },
        onError: (errors) => {
            showError('Please check the form for errors.');
        }
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
};

// Set default expense account when vendor changes
watch(() => form.vendor_id, (newVendorId) => {
    const vendor = props.vendors.find(v => v.id === newVendorId);
    if (vendor && vendor.default_expense_account_id && form.items.length === 1 && !form.items[0].chart_of_account_id) {
        form.items[0].chart_of_account_id = vendor.default_expense_account_id;
    }
});

</script>

<template>
    <Head :title="`${isEditing ? 'Edit Bill #' + bill.bill_number : 'Record New Bill'} - Horizon ERP`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="route('accounting.bills.index')" class="text-indigo-600 hover:text-indigo-900">
                    <ChevronLeftIcon class="w-5 h-5" />
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ isEditing ? 'Edit ' + form.type + ' #' + bill.bill_number : 'Record New ' + form.type }}
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100 p-6">
                    <form @submit.prevent>
                        <!-- Header Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8 bg-gray-50 p-6 rounded-xl border border-gray-200">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Transaction Type <span class="text-red-500">*</span></label>
                                    <select
                                        v-model="form.type"
                                        class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                        required
                                    >
                                        <option value="Bill">Standard Bill (Accounts Payable)</option>
                                        <option value="Debit Memo">Debit Memo (Reduction/Return)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1 italic text-indigo-700">Vendor <span class="text-red-500">*</span></label>
                                    <Autocomplete
                                        v-model="form.vendor_id"
                                        :options="vendors"
                                        labelKey="name"
                                        valueKey="id"
                                        placeholder="Search for a verified vendor..."
                                        class="!rounded-md !border-gray-300 focus:!border-indigo-500 focus:!ring-indigo-500 !py-2 !shadow-sm !text-sm"
                                    />
                                    <div v-if="form.errors.vendor_id" class="text-red-500 text-xs mt-1 italic">{{ form.errors.vendor_id }}</div>
                                </div>
                                <div v-if="form.purchase_order_id" class="p-3 bg-indigo-50 rounded-lg border border-indigo-100 flex items-center">
                                    <InformationCircleIcon class="w-4 h-4 mr-2 text-indigo-500" />
                                    <span class="text-xs font-bold text-indigo-700 uppercase tracking-widest">Linked to PO #{{ bill?.purchase_order_id }}</span>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1 italic text-indigo-700">
                                        {{ isDebitMemo ? 'Debit Memo #' : 'Bill / Invoice #' }} <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.bill_number"
                                        type="text"
                                        class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                        :placeholder="isDebitMemo ? 'Enter memo reference' : 'Enter vendor invoice number'"
                                        required
                                    />
                                    <div v-if="form.errors.bill_number" class="text-red-500 text-xs mt-1 italic">{{ form.errors.bill_number }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1 italic text-indigo-700">Date <span class="text-red-500">*</span></label>
                                    <input
                                        v-model="form.bill_date"
                                        type="date"
                                        class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm font-medium"
                                        required
                                    />
                                    <div v-if="form.errors.bill_date" class="text-red-500 text-xs mt-1 italic">{{ form.errors.bill_date }}</div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="bg-slate-900 p-4 rounded-xl text-white space-y-2">
                                    <div class="flex justify-between items-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                        <span>Subtotal</span>
                                        <span>{{ formatCurrency(grossAmount) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                        <span>VAT (12%)</span>
                                        <span>{{ formatCurrency(vatAmount) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-[10px] font-bold text-rose-400 uppercase tracking-widest">
                                        <span>EWT Deduction</span>
                                        <span>({{ formatCurrency(ewtAmount) }})</span>
                                    </div>
                                    <div class="pt-2 border-t border-white/10">
                                        <p class="text-[10px] font-black text-indigo-300 uppercase tracking-widest mb-1">Final Net Amount</p>
                                        <h3 class="text-xl font-black">{{ formatCurrency(netAmount) }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tax Settings -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10 p-6 bg-slate-50 rounded-xl border border-slate-200">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">VAT Setting</label>
                                <div class="flex p-1 bg-slate-200/50 rounded-xl space-x-1">
                                    <button type="button" @click="form.tax_type = 'VAT Exclusive'" :class="['flex-1 py-2 text-[10px] font-black uppercase rounded-lg transition-all', form.tax_type === 'VAT Exclusive' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700']">VAT Exclusive</button>
                                    <button type="button" @click="form.tax_type = 'VAT Inclusive'" :class="['flex-1 py-2 text-[10px] font-black uppercase rounded-lg transition-all', form.tax_type === 'VAT Inclusive' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700']">VAT Inclusive</button>
                                    <button type="button" @click="form.tax_type = 'Non-VAT'" :class="['flex-1 py-2 text-[10px] font-black uppercase rounded-lg transition-all', form.tax_type === 'Non-VAT' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700']">Non-VAT</button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Withholding Tax (EWT %)</label>
                                <select v-model="form.ewt_rate" class="block w-full rounded-xl border-slate-200 bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 font-bold text-slate-700">
                                    <option :value="0">0% - No Withholding</option>
                                    <option :value="1">1% - Purchase of Goods</option>
                                    <option :value="2">2% - Purchase of Services</option>
                                    <option :value="5">5% - Professional Fees</option>
                                    <option :value="10">10% - Rentals/Others</option>
                                </select>
                            </div>
                        </div>

                        <!-- Bill Items -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <BanknotesIcon class="w-5 h-5 mr-2 text-indigo-600" />
                                {{ isDebitMemo ? 'Adjustment Items' : 'Bill Items / Allocation' }}
                            </h3>
                            <div class="overflow-x-auto rounded-lg border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-1/4">Account <span class="text-red-500">*</span></th>
                                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Description <span class="text-red-500">*</span></th>
                                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-1/6">Amount <span class="text-red-500">*</span></th>
                                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-1/5">Project Tag</th>
                                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider w-12"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="(item, index) in form.items" :key="index">
                                            <td class="px-4 py-3">
                                                <select
                                                    v-model="item.chart_of_account_id"
                                                    class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                                    required
                                                >
                                                    <option value="">Select Account</option>
                                                    <option v-for="account in accounts" :key="account.id" :value="account.id">
                                                        {{ account.code }} - {{ account.name }}
                                                    </option>
                                                </select>
                                                <div v-if="form.errors[`items.${index}.chart_of_account_id`]" class="text-red-500 text-[10px] mt-1 italic">Required</div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <input
                                                    v-model="item.description"
                                                    type="text"
                                                    class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm italic"
                                                    placeholder="Item or service description"
                                                    required
                                                />
                                                <div v-if="form.errors[`items.${index}.description`]" class="text-red-500 text-[10px] mt-1 italic">Required</div>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="relative rounded-md shadow-sm">
                                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2">
                                                        <span class="text-gray-500 sm:text-xs">₱</span>
                                                    </div>
                                                    <input
                                                        v-model="item.amount"
                                                        type="number"
                                                        step="0.01"
                                                        class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md pl-6 pr-2 py-2 shadow-sm text-sm font-bold text-right text-indigo-700"
                                                        placeholder="0.00"
                                                        required
                                                    />
                                                </div>
                                                <div v-if="form.errors[`items.${index}.amount`]" class="text-red-500 text-[10px] mt-1 italic">Required</div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <select
                                                    v-model="item.project_id"
                                                    class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                                >
                                                    <option value="">Inherit Global</option>
                                                    <option v-for="project in projects" :key="project.id" :value="project.id">
                                                        {{ project.name }}
                                                    </option>
                                                </select>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <button
                                                    v-if="form.items.length > 1"
                                                    type="button"
                                                    @click="removeItem(index)"
                                                    class="text-red-600 hover:text-red-900 focus:outline-none"
                                                >
                                                    <TrashIcon class="w-5 h-5" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-gray-50/50">
                                        <tr>
                                            <td colspan="2" class="px-6 py-4">
                                                <button
                                                    type="button"
                                                    @click="addItem"
                                                    class="inline-flex items-center text-sm text-indigo-600 font-bold hover:text-indigo-900 px-4 py-2 bg-indigo-50 rounded-xl border border-indigo-100 transition-all"
                                                >
                                                    <PlusIcon class="w-4 h-4 mr-1" />
                                                    Add Line
                                                </button>
                                            </td>
                                            <td colspan="3" class="px-6 py-4">
                                                <div class="flex flex-col items-end space-y-2 max-w-xs ml-auto">
                                                    <div class="flex justify-between w-full text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                                        <span>Subtotal (Net)</span>
                                                        <span>{{ formatCurrency(grossAmount) }}</span>
                                                    </div>
                                                    <div v-if="parseFloat(vatAmount) > 0" class="flex justify-between w-full text-[10px] font-black text-indigo-500 uppercase tracking-widest">
                                                        <span>VAT (12%)</span>
                                                        <span>+ {{ formatCurrency(vatAmount) }}</span>
                                                    </div>
                                                    <div v-if="parseFloat(ewtAmount) > 0" class="flex justify-between w-full text-[10px] font-black text-rose-500 uppercase tracking-widest">
                                                        <span>EWT Deduction</span>
                                                        <span>- {{ formatCurrency(ewtAmount) }}</span>
                                                    </div>
                                                    <div class="pt-3 border-t-2 border-indigo-100 flex justify-between w-full items-baseline">
                                                        <span class="text-xs font-black text-gray-500 uppercase tracking-tighter">Net Payable</span>
                                                        <span class="text-2xl font-black text-indigo-700 ml-4">{{ formatCurrency(netAmount) }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end space-x-4 border-t border-gray-100 pt-6">
                            <Link
                                :href="route('accounting.bills.index')"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                            >
                                Cancel
                            </Link>
                            <button
                                type="button"
                                @click="submit('Draft')"
                                :disabled="form.processing"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Save as Draft
                            </button>
                            <button
                                type="button"
                                @click="submit('Approved')"
                                :disabled="form.processing"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-lg shadow-indigo-100"
                            >
                                Post & Approve
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
