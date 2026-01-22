<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    BuildingOfficeIcon, 
    ArrowLeftIcon,
    MapIcon,
    CalendarDaysIcon
} from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    project: Object,
});

const isSvg = computed(() => {
    return props.project.map_overlay && props.project.map_overlay.trim().startsWith('<svg');
});

const isIframe = computed(() => {
    if (!props.project.map_overlay) return false;
    const content = props.project.map_overlay.trim();
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
    <Head :title="`${project.name} - Project Details`" />

    <AppLayout :fluid="true">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">Project Details</h2>
                    <p class="text-sm text-slate-500 mt-1">Overview of development project and site plan.</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <Link
                        :href="route('projects.index')"
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
                <!-- Project Header Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-100 p-6 md:p-8">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between">
                        <div class="flex items-center">
                            <div class="h-16 w-16 bg-blue-50 rounded-2xl flex items-center justify-center border border-blue-100 text-blue-600">
                                <BuildingOfficeIcon class="w-8 h-8" />
                            </div>
                            <div class="ml-6">
                                <h1 class="text-2xl font-bold text-slate-900">{{ project.name }}</h1>
                                <div class="flex items-center space-x-3 mt-1">
                                    <span class="text-sm font-mono font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded">{{ project.code }}</span>
                                    <span 
                                        :class="[
                                            'px-2 py-0.5 text-xs font-bold rounded-full border',
                                            project.is_active 
                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-100' 
                                                : 'bg-slate-50 text-slate-500 border-slate-200'
                                        ]"
                                    >
                                        {{ project.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8 border-t border-slate-100 pt-6">
                        <h3 class="flex items-center text-sm font-bold text-slate-900 uppercase tracking-wider mb-2">
                            <MapIcon class="w-4 h-4 mr-2 text-slate-400" />
                            Location
                        </h3>
                        <p class="text-slate-600 leading-relaxed max-w-3xl">
                            {{ project.location }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Site Plan / Map Overlay (Full Screen Width) -->
            <div class="mt-6 w-full px-0 sm:px-4 lg:px-6">
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-2xl border border-slate-200">
                    <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between bg-slate-50">
                        <h3 class="text-lg font-bold text-slate-900 flex items-center">
                            <MapIcon class="w-5 h-5 mr-2 text-blue-600" />
                            Site Plan Explorer
                        </h3>
                        <span class="text-xs text-slate-500 font-medium">Interactive Map / Virtual Tour</span>
                    </div>
                    
                    <div class="w-full bg-slate-100 relative h-[85vh]">
                        <div v-if="project.map_overlay" class="w-full h-full">
                            <div v-if="isSvg" v-html="project.map_overlay" class="w-full h-full flex items-center justify-center overflow-auto p-4"></div>
                            <iframe 
                                v-else-if="isIframe" 
                                :src="project.map_overlay" 
                                class="w-full h-full border-0 block" 
                                allowfullscreen
                                loading="lazy"
                            ></iframe>
                            <div v-else class="flex items-center justify-center p-8 w-full h-full">
                                <img 
                                    :src="project.map_overlay" 
                                    alt="Site Plan" 
                                    class="max-w-full max-h-full object-contain shadow-2xl" 
                                />
                            </div>
                        </div>
                        <div v-else class="text-center h-full flex flex-col items-center justify-center p-12">
                            <MapIcon class="w-20 h-20 text-slate-300 mb-4" />
                            <p class="text-xl text-slate-500 font-bold mb-2">No site plan overlay available</p>
                            <p class="text-slate-400">Upload or provide an SVG/URL in the edit settings to activate the explorer.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Stats / Info -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-100 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-slate-900">System Metadata</h3>
                            <CalendarDaysIcon class="w-5 h-5 text-slate-400" />
                        </div>
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-bold text-slate-500 uppercase tracking-wider">Created At</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900">{{ new Date(project.created_at).toLocaleDateString() }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-slate-500 uppercase tracking-wider">Last Updated</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900">{{ new Date(project.updated_at).toLocaleDateString() }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
