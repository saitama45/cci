<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    HomeIcon,
    Bars3Icon,
    XMarkIcon,
    UserGroupIcon,
    ShieldCheckIcon,
    BuildingOfficeIcon,
    ClipboardDocumentListIcon,
    ClipboardDocumentCheckIcon,
    CurrencyDollarIcon,
    MapIcon,
    UserIcon
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
            <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-1 custom-scrollbar z-10">
                <!-- Dashboard -->
                <Link
                    v-if="hasPermission('dashboard.view')"
                    :href="route('dashboard')"
                    :class="[
                        'flex items-center px-3 py-2.5 rounded-lg transition-all duration-200 group relative mb-6',
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
                    <span v-if="!isCollapsed" class="font-medium text-sm">Dashboard</span>
                </Link>

                <!-- Module: Admin & Security -->
                <template v-if="hasAnyPermission(['users.view', 'companies.view', 'roles.view', 'document_requirements.view'])">
                    <div v-if="!isCollapsed" class="px-3 mb-2 mt-6">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Administration</p>
                    </div>
                    <div v-else class="my-4 border-t border-slate-800"></div>


                    <!-- Users -->
                    <Link
                        v-if="hasPermission('users.view')"
                        :href="route('users.index')"
                        :class="[
                            'flex items-center px-3 py-2.5 rounded-lg transition-all duration-200 group relative',
                            route().current('users.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        ]"
                        @mouseenter="handleMouseEnter($event, 'Users')"
                        @mouseleave="handleMouseLeave"
                    >
                        <UserGroupIcon
                            :class="[
                                'w-5 h-5 flex-shrink-0 transition-colors',
                                 route().current('users.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-white',
                                isCollapsed ? 'mx-auto' : 'mr-3'
                            ]"
                        />
                        <span v-if="!isCollapsed" class="font-medium text-sm">Users</span>
                    </Link>

                    <!-- Companies -->
                    <Link
                        v-if="hasPermission('companies.view')"
                        :href="route('companies.index')"
                        :class="[
                            'flex items-center px-3 py-2.5 rounded-lg transition-all duration-200 group relative',
                            route().current('companies.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        ]"
                        @mouseenter="handleMouseEnter($event, 'Companies')"
                        @mouseleave="handleMouseLeave"
                    >
                        <BuildingOfficeIcon
                            :class="[
                                'w-5 h-5 flex-shrink-0 transition-colors',
                                 route().current('companies.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-white',
                                isCollapsed ? 'mx-auto' : 'mr-3'
                            ]"
                        />
                        <span v-if="!isCollapsed" class="font-medium text-sm">Companies</span>
                    </Link>

                    <!-- Roles -->
                    <Link
                        v-if="hasPermission('roles.view')"
                        :href="route('roles.index')"
                        :class="[
                            'flex items-center px-3 py-2.5 rounded-lg transition-all duration-200 group relative',
                             route().current('roles.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        ]"
                        @mouseenter="handleMouseEnter($event, 'Roles & Permissions')"
                        @mouseleave="handleMouseLeave"
                    >
                        <ShieldCheckIcon
                            :class="[
                                'w-5 h-5 flex-shrink-0 transition-colors',
                                 route().current('roles.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-white',
                                isCollapsed ? 'mx-auto' : 'mr-3'
                            ]"
                        />
                        <span v-if="!isCollapsed" class="font-medium text-sm">Roles & Permissions</span>
                    </Link>

                    <!-- Document Checklist Config -->
                    <Link
                        v-if="hasPermission('document_requirements.view')"
                        :href="route('document-requirements.index')"
                        :class="[
                            'flex items-center px-3 py-2.5 rounded-lg transition-all duration-200 group relative',
                             route().current('document-requirements.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        ]"
                        @mouseenter="handleMouseEnter($event, 'Document Checklist')"
                        @mouseleave="handleMouseLeave"
                    >
                        <ClipboardDocumentCheckIcon
                            :class="[
                                'w-5 h-5 flex-shrink-0 transition-colors',
                                 route().current('document-requirements.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-white',
                                isCollapsed ? 'mx-auto' : 'mr-3'
                            ]"
                        />
                        <span v-if="!isCollapsed" class="font-medium text-sm">Document Checklist</span>
                    </Link>
                </template>

                 <!-- Project & Inventory -->
                 <template v-if="hasAnyPermission(['projects.view', 'units.view', 'price_lists.view'])">
                    <div v-if="!isCollapsed" class="px-3 mb-2 mt-6">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Project & Inventory</p>
                    </div>
                    <div v-else class="my-4 border-t border-slate-800"></div>

                    <Link
                        v-if="hasPermission('projects.view')"
                        :href="route('projects.index')"
                        :class="[
                            'flex items-center px-3 py-2.5 rounded-lg transition-all duration-200 group relative',
                            route().current('projects.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        ]"
                        @mouseenter="handleMouseEnter($event, 'Projects')"
                        @mouseleave="handleMouseLeave"
                    >
                        <BuildingOfficeIcon
                            :class="[
                                'w-5 h-5 flex-shrink-0 transition-colors',
                                route().current('projects.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-white',
                                isCollapsed ? 'mx-auto' : 'mr-3'
                            ]"
                        />
                        <span v-if="!isCollapsed" class="font-medium text-sm">Projects</span>
                    </Link>

                    <!-- Units -->
                    <Link
                        v-if="hasPermission('units.view')"
                        :href="route('units.index')"
                        :class="[
                            'flex items-center px-3 py-2.5 rounded-lg transition-all duration-200 group relative',
                            route().current('units.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        ]"
                        @mouseenter="handleMouseEnter($event, 'Units / Lots')"
                        @mouseleave="handleMouseLeave"
                    >
                        <MapIcon
                            :class="[
                                'w-5 h-5 flex-shrink-0 transition-colors',
                                route().current('units.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-white',
                                isCollapsed ? 'mx-auto' : 'mr-3'
                            ]"
                        />
                        <span v-if="!isCollapsed" class="font-medium text-sm">Units / Lots</span>
                    </Link>

                    <!-- Price Lists -->
                    <Link
                        v-if="hasPermission('price_lists.view')"
                        :href="route('price-lists.index')"
                        :class="[
                            'flex items-center px-3 py-2.5 rounded-lg transition-all duration-200 group relative',
                            route().current('price-lists.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        ]"
                        @mouseenter="handleMouseEnter($event, 'Price Lists')"
                        @mouseleave="handleMouseLeave"
                    >
                        <CurrencyDollarIcon
                            :class="[
                                'w-5 h-5 flex-shrink-0 transition-colors',
                                route().current('price-lists.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-white',
                                isCollapsed ? 'mx-auto' : 'mr-3'
                            ]"
                        />
                        <span v-if="!isCollapsed" class="font-medium text-sm">Price Lists</span>
                    </Link>
                 </template>

                 <!-- Sales -->
                 <template v-if="hasAnyPermission(['customers.view', 'reservations.view'])">
                    <div v-if="!isCollapsed" class="px-3 mb-2 mt-6">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Sales</p>
                    </div>
                    <div v-else class="my-4 border-t border-slate-800"></div>

                    <!-- Customers -->
                    <Link
                        v-if="hasPermission('customers.view')"
                        :href="route('customers.index')"
                        :class="[
                            'flex items-center px-3 py-2.5 rounded-lg transition-all duration-200 group relative',
                            route().current('customers.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        ]"
                        @mouseenter="handleMouseEnter($event, 'Customers')"
                        @mouseleave="handleMouseLeave"
                    >
                        <UserIcon
                            :class="[
                                'w-5 h-5 flex-shrink-0 transition-colors',
                                route().current('customers.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-white',
                                isCollapsed ? 'mx-auto' : 'mr-3'
                            ]"
                        />
                        <span v-if="!isCollapsed" class="font-medium text-sm">Customers</span>
                    </Link>
                    
                    <!-- Reservations -->
                    <Link
                        v-if="hasPermission('reservations.view')"
                        :href="route('reservations.index')"
                        :class="[
                            'flex items-center px-3 py-2.5 rounded-lg transition-all duration-200 group relative',
                            route().current('reservations.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        ]"
                        @mouseenter="handleMouseEnter($event, 'Reservations')"
                        @mouseleave="handleMouseLeave"
                    >
                        <ClipboardDocumentListIcon
                            :class="[
                                'w-5 h-5 flex-shrink-0 transition-colors',
                                route().current('reservations.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-white',
                                isCollapsed ? 'mx-auto' : 'mr-3'
                            ]"
                        />
                        <span v-if="!isCollapsed" class="font-medium text-sm">Reservations</span>
                    </Link>
                 </template>

                 <!-- Finance -->
                 <template v-if="hasPermission('collections.view')">
                    <div v-if="!isCollapsed" class="px-3 mb-2 mt-6">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Finance</p>
                    </div>
                    <div v-else class="my-4 border-t border-slate-800"></div>

                    <button 
                        class="w-full flex items-center px-3 py-2.5 rounded-lg text-slate-500 hover:bg-slate-800 hover:text-white transition-all duration-200 group relative cursor-not-allowed opacity-60"
                        @mouseenter="handleMouseEnter($event, 'Collections (Coming Soon)')"
                        @mouseleave="handleMouseLeave"
                    >
                        <CurrencyDollarIcon :class="['w-5 h-5 flex-shrink-0', isCollapsed ? 'mx-auto' : 'mr-3']" />
                        <span v-if="!isCollapsed" class="font-medium text-sm">Collections</span>
                    </button>
                 </template>

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
