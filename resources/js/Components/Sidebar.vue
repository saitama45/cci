<script setup>
import { computed, ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    HomeIcon,
    Bars3Icon,
    XMarkIcon,
    UserGroupIcon,
    ShieldCheckIcon,
    BuildingOfficeIcon,
    BuildingLibraryIcon,
    ClipboardDocumentListIcon,
    ClipboardDocumentCheckIcon,
    CurrencyDollarIcon,
    MapIcon,
    UserIcon,
    ClockIcon,
    ChartPieIcon,
    ScaleIcon,
    ChevronDownIcon,
    ChevronRightIcon,
    BanknotesIcon,
    DocumentChartBarIcon,
    TableCellsIcon,
    Cog6ToothIcon
} from '@heroicons/vue/24/outline';
import { usePermission } from '@/Composables/usePermission';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    isCollapsed: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['toggle']);

const page = usePage();
const user = computed(() => page.props.auth?.user || {});
const { hasPermission, hasAnyPermission } = usePermission();

// Collapsible State Management
const openMenus = ref({
    sales: false,
    inventory: false,
    procurement: false,
    treasury: false,
    accounting: false,
    reports: false,
    admin: false
});

const toggleMenu = (menu) => {
    if (props.isCollapsed) {
        emit('toggle');
        // Delay opening the menu slightly to allow sidebar to expand first
        setTimeout(() => {
            openMenus.value[menu] = !openMenus.value[menu];
        }, 100);
    } else {
        openMenus.value[menu] = !openMenus.value[menu];
    }
};

// Auto-expand menu based on current route
onMounted(() => {
    if (route().current('customers.*') || route().current('brokers.*') || route().current('reservations.*') || route().current('contracted-sales.*')) openMenus.value.sales = true;
    if (route().current('projects.*') || route().current('units.*') || route().current('price-lists.*')) openMenus.value.inventory = true;
    if (route().current('vendors.*') || route().current('accounting.purchase-orders.*') || route().current('accounting.bills.*') || route().current('accounting.disbursements.*')) openMenus.value.procurement = true;
    if (route().current('payments.*') || route().current('accounting.reconciliations.*') || route().current('accounting.banks.*')) openMenus.value.treasury = true;
    if (route().current('chart-of-accounts.*') || route().current('journal-entries.*')) openMenus.value.accounting = true;
    if (route().current('accounting.trial-balance') || route().current('accounting.general-ledger') || route().current('accounting.project-pl') || route().current('accounting.ar-aging') || route().current('accounting.ap-aging') || route().current('accounting.overall-receivables')) openMenus.value.reports = true;
    if (route().current('users.*') || route().current('companies.*') || route().current('roles.*') || route().current('document-requirements.*') || route().current('admin.settings.*') || route().current('admin.activity-logs.*')) openMenus.value.admin = true;
});

// Scroll Persistence Logic
const navRef = ref(null);

const handleScroll = (e) => {
    sessionStorage.setItem('sidebar-scroll', e.target.scrollTop);
};

onMounted(() => {
    if (navRef.value) {
        const savedScroll = sessionStorage.getItem('sidebar-scroll');
        if (savedScroll) {
            navRef.value.scrollTop = savedScroll;
        }
    }
});

const toggleSidebar = () => {
    emit('toggle');
};

// Tooltip State
const tooltipLabel = ref('');
const tooltipStyle = ref({ top: '0px', left: '0px' });
const showTooltip = ref(false);

const handleMouseEnter = (event, label) => {
    if (!props.isCollapsed) return;
    
    const rect = event.currentTarget.getBoundingClientRect();
    tooltipLabel.value = label;
    tooltipStyle.value = {
        top: `${rect.top + (rect.height / 2) - 16}px`, // Center vertically relative to item (approx height of tooltip is 32px)
        left: `${rect.right + 12}px` // 12px gap
    };
    showTooltip.value = true;
};

const handleMouseLeave = () => {
    showTooltip.value = false;
};
</script>

