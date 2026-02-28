<script setup>
import { computed } from 'vue';
import { 
    CheckCircleIcon, 
    ClockIcon, 
    UserCircleIcon,
    ArrowRightCircleIcon
} from '@heroicons/vue/24/solid';

const props = defineProps({
    status: String,
    preparedBy: Object,
    approvedBy: Object,
    createdAt: String,
    updatedAt: String,
    // Optional additional stages (e.g. for Disbursements)
    paidAt: String,
    isBilled: Boolean
});

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('en-PH', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const stages = computed(() => {
    const list = [
        {
            name: 'Preparation',
            description: 'Document created and drafted',
            user: props.preparedBy?.name,
            date: formatDate(props.createdAt),
            isComplete: true,
            isActive: props.status === 'Draft'
        },
        {
            name: 'Approval',
            description: 'Technical and management review',
            user: props.approvedBy?.name,
            date: formatDate(props.status === 'Approved' || props.status === 'Paid' || props.status === 'Billed' ? props.updatedAt : null),
            isComplete: ['Approved', 'Paid', 'Billed'].includes(props.status),
            isActive: props.status === 'Draft', // If draft, this is next
            isNext: props.status === 'Draft'
        }
    ];

    // Add final stage based on type
    if (props.isBilled !== undefined) {
        list.push({
            name: 'Fulfillment',
            description: 'Converted to Bill/AP',
            date: props.status === 'Billed' ? formatDate(props.updatedAt) : null,
            isComplete: props.status === 'Billed',
            isActive: props.status === 'Approved',
            isNext: props.status === 'Approved'
        });
    } else {
        list.push({
            name: 'Finalization',
            description: props.status === 'Paid' ? 'Payment Released' : 'Ready for Payment',
            date: props.status === 'Paid' ? formatDate(props.updatedAt) : null,
            isComplete: props.status === 'Paid',
            isActive: props.status === 'Approved',
            isNext: props.status === 'Approved'
        });
    }

    return list;
});
</script>

<template>
    <div class="bg-white rounded-3xl p-8 border border-slate-100 shadow-sm">
        <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest mb-8 flex items-center">
            <ArrowRightCircleIcon class="w-5 h-5 mr-2 text-indigo-500" />
            Workflow Timeline
        </h3>

        <div class="relative space-y-8">
            <!-- Connector Line -->
            <div class="absolute left-[19px] top-2 bottom-2 w-0.5 bg-slate-100"></div>

            <div v-for="(stage, index) in stages" :key="index" class="relative flex items-start group">
                <!-- Status Icon -->
                <div class="relative z-10">
                    <div v-if="stage.isComplete" class="w-10 h-10 rounded-full bg-emerald-500 flex items-center justify-center ring-4 ring-white">
                        <CheckCircleIcon class="w-6 h-6 text-white" />
                    </div>
                    <div v-else-if="stage.isNext" class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center ring-4 ring-white animate-pulse">
                        <ClockIcon class="w-6 h-6 text-amber-600" />
                    </div>
                    <div v-else class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center ring-4 ring-white">
                        <div class="w-2 h-2 rounded-full bg-slate-300"></div>
                    </div>
                </div>

                <!-- Content -->
                <div class="ml-6 min-w-0 flex-1">
                    <div class="flex items-center justify-between">
                        <h4 :class="['text-sm font-black uppercase tracking-tight', stage.isComplete ? 'text-slate-900' : 'text-slate-400']">
                            {{ stage.name }}
                        </h4>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">{{ stage.description }}</p>
                    
                    <div v-if="stage.date" class="mt-2 flex items-center space-x-1.5 text-[10px] font-bold text-slate-400 tabular-nums">
                        <ClockIcon class="w-3 h-3" />
                        <span>{{ stage.date }}</span>
                    </div>
                    
                    <div v-if="stage.user" class="mt-3 flex items-center space-x-2 p-2 bg-slate-50 rounded-xl w-fit border border-slate-100">
                        <UserCircleIcon class="w-4 h-4 text-slate-400" />
                        <span class="text-[10px] font-black text-slate-600 uppercase">{{ stage.user }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rejection / Cancellation Status -->
        <div v-if="status === 'Cancelled'" class="mt-8 p-4 bg-rose-50 rounded-2xl border border-rose-100 flex items-center space-x-3">
            <div class="p-2 bg-rose-500 rounded-lg">
                <XCircleIcon class="w-4 h-4 text-white" />
            </div>
            <div>
                <p class="text-[10px] font-black text-rose-900 uppercase tracking-widest leading-none">Process Terminated</p>
                <p class="text-[10px] text-rose-600 font-bold mt-1">This document has been cancelled and is no longer active.</p>
            </div>
        </div>
    </div>
</template>
