<script setup>
import { computed, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BuildingTag from '../../Components/BuildingTag.vue';
import Combobox from '../../Components/Combobox.vue';
import Icon from '../../Components/Icon.vue';
import Pagination from '../../Components/Pagination.vue';
import SortHeader from '../../Components/SortHeader.vue';
import { formatKwh, formatMoney } from '../../lib/format';

const props = defineProps({
    records: { type: Object, required: true },
    filters: { type: Object, required: true },
    options: { type: Object, required: true },
});

const sortOptions = [
    { value: '', label: 'Newest first' },
    { value: 'building_type:asc', label: 'Building type (A–Z)' },
    { value: 'building_type:desc', label: 'Building type (Z–A)' },
    { value: 'usability_kwh:asc', label: 'Usability (low to high)' },
    { value: 'usability_kwh:desc', label: 'Usability (high to low)' },
];

const search = reactive({
    building_type: props.filters.building_type ?? '',
    state: props.filters.state ?? '',
    order: props.filters.sort ? `${props.filters.sort}:${props.filters.direction}` : '',
});

const hasFilters = computed(() => !!(props.filters.building_type || props.filters.state || props.filters.sort));

function visit(params) {
    const query = Object.fromEntries(Object.entries(params).filter(([, v]) => v !== '' && v !== null && v !== undefined));
    router.get('/records', query, { preserveState: true, preserveScroll: true, replace: true });
}

function applySearch() {
    const [sort, direction] = search.order ? search.order.split(':') : [null, null];
    visit({ building_type: search.building_type, state: search.state, sort, direction });
}

function sortBy(column, direction) {
    search.order = `${column}:${direction}`;
    applySearch();
}

function reset() {
    search.building_type = '';
    search.state = '';
    search.order = '';
    visit({});
}

function destroy(record) {
    if (window.confirm(`Delete the record for ${record.customer_name}? This can't be undone.`)) {
        router.delete(`/records/${record.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Records" />

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Meter readings</h1>
            <p class="mt-1 text-sm text-ink-soft">
                {{ records.total }} {{ records.total === 1 ? 'record' : 'records' }}{{ hasFilters ? ' match your search' : ' saved' }}
            </p>
        </div>
        <Link href="/records/create" class="btn btn-primary"><Icon name="plus" />Add record</Link>
    </div>

    <form
        class="mb-6 grid gap-4 rounded-lg border border-line bg-white p-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1.2fr_auto]"
        role="search"
        aria-label="Filter records"
        @submit.prevent="applySearch"
    >
        <div>
            <label for="filter-building-type" class="field-label">Building type</label>
            <select id="filter-building-type" v-model="search.building_type" class="field-input">
                <option value="">All building types</option>
                <option v-for="t in options.building_types" :key="t.value" :value="t.value">{{ t.label }}</option>
            </select>
        </div>
        <div>
            <label for="filter-state" class="field-label">State</label>
            <Combobox id="filter-state" v-model="search.state" :options="options.states" placeholder="All states" />
        </div>
        <div>
            <label for="filter-order" class="field-label">Order by</label>
            <select id="filter-order" v-model="search.order" class="field-input">
                <option v-for="o in sortOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="btn btn-primary">Search</button>
            <button v-if="hasFilters" type="button" class="btn btn-secondary" @click="reset">Clear</button>
        </div>
    </form>

    <div v-if="records.data.length" class="overflow-x-auto rounded-lg border border-line bg-paper">
        <table class="data-table min-w-[760px]">
            <thead>
                <tr>
                    <th class="w-12">No</th>
                    <th>Customer name</th>
                    <SortHeader column="building_type" label="Building type" :sort="filters.sort" :direction="filters.direction" @sort="sortBy" />
                    <th>Month</th>
                    <th>State</th>
                    <SortHeader
                        column="usability_kwh"
                        label="Usability (kWh)"
                        class="text-right"
                        :sort="filters.sort"
                        :direction="filters.direction"
                        @sort="sortBy"
                    />
                    <th class="text-right">Bill (RM)</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(record, i) in records.data" :key="record.id">
                    <td class="text-ink-soft">{{ records.from + i }}</td>
                    <td class="font-semibold">{{ record.customer_name }}</td>
                    <td><BuildingTag :type="record.building_type" :label="record.building_type_label" /></td>
                    <td>{{ record.month_label }}</td>
                    <td>{{ record.state }}</td>
                    <td class="text-right">{{ formatKwh(record.usability_kwh) }}</td>
                    <td class="text-right font-semibold">{{ formatMoney(record.bill) }}</td>
                    <td class="text-right whitespace-nowrap">
                        <Link
                            :href="`/records/${record.id}/edit`"
                            class="btn btn-secondary p-2!"
                            :aria-label="`Edit record for ${record.customer_name}`"
                            title="Edit"
                        >
                            <Icon name="pen" />
                        </Link>
                        <button
                            type="button"
                            class="btn btn-danger ml-1 p-2!"
                            :aria-label="`Delete record for ${record.customer_name}`"
                            title="Delete"
                            @click="destroy(record)"
                        >
                            <Icon name="trash" />
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-else class="rounded-lg border border-dashed border-line bg-white px-6 py-12 text-center">
        <p class="font-semibold">{{ hasFilters ? 'No records match this search.' : 'No meter readings yet.' }}</p>
        <p class="mt-1 text-sm text-ink-soft">
            {{ hasFilters ? 'Try another building type or state.' : 'Add the first reading to start billing.' }}
        </p>
        <div class="mt-4">
            <button v-if="hasFilters" type="button" class="btn btn-secondary" @click="reset">Clear search</button>
            <Link v-else href="/records/create" class="btn btn-primary"><Icon name="plus" />Add record</Link>
        </div>
    </div>

    <div class="mt-5">
        <Pagination :links="records.links" />
    </div>
</template>
