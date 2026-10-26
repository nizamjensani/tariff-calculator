<script setup>
defineProps({
    name: { type: String, required: true },
});

// Stroke icons on a 24×24 grid, drawn in the current text colour.
const paths = {
    pen: ['M21.17 6.81a1 1 0 0 0-3.98-3.98L3.84 16.17a2 2 0 0 0-.5.83l-1.32 4.35a.5.5 0 0 0 .62.62l4.35-1.32a2 2 0 0 0 .83-.5z', 'm15 5 4 4'],
    trash: ['M3 6h18', 'M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6', 'M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2', 'M10 11v6', 'M14 11v6'],
    plus: ['M5 12h14', 'M12 5v14'],
    printer: ['M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2', 'M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6', 'M7 14h10v7a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1z'],
    chevron: ['m6 9 6 6 6-6'],
};
</script>

<template>
    <svg
        viewBox="0 0 24 24"
        class="size-4 shrink-0"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <path v-for="(d, i) in paths[name]" :key="i" :d="d" />
    </svg>
</template>
