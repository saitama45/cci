<script setup>
import { ref, watch, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ArrowLeftIcon,
    BanknotesIcon,
    PlusIcon,
    TrashIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    vendors: Array,
    banks: Array,
    bankAccounts: Array
});

const verifiedVendors = computed(() => {
    return props.vendors.filter(vendor => vendor.verification_status === 'Verified');
});

const form = useForm({
    vendor_id: '',
    voucher_no: 'PV-' + new Date().getFullYear() + '-' + Math.floor(1000 + Math.random() * 9000),
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'Bank Transfer',
    bank_account_id: '',
    total_amount: 0,
    notes: '',
    bills: [], // { bill_id, bill_number, amount, balance }
    pdc_details: {
        check_no: '',
        check_date: '',
        bank_id: '',
        bank_name: '',
        bank_branch: ''
    }
});

watch(() => form.pdc_details.bank_id, (newId) => {
    const bank = props.banks.find(b => b.id === newId);
    if (bank) {
        form.pdc_details.bank_name = bank.name;
        form.pdc_details.bank_branch = bank.branch;
    }
});

// Auto-sync date for Current Dated Checks
watch([() => form.payment_method, () => form.payment_date], ([newMethod, newDate]) => {
    if (newMethod === 'Check') {
        form.pdc_details.check_date = newDate;
    }
});

const vendorBills = ref([]);
const isLoadingBills = ref(false);

watch(() => form.vendor_id, async (newVal) => {
    if (!newVal) {
        vendorBills.value = [];
        return;
    }
    
    isLoadingBills.value = true;
    try {
        const response = await axios.get(route('api.vendors.bills', newVal));
        vendorBills.value = response.data;
    } catch (error) {
        console.error('Failed to fetch bills', error);
    } finally {
        isLoadingBills.value = false;
    }
});

const addBillToVoucher = (bill) => {
    if (form.bills.find(b => b.bill_id === bill.id)) return;
    
    form.bills.push({
        bill_id: bill.id,
        bill_number: bill.bill_number,
        amount: bill.balance,
        balance: bill.balance
    });
    calculateTotal();
};

const removeBillFromVoucher = (index) => {
    form.bills.splice(index, 1);
    calculateTotal();
};

const calculateTotal = () => {
    form.total_amount = form.bills.reduce((sum, bill) => sum + parseFloat(bill.amount || 0), 0);
};

const submit = () => {
    form.post(route('accounting.disbursements.store'));
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
};
</script>