<template>
    <div class="flex h-screen sticky top-0">
        <!-- Sidebar -->
        <div
            :class="[
                'bg-slate-900 text-slate-300 transition-all duration-300 ease-in-out flex flex-col border-r border-slate-800',
                isCollapsed ? 'w-20' : 'w-72'
            ]"
        >
            <!-- Sidebar Header -->
            <div class="h-16 flex items-center justify-between px-4 border-b border-slate-800 bg-slate-900 z-20">
                <div v-if="!isCollapsed" class="flex items-center space-x-3 overflow-hidden">
                    <ApplicationLogo class="w-8 h-8 text-blue-500 flex-shrink-0" />
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-white leading-tight">GalgaTech</span>
                        <span class="text-xs text-blue-400 font-medium">Horizon ERP</span>
                    </div>
                </div>
                 <div v-else class="mx-auto">
                    <ApplicationLogo class="w-8 h-8 text-blue-500" />
                </div>

                <button
                    v-if="!isCollapsed"
                    @click="toggleSidebar"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                >
                    <XMarkIcon class="w-5 h-5" />
                </button>
            </div>
             <!-- Collapsed Toggle (when collapsed) -->
             <div v-if="isCollapsed" class="flex justify-center py-4 border-b border-slate-800 z-20 bg-slate-900">
                 <button
                    @click="toggleSidebar"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                >
                    <Bars3Icon class="w-5 h-5" />
                </button>
             </div>


            <!-- Navigation -->
            <nav 
                ref="navRef"
                @scroll="handleScroll"
                class="flex-1 overflow-y-auto py-6 px-3 space-y-1 custom-scrollbar z-10"
            >
                <!-- 1. DASHBOARD -->
                <Link
                    v-if="hasPermission('dashboard.view')"
                    :href="route('dashboard')"
                    :class="[
                        'flex items-center px-3 py-2.5 rounded-lg transition-all duration-200 group relative mb-4',
                        route().current('dashboard')
                            ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50'
                            : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                    ]"
                    @mouseenter="handleMouseEnter($event, 'Dashboard')"
                    @mouseleave="handleMouseLeave"
                >
                    <HomeIcon
                        :class="[
                            'w-5 h-5 flex-shrink-0 transition-colors',
                            route().current('dashboard') ? 'text-white' : 'text-slate-500 group-hover:text-white',
                            isCollapsed ? 'mx-auto' : 'mr-3'
                        ]"
                    />
                    <span v-if="!isCollapsed" class="font-bold text-sm">Dashboard</span>
                </Link>

                <!-- 2. SALES & CRM -->
                <div v-if="hasAnyPermission(['customers.view', 'reservations.view', 'brokers.view', 'contracted_sales.view'])" class="space-y-1">
                    <button 
                        @click="toggleMenu('sales')"
                        :class="[
                            'w-full flex items-center justify-between px-3 py-2 rounded-lg transition-all duration-200 group',
                            openMenus.sales ? 'bg-slate-800/50 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        ]"
                    >
                        <div class="flex items-center">
                            <UserGroupIcon :class="['w-5 h-5 flex-shrink-0 mr-3', isCollapsed ? 'mx-auto' : '', openMenus.sales ? 'text-blue-400' : 'text-slate-500 group-hover:text-white']" />
                            <span v-if="!isCollapsed" class="text-sm font-bold">Sales & CRM</span>
                        </div>
                        <ChevronDownIcon v-if="!isCollapsed" :class="['w-3.5 h-3.5 transition-transform duration-200', openMenus.sales ? 'rotate-180' : '']" />
                    </button>
                    
                    <div v-if="openMenus.sales && !isCollapsed" class="pl-10 space-y-1 mt-1 border-l border-slate-800 ml-5 animate-in slide-in-from-top-2 duration-200">
                        <Link v-if="hasPermission('customers.view')" :href="route('customers.index')" :class="[route().current('customers.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Customers</Link>
                        <Link v-if="hasPermission('brokers.view')" :href="route('brokers.index')" :class="[route().current('brokers.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Brokers & Agents</Link>
                        <Link v-if="hasPermission('reservations.view')" :href="route('reservations.index')" :class="[route().current('reservations.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Reservations</Link>
                        <Link v-if="hasPermission('contracted_sales.view')" :href="route('contracted-sales.index')" :class="[route().current('contracted-sales.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Contracted Sales</Link>
                    </div>
                </div>

                <!-- 3. PROJECT & INVENTORY -->
                <div v-if="hasAnyPermission(['projects.view', 'units.view', 'price_lists.view'])" class="space-y-1">
                    <button 
                        @click="toggleMenu('inventory')"
                        :class="[
                            'w-full flex items-center justify-between px-3 py-2 rounded-lg transition-all duration-200 group',
                            openMenus.inventory ? 'bg-slate-800/50 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        ]"
                    >
                        <div class="flex items-center">
                            <BuildingOfficeIcon :class="['w-5 h-5 flex-shrink-0 mr-3', isCollapsed ? 'mx-auto' : '', openMenus.inventory ? 'text-blue-400' : 'text-slate-500 group-hover:text-white']" />
                            <span v-if="!isCollapsed" class="text-sm font-bold">Project & Inventory</span>
                        </div>
                        <ChevronDownIcon v-if="!isCollapsed" :class="['w-3.5 h-3.5 transition-transform duration-200', openMenus.inventory ? 'rotate-180' : '']" />
                    </button>
                    
                    <div v-if="openMenus.inventory && !isCollapsed" class="pl-10 space-y-1 mt-1 border-l border-slate-800 ml-5 animate-in slide-in-from-top-2 duration-200">
                        <Link v-if="hasPermission('projects.view')" :href="route('projects.index')" :class="[route().current('projects.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Projects</Link>
                        <Link v-if="hasPermission('units.view')" :href="route('units.index')" :class="[route().current('units.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Units / Lots</Link>
                        <Link v-if="hasPermission('price_lists.view')" :href="route('price-lists.index')" :class="[route().current('price-lists.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Price Lists</Link>
                    </div>
                </div>

                <!-- 4. PROCUREMENT (AP) -->
                <div v-if="hasAnyPermission(['vendors.view', 'purchase_orders.view', 'bills.view', 'disbursements.view'])" class="space-y-1">
                    <button 
                        @click="toggleMenu('procurement')"
                        :class="[
                            'w-full flex items-center justify-between px-3 py-2 rounded-lg transition-all duration-200 group',
                            openMenus.procurement ? 'bg-slate-800/50 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        ]"
                    >
                        <div class="flex items-center">
                            <BanknotesIcon :class="['w-5 h-5 flex-shrink-0 mr-3', isCollapsed ? 'mx-auto' : '', openMenus.procurement ? 'text-blue-400' : 'text-slate-500 group-hover:text-white']" />
                            <span v-if="!isCollapsed" class="text-sm font-bold">Procurement (AP)</span>
                        </div>
                        <ChevronDownIcon v-if="!isCollapsed" :class="['w-3.5 h-3.5 transition-transform duration-200', openMenus.procurement ? 'rotate-180' : '']" />
                    </button>
                    
                    <div v-if="openMenus.procurement && !isCollapsed" class="pl-10 space-y-1 mt-1 border-l border-slate-800 ml-5 animate-in slide-in-from-top-2 duration-200">
                        <Link v-if="hasPermission('vendors.view')" :href="route('vendors.index')" :class="[route().current('vendors.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Vendors</Link>
                        <Link v-if="hasPermission('purchase_orders.view')" :href="route('accounting.purchase-orders.index')" :class="[route().current('accounting.purchase-orders.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Purchase Orders</Link>
                        <Link v-if="hasPermission('bills.view')" :href="route('accounting.bills.index')" :class="[route().current('accounting.bills.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Bills / AP</Link>
                        <Link v-if="hasPermission('disbursements.view')" :href="route('accounting.disbursements.index')" :class="[route().current('accounting.disbursements.index') || route().current('accounting.disbursements.show') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Disbursements / PV</Link>
                        <Link v-if="hasPermission('disbursements.vault')" :href="route('accounting.disbursements.vault')" :class="[route().current('accounting.disbursements.vault') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">PDC Vault</Link>
                    </div>
                </div>

                <!-- 5. TREASURY & CASH -->
                <div v-if="hasAnyPermission(['payments.view', 'reconciliations.view', 'banks.view'])" class="space-y-1">
                    <button 
                        @click="toggleMenu('treasury')"
                        :class="[
                            'w-full flex items-center justify-between px-3 py-2 rounded-lg transition-all duration-200 group',
                            openMenus.treasury ? 'bg-slate-800/50 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        ]"
                    >
                        <div class="flex items-center">
                            <BuildingLibraryIcon :class="['w-5 h-5 flex-shrink-0 mr-3', isCollapsed ? 'mx-auto' : '', openMenus.treasury ? 'text-blue-400' : 'text-slate-500 group-hover:text-white']" />
                            <span v-if="!isCollapsed" class="text-sm font-bold">Treasury & Cash</span>
                        </div>
                        <ChevronDownIcon v-if="!isCollapsed" :class="['w-3.5 h-3.5 transition-transform duration-200', openMenus.treasury ? 'rotate-180' : '']" />
                    </button>
                    
                    <div v-if="openMenus.treasury && !isCollapsed" class="pl-10 space-y-1 mt-1 border-l border-slate-800 ml-5 animate-in slide-in-from-top-2 duration-200">
                        <Link v-if="hasPermission('payments.view')" :href="route('payments.index')" :class="[route().current('payments.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Collections</Link>
                        <Link v-if="hasPermission('reconciliations.view')" :href="route('accounting.reconciliations.index')" :class="[route().current('accounting.reconciliations.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Bank Recon</Link>
                        <Link v-if="hasPermission('banks.view')" :href="route('accounting.banks.index')" :class="[route().current('accounting.banks.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Bank Management</Link>
                    </div>
                </div>

                <!-- 6. ACCOUNTING (GL) -->
                <div v-if="hasAnyPermission(['journal_entries.view', 'chart_of_accounts.view'])" class="space-y-1">
                    <button 
                        @click="toggleMenu('accounting')"
                        :class="[
                            'w-full flex items-center justify-between px-3 py-2 rounded-lg transition-all duration-200 group',
                            openMenus.accounting ? 'bg-slate-800/50 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        ]"
                    >
                        <div class="flex items-center">
                            <ShieldCheckIcon :class="['w-5 h-5 flex-shrink-0 mr-3', isCollapsed ? 'mx-auto' : '', openMenus.accounting ? 'text-blue-400' : 'text-slate-500 group-hover:text-white']" />
                            <span v-if="!isCollapsed" class="text-sm font-bold">Accounting (GL)</span>
                        </div>
                        <ChevronDownIcon v-if="!isCollapsed" :class="['w-3.5 h-3.5 transition-transform duration-200', openMenus.accounting ? 'rotate-180' : '']" />
                    </button>
                    
                    <div v-if="openMenus.accounting && !isCollapsed" class="pl-10 space-y-1 mt-1 border-l border-slate-800 ml-5 animate-in slide-in-from-top-2 duration-200">
                        <Link v-if="hasPermission('chart_of_accounts.view')" :href="route('chart-of-accounts.index')" :class="[route().current('chart-of-accounts.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Chart of Accounts</Link>
                        <Link v-if="hasPermission('journal_entries.view')" :href="route('journal-entries.index')" :class="[route().current('journal-entries.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Journal Entries</Link>
                    </div>
                </div>

                <!-- 7. FINANCIAL REPORTS -->
                <div v-if="hasPermission('accounting.view')" class="space-y-1">
                    <button 
                        @click="toggleMenu('reports')"
                        :class="[
                            'w-full flex items-center justify-between px-3 py-2 rounded-lg transition-all duration-200 group',
                            openMenus.reports ? 'bg-slate-800/50 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        ]"
                    >
                        <div class="flex items-center">
                            <ChartPieIcon :class="['w-5 h-5 flex-shrink-0 mr-3', isCollapsed ? 'mx-auto' : '', openMenus.reports ? 'text-blue-400' : 'text-slate-500 group-hover:text-white']" />
                            <span v-if="!isCollapsed" class="text-sm font-bold">Financial Reports</span>
                        </div>
                        <ChevronDownIcon v-if="!isCollapsed" :class="['w-3.5 h-3.5 transition-transform duration-200', openMenus.reports ? 'rotate-180' : '']" />
                    </button>
                    
                    <div v-if="openMenus.reports && !isCollapsed" class="pl-10 space-y-1 mt-1 border-l border-slate-800 ml-5 animate-in slide-in-from-top-2 duration-200">
                        <Link :href="route('accounting.trial-balance')" :class="[route().current('accounting.trial-balance') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Trial Balance</Link>
                        <Link :href="route('accounting.general-ledger')" :class="[route().current('accounting.general-ledger') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">General Ledger</Link>
                        <Link :href="route('accounting.project-pl')" :class="[route().current('accounting.project-pl') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Project P&L</Link>
                        <Link :href="route('accounting.ar-aging')" :class="[route().current('accounting.ar-aging') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">AR Aging</Link>
                        <Link :href="route('accounting.ap-aging')" :class="[route().current('accounting.ap-aging') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">AP Aging</Link>
                        <Link :href="route('accounting.overall-receivables')" :class="[route().current('accounting.overall-receivables') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Overall Receivables</Link>
                    </div>
                </div>

                <!-- 8. ADMINISTRATION -->
                <div v-if="hasAnyPermission(['users.view', 'companies.view', 'roles.view'])" class="space-y-1">
                    <button 
                        @click="toggleMenu('admin')"
                        :class="[
                            'w-full flex items-center justify-between px-3 py-2 rounded-lg transition-all duration-200 group',
                            openMenus.admin ? 'bg-slate-800/50 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        ]"
                    >
                        <div class="flex items-center">
                            <Cog6ToothIcon :class="['w-5 h-5 flex-shrink-0 mr-3', isCollapsed ? 'mx-auto' : '', openMenus.admin ? 'text-blue-400' : 'text-slate-500 group-hover:text-white']" />
                            <span v-if="!isCollapsed" class="text-sm font-bold">Administration</span>
                        </div>
                        <ChevronDownIcon v-if="!isCollapsed" :class="['w-3.5 h-3.5 transition-transform duration-200', openMenus.admin ? 'rotate-180' : '']" />
                    </button>
                    
                    <div v-if="openMenus.admin && !isCollapsed" class="pl-10 space-y-1 mt-1 border-l border-slate-800 ml-5 animate-in slide-in-from-top-2 duration-200">
                        <Link v-if="hasPermission('users.view')" :href="route('users.index')" :class="[route().current('users.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Users</Link>
                        <Link v-if="hasPermission('companies.view')" :href="route('companies.index')" :class="[route().current('companies.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Companies</Link>
                        <Link v-if="hasPermission('roles.view')" :href="route('roles.index')" :class="[route().current('roles.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Roles & Permissions</Link>
                        <Link v-if="hasPermission('document_requirements.view')" :href="route('document-requirements.index')" :class="[route().current('document-requirements.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Document Checklist</Link>
                        <Link v-if="hasPermission('roles.view')" :href="route('admin.settings.index')" :class="[route().current('admin.settings.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">System Settings</Link>
                        <Link v-if="hasPermission('roles.view')" :href="route('admin.activity-logs.index')" :class="[route().current('admin.activity-logs.*') ? 'text-blue-400 font-bold' : 'text-slate-500 hover:text-white', 'block py-1.5 text-xs transition-colors']">Audit Trail</Link>
                    </div>
                </div>

            </nav>

            <!-- User Section -->
            <div class="p-4 border-t border-slate-800 bg-slate-900 z-20">
                <div class="flex items-center group relative">
                    <div 
                        class="relative"
                         @mouseenter="handleMouseEnter($event, user.name)"
                         @mouseleave="handleMouseLeave"
                    >
                        <div v-if="user.profile_photo" class="w-9 h-9 rounded-full overflow-hidden border-2 border-slate-700 ring-2 ring-slate-900">
                            <img :src="'/storage/' + user.profile_photo" class="h-full w-full object-cover" :alt="user.name">
                        </div>
                        <div v-else class="w-9 h-9 bg-slate-700 rounded-full flex items-center justify-center border-2 border-slate-600 ring-2 ring-slate-900">
                            <span class="text-xs font-bold text-slate-300">
                                {{ user.name?.charAt(0)?.toUpperCase() || 'U' }}
                            </span>
                        </div>
                        <div class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border-2 border-slate-900 rounded-full"></div>
                    </div>
                    
                    <div v-if="!isCollapsed" class="ml-3 flex-1 overflow-hidden">
                        <p class="text-sm font-semibold text-white truncate">{{ user.name || 'User' }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ user.email || 'user@example.com' }}</p>
                    </div>

                    
                    <Link 
                        v-if="!isCollapsed"
                        :href="route('logout')" 
                        method="post" 
                        as="button" 
                        class="ml-2 p-1.5 text-slate-500 hover:text-red-400 hover:bg-slate-800 rounded-lg transition-colors"
                        title="Logout"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Floating Tooltip Portal (Rendered outside overflow containers) -->
        <Teleport to="body">
            <div 
                v-if="showTooltip && isCollapsed" 
                class="fixed z-[100] px-3 py-2 bg-slate-800 text-white text-xs font-bold rounded-lg shadow-2xl border border-slate-700 pointer-events-none animate-in fade-in zoom-in duration-75"
                :style="tooltipStyle"
            >
                {{ tooltipLabel }}
                <div class="absolute top-1/2 -left-1 -translate-y-1/2 w-2 h-2 bg-slate-800 border-l border-b border-slate-700 rotate-45"></div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #475569;
}
</style>
