<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { 
    MagnifyingGlassIcon,
    UserIcon,
    UserGroupIcon,
    ClipboardDocumentCheckIcon,
    BuildingOfficeIcon,
    BanknotesIcon,
    ClockIcon,
    XMarkIcon,
    ChevronRightIcon,
    DocumentTextIcon
} from '@heroicons/vue/24/outline';

const isOpen = ref(false);
const query = ref('');
const results = ref([]);
const isLoading = ref(false);
const selectedIndex = ref(0);
const searchInput = ref(null);

const iconMap = {
    UserIcon,
    UserGroupIcon,
    ClipboardDocumentCheckIcon,
    BuildingOfficeIcon,
    BanknotesIcon,
    ClockIcon,
    ChevronRightIcon,
    DocumentTextIcon
};

const toggleSearch = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        query.value = '';
        results.value = [];
        selectedIndex.value = 0;
        setTimeout(() => searchInput.value?.focus(), 100);
    }
};

const handleKeydown = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        toggleSearch();
    }
    if (e.key === '/') {
        // Only trigger if not in an input/textarea
        if (['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) return;
        e.preventDefault();
        toggleSearch();
    }
    if (e.key === 'Escape' && isOpen.value) {
        isOpen.value = false;
    }
};

const navigateResults = (e) => {
    if (!isOpen.value || results.value.length === 0) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedIndex.value = (selectedIndex.value + 1) % results.value.length;
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedIndex.value = (selectedIndex.value - 1 + results.value.length) % results.value.length;
    } else if (e.key === 'Enter') {
        e.preventDefault();
        goToResult(results.value[selectedIndex.value]);
    }
};

const goToResult = (result) => {
    isOpen.value = false;
    router.visit(result.url);
};

let searchTimeout;
const performSearch = () => {
    if (query.value.length < 2) {
        results.value = [];
        return;
    }

    isLoading.value = true;
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(async () => {
        try {
            const response = await axios.get(route('api.global-search'), {
                params: { q: query.value }
            });
            results.value = response.data;
            selectedIndex.value = 0;
        } catch (error) {
            console.error('Search error:', error);
        } finally {
            isLoading.value = false;
        }
    }, 300);
};

watch(query, performSearch);

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
    window.addEventListener('keydown', navigateResults);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    window.removeEventListener('keydown', navigateResults);
});
</script>

<template>
    <div>
        <!-- Trigger Button (Fake Input in Header) -->
        <button 
            @click="toggleSearch"
            class="group flex items-center w-full max-w-sm px-4 py-2 bg-slate-100 border border-slate-200 rounded-xl text-slate-500 hover:bg-white hover:border-blue-300 hover:shadow-md transition-all duration-200"
        >
            <MagnifyingGlassIcon class="w-5 h-5 mr-3 group-hover:text-blue-500 transition-colors" />
            <span class="text-sm font-medium">Global Search...</span>
            <span class="ml-auto flex items-center space-x-1 text-[10px] font-bold text-slate-400 bg-white px-2 py-0.5 rounded-md border border-slate-200">
                <span>Ctrl</span>
                <span>+</span>
                <span>K</span>
            </span>
        </button>

        <!-- Search Modal Overlay -->
        <Teleport to="body">
            <div v-if="isOpen" class="fixed inset-0 z-[100] flex items-start justify-center pt-20 px-4 sm:pt-40">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200" @click="isOpen = false"></div>
                
                <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden animate-in zoom-in slide-in-from-top-4 duration-200">
                    <!-- Search Input Area -->
                    <div class="relative border-b border-slate-100 p-6">
                        <MagnifyingGlassIcon class="absolute left-10 top-1/2 -translate-y-1/2 w-6 h-6 text-slate-400" />
                        <input 
                            ref="searchInput"
                            v-model="query"
                            type="text" 
                            placeholder="What can I find for you today? (e.g. Maria, CNT-002, Amiya...)"
                            class="w-full bg-slate-50 border-none rounded-2xl pl-14 pr-12 py-4 text-lg font-bold text-slate-900 placeholder-slate-400 focus:ring-4 focus:ring-blue-500/10 transition-all"
                        >
                        <button @click="isOpen = false" class="absolute right-10 top-1/2 -translate-y-1/2 p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-all">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Results Area -->
                    <div class="max-h-[500px] overflow-y-auto p-4 custom-scrollbar">
                        <div v-if="isLoading && query.length >= 2" class="py-20 text-center">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
                            <p class="mt-4 text-sm font-bold text-slate-400">Searching records...</p>
                        </div>

                        <div v-else-if="results.length > 0" class="space-y-1">
                            <div 
                                v-for="(result, index) in results" 
                                :key="result.type + result.id"
                                @click="goToResult(result)"
                                @mouseenter="selectedIndex = index"
                                :class="[
                                    'group flex items-center p-4 rounded-2xl transition-all cursor-pointer',
                                    selectedIndex === index ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'hover:bg-slate-50'
                                ]"
                            >
                                <div :class="[
                                    'p-3 rounded-xl mr-4 transition-colors',
                                    selectedIndex === index ? 'bg-white/20' : 'bg-slate-100 text-slate-500 group-hover:bg-blue-50 group-hover:text-blue-600'
                                ]">
                                    <component :is="iconMap[result.icon]" class="w-6 h-6" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 :class="['text-sm font-black truncate', selectedIndex === index ? 'text-white' : 'text-slate-900']">
                                            {{ result.title }}
                                        </h4>
                                        <span :class="[
                                            'text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded-md border',
                                            selectedIndex === index ? 'bg-white/20 border-white/30 text-white' : 'bg-slate-100 border-slate-200 text-slate-500'
                                        ]">
                                            {{ result.type }}
                                        </span>
                                    </div>
                                    <p :class="['text-xs font-medium truncate mt-0.5', selectedIndex === index ? 'text-blue-100' : 'text-slate-500']">
                                        {{ result.subtitle }}
                                    </p>
                                </div>
                                <div v-if="selectedIndex === index" class="ml-4 animate-in slide-in-from-left-2 duration-200">
                                    <ChevronRightIcon class="w-5 h-5 text-white/50" />
                                </div>
                            </div>
                        </div>

                        <div v-else-if="query.length >= 2" class="py-20 text-center">
                            <MagnifyingGlassIcon class="w-12 h-12 text-slate-200 mx-auto mb-4" />
                            <p class="text-sm font-black text-slate-400 uppercase tracking-widest">No matching records found</p>
                            <p class="text-xs text-slate-400 mt-1">Try searching for a name, contract number, or bill reference.</p>
                        </div>

                        <div v-else class="py-12 px-6 text-center">
                            <div class="grid grid-cols-2 gap-4 max-w-md mx-auto">
                                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 text-left">
                                    <UserIcon class="w-5 h-5 text-blue-500 mb-2" />
                                    <p class="text-xs font-black text-slate-900">Customers</p>
                                    <p class="text-[10px] text-slate-400 mt-1">Search by name or email</p>
                                </div>
                                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 text-left">
                                    <ClipboardDocumentCheckIcon class="w-5 h-5 text-emerald-500 mb-2" />
                                    <p class="text-xs font-black text-slate-900">Contracts</p>
                                    <p class="text-[10px] text-slate-400 mt-1">Search by contract number</p>
                                </div>
                            </div>
                            <p class="mt-8 text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center justify-center">
                                <ClockIcon class="w-3 h-3 mr-1.5" />
                                Pro Tip: Press <span class="mx-1 px-1.5 py-0.5 bg-slate-100 rounded border border-slate-200">Enter</span> to quickly navigate
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #e2e8f0;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background-color: #cbd5e1;
}
</style>