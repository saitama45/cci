<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    HomeIcon, 
    ArrowLeftIcon,
    MapIcon,
    CalendarDaysIcon,
    TagIcon,
    Square2StackIcon
} from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    unit: Object,
});

const statusColors = {
    'Available': 'bg-emerald-50 text-emerald-700 border-emerald-100',
    'Reserved': 'bg-blue-50 text-blue-700 border-blue-100',
    'Sold': 'bg-slate-50 text-slate-700 border-slate-200',
    'Blocked': 'bg-red-50 text-red-700 border-red-100',
};

const isSvg = computed(() => {
    return props.unit.svg_path && props.unit.svg_path.trim().startsWith('<svg');
});

const isIframe = computed(() => {
    if (!props.unit.svg_path) return false;
    const content = props.unit.svg_path.trim();
    const isUrl = content.startsWith('http://') || content.startsWith('https://');
    
    if (!isUrl) return false;

    // Check if it looks like an image URL
    const lower = content.toLowerCase();
    const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp', '.svg'];
    const hasImageExt = imageExtensions.some(ext => lower.endsWith(ext));

    return !hasImageExt;
});
</script>

<template>
    <Head :title="`${unit.name} - Unit Details`" />

    <AppLayout :fluid="true">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Unit Details</h2>
                    <p class="text-sm text-slate-500 mt-1">Detailed information for B{{ unit.block_num }} L{{ unit.lot_num }}.</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <Link
                        :href="route('units.index')"
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
                <!-- Unit Header Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-100 p-6 md:p-8">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between">
                        <div class="flex items-center">
                            <div class="h-16 w-16 bg-blue-50 rounded-2xl flex items-center justify-center border border-blue-100 text-blue-600">
                                <HomeIcon class="w-8 h-8" />
                            </div>
                            <div class="ml-6">
                                <h1 class="text-2xl font-bold text-slate-900">{{ unit.name }}</h1>
                                <div class="flex items-center space-x-3 mt-1">
                                    <span class="text-sm font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded flex items-center">
                                        <Square2StackIcon class="w-4 h-4 mr-1" />
                                        Block {{ unit.block_num }} Lot {{ unit.lot_num }}
                                    </span>
                                    <span 
                                        :class="[
                                            'px-2 py-0.5 text-xs font-bold rounded-full border',
                                            statusColors[unit.status] || 'bg-slate-50 text-slate-500 border-slate-200'
                                        ]"
                                    >
                                        {{ unit.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8 border-t border-slate-100 pt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                         <div>
                            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Project</h3>
                            <p class="text-lg font-semibold text-slate-900">{{ unit.project?.name || 'Unknown Project' }}</p>
                         </div>
                         <div>
                            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Lot Area</h3>
                            <p class="text-lg font-semibold text-slate-900">{{ unit.sqm_area }} sqm</p>
                         </div>
                         <div>
                            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Last Updated</h3>
                            <p class="text-lg font-semibold text-slate-900">{{ new Date(unit.updated_at).toLocaleDateString() }}</p>
                         </div>
                    </div>
                </div>
            </div>

            <!-- Map Integration Data / Visualizer (Full Screen Width) -->
            <div class="mt-6 w-full px-0 sm:px-4 lg:px-6">
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-2xl border border-slate-200">
                    <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between bg-slate-50">
                        <h3 class="text-lg font-bold text-slate-900 flex items-center">
                            <MapIcon class="w-5 h-5 mr-2 text-blue-600" />
                            Unit Visualizer / Map Integration
                        </h3>
                    </div>
                    
                    <div class="w-full bg-slate-100 relative h-[85vh]">
                        <div v-if="unit.svg_path" class="w-full h-full">
                            <div v-if="isSvg" class="w-full h-full flex items-center justify-center p-4">
                                <code class="text-xs font-mono text-slate-600 break-all block bg-white p-4 rounded border border-slate-200 max-h-full overflow-auto">
                                    {{ unit.svg_path }}
                                </code>
                            </div>
                            <iframe 
                                v-else-if="isIframe" 
                                :src="unit.svg_path" 
                                class="w-full h-full border-0 block" 
                                allowfullscreen
                                loading="lazy"
                            ></iframe>
                            <div v-else class="flex items-center justify-center p-8 w-full h-full">
                                <img 
                                    :src="unit.svg_path" 
                                    alt="Unit Plan" 
                                    class="max-w-full max-h-full object-contain shadow-2xl" 
                                />
                            </div>
                        </div>
                        <div v-else class="text-center h-full flex flex-col items-center justify-center p-12">
                             <MapIcon class="w-20 h-20 text-slate-300 mb-4" />
                            <p class="text-xl text-slate-500 font-bold mb-2">No visualization data available</p>
                            <p class="text-slate-400">Add SVG path data or a URL in the edit settings to activate.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                 <!-- Additional metadata or future content can go here -->
            </div>
        </div>
    </AppLayout>
</template>
