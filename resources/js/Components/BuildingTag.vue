<script setup>
defineProps({
    type: { type: String, required: true },
    label: { type: String, required: true },
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded px-2 py-0.5 text-[13px] font-semibold"
        :class="type === 'residential' ? 'bg-grid-soft text-grid' : 'bg-ink/8 text-ink'"
    >
        <span class="size-1.5 rounded-full" :class="type === 'residential' ? 'bg-grid' : 'bg-ink'" aria-hidden="true" />
        {{ label }}
    </span>
</template>
