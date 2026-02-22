<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    CurrencyDollarIcon, 
    ArrowLeftIcon,
    CalculatorIcon,
    HomeIcon,
    BanknotesIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    priceList: Object,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(value);
};
</script>

<template>
    <Head :title="`Price Details - B${priceList.unit.block_num} L${priceList.unit.lot_num}`" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Pricing Details</h2>
                    <p class="text-sm text-slate-500 mt-1">Financial breakdown for Unit B{{ priceList.unit.block_num }} L{{ priceList.unit.lot_num }}.</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <Link
                        :href="route('price-lists.index')"
                        class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-lg font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50 transition ease-in-out duration-150"
                    >
                        <ArrowLeftIcon class="w-4 h-4 mr-2" />
                        Back to List
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Main Info Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-100 p-6 md:p-8">
                    <div class="flex flex-col md:flex-row items-start justify-between">
                        <div class="flex items-center">
                            <div class="h-16 w-16 bg-emerald-50 rounded-2xl flex items-center justify-center border border-emerald-100 text-emerald-600">
                                <CurrencyDollarIcon class="w-8 h-8" />
                            </div>
                            <div class="ml-6">
                                <h1 class="text-2xl font-bold text-slate-900">{{ formatCurrency(priceList.tcp) }}</h1>
                                <p class="text-sm font-medium text-slate-500">Total Contract Price (TCP)</p>
                            </div>
                        </div>
                        
                        <div class="mt-4 md:mt-0 text-right">
                             <div class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-sm font-bold text-slate-600">
                                Effective: {{ new Date(priceList.effective_date).toLocaleDateString() }}
                             </div>
                        </div>
                    </div>

                    <div class="mt-8 border-t border-slate-100 pt-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Unit Info -->
                        <div>
                            <h3 class="flex items-center text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">
                                <HomeIcon class="w-4 h-4 mr-2 text-slate-400" />
                                Unit Information
                            </h3>
                            <dl class="space-y-3">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-slate-500">Project Name</dt>
                                    <dd class="text-sm font-semibold text-slate-900">{{ priceList.unit.project.name }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-slate-500">Block & Lot</dt>
                                    <dd class="text-sm font-semibold text-slate-900">Block {{ priceList.unit.block_num }} Lot {{ priceList.unit.lot_num }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-slate-500">Unit Model / Name</dt>
                                    <dd class="text-sm font-semibold text-slate-900">{{ priceList.unit.name }}</dd>
                                </div>
                                <div class="flex justify-between border-t border-slate-50 pt-3">
                                    <dt class="text-sm font-bold text-slate-700">Lot Area</dt>
                                    <dd class="text-sm font-bold text-slate-900">{{ priceList.unit.sqm_area }} sqm</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Price Breakdown -->
                        <div>
                            <h3 class="flex items-center text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">
                                <CalculatorIcon class="w-4 h-4 mr-2 text-slate-400" />
                                Cost Computation
                            </h3>
                            <div class="bg-slate-50 rounded-xl p-5 border border-slate-100 space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-slate-600">Price per Sqm</span>
                                    <span class="text-sm font-mono font-medium text-slate-900">{{ formatCurrency(priceList.price_per_sqm) }}</span>
                                </div>
                                <div class="flex justify-between items-center border-b border-slate-200 pb-3">
                                    <span class="text-sm text-slate-600">Base Price (Area x Price)</span>
                                    <span class="text-sm font-mono font-medium text-slate-900">
                                        {{ formatCurrency(priceList.price_per_sqm * priceList.unit.sqm_area) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-slate-600">VAT (12%)</span>
                                    <span class="text-sm font-mono font-medium text-slate-900">{{ formatCurrency(priceList.vat_amount) }}</span>
                                </div>
                                <div class="flex justify-between items-center pt-2 border-t border-slate-200">
                                    <span class="text-base font-bold text-emerald-700">Total Contract Price</span>
                                    <span class="text-base font-mono font-bold text-emerald-700">{{ formatCurrency(priceList.tcp) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Downpayment Details -->
                        <div>
                            <h3 class="flex items-center text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">
                                <BanknotesIcon class="w-4 h-4 mr-2 text-slate-400" />
                                Downpayment Terms
                            </h3>
                            <div class="bg-blue-50 rounded-xl p-5 border border-blue-100 space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-blue-700">DP Percentage</span>
                                    <span class="text-sm font-black text-blue-900">
                                        {{ ((parseFloat(priceList.downpayment_amount || 0) / parseFloat(priceList.tcp || 1)) * 100).toFixed(2) }}%
                                    </span>
                                </div>
                                <div class="flex justify-between items-center pt-2 border-t border-blue-200">
                                    <span class="text-base font-bold text-blue-800">Downpayment Amount</span>
                                    <span class="text-base font-mono font-bold text-blue-900">{{ formatCurrency(priceList.downpayment_amount) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
