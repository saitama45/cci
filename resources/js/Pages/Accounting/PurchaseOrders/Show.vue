<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ChevronLeftIcon,
    PrinterIcon,
    CheckCircleIcon,
    DocumentDuplicateIcon,
    ShoppingCartIcon,
    CalendarIcon,
    BuildingOfficeIcon,
    UserIcon,
    InformationCircleIcon,
    ClockIcon,
    ArrowPathIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';
import { usePermission } from '@/Composables/usePermission';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    purchaseOrder: Object
});

const { hasPermission } = usePermission();
const { showSuccess, showError } = useToast();
const { confirm } = useConfirm();

const isBillingModalOpen = ref(false);
const billingForm = useForm({
    bill_number: 'BILL-' + props.purchaseOrder.po_number + '-' + Math.floor(100 + Math.random() * 900),
    bill_date: new Date().toISOString().split('T')[0],
    items: props.purchaseOrder.items.map(item => ({
        po_item_id: item.id,
        description: item.description,
        remaining_qty: parseFloat(item.quantity) - parseFloat(item.quantity_billed),
        quantity_to_bill: parseFloat(item.quantity) - parseFloat(item.quantity_billed),
        unit_price: item.unit_price
    }))
});

const openBillingModal = () => {
    isBillingModalOpen.value = true;
};

