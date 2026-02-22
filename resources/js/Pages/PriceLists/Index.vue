<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, watch, nextTick, computed, reactive } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useErrorHandler } from '@/Composables/useErrorHandler';
import { useToast } from '@/Composables/useToast';
import { usePagination } from '@/Composables/usePagination';
import { usePermission } from '@/Composables/usePermission';
import { useInputRestriction } from '@/Composables/useInputRestriction';
import { 
    CurrencyDollarIcon,
    PencilSquareIcon, 
    TrashIcon,
    PlusIcon,
    EyeIcon,
    CalculatorIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    priceLists: Object,
    projects: Array,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingPriceList = ref(null);
const availableUnits = ref([]);
const selectedProjectId = ref('');
const isFetchingUnits = ref(false);

// Centralized reactive display values to fix template unwrap issues
const displays = ref({
    createPrice: '',
    editPrice: '',
    createDP: '',
    editDP: ''
});

const selectedUnit = computed(() => {
    return availableUnits.value.find(u => u.id == createForm.unit_id);
});

const formatPriceInput = (value) => {
    if (value === null || value === undefined || value === '') return '';
    
    // Convert to string and strip existing commas for clean processing
    let cleanValue = value.toString().replace(/,/g, '');
    
    // Apply centralized numeric restriction (positive only)
    cleanValue = restrictNumeric(cleanValue, true, false);
    
    if (!cleanValue) return '';

    const [integerPart, decimalPart] = cleanValue.split('.');
    const formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    
    // Limit to 2 decimal places if present
    let formattedDecimal = decimalPart;
    if (decimalPart && decimalPart.length > 2) {
        formattedDecimal = decimalPart.substring(0, 2);
    }
    
    return formattedDecimal !== undefined ? `${formattedInteger}.${formattedDecimal}` : formattedInteger;
};

const unformatPriceInput = (value) => {
    if (!value) return '';
    return value.toString().replace(/,/g, '');
};

const handlePriceInput = (e, form, field, displayName) => {
    const input = e.target;
    const start = input.selectionStart;
    const oldVal = input.value;
    
    // Apply restriction and formatting
    const formatted = formatPriceInput(oldVal);
    const unformatted = unformatPriceInput(formatted);
    
    // Update reactive state
    displays.value[displayName] = formatted;
    form[field] = unformatted;
    
    // Sync DOM directly to handle commas without losing focus/cursor
    input.value = formatted;
    
    nextTick(() => {
        const newPos = Math.max(0, start + (formatted.length - oldVal.length));
        input.setSelectionRange(newPos, newPos);
    });
};

const { confirm } = useConfirm();
const { post, put, destroy } = useErrorHandler();
const { showSuccess, showError } = useToast();
const { hasPermission } = usePermission();
const { restrictNumeric, formatDateForInput } = useInputRestriction();

const pagination = usePagination(props.priceLists, 'price-lists.index');

onMounted(() => {
    pagination.updateData(props.priceLists);
});

watch(() => props.priceLists, (newPriceLists) => {
    pagination.updateData(newPriceLists);
}, { deep: true });

const processing = ref(false);

const createForm = useForm({
    unit_id: '',
    price_per_sqm: '',
    downpayment_amount: '',
    dp_percentage: 20,
    effective_date: new Date().toISOString().substr(0, 10),
});

const editForm = useForm({
    price_per_sqm: '',
    downpayment_amount: '',
    dp_percentage: 20,
    effective_date: '',
});

// Real-time Calculators with robust safety
const calculateTCP = (pricePerSqm, area) => {
    if (!pricePerSqm || !area) return 0;
    const price = parseFloat(pricePerSqm.toString().replace(/,/g, '') || 0);
    const sqm = parseFloat(area || 0);
    if (price <= 0 || sqm <= 0) return 0;
    return (price * sqm) * 1.12; // 12% VAT
};

const createTCP = computed(() => {
    return calculateTCP(createForm.price_per_sqm, selectedUnit.value?.sqm_area);
});
const editTCP = computed(() => {
    return calculateTCP(editForm.price_per_sqm, editingPriceList.value?.unit?.sqm_area);
});

// Watchers for DP auto-sync
watch(() => createForm.dp_percentage, (val) => {
    if (createTCP.value > 0) {
        const amount = (createTCP.value * (parseFloat(val || 0) / 100)).toFixed(2);
        createForm.downpayment_amount = amount;
        displays.value.createDP = formatPriceInput(amount);
    }
});

watch(() => editForm.dp_percentage, (val) => {
    if (editTCP.value > 0) {
        const amount = (editTCP.value * (parseFloat(val || 0) / 100)).toFixed(2);
        editForm.downpayment_amount = amount;
        displays.value.editDP = formatPriceInput(amount);
    }
});

watch(createTCP, (newTcp) => {
    if (newTcp > 0 && createForm.dp_percentage) {
        const amount = (newTcp * (parseFloat(createForm.dp_percentage) / 100)).toFixed(2);
        createForm.downpayment_amount = amount;
        displays.value.createDP = formatPriceInput(amount);
    }
});

watch(editTCP, (newTcp) => {
    if (newTcp > 0 && editForm.dp_percentage) {
        const amount = (newTcp * (parseFloat(editForm.dp_percentage) / 100)).toFixed(2);
        editForm.downpayment_amount = amount;
        displays.value.editDP = formatPriceInput(amount);
    }
});

// Sync DP when unit selection changes in Create Modal
watch(() => createForm.unit_id, () => {
    nextTick(() => {
        if (createTCP.value > 0) {
            const amount = (createTCP.value * (parseFloat(createForm.dp_percentage) / 100)).toFixed(2);
            createForm.downpayment_amount = amount;
            displays.value.createDP = formatPriceInput(amount);
        }
    });
});

// Fetch units when project changes in Create Modal
const fetchUnits = async () => {
    if (!selectedProjectId.value) {
        availableUnits.value = [];
        return;
    }
    
    isFetchingUnits.value = true;
    try {
        const response = await axios.get(route('api.projects.units', selectedProjectId.value));
        availableUnits.value = response.data;
    } catch (error) {
        console.error('Failed to fetch units:', error);
        showError('Failed to load units for the selected project.');
    } finally {
        isFetchingUnits.value = false;
    }
};

watch(selectedProjectId, () => {
    createForm.unit_id = ''; // Reset unit selection
    fetchUnits();
});

const createPriceList = () => {
    createForm.price_per_sqm = unformatPriceInput(displays.value.createPrice);
    createForm.downpayment_amount = unformatPriceInput(displays.value.createDP);
    
    post(route('price-lists.store'), createForm.data(), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
            Object.keys(displays.value).forEach(key => displays.value[key] = '');
            selectedProjectId.value = '';
            availableUnits.value = [];
            showSuccess('Price list created successfully')
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join(', ') || 'An error occurred'
            showError(errorMessage)
        }
    });
};

