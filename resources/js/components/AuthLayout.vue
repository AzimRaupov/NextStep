<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
});
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-white px-6 py-12">
        <div class="w-full max-w-sm">
            <div class="mb-10 text-center">
                <div class="mx-auto mb-6 flex h-9 w-9 items-center justify-center rounded-lg bg-neutral-900">
                    <span class="text-sm font-semibold text-white">R</span>
                </div>
                <h1 class="text-xl font-medium text-neutral-900">{{ title }}</h1>
                <p v-if="subtitle" class="mt-2 text-sm text-neutral-500">{{ subtitle }}</p>
            </div>

            <slot />

            <p class="mt-8 text-center text-sm text-neutral-500">
                <slot name="footer" />
            </p>
        </div>
    </div>
</template>