const submitPartialBill = () => {
    billingForm.post(route('accounting.purchase-orders.convert-to-bill', props.purchaseOrder.id), {
        onSuccess: () => {
            isBillingModalOpen.value = false;
        },
        onError: (errors) => {
            showError('Failed to generate bill. Check quantities.');
        }
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getStatusClass = (status) => {
    const baseClasses = 'px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest border shadow-sm';
    switch (status) {
        case 'Draft': return `${baseClasses} bg-gray-50 text-gray-600 border-gray-200`;
        case 'Approved': return `${baseClasses} bg-blue-50 text-blue-600 border-blue-200`;
        case 'Partially Billed': return `${baseClasses} bg-amber-50 text-amber-600 border-amber-200`;
        case 'Billed': return `${baseClasses} bg-emerald-50 text-emerald-600 border-emerald-200`;
        case 'Cancelled': return `${baseClasses} bg-red-50 text-red-600 border-red-200`;
        default: return baseClasses;
    }
};

const approvePO = async () => {
    const confirmed = await confirm({
        title: 'Approve Purchase Order',
        message: `Are you sure you want to approve PO #${props.purchaseOrder.po_number}? This will authorize the procurement.`,
        confirmButtonText: 'Yes, Approve PO',
        type: 'info'
    });

    if (confirmed) {
        router.post(route('accounting.purchase-orders.approve', props.purchaseOrder.id));
    }
};

const printPO = () => {
    window.open(route('accounting.purchase-orders.print', props.purchaseOrder.id), '_blank');
};
</script>

<template>
    <Head :title="`PO ${purchaseOrder.po_number} - Horizon ERP`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link :href="route('accounting.purchase-orders.index')" class="p-2.5 bg-white rounded-xl border border-slate-200 text-slate-400 hover:text-indigo-600 transition-all shadow-sm">
                        <ChevronLeftIcon class="w-5 h-5" />
                    </Link>
                    <div>
                        <h2 class="font-black text-2xl text-slate-900 leading-none">
                            PO #{{ purchaseOrder.po_number }}
                        </h2>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1.5">Purchase Order Details</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-3">
                    <button 
                        @click="printPO"
                        class="inline-flex items-center px-6 py-2.5 bg-white border border-slate-200 text-slate-700 text-xs font-black uppercase tracking-widest rounded-xl shadow-sm hover:bg-slate-50 transition-all"
                    >
                        <PrinterIcon class="w-4 h-4 mr-2" />
                        Print PO
                    </button>

                    <button 
                        v-if="purchaseOrder.status === 'Draft' && hasPermission('purchase_orders.approve')"
                        @click="approvePO"
                        class="inline-flex items-center px-6 py-2.5 bg-indigo-600 text-white text-xs font-black uppercase tracking-widest rounded-xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all"
                    >
                        <CheckCircleIcon class="w-4 h-4 mr-2" />
                        Approve Order
                    </button>
                    
                    <button 
                        v-if="['Approved', 'Partially Billed'].includes(purchaseOrder.status) && hasPermission('purchase_orders.convert')"
                        @click="openBillingModal"
                        class="inline-flex items-center px-6 py-2.5 bg-emerald-600 text-white text-xs font-black uppercase tracking-widest rounded-xl shadow-lg shadow-emerald-200 hover:bg-emerald-700 transition-all"
                    >
                        <DocumentDuplicateIcon class="w-4 h-4 mr-2" />
                        Create Bill
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left: Main Details -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Header Card -->
                        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                            <div class="p-10 border-b border-slate-50 flex flex-col md:flex-row md:items-center justify-between space-y-6 md:space-y-0">
                                <div class="flex items-center space-x-6">
                                    <div class="w-20 h-20 bg-indigo-50 rounded-[2rem] flex items-center justify-center">
                                        <ShoppingCartIcon class="w-10 h-10 text-indigo-600" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Vendor / Payee</p>
                                        <h3 class="text-2xl font-black text-slate-900">{{ purchaseOrder.vendor?.name }}</h3>
                                        <div class="flex items-center space-x-3 mt-1">
                                            <span class="text-xs font-bold text-slate-500">{{ purchaseOrder.vendor?.tin }}</span>
                                            <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                                            <span class="text-xs font-bold text-slate-500">{{ purchaseOrder.vendor?.category }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span :class="getStatusClass(purchaseOrder.status)">{{ purchaseOrder.status }}</span>
                                    <p class="text-[10px] font-bold text-slate-400 mt-4 uppercase tracking-widest">Order Date: {{ formatDate(purchaseOrder.po_date) }}</p>
                                </div>
                            </div>

                            <div class="p-10 grid grid-cols-1 md:grid-cols-3 gap-10 bg-slate-50/30">
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Project</p>
                                    <div class="flex items-center text-sm font-bold text-slate-700">
                                        <BuildingOfficeIcon class="w-4 h-4 mr-2 text-slate-400" />
                                        {{ purchaseOrder.project?.name || 'General Allocation' }}
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Expected Delivery</p>
                                    <div class="flex items-center text-sm font-bold text-slate-700">
                                        <CalendarIcon class="w-4 h-4 mr-2 text-slate-400" />
                                        {{ formatDate(purchaseOrder.expected_delivery_date) }}
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Net Pay to Vendor</p>
                                    <div class="text-xl font-black text-indigo-600">
                                        {{ formatCurrency(purchaseOrder.net_amount) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                            <div class="px-10 py-6 border-b border-slate-50 bg-slate-50/20 flex items-center justify-between">
                                <h3 class="font-black text-slate-900 uppercase tracking-tight text-xs">Order Line Items</h3>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Fulfillment Tracking</span>
                            </div>
                            <table class="min-w-full divide-y divide-slate-100">
                                <thead class="bg-slate-50/50">
                                    <tr>
                                        <th class="px-10 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Item Description</th>
                                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest w-24">Ordered</th>
                                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest w-24">Billed</th>
                                        <th class="px-10 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest w-36">Total Value</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="item in purchaseOrder.items" :key="item.id">
                                        <td class="px-10 py-5">
                                            <div class="text-sm font-bold text-slate-800">{{ item.description }}</div>
                                            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter mt-0.5">{{ item.account?.code }} - {{ item.account?.name }}</div>
                                        </td>
                                        <td class="px-6 py-5 text-center text-xs font-black text-slate-700 bg-slate-50/30">
                                            {{ item.quantity }}
                                        </td>
                                        <td class="px-6 py-5 text-center text-xs font-black" :class="parseFloat(item.quantity_billed) >= parseFloat(item.quantity) ? 'text-emerald-600' : 'text-amber-600'">
                                            {{ item.quantity_billed }}
                                        </td>
                                        <td class="px-10 py-5 text-right text-sm font-black text-slate-900">
                                            {{ formatCurrency(item.amount) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-slate-900 text-white">
                                    <tr>
                                        <td colspan="3" class="px-10 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Subtotal (Sum of Items)</td>
                                        <td class="px-10 py-3 text-sm font-black text-right">{{ formatCurrency(purchaseOrder.items.reduce((sum, i) => sum + parseFloat(i.amount), 0)) }}</td>
                                    </tr>
                                    <tr v-if="parseFloat(purchaseOrder.vat_amount) > 0">
                                        <td colspan="3" class="px-10 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">VAT (12%)</td>
                                        <td class="px-10 py-3 text-sm font-black text-right text-indigo-300">+ {{ formatCurrency(purchaseOrder.vat_amount) }}</td>
                                    </tr>
                                    <tr v-if="parseFloat(purchaseOrder.ewt_amount) > 0">
                                        <td colspan="3" class="px-10 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">EWT ({{ purchaseOrder.ewt_rate }}%)</td>
                                        <td class="px-10 py-3 text-sm font-black text-right text-rose-400">- {{ formatCurrency(purchaseOrder.ewt_amount) }}</td>
                                    </tr>
                                    <tr class="border-t border-white/10">
                                        <td colspan="3" class="px-10 py-6 text-sm font-bold text-white uppercase tracking-widest text-right">Net Payout to Vendor</td>
                                        <td class="px-10 py-6 text-2xl font-black text-right text-emerald-400">{{ formatCurrency(purchaseOrder.net_amount) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Right: Info Cards -->
                    <div class="space-y-6">
                        <!-- Tax Breakdown Card -->
                        <div class="bg-slate-900 rounded-[2rem] p-8 shadow-xl border border-slate-800 text-white relative overflow-hidden">
                            <div class="relative z-10 space-y-4">
                                <h3 class="text-xs font-black text-indigo-400 uppercase tracking-widest mb-2 flex items-center">
                                    <InformationCircleIcon class="w-4 h-4 mr-2" />
                                    Tax Breakdown
                                </h3>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-400">Tax Basis:</span>
                                    <span class="font-bold">{{ purchaseOrder.tax_type }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-400">VAT (12%):</span>
                                    <span class="font-bold">{{ formatCurrency(purchaseOrder.vat_amount) }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-400">EWT ({{ purchaseOrder.ewt_rate }}%):</span>
                                    <span class="font-bold text-rose-400">({{ formatCurrency(purchaseOrder.ewt_amount) }})</span>
                                </div>
                                <div class="pt-4 border-t border-white/10">
                                    <p class="text-[10px] font-black text-indigo-300 uppercase tracking-widest mb-1">Final Net Amount</p>
                                    <h3 class="text-2xl font-black">{{ formatCurrency(purchaseOrder.net_amount) }}</h3>
                                </div>
                            </div>
                            <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/10 blur-3xl rounded-full -mr-16 -mt-16"></div>
                        </div>

                        <!-- Linked Bills -->
                        <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-200">
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6">Fulfillment History</h3>
                            <div v-if="purchaseOrder.bills.length === 0" class="text-center py-6">
                                <ArrowPathIcon class="w-10 h-10 text-slate-100 mx-auto mb-3" />
                                <p class="text-xs text-slate-400 italic">No bills linked yet.</p>
                            </div>
                            <div v-else class="space-y-4">
                                <Link v-for="bill in purchaseOrder.bills" :key="bill.id" :href="route('accounting.bills.show', bill.id)" class="flex items-center justify-between p-4 rounded-2xl border border-slate-50 hover:border-indigo-100 hover:bg-indigo-50/30 transition-all group">
                                    <div>
                                        <p class="text-[10px] font-black text-slate-400 uppercase mb-0.5 group-hover:text-indigo-400">{{ bill.bill_number }}</p>
                                        <p class="text-xs font-bold text-slate-700">{{ formatDate(bill.bill_date) }}</p>
                                    </div>
                                    <span class="text-sm font-black text-slate-900">{{ formatCurrency(bill.total_amount) }}</span>
                                </Link>
                            </div>
                        </div>

                        <!-- Audit Trail -->
                        <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-200">
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6">Internal Controls</h3>
                            <div class="space-y-6">
                                <div class="flex items-start space-x-4">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center flex-shrink-0">
                                        <UserIcon class="w-4 h-4 text-slate-500" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">Prepared By</p>
                                        <p class="text-sm font-bold text-slate-800">{{ purchaseOrder.prepared_by?.name || 'System' }}</p>
                                        <p class="text-[10px] text-slate-400">{{ purchaseOrder.created_at }}</p>
                                    </div>
                                </div>
                                <div v-if="purchaseOrder.approved_by" class="flex items-start space-x-4">
                                    <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center flex-shrink-0">
                                        <CheckCircleIcon class="w-4 h-4 text-blue-600" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-blue-400 uppercase tracking-tighter">Approved By</p>
                                        <p class="text-sm font-bold text-slate-800">{{ purchaseOrder.approved_by?.name }}</p>
                                        <p class="text-[10px] text-slate-400">{{ purchaseOrder.updated_at }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Partial Billing Modal -->
        <div v-if="isBillingModalOpen" class="fixed inset-0 z-[60] overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="isBillingModalOpen = false"></div>
            
            <div class="bg-white rounded-[2.5rem] shadow-2xl max-w-3xl w-full relative animate-in fade-in zoom-in duration-200 overflow-hidden">
                <div class="px-10 py-8 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div>
                        <h3 class="text-xl font-black text-slate-900">Generate Vendor Bill</h3>
                        <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Fulfillment for PO #{{ purchaseOrder.po_number }}</p>
                    </div>
                    <button @click="isBillingModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <XMarkIcon class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-10 space-y-8">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Vendor Invoice / Bill #</label>
                            <input v-model="billingForm.bill_number" type="text" required class="block w-full rounded-2xl border-slate-200 bg-slate-50 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 font-black text-slate-900" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Billing Date</label>
                            <input v-model="billingForm.bill_date" type="date" required class="block w-full rounded-2xl border-slate-200 bg-slate-50 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 font-bold text-slate-700" />
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-100">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-[10px] font-black text-slate-400 uppercase">Item</th>
                                    <th class="px-6 py-3 text-center text-[10px] font-black text-slate-400 uppercase">Remaining</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-black text-slate-400 uppercase w-32">Bill Qty</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="(item, index) in billingForm.items" :key="index">
                                    <td class="px-6 py-4 text-sm font-bold text-slate-700">{{ item.description }}</td>
                                    <td class="px-6 py-4 text-center text-xs font-black text-slate-400">{{ item.remaining_qty }}</td>
                                    <td class="px-6 py-4">
                                        <input v-model="item.quantity_to_bill" type="number" step="0.01" :max="item.remaining_qty" min="0" class="block w-full rounded-xl border-slate-200 text-xs font-black text-right text-emerald-600 focus:ring-emerald-500" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="px-10 py-8 bg-slate-50 border-t border-slate-100 flex items-center justify-end space-x-4">
                    <button @click="isBillingModalOpen = false" class="text-xs font-black text-slate-400 uppercase tracking-widest px-6">Cancel</button>
                    <button @click="submitPartialBill" :disabled="billingForm.processing" class="px-10 py-4 bg-emerald-600 text-white text-xs font-black rounded-2xl shadow-xl shadow-emerald-600/30 hover:bg-emerald-700 transition-all uppercase tracking-widest">
                        {{ billingForm.processing ? 'Processing...' : 'Generate Bill' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