const editPriceList = (priceList) => {
    editingPriceList.value = priceList;
    
    displays.value.editPrice = formatPriceInput(priceList.price_per_sqm);
    editForm.price_per_sqm = priceList.price_per_sqm;
    
    displays.value.editDP = formatPriceInput(priceList.downpayment_amount);
    editForm.downpayment_amount = priceList.downpayment_amount;

    const tcp = parseFloat(priceList.tcp || 0);
    const dp = parseFloat(priceList.downpayment_amount || 0);
    editForm.dp_percentage = tcp > 0 ? ((dp / tcp) * 100).toFixed(2) : 20;

    editForm.effective_date = formatDateForInput(priceList.effective_date);
    showEditModal.value = true;
};

const updatePriceList = () => {
    editForm.price_per_sqm = unformatPriceInput(displays.value.editPrice);
    editForm.downpayment_amount = unformatPriceInput(displays.value.editDP);
    
    put(route('price-lists.update', editingPriceList.value.id), editForm.data(), {
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
            Object.keys(displays.value).forEach(key => displays.value[key] = '');
            editingPriceList.value = null;
            showSuccess('Price list updated successfully')
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join(', ') || 'An error occurred'
            showError(errorMessage)
        }
    });
};

const deletePriceList = async (priceList) => {
    const confirmed = await confirm({
        title: 'Delete Price List',
        message: `Are you sure you want to delete this price list entry? This action cannot be undone.`,
        confirmButtonText: 'Delete Price',
        type: 'danger'
    })
    
    if (confirmed) {
        destroy(route('price-lists.destroy', priceList.id), {
            onSuccess: () => showSuccess('Price list deleted successfully'),
            onError: (errors) => {
                const errorMessage = Object.values(errors).flat().join(', ') || 'Cannot delete price list'
                showError(errorMessage)
            }
        });
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(value);
};
</script>

<template>
    <Head title="Price Lists - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Price Lists</h2>
                    <p class="text-sm text-slate-500 mt-1">Manage unit pricing, VAT, and Total Contract Prices.</p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Data Table Container -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <DataTable
                        title="Pricing Directory"
                        subtitle="Current effective prices for inventory"
                        search-placeholder="Search by unit, project or price..."
                        empty-message="No price lists found. Create your first pricing."
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
                                v-if="hasPermission('price_lists.create')"
                                @click="showCreateModal = true"
                                class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition-all duration-200 flex items-center space-x-2 text-sm font-semibold shadow-lg shadow-blue-600/20"
                            >
                                <PlusIcon class="w-5 h-5" />
                                <span>Add Price</span>
                            </button>
                        </template>

                        <template #header>
                            <tr class="bg-slate-50">
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Unit Details</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Base Price / Sqm</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Downpayment</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Total Contract Price</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Effective Date</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">Actions</th>
                            </tr>
                        </template>

                        <template #body="{ data }">
                            <tr v-for="price in data" :key="price.id" class="hover:bg-slate-50/50 transition-colors border-b border-slate-50 last:border-0">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-500 font-bold border border-slate-200">
                                            <CurrencyDollarIcon class="w-5 h-5" />
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-slate-900">{{ price.unit?.name || 'Unknown Unit' }}</div>
                                            <div class="text-xs text-slate-500">
                                                {{ price.unit?.project?.name }} • B{{ price.unit?.block_num }} L{{ price.unit?.lot_num }} • {{ price.unit?.sqm_area }} sqm
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-mono text-slate-700">{{ formatCurrency(price.price_per_sqm) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-bold text-slate-700">{{ formatCurrency(price.downpayment_amount) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-emerald-600">{{ formatCurrency(price.tcp) }}</span>
                                        <span class="text-[10px] text-slate-400">VAT: {{ formatCurrency(price.vat_amount) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 text-slate-600">
                                        {{ new Date(price.effective_date).toLocaleDateString() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-1">
                                         <Link
                                            v-if="hasPermission('price_lists.view')"
                                            :href="route('price-lists.show', price.id)"
                                            class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                                            title="View Details"
                                        >
                                            <EyeIcon class="w-5 h-5" />
                                        </Link>
                                        <button
                                            v-if="hasPermission('price_lists.edit')"
                                            @click="editPriceList(price)"
                                            class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                                            title="Update Price"
                                        >
                                            <PencilSquareIcon class="w-5 h-5" />
                                        </button>
                                        <button
                                            v-if="hasPermission('price_lists.delete')"
                                            @click="deletePriceList(price)"
                                            class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                                            title="Delete Price"
                                        >
                                            <TrashIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- Create Price List Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto flex items-start sm:items-center justify-center p-4 pt-10 sm:pt-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="showCreateModal = false"></div>
            
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full relative overflow-hidden animate-in fade-in zoom-in duration-200 my-auto">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-xl font-bold text-slate-900">New Price Entry</h3>
                    <p class="text-sm text-slate-500">Set base price and calculate TCP for a unit.</p>
                </div>
                
                <form @submit.prevent="createPriceList" class="p-8 space-y-5">
                    
                    <!-- Project Selection (To filter units) -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Project Filter</label>
                        <select v-model="selectedProjectId" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                            <option value="" disabled>Select a Project</option>
                            <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Unit</label>
                        <select v-model="createForm.unit_id" required :disabled="!selectedProjectId || isFetchingUnits" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all disabled:opacity-50">
                            <option value="" disabled>{{ isFetchingUnits ? 'Loading units...' : 'Select a Unit' }}</option>
                            <option v-for="unit in availableUnits" :key="unit.id" :value="unit.id">
                                B{{ unit.block_num }} L{{ unit.lot_num }} - {{ unit.name }}
                            </option>
                        </select>
                        <div v-if="selectedUnit" class="mt-2 p-3 bg-blue-50 border border-blue-100 rounded-xl flex items-center justify-between">
                            <span class="text-xs font-bold text-blue-600 uppercase tracking-widest">Unit Area</span>
                            <span class="text-sm font-black text-blue-700">{{ selectedUnit.sqm_area }} sqm</span>
                        </div>
                         <p v-if="!selectedProjectId" class="text-xs text-slate-400 mt-1">Please select a project first.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Price per Sqm (PHP)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-slate-500 sm:text-sm">₱</span>
                            </div>
                            <input 
                                :value="displays.createPrice"
                                @input="handlePriceInput($event, createForm, 'price_per_sqm', 'createPrice')"
                                type="text" 
                                placeholder="0.00"
                                required 
                                class="block w-full pl-7 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                            >
                        </div>
                        <div class="mt-2 flex items-center justify-between px-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Est. Total Contract Price (Incl. 12% VAT)</span>
                            <span class="text-sm font-black" :class="createTCP > 0 ? 'text-emerald-600' : 'text-slate-300'">
                                {{ formatCurrency(createTCP) }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">DP %</label>
                            <div class="relative">
                                <input v-model="createForm.dp_percentage" type="number" step="0.01" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-bold text-slate-700">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-slate-400 text-xs">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Downpayment (PHP)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-slate-500 sm:text-sm">₱</span>
                                </div>
                                <input 
                                    :value="displays.createDP"
                                    @input="handlePriceInput($event, createForm, 'downpayment_amount', 'createDP')"
                                    type="text" 
                                    placeholder="0.00"
                                    required 
                                    class="block w-full pl-7 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                                >
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Effective Date</label>
                        <input v-model="createForm.effective_date" type="date" required class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                    </div>
                    
                    <div class="bg-blue-50 rounded-lg p-4 flex items-start space-x-3">
                        <CalculatorIcon class="w-5 h-5 text-blue-600 mt-0.5" />
                        <div class="text-xs text-blue-700">
                            <p class="font-bold">Automated Calculation:</p>
                            <p>TCP and VAT (12%) will be automatically computed based on the Unit Area and Price per Sqm upon saving.</p>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="showCreateModal = false" class="px-6 py-2.5 text-slate-600 font-bold bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">Cancel</button>
                        <button type="submit" :disabled="createForm.processing" class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-600/20 disabled:opacity-50 transition-all">Save Price</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Price List Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto flex items-start sm:items-center justify-center p-4 pt-10 sm:pt-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="showEditModal = false"></div>
            
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full relative overflow-hidden animate-in fade-in zoom-in duration-200 my-auto">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-xl font-bold text-slate-900">Update Price</h3>
                    <p class="text-sm text-slate-500">Modify pricing for <strong>B{{ editingPriceList?.unit?.block_num }} L{{ editingPriceList?.unit?.lot_num }}</strong>.</p>
                    <div class="mt-2 inline-flex items-center px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-black uppercase tracking-wider border border-blue-100">
                        Area: {{ editingPriceList?.unit?.sqm_area }} sqm
                    </div>
                </div>
                
                <form @submit.prevent="updatePriceList" class="p-8 space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Price per Sqm (PHP)</label>
                         <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-slate-500 sm:text-sm">₱</span>
                            </div>
                            <input 
                                :value="displays.editPrice"
                                @input="handlePriceInput($event, editForm, 'price_per_sqm', 'editPrice')"
                                type="text" 
                                placeholder="0.00"
                                required 
                                class="block w-full pl-7 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                            >
                        </div>
                        <div class="mt-2 flex items-center justify-between px-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Est. Total Contract Price (Incl. 12% VAT)</span>
                            <span class="text-sm font-black" :class="editTCP > 0 ? 'text-emerald-600' : 'text-slate-300'">
                                {{ formatCurrency(editTCP) }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">DP %</label>
                            <div class="relative">
                                <input v-model="editForm.dp_percentage" type="number" step="0.01" class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-bold text-slate-700">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-slate-400 text-xs">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Downpayment (PHP)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-slate-500 sm:text-sm">₱</span>
                                </div>
                                <input 
                                    :value="displays.editDP"
                                    @input="handlePriceInput($event, editForm, 'downpayment_amount', 'editDP')"
                                    type="text" 
                                    placeholder="0.00"
                                    required 
                                    class="block w-full pl-7 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                                >
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Effective Date</label>
                        <input v-model="editForm.effective_date" type="date" required class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                    </div>

                    <div class="bg-amber-50 rounded-lg p-4 flex items-start space-x-3">
                        <CalculatorIcon class="w-5 h-5 text-amber-600 mt-0.5" />
                        <div class="text-xs text-amber-700">
                            <p class="font-bold">Recalculation Warning:</p>
                            <p>Changing the Price per Sqm will automatically update the Total Contract Price (TCP) and VAT amount for this unit.</p>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="showEditModal = false" class="px-6 py-2.5 text-slate-600 font-bold bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">Cancel</button>
                        <button type="submit" :disabled="editForm.processing" class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-600/20 disabled:opacity-50 transition-all">Update Price</button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
