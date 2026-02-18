<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { ChevronDownIcon, MagnifyingGlassIcon, CheckIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: [String, Number],
    options: {
        type: Array,
        default: () => [],
    },
    labelKey: {
        type: String,
        default: 'label',
    },
    valueKey: {
        type: String,
        default: 'value',
    },
    placeholder: {
        type: String,
        default: 'Select an option',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    searchPlaceholder: {
        type: String,
        default: 'Search...',
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const searchQuery = ref('');
const containerRef = ref(null);
const searchInputRef = ref(null);

const selectedOption = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') return null;
    
    return props.options.find(opt => {
        const val = typeof opt === 'object' ? opt[props.valueKey] : opt;
        return String(val) === String(props.modelValue);
    });
});

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(opt => {
        const label = typeof opt === 'object' ? opt[props.labelKey] : String(opt);
        return label.toLowerCase().includes(query);
    });
});

const toggle = () => {
    if (!props.disabled) {
        isOpen.value = !isOpen.value;
        if (isOpen.value) {
            searchQuery.value = '';
            nextTick(() => {
                searchInputRef.value?.focus();
            });
        }
    }
};

const close = () => {
    isOpen.value = false;
};

const select = (option) => {
    const val = typeof option === 'object' ? optValue(option) : option;
    emit('update:modelValue', val);
    emit('change', option);
    close();
};

const optValue = (opt) => typeof opt === 'object' ? opt[props.valueKey] : opt;

watch(() => props.modelValue, () => {
    // If the value was changed externally, we don't necessarily close, 
    // but the component should reflect the new selection.
});

const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        close();
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});
</script>

<template>
    <div ref="containerRef" class="relative">
        <!-- Trigger Button -->
        <button
            type="button"
            @click="toggle"
            :disabled="disabled"
            class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-left cursor-default focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all duration-200"
            :class="{ 'opacity-60 cursor-not-allowed': disabled, 'hover:border-slate-300': !disabled }"
        >
            <div class="flex items-center justify-between">
                <span class="block truncate font-semibold" :class="selectedOption ? 'text-slate-700' : 'text-slate-400'">
                    {{ selectedOption ? (typeof selectedOption === 'object' ? selectedOption[labelKey] : selectedOption) : placeholder }}
                </span>
                <ChevronDownIcon class="h-5 w-5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': isOpen }" />
            </div>
        </button>

        <!-- Dropdown Menu -->
        <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div v-if="isOpen" class="absolute z-[100] mt-2 w-full bg-white shadow-2xl rounded-2xl border border-slate-100 overflow-hidden focus:outline-none">
                <!-- Search Box -->
                <div class="p-3 border-b border-slate-50">
                    <div class="relative">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input
                            ref="searchInputRef"
                            v-model="searchQuery"
                            type="text"
                            class="w-full pl-9 pr-4 py-2 bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20"
                            :placeholder="searchPlaceholder"
                            @keydown.esc="close"
                        >
                    </div>
                </div>

                <!-- Options List -->
                <ul class="max-h-60 overflow-y-auto py-1 custom-scrollbar scroll-smooth">
                    <li
                        v-for="(option, index) in filteredOptions"
                        :key="index"
                        class="px-4 py-2.5 text-sm cursor-default select-none flex items-center justify-between transition-colors"
                        :class="[
                            (typeof option === 'object' ? option[valueKey] : option) == modelValue 
                                ? 'bg-blue-50 text-blue-700 font-bold' 
                                : 'text-slate-600 hover:bg-slate-50'
                        ]"
                        @click="select(option)"
                    >
                        <span class="block truncate">
                            {{ typeof option === 'object' ? option[labelKey] : option }}
                        </span>
                        <CheckIcon 
                            v-if="(typeof option === 'object' ? option[valueKey] : option) == modelValue"
                            class="w-4 h-4 text-blue-600" 
                        />
                    </li>
                    
                    <li v-if="filteredOptions.length === 0" class="px-4 py-8 text-center text-slate-400 italic text-xs">
                        No results found for "{{ searchQuery }}"
                    </li>
                </ul>
            </div>
        </transition>
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
    background: #e2e8f0;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>