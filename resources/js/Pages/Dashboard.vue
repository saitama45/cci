<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    BuildingOfficeIcon, 
    UserGroupIcon, 
    CurrencyDollarIcon, 
    ClipboardDocumentCheckIcon,
    ClipboardDocumentListIcon,
    ShoppingCartIcon,
    DocumentTextIcon,
    BanknotesIcon,
    ScaleIcon,
    ClockIcon,
    MapIcon,
    TagIcon,
    IdentificationIcon,
    PlusIcon,
    ChevronRightIcon,
    ShieldCheckIcon,
    ChartPieIcon,
    TableCellsIcon
} from '@heroicons/vue/24/outline';

const page = usePage();
const user = computed(() => page.props.auth?.user || {});

const currentDate = new Date().toLocaleDateString('en-US', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
});

const workflowIconClass = "w-10 h-10 transition-transform duration-300 group-hover:scale-110";
</script>

<template>
    <Head title="Dashboard - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-black text-2xl text-slate-900 leading-tight tracking-tight">Enterprise Dashboard</h2>
                    <p class="text-sm text-slate-500 mt-1">Operational Workflow & System Overview</p>
                </div>
                <div class="mt-4 md:mt-0 text-xs font-black uppercase tracking-widest text-slate-400 bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-100">
                    {{ currentDate }}
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-12">
                
                <!-- Welcome & Stats Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 bg-gradient-to-br from-slate-900 to-indigo-950 rounded-[2.5rem] p-10 text-white shadow-2xl relative overflow-hidden flex items-center">
                                            <div class="relative z-10">
                                                <h3 class="text-3xl font-black mb-2">Welcome, {{ user.name }}</h3>
                                                <p class="text-indigo-200 max-w-xl text-lg opacity-80 leading-relaxed">
                                                    Navigate using the visual workflows below to maintain data integrity and project oversight.
                                                </p>
                                            </div>                        <div class="absolute right-0 bottom-0 opacity-10">
                            <BuildingOfficeIcon class="w-64 h-64 -mr-16 -mb-16" />
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm flex flex-col justify-between">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Quick Actions</p>
                            <div class="grid grid-cols-2 gap-3">
                                <Link :href="route('accounting.purchase-orders.create')" class="flex flex-col items-center justify-center p-4 bg-slate-50 rounded-2xl hover:bg-indigo-50 transition-all border border-transparent hover:border-indigo-100 group">
                                    <PlusIcon class="w-5 h-5 text-indigo-600 mb-2" />
                                    <span class="text-[10px] font-black uppercase tracking-tighter text-slate-600 group-hover:text-indigo-700">New PO</span>
                                </Link>
                                <Link :href="route('accounting.bills.create')" class="flex flex-col items-center justify-center p-4 bg-slate-50 rounded-2xl hover:bg-emerald-50 transition-all border border-transparent hover:border-emerald-100 group">
                                    <PlusIcon class="w-5 h-5 text-emerald-600 mb-2" />
                                    <span class="text-[10px] font-black uppercase tracking-tighter text-slate-600 group-hover:text-emerald-700">New Bill</span>
                                </Link>
                            </div>
                        </div>
                        <div class="mt-6 pt-6 border-t border-slate-50">
                            <div class="flex items-center justify-between text-xs font-bold">
                                <span class="text-slate-400 uppercase tracking-widest">System Status</span>
                                <span class="flex items-center text-emerald-500">
                                    <div class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></div>
                                    Operational
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- WORKFLOW 1: SETUP & ASSETS -->
                <section class="space-y-6">
                    <div class="flex items-center space-x-4">
                        <div class="h-px flex-1 bg-slate-200"></div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">Phase 1: Setup & Inventory</h3>
                        <div class="h-px flex-1 bg-slate-200"></div>
                    </div>
                    
                    <div class="flex flex-wrap items-center justify-center gap-8">
                        <Link :href="route('projects.index')" class="workflow-node group">
                            <div class="w-24 h-24 bg-white rounded-[2rem] shadow-xl border border-slate-100 flex items-center justify-center group-hover:border-indigo-500 transition-all">
                                <BuildingOfficeIcon :class="[workflowIconClass, 'text-indigo-600']" />
                            </div>
                            <span class="workflow-label">Projects</span>
                        </Link>
                        
                        <ChevronRightIcon class="w-6 h-6 text-slate-200" />

                        <Link :href="route('units.index')" class="workflow-node group">
                            <div class="w-24 h-24 bg-white rounded-[2rem] shadow-xl border border-slate-100 flex items-center justify-center group-hover:border-indigo-500 transition-all">
                                <MapIcon :class="[workflowIconClass, 'text-indigo-600']" />
                            </div>
                            <span class="workflow-label">Units & Lots</span>
                        </Link>

                        <ChevronRightIcon class="w-6 h-6 text-slate-200" />

                        <Link :href="route('price-lists.index')" class="workflow-node group">
                            <div class="w-24 h-24 bg-white rounded-[2rem] shadow-xl border border-slate-100 flex items-center justify-center group-hover:border-indigo-500 transition-all">
                                <TagIcon :class="[workflowIconClass, 'text-indigo-600']" />
                            </div>
                            <span class="workflow-label">Price Lists</span>
                        </Link>
                    </div>
                </section>

                <!-- WORKFLOW 2: REVENUE FLOW (AR) -->
                <section class="space-y-6">
                    <div class="flex items-center space-x-4">
                        <div class="h-px flex-1 bg-slate-200"></div>
                        <h3 class="text-[10px] font-black text-emerald-500 uppercase tracking-[0.3em]">Phase 2: Revenue Flow (AR)</h3>
                        <div class="h-px flex-1 bg-slate-200"></div>
                    </div>
                    
                    <div class="flex flex-wrap items-center justify-center gap-8">
                        <Link :href="route('customers.index')" class="workflow-node group">
                            <div class="w-24 h-24 bg-emerald-50 rounded-[2rem] shadow-xl border border-emerald-100 flex items-center justify-center group-hover:border-emerald-500 transition-all">
                                <IdentificationIcon :class="[workflowIconClass, 'text-emerald-600']" />
                            </div>
                            <span class="workflow-label">Customers</span>
                        </Link>
                        
                        <ChevronRightIcon class="w-6 h-6 text-emerald-200" />

                        <Link :href="route('reservations.index')" class="workflow-node group">
                            <div class="w-24 h-24 bg-emerald-50 rounded-[2rem] shadow-xl border border-emerald-100 flex items-center justify-center group-hover:border-emerald-500 transition-all">
                                <ClipboardDocumentListIcon :class="[workflowIconClass, 'text-emerald-600']" />
                            </div>
                            <span class="workflow-label">Reservations</span>
                        </Link>

                        <ChevronRightIcon class="w-6 h-6 text-emerald-200" />

                        <Link :href="route('contracted-sales.index')" class="workflow-node group">
                            <div class="w-24 h-24 bg-emerald-50 rounded-[2rem] shadow-xl border border-emerald-100 flex items-center justify-center group-hover:border-emerald-500 transition-all">
                                <ClipboardDocumentCheckIcon :class="[workflowIconClass, 'text-emerald-600']" />
                            </div>
                            <span class="workflow-label">Contracts</span>
                        </Link>

                        <ChevronRightIcon class="w-6 h-6 text-emerald-200" />

                        <Link :href="route('payments.index')" class="workflow-node group">
                            <div class="w-24 h-24 bg-emerald-600 rounded-[2rem] shadow-2xl shadow-emerald-200 flex items-center justify-center group-hover:bg-emerald-700 transition-all scale-110">
                                <BanknotesIcon :class="[workflowIconClass, 'text-white']" />
                            </div>
                            <span class="workflow-label font-black text-emerald-700">Collections</span>
                        </Link>
                    </div>
                </section>

                <!-- WORKFLOW 3: EXPENDITURE FLOW (AP) -->
                <section class="space-y-6">
                    <div class="flex items-center space-x-4">
                        <div class="h-px flex-1 bg-slate-200"></div>
                        <h3 class="text-[10px] font-black text-rose-500 uppercase tracking-[0.3em]">Phase 3: Expenditure Flow (AP)</h3>
                        <div class="h-px flex-1 bg-slate-200"></div>
                    </div>
                    
                    <div class="flex flex-wrap items-center justify-center gap-8">
                        <Link :href="route('vendors.index')" class="workflow-node group">
                            <div class="w-24 h-24 bg-rose-50 rounded-[2rem] shadow-xl border border-rose-100 flex items-center justify-center group-hover:border-rose-500 transition-all">
                                <UserGroupIcon :class="[workflowIconClass, 'text-rose-600']" />
                            </div>
                            <span class="workflow-label">Vendors</span>
                        </Link>
                        
                        <ChevronRightIcon class="w-6 h-6 text-rose-200" />

                        <Link :href="route('accounting.purchase-orders.index')" class="workflow-node group">
                            <div class="w-24 h-24 bg-rose-50 rounded-[2rem] shadow-xl border border-rose-100 flex items-center justify-center group-hover:border-rose-500 transition-all">
                                <ShoppingCartIcon :class="[workflowIconClass, 'text-rose-600']" />
                            </div>
                            <span class="workflow-label">Purchase Orders</span>
                        </Link>

                        <ChevronRightIcon class="w-6 h-6 text-rose-200" />

                        <Link :href="route('accounting.bills.index')" class="workflow-node group">
                            <div class="w-24 h-24 bg-rose-50 rounded-[2rem] shadow-xl border border-rose-100 flex items-center justify-center group-hover:border-rose-500 transition-all">
                                <DocumentTextIcon :class="[workflowIconClass, 'text-rose-600']" />
                            </div>
                            <span class="workflow-label">Vendor Bills</span>
                        </Link>

                        <ChevronRightIcon class="w-6 h-6 text-rose-200" />

                        <Link :href="route('accounting.disbursements.index')" class="workflow-node group">
                            <div class="w-24 h-24 bg-rose-600 rounded-[2rem] shadow-2xl shadow-rose-200 flex items-center justify-center group-hover:bg-rose-700 transition-all scale-110">
                                <CurrencyDollarIcon :class="[workflowIconClass, 'text-white']" />
                            </div>
                            <span class="workflow-label font-black text-rose-700">Disbursements</span>
                        </Link>
                    </div>
                </section>

                <!-- WORKFLOW 4: TREASURY & ACCOUNTING -->
                <section class="grid grid-cols-1 md:grid-cols-2 gap-12">
                    <div class="space-y-6">
                        <div class="flex items-center space-x-4">
                            <div class="h-px flex-1 bg-slate-200"></div>
                            <h3 class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.3em]">Treasury</h3>
                            <div class="h-px flex-1 bg-slate-200"></div>
                        </div>
                        <div class="flex items-center justify-center space-x-10">
                            <Link :href="route('accounting.disbursements.vault')" class="workflow-node group">
                                <div class="w-20 h-20 bg-amber-50 rounded-2xl shadow-xl border border-amber-100 flex items-center justify-center group-hover:border-amber-500 transition-all">
                                    <ClockIcon class="w-8 h-8 text-amber-600" />
                                </div>
                                <span class="workflow-label">PDC Vault</span>
                            </Link>
                            <Link :href="route('accounting.reconciliations.index')" class="workflow-node group">
                                <div class="w-20 h-20 bg-indigo-50 rounded-2xl shadow-xl border border-indigo-100 flex items-center justify-center group-hover:border-indigo-500 transition-all">
                                    <ScaleIcon class="w-8 h-8 text-indigo-600" />
                                </div>
                                <span class="workflow-label">Bank Recon</span>
                            </Link>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-center space-x-4">
                            <div class="h-px flex-1 bg-slate-200"></div>
                            <h3 class="text-[10px] font-black text-slate-900 uppercase tracking-[0.3em]">Final Ledger</h3>
                            <div class="h-px flex-1 bg-slate-200"></div>
                        </div>
                        <div class="flex items-center justify-center space-x-10">
                            <Link :href="route('chart-of-accounts.index')" class="workflow-node group">
                                <div class="w-20 h-20 bg-slate-900 rounded-2xl shadow-xl flex items-center justify-center group-hover:bg-slate-800 transition-all">
                                    <TableCellsIcon class="w-8 h-8 text-slate-400" />
                                </div>
                                <span class="workflow-label">Chart of Accounts</span>
                            </Link>
                            <Link :href="route('journal-entries.index')" class="workflow-node group">
                                <div class="w-20 h-20 bg-slate-900 rounded-2xl shadow-xl flex items-center justify-center group-hover:bg-slate-800 transition-all">
                                    <ShieldCheckIcon class="w-8 h-8 text-slate-400" />
                                </div>
                                <span class="workflow-label">General Ledger</span>
                            </Link>
                            <Link :href="route('accounting.project-pl')" class="workflow-node group">
                                <div class="w-20 h-20 bg-slate-900 rounded-2xl shadow-xl flex items-center justify-center group-hover:bg-slate-800 transition-all">
                                    <ChartPieIcon class="w-8 h-8 text-indigo-400" />
                                </div>
                                <span class="workflow-label italic font-black text-indigo-600">P&L Reports</span>
                            </Link>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.workflow-node {
    @apply flex flex-col items-center space-y-3 transition-all duration-300;
}
.workflow-label {
    @apply text-[10px] font-black text-slate-500 uppercase tracking-tighter text-center max-w-[100px] leading-tight;
}
</style>
