<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ChevronLeftIcon,
    PlusIcon,
    TrashIcon,
    ShoppingCartIcon,
    CalendarIcon,
    BuildingOfficeIcon,
    BanknotesIcon,
    DocumentTextIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    vendors: Array,
    projects: Array,
    accounts: Array
});

const { showError } = useToast();

const form = useForm({
    vendor_id: '',
    project_id: '',
    po_number: 'PO-' + new Date().getFullYear() + '-' + Math.floor(1000 + Math.random() * 9000),
    po_date: new Date().toISOString().split('T')[0],
    expected_delivery_date: '',
    notes: '',
    tax_type: 'VAT Exclusive',
    ewt_rate: 0,
    items: [
        { chart_of_account_id: '', description: '', quantity: 1, unit_price: 0 }
    ]
});

// Tax Calculations
const grossAmount = computed(() => {
    return form.items.reduce((sum, item) => sum + (parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0)), 0);
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

const addItem = () => {
    form.items.push({ chart_of_account_id: '', description: '', quantity: 1, unit_price: 0 });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

// Budget Impact Logic (Conceptual - would need a backend endpoint for production)
const budgetAlert = computed(() => {
    if (!form.project_id) return null;
    // In a real app, we would fetch props.projectBudgets
    return {
        status: 'Warning',
        message: 'Project budget for Materials is at 85% capacity.'
    };
});

const submit = () => {
    form.post(route('accounting.purchase-orders.store'), {
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
</script>

<template>
    <Head title="Create Purchase Order - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="route('accounting.purchase-orders.index')" class="text-slate-500 hover:text-slate-700">
                    <ChevronLeftIcon class="w-6 h-6" />
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    New Purchase Order
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-10">
                            <!-- Column 1: Vendor & Project -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Vendor (Verified)</label>
                                    <select v-model="form.vendor_id" required class="block w-full rounded-2xl border-slate-200 bg-slate-50 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 font-bold text-slate-700">
                                        <option value="">Select Vendor</option>
                                        <option v-for="vendor in vendors" :key="vendor.id" :value="vendor.id">{{ vendor.name }}</option>
                                    </select>
                                    <div v-if="form.errors.vendor_id" class="text-red-500 text-[10px] mt-1 italic font-bold">{{ form.errors.vendor_id }}</div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Project Allocation</label>
                                    <select v-model="form.project_id" class="block w-full rounded-2xl border-slate-200 bg-slate-50 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 font-bold text-slate-700">
                                        <option value="">General (Head Office)</option>
                                        <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.name }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Column 2: PO Details -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">PO Number</label>
                                    <input v-model="form.po_number" type="text" required class="block w-full rounded-2xl border-slate-200 bg-slate-50 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 font-black text-slate-900" />
                                    <div v-if="form.errors.po_number" class="text-red-500 text-[10px] mt-1 italic font-bold">{{ form.errors.po_number }}</div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">PO Date</label>
                                        <input v-model="form.po_date" type="date" required class="block w-full rounded-2xl border-slate-200 bg-slate-50 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 font-bold text-slate-700 text-xs" />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Expected Delivery</label>
                                        <input v-model="form.expected_delivery_date" type="date" class="block w-full rounded-2xl border-slate-200 bg-slate-50 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 font-bold text-slate-700 text-xs" />
                                    </div>
                                </div>
                            </div>

                            <!-- Column 3: Totals & Notes -->
                            <div class="space-y-4">
                                <div class="p-6 bg-slate-900 rounded-3xl text-white relative overflow-hidden">
                                    <div class="relative z-10 space-y-2">
                                        <div class="flex justify-between items-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                            <span>Subtotal</span>
                                            <span>{{ formatCurrency(grossAmount) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                            <span>VAT (12%)</span>
                                            <span>{{ formatCurrency(vatAmount) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center text-[10px] font-bold text-rose-400 uppercase tracking-widest">
                                            <span>EWT ({{ form.ewt_rate }}%)</span>
                                            <span>({{ formatCurrency(ewtAmount) }})</span>
                                        </div>
                                        <div class="pt-2 border-t border-white/10">
                                            <p class="text-[10px] font-black text-indigo-300 uppercase tracking-widest mb-1">Net Pay to Vendor</p>
                                            <h3 class="text-3xl font-black">{{ formatCurrency(netAmount) }}</h3>
                                        </div>
                                    </div>
                                    <ShoppingCartIcon class="absolute right-[-20px] bottom-[-20px] w-24 h-24 text-white/5 rotate-12 pointer-events-none" />
                                </div>

                                <!-- Budget Impact Widget -->
                                <div v-if="budgetAlert" class="p-4 bg-amber-50 rounded-2xl border border-amber-100 flex items-start space-x-3">
                                    <InformationCircleIcon class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" />
                                    <div>
                                        <p class="text-[10px] font-black text-amber-800 uppercase tracking-widest">Budget Awareness</p>
                                        <p class="text-xs text-amber-700 font-medium leading-tight mt-1">{{ budgetAlert.message }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tax Settings -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10 p-6 bg-slate-50 rounded-3xl border border-slate-100">
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

                        <!-- PO Items Table -->
                        <div class="mb-10">
                            <h3 class="text-lg font-black text-slate-900 mb-6 flex items-center">
                                <DocumentTextIcon class="w-6 h-6 mr-2 text-indigo-600" />
                                Order Items
                            </h3>
                            <div class="overflow-x-auto rounded-3xl border border-slate-100 overflow-hidden">
                                <table class="min-w-full divide-y divide-slate-100">
                                    <thead class="bg-slate-50/50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest w-1/4">GL Account</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Description</th>
                                            <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest w-24">Qty</th>
                                            <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest w-32">Unit Price</th>
                                            <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest w-36">Total</th>
                                            <th class="px-6 py-4 w-12"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <tr v-for="(item, index) in form.items" :key="index" class="group hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4">
                                                <select v-model="item.chart_of_account_id" required class="block w-full rounded-xl border-slate-200 text-xs font-bold text-slate-600 focus:ring-indigo-500">
                                                    <option value="">Select Account</option>
                                                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.code }} - {{ acc.name }}</option>
                                                </select>
                                            </td>
                                            <td class="px-6 py-4">
                                                <input v-model="item.description" type="text" required class="block w-full rounded-xl border-slate-200 text-xs font-bold text-slate-900 focus:ring-indigo-500" />
                                            </td>
                                            <td class="px-6 py-4">
                                                <input v-model="item.quantity" type="number" step="0.01" required class="block w-full rounded-xl border-slate-200 text-xs font-bold text-center focus:ring-indigo-500" />
                                            </td>
                                            <td class="px-6 py-4">
                                                <input v-model="item.unit_price" type="number" step="0.01" required class="block w-full rounded-xl border-slate-200 text-xs font-black text-right text-indigo-600 focus:ring-indigo-500" />
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <span class="text-xs font-black text-slate-900">{{ formatCurrency(item.quantity * item.unit_price) }}</span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <button v-if="form.items.length > 1" @click="removeItem(index)" type="button" class="p-2 text-rose-300 hover:text-rose-600 transition-colors">
                                                    <TrashIcon class="w-5 h-5" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-slate-50/30">
                                        <tr>
                                            <td colspan="6" class="px-6 py-4">
                                                <button @click="addItem" type="button" class="inline-flex items-center text-xs font-black text-indigo-600 hover:text-indigo-800 tracking-widest uppercase">
                                                    <PlusIcon class="w-4 h-4 mr-1" />
                                                    Add Order Line
                                                </button>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="flex items-center justify-end space-x-4 border-t border-slate-100 pt-8">
                            <Link :href="route('accounting.purchase-orders.index')" class="px-8 py-3 text-xs font-black text-slate-400 hover:text-slate-600 uppercase tracking-widest transition-colors">Cancel</Link>
                            <button type="submit" :disabled="form.processing" class="px-10 py-4 bg-indigo-600 text-white text-xs font-black rounded-2xl shadow-xl shadow-indigo-600/30 hover:bg-indigo-700 transition-all uppercase tracking-widest">
                                Save Draft Order
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
