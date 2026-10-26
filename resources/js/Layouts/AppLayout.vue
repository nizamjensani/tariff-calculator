<script setup>
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const nav = [
    { label: 'Records', href: '/records', match: (url) => url === '/records' || url.startsWith('/records?') || /^\/records\/\d+/.test(url) },
    { label: 'Add record', href: '/records/create', match: (url) => url.startsWith('/records/create') },
    { label: 'Bill report', href: '/report', match: (url) => url.startsWith('/report') },
];

const flash = ref(null);

watch(
    () => page.props.flash?.success,
    (message) => {
        flash.value = message || null;
    },
    { immediate: true },
);

const appName = computed(() => page.props.appName || 'Electricity Billing');
</script>

<template>
    <div class="min-h-screen">
        <header class="bg-ink text-white">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-8 gap-y-2 px-4 py-3 sm:px-6">
                <Link href="/records" class="flex items-center gap-2 text-[17px] font-bold tracking-tight">
                    <svg viewBox="0 0 24 24" class="size-5 text-volt" aria-hidden="true">
                        <path fill="currentColor" d="M13.5 2 4 13.5h6.5L9.5 22 20 9.5h-6.6z" />
                    </svg>
                    {{ appName }}
                </Link>
                <nav aria-label="Main" class="-mx-2 flex flex-wrap">
                    <Link
                        v-for="item in nav"
                        :key="item.href"
                        :href="item.href"
                        class="rounded px-2 py-1.5 text-sm font-medium text-white/75 hover:text-white"
                        :class="{ 'text-white! underline decoration-volt decoration-2 underline-offset-[10px]': item.match(page.url) }"
                        :aria-current="item.match(page.url) ? 'page' : undefined"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
            <div
                v-if="flash"
                role="status"
                class="mb-6 flex items-start justify-between gap-4 rounded-md border border-grid/30 bg-grid-soft px-4 py-3 text-sm text-ink"
            >
                <span>{{ flash }}</span>
                <button type="button" class="font-semibold text-grid hover:underline" @click="flash = null">Dismiss</button>
            </div>

            <slot />
        </main>
    </div>
</template>
