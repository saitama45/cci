<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col lg:flex-row">
        <!-- Left Panel - Branding & Visual -->
        <div class="hidden lg:flex lg:w-1/2 bg-slate-900 relative overflow-hidden items-center justify-center">
             <!-- Abstract Background -->
            <div class="absolute inset-0">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-600/20 to-indigo-600/20 mix-blend-multiply"></div>
                <div class="absolute bottom-0 left-0 right-0 top-0 bg-[linear-gradient(to_right,#4f4f4f2e_1px,transparent_1px),linear-gradient(to_bottom,#4f4f4f2e_1px,transparent_1px)] bg-[size:14px_24px] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)]"></div>
            </div>

            <!-- Content -->
            <div class="relative z-10 p-12 text-center max-w-xl">
                 <Link href="/" class="inline-block mb-12 transform hover:scale-105 transition-transform duration-300">
                    <ApplicationLogo class="h-24 w-24 mx-auto text-white drop-shadow-2xl" />
                </Link>

                <h1 class="text-4xl md:text-5xl font-bold text-white mb-6 tracking-tight">
                    GalgaTech <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-400">Horizon ERP</span>
                </h1>
                
                <p class="text-lg text-slate-300 leading-relaxed mb-10 font-light">
                    The unified management platform for <span class="text-white font-medium">Real Estate Developers</span> Streamlining property inventory, sales reservations, collections, and financial integrity.
                </p>

                <!-- Stats or Trust Indicators -->
                <div class="grid grid-cols-3 gap-6 border-t border-slate-700/50 pt-8">
                    <div>
                        <div class="text-2xl font-bold text-white">Precision</div>
                        <div class="text-xs text-slate-400 uppercase tracking-wider mt-1">Accounting</div>
                    </div>
                    <div>
                         <div class="text-2xl font-bold text-white">Real-time</div>
                        <div class="text-xs text-slate-400 uppercase tracking-wider mt-1">Inventory</div>
                    </div>
                     <div>
                         <div class="text-2xl font-bold text-white">Unified</div>
                        <div class="text-xs text-slate-400 uppercase tracking-wider mt-1">Operations</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Auth Form -->
        <div class="flex-1 flex flex-col justify-center items-center bg-white p-6 sm:p-12 relative">
             <!-- Mobile Logo -->
             <div class="lg:hidden absolute top-8 left-8">
                <Link href="/">
                    <ApplicationLogo class="h-10 w-10 text-blue-600" />
                </Link>
            </div>

            <div class="w-full max-w-md space-y-8">
                <slot />
            </div>

             <div class="mt-8 text-center lg:hidden">
                <p class="text-xs text-gray-400">
                    &copy; 2026 Real Estate Developers | Horizon ERP
                </p>
            </div>
        </div>
    </div>
</template>
