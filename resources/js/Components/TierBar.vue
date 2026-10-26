<script setup>
import { computed } from 'vue';

/**
 * Shows how a reading splits across the tariff blocks:
 * ink for the first block, volt yellow for usage charged at the higher rate.
 */
const props = defineProps({
    breakdown: { type: Array, required: true },
    scale: { type: Number, required: true },
});

const segments = computed(() =>
    props.breakdown
        .filter((line) => line.kwh > 0)
        .map((line, i) => ({
            key: i,
            width: props.scale > 0 ? (line.kwh / props.scale) * 100 : 0,
            higher: line.to === null,
        })),
);
</script>

<template>
    <div class="flex h-2 w-full min-w-24 overflow-hidden rounded-sm bg-line/50" aria-hidden="true">
        <span v-for="s in segments" :key="s.key" class="h-full" :class="s.higher ? 'bg-volt' : 'bg-ink'" :style="{ width: `${s.width}%` }" />
    </div>
</template>
