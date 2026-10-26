<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: { type: Array, required: true },
});

const clean = (label) => label.replace('&laquo;', '‹').replace('&raquo;', '›');
</script>

<template>
    <nav v-if="links.length > 3" aria-label="Pagination" class="flex flex-wrap gap-1">
        <template v-for="(link, i) in links" :key="i">
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                class="min-w-9 rounded-md border px-3 py-1.5 text-center text-sm"
                :class="link.active ? 'border-ink bg-ink text-white' : 'border-line bg-white hover:border-ink-soft/60'"
                :aria-current="link.active ? 'page' : undefined"
            >
                {{ clean(link.label) }}
            </Link>
            <span v-else class="min-w-9 rounded-md px-3 py-1.5 text-center text-sm text-ink-soft/50">{{ clean(link.label) }}</span>
        </template>
    </nav>
</template>
