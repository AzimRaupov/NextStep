<script setup>
defineProps({
    label: { type: String, required: true },
    type: { type: String, default: 'text' },
    modelValue: { type: String, default: '' },
    error: { type: String, default: '' },
    autocomplete: { type: String, default: 'off' },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <label class="block">
        <span class="mb-1.5 block text-sm font-medium text-neutral-700">{{ label }}</span>
        <input
            :type="type"
            :value="modelValue"
            :autocomplete="autocomplete"
            @input="$emit('update:modelValue', $event.target.value)"
            class="w-full rounded-lg border border-neutral-200 px-3.5 py-2.5 text-base text-neutral-900 placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900 sm:text-sm"
            :class="{ 'border-red-400 focus:border-red-500 focus:ring-red-500': error }"
        />
        <span v-if="error" class="mt-1.5 block text-xs text-red-500">{{ error }}</span>
    </label>
</template>
