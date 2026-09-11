<script setup>
import { computed } from 'vue';

const props = defineProps({
    ratio: { type: Number, default: null },
});

// The bar shows how much of the planned time has already been spent, like a
// budget: the fill is time spent so far, and the tick marks the 100% "on
// schedule" point. The scale goes up to 160% so a student who's mildly over
// plan still fits on the bar instead of pinning at the edge.
const SCALE_MAX = 1.6;
const BEHIND_THRESHOLD = 1.3;
const markerPercent = (1 / SCALE_MAX) * 100;

const fillPercent = computed(() => {
    if (props.ratio === null) {
        return 0;
    }

    return (Math.min(Math.max(props.ratio, 0), SCALE_MAX) / SCALE_MAX) * 100;
});

const isBehind = computed(() => props.ratio !== null && props.ratio > BEHIND_THRESHOLD);

const percentLabel = computed(() => (props.ratio === null ? '—' : `${Math.round(props.ratio * 100)}%`));
</script>

<template>
    <div class="flex items-center gap-2.5">
        <div class="relative h-1.5 flex-1 rounded-full bg-neutral-100">
            <div
                class="h-full rounded-full transition-all"
                :class="isBehind ? 'bg-rose-500' : 'bg-sky-600'"
                :style="{ width: fillPercent + '%' }"
            ></div>
            <div
                class="absolute top-1/2 h-2.5 w-px -translate-y-1/2 bg-neutral-300"
                :style="{ left: markerPercent + '%' }"
            ></div>
        </div>
        <span class="w-9 shrink-0 text-right font-mono text-[11px]" :class="isBehind ? 'text-rose-600' : 'text-sky-700'">
            {{ percentLabel }}
        </span>
    </div>
</template>