<template>
    <Head title="Create Payment Voucher - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="route('accounting.disbursements.index')" class="text-slate-500 hover:text-slate-700">
                    <ArrowLeftIcon class="w-6 h-6" />
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    New Payment Voucher
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        <!-- Left: Main Details -->
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center">
                                    <InformationCircleIcon class="w-5 h-5 mr-2 text-blue-500" />
                                    Voucher Header
                                </h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Vendor</label>
                                        <select v-model="form.vendor_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">Select Vendor</option>
                                            <option v-for="vendor in verifiedVendors" :key="vendor.id" :value="vendor.id">
                                                {{ vendor.name }}
                                            </option>
                                        </select>
                                        <div v-if="form.errors.vendor_id" class="text-red-500 text-xs mt-1">{{ form.errors.vendor_id }}</div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Voucher Number</label>
                                        <input type="text" v-model="form.voucher_no" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                        <div v-if="form.errors.voucher_no" class="text-red-500 text-xs mt-1">{{ form.errors.voucher_no }}</div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Payment Date</label>
                                        <input type="date" v-model="form.payment_date" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Bank/Cash Account</label>
                                        <select v-model="form.bank_account_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">Select Account</option>
                                            <option v-for="acc in bankAccounts" :key="acc.id" :value="acc.id">
                                                {{ acc.code }} - {{ acc.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Payment Method</label>
                                        <select v-model="form.payment_method" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option>Cash</option>
                                            <option value="Check">Check (Current Dated)</option>
                                            <option>Bank Transfer</option>
                                            <option value="PDC">PDC (Post-Dated)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Applied Bills Section -->
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center justify-between">
                                    <span class="flex items-center">
                                        <BanknotesIcon class="w-5 h-5 mr-2 text-emerald-500" />
                                        Applied Bills
                                    </span>
                                </h3>
                                
                                <div v-if="form.bills.length === 0" class="text-center py-10 bg-slate-50 rounded-lg border-2 border-dashed border-slate-200">
                                    <p class="text-slate-500">No bills applied. Select from the sidebar to add.</p>
                                </div>
                                
                                <table v-else class="min-w-full divide-y divide-slate-200">
                                    <thead>
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-bold text-slate-500 uppercase">Bill #</th>
                                            <th class="px-4 py-2 text-right text-xs font-bold text-slate-500 uppercase">Balance</th>
                                            <th class="px-4 py-2 text-right text-xs font-bold text-slate-500 uppercase w-32">Payment</th>
                                            <th class="px-4 py-2 text-right"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="(bill, index) in form.bills" :key="index">
                                            <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ bill.bill_number }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-600 text-right">{{ formatCurrency(bill.balance) }}</td>
                                            <td class="px-4 py-3">
                                                <input 
                                                    type="number" 
                                                    v-model="bill.amount" 
                                                    @input="calculateTotal"
                                                    step="0.01"
                                                    class="block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm text-right"
                                                />
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <button @click="removeBillFromVoucher(index)" type="button" class="text-red-400 hover:text-red-600">
                                                    <TrashIcon class="w-5 h-5" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-slate-50">
                                            <td colspan="2" class="px-4 py-3 text-sm font-bold text-slate-800 text-right">TOTAL DISBURSEMENT</td>
                                            <td class="px-4 py-3 text-sm font-bold text-slate-900 text-right">{{ formatCurrency(form.total_amount) }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <div v-if="form.errors.bills" class="text-red-500 text-xs mt-2">{{ form.errors.bills }}</div>
                            </div>

                            <!-- Check / PDC Details (Consolidated) -->
                            <div v-if="['Check', 'PDC'].includes(form.payment_method)" class="bg-amber-50 rounded-xl shadow-sm border border-amber-200 p-6 animate-in slide-in-from-top duration-300">
                                <h3 class="text-lg font-bold text-amber-800 mb-4 flex items-center">
                                    <ClockIcon class="w-5 h-5 mr-2" />
                                    {{ form.payment_method === 'PDC' ? 'PDC Vault Details' : 'Check Issuance Details' }}
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-amber-700">Check Number</label>
                                        <input type="text" v-model="form.pdc_details.check_no" class="mt-1 block w-full rounded-md border-amber-300 focus:border-amber-500 focus:ring-amber-500 sm:text-sm" placeholder="e.g. 0000123" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-amber-700">{{ form.payment_method === 'PDC' ? 'Maturity Date' : 'Check Date' }}</label>
                                        <input type="date" v-model="form.pdc_details.check_date" class="mt-1 block w-full rounded-md border-amber-300 focus:border-amber-500 focus:ring-amber-500 sm:text-sm" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-amber-700">Bank</label>
                                        <select v-model="form.pdc_details.bank_id" class="mt-1 block w-full rounded-md border-amber-300 focus:border-amber-500 focus:ring-amber-500 sm:text-sm">
                                            <option value="">Select Bank</option>
                                            <option v-for="bank in banks" :key="bank.id" :value="bank.id">
                                                {{ bank.name }} - {{ bank.code }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-amber-700">Branch</label>
                                        <input type="text" v-model="form.pdc_details.bank_branch" class="mt-1 block w-full rounded-md border-amber-300 focus:border-amber-500 focus:ring-amber-500 sm:text-sm" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Sidebar Bills Selection -->
                        <div class="space-y-6">
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 sticky top-20">
                                <h3 class="text-md font-bold text-slate-800 mb-4 uppercase tracking-wider text-xs">Unpaid Bills</h3>
                                
                                <div v-if="!form.vendor_id" class="text-center py-6">
                                    <p class="text-slate-400 text-sm italic">Please select a vendor first.</p>
                                </div>
                                
                                <div v-else-if="isLoadingBills" class="text-center py-6">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500 mx-auto"></div>
                                </div>

                                <div v-else-if="vendorBills.length === 0" class="text-center py-6">
                                    <p class="text-slate-500 text-sm">No unpaid bills found for this vendor.</p>
                                </div>

                                <div v-else class="space-y-3 max-h-96 overflow-y-auto pr-2 custom-scrollbar">
                                    <div v-for="bill in vendorBills" :key="bill.id" 
                                         class="p-3 rounded-lg border border-slate-100 hover:border-indigo-300 hover:bg-indigo-50 transition-all cursor-pointer group"
                                         @click="addBillToVoucher(bill)">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="text-sm font-bold text-slate-800">{{ bill.bill_number }}</p>
                                                <p class="text-xs text-slate-500">{{ bill.bill_date }}</p>
                                            </div>
                                            <PlusIcon class="w-5 h-5 text-slate-300 group-hover:text-indigo-500" />
                                        </div>
                                        <div class="mt-2 flex justify-between items-center">
                                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600">{{ bill.status }}</span>
                                            <span class="text-sm font-bold text-slate-900">{{ formatCurrency(bill.balance) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-8 pt-6 border-t border-slate-100">
                                    <button 
                                        type="submit" 
                                        :disabled="form.processing || form.bills.length === 0"
                                        class="w-full inline-flex justify-center items-center px-4 py-3 bg-indigo-600 border border-transparent rounded-lg font-bold text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50 transition duration-150"
                                    >
                                        {{ form.processing ? 'Processing...' : 'Save Draft Voucher' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 4px;
}
</style>
