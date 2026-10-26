<script setup>
import { computed } from 'vue';

const props = defineProps({
    column: { type: String, required: true },
    label: { type: String, required: true },
    sort: { type: String, default: null },
    direction: { type: String, default: null },
});

const emit = defineEmits(['sort']);

const active = computed(() => props.sort === props.column);
const ariaSort = computed(() => (active.value ? (props.direction === 'desc' ? 'descending' : 'ascending') : 'none'));
const next = computed(() => (active.value && props.direction === 'asc' ? 'desc' : 'asc'));
</script>

<template>
    <th :aria-sort="ariaSort">
        <button
            type="button"
            class="inline-flex items-center gap-1.5 hover:underline"
            :title="`Sort by ${label.toLowerCase()} (${next === 'asc' ? 'ascending' : 'descending'})`"
            @click="emit('sort', column, next)"
        >
            {{ label }}
            <svg viewBox="0 0 10 14" class="h-3.5 w-2.5" aria-hidden="true">
                <path d="M5 1 9 5.5H1z" :class="active && direction === 'asc' ? 'fill-ink' : 'fill-ink/25'" />
                <path d="M5 13 1 8.5h8z" :class="active && direction === 'desc' ? 'fill-ink' : 'fill-ink/25'" />
            </svg>
        </button>
    </th>
</template>
