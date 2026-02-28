<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    Cog6ToothIcon, 
    ShieldCheckIcon, 
    ClockIcon,
    ArrowPathIcon,
    ExclamationTriangleIcon,
    TrashIcon
} from '@heroicons/vue/24/outline';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    settings: Object
});

const { showSuccess, showError } = useToast();
const { confirm } = useConfirm();

const auditForm = useForm({
    audit_enabled: props.settings.audit_enabled,
    audit_retention_months: props.settings.audit_retention_months
});

const submitAuditSettings = () => {
    auditForm.put(route('admin.settings.audit.update'), {
        preserveScroll: true,
        onSuccess: () => showSuccess('Audit settings updated.'),
        onError: () => showError('Failed to update settings.')
    });
};

const runManualPrune = async () => {
    const isConfirmed = await confirm({
        title: 'Confirm System Cleanup',
        message: `Are you sure you want to permanently delete activity logs older than ${props.settings.audit_retention_months} months? This action cannot be undone.`,
        type: 'danger',
        confirmLabel: 'Yes, Purge Old Logs'
    });

    if (isConfirmed) {
        router.post(route('admin.settings.audit.prune'), {
            preserveScroll: true,
            onError: () => showError('Failed to run cleanup.')
        });
    }
};
</script>

<template>
    <Head title="System Settings - Horizon ERP" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">System Configuration</h2>
                    <p class="text-sm text-slate-500 mt-1">Manage global system behaviors and security policies.</p>
                </div>
            </div>
        </template>

        <div class="py-6 space-y-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Audit Trail Settings Card -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/30">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-indigo-50 rounded-xl">
                                <ShieldCheckIcon class="w-6 h-6 text-indigo-600" />
                            </div>
                            <div>
                                <h3 class="font-black text-slate-900">Audit Trail & Security Logging</h3>
                                <p class="text-xs text-slate-500">Track user actions and financial record changes.</p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <span :class="[
                                'px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border',
                                auditForm.audit_enabled ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100'
                            ]">
                                {{ auditForm.audit_enabled ? 'System Active' : 'Logging Disabled' }}
                            </span>
                        </div>
                    </div>

                    <form @submit.prevent="submitAuditSettings" class="p-8 space-y-8">
                        <!-- Toggle -->
                        <div class="flex items-start justify-between">
                            <div class="max-w-xl">
                                <h4 class="text-sm font-black text-slate-900 mb-1">Enable Activity Logging</h4>
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    When enabled, the system will record "Who, What, and When" for all critical actions including creations, approvals, and deletions. 
                                    Disabling this will stop all new audit entries but won't delete existing ones.
                                </p>
                            </div>
                            <button 
                                type="button"
                                @click="auditForm.audit_enabled = !auditForm.audit_enabled"
                                :class="[
                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2',
                                    auditForm.audit_enabled ? 'bg-indigo-600' : 'bg-slate-200'
                                ]"
                            >
                                <span 
                                    :class="[
                                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                        auditForm.audit_enabled ? 'translate-x-5' : 'translate-x-0'
                                    ]" 
                                />
                            </button>
                        </div>

                        <!-- Retention Period -->
                        <div class="pt-8 border-t border-slate-50">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-2">
                                    <ClockIcon class="w-4 h-4 text-slate-400" />
                                    <h4 class="text-sm font-black text-slate-900">Retention Policy</h4>
                                </div>
                                <button 
                                    type="button"
                                    @click="runManualPrune"
                                    class="text-[10px] font-black uppercase tracking-widest text-rose-600 hover:text-rose-700 flex items-center bg-rose-50 px-3 py-1.5 rounded-xl border border-rose-100 transition-all"
                                >
                                    <TrashIcon class="w-3.5 h-3.5 mr-1.5" />
                                    Run Cleanup Now
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <label 
                                    v-for="option in [
                                        { label: '6 Months', value: 6 },
                                        { label: '1 Year', value: 12 },
                                        { label: '3 Years', value: 36 },
                                        { label: '5 Years', value: 60 }
                                    ]" 
                                    :key="option.value"
                                    :class="[
                                        'relative flex flex-col p-4 border rounded-2xl cursor-pointer hover:bg-slate-50 transition-all',
                                        auditForm.audit_retention_months === option.value 
                                            ? 'border-indigo-600 bg-indigo-50/30 ring-1 ring-indigo-600' 
                                            : 'border-slate-200'
                                    ]"
                                >
                                    <input type="radio" v-model="auditForm.audit_retention_months" :value="option.value" class="sr-only">
                                    <span class="text-xs font-black text-slate-900">{{ option.label }}</span>
                                    <span class="text-[10px] text-slate-500 mt-1">Automatic Purge</span>
                                </label>
                            </div>
                            
                            <div class="mt-6 flex items-start space-x-3 p-4 bg-amber-50 rounded-2xl border border-amber-100">
                                <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 flex-shrink-0" />
                                <p class="text-[11px] text-amber-800 font-medium leading-relaxed">
                                    <strong>Warning:</strong> Records older than the selected period will be permanently removed from the database during the next scheduled cleanup. 
                                    Selecting a longer retention period (3 or 5 years) will increase your database storage consumption over time.
                                </p>
                            </div>
                        </div>

                        <!-- Save Actions -->
                        <div class="pt-6 flex items-center justify-end">
                            <button 
                                type="submit" 
                                :disabled="auditForm.processing"
                                class="flex items-center space-x-2 px-8 py-3 bg-slate-900 text-white font-black rounded-2xl hover:bg-slate-800 transition-all shadow-xl shadow-slate-900/20 disabled:opacity-50"
                            >
                                <ArrowPathIcon v-if="auditForm.processing" class="w-5 h-5 animate-spin" />
                                <span>Save Audit Policy</span>
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
