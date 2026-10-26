<script setup>
import { computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import TierBar from '../../Components/TierBar.vue';
import { blockRange, formatKwh, formatMoney } from '../../lib/format';

const props = defineProps({
    rows: { type: Array, required: true },
    totals: { type: Object, required: true },
    filters: { type: Object, required: true },
    options: { type: Object, required: true },
    tariffs: { type: Array, required: true },
});

const search = reactive({
    month: props.filters.month ?? '',
    building_type: props.filters.building_type ?? '',
});

const scale = computed(() => Math.max(1, ...props.rows.map((r) => r.usability_kwh)));
const hasFilters = computed(() => !!(props.filters.month || props.filters.building_type));

const heading = computed(() => {
    const month = props.options.months.find((m) => m.value === props.filters.month)?.label;
    const type = props.options.building_types.find((t) => t.value === props.filters.building_type)?.label;
    if (month && type) return `${type} bills for ${month}`;
    if (month) return `All bills for ${month}`;
    if (type) return `${type} bills, all months`;
    return 'All bills, all months';
});

function apply() {
    const query = Object.fromEntries(Object.entries(search).filter(([, v]) => v !== '' && v !== null));
    router.get('/report', query, { preserveState: true, replace: true });
}

function printReport() {
    window.print();
}

function reset() {
    search.month = '';
    search.building_type = '';
    apply();
}
</script>

<template>
    <Head title="Bill report" />

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Bill report</h1>
            <p class="mt-1 text-sm text-ink-soft">{{ heading }}</p>
        </div>
        <button v-if="rows.length" type="button" class="btn btn-secondary print:hidden" @click="printReport">
            <Icon name="printer" />Print report
        </button>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_17rem]">
        <div class="min-w-0">
            <form
                class="mb-6 grid gap-4 rounded-lg border border-line bg-white p-4 sm:grid-cols-[1fr_1fr_auto] print:hidden"
                role="search"
                aria-label="Filter report"
                @submit.prevent="apply"
            >
                <div>
                    <label for="report-month" class="field-label">Month</label>
                    <select id="report-month" v-model="search.month" class="field-input">
                        <option value="">All months</option>
                        <option v-for="m in options.months" :key="m.value" :value="m.value">{{ m.label }}</option>
                    </select>
                </div>
                <div>
                    <label for="report-building-type" class="field-label">Building type</label>
                    <select id="report-building-type" v-model="search.building_type" class="field-input">
                        <option value="">All building types</option>
                        <option v-for="t in options.building_types" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <button v-if="hasFilters" type="button" class="btn btn-secondary" @click="reset">Clear</button>
                </div>
            </form>

            <div v-if="rows.length" class="overflow-x-auto rounded-lg border border-line">
                <table class="data-table min-w-[680px]">
                    <thead>
                        <tr>
                            <th class="w-12">No</th>
                            <th>Customer name</th>
                            <th>Month</th>
                            <th>Building type</th>
                            <th class="text-right">Usability (kWh)</th>
                            <th class="w-36"><span class="sr-only">Tariff split</span></th>
                            <th class="text-right">Bill (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in rows" :key="row.id">
                            <td class="text-ink-soft">{{ i + 1 }}</td>
                            <td class="font-semibold">{{ row.customer_name }}</td>
                            <td>{{ row.month_label }}</td>
                            <td>{{ row.building_type_label }}</td>
                            <td class="text-right">{{ formatKwh(row.usability_kwh) }}</td>
                            <td><TierBar :breakdown="row.breakdown" :scale="scale" /></td>
                            <td class="text-right font-semibold">{{ formatMoney(row.bill) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="font-bold">
                            <td colspan="4" class="border-t-2 border-ink px-3 py-3">Total ({{ totals.records }} {{ totals.records === 1 ? 'bill' : 'bills' }})</td>
                            <td class="border-t-2 border-ink px-3 py-3 text-right">{{ formatKwh(totals.usability_kwh) }}</td>
                            <td class="border-t-2 border-ink" />
                            <td class="border-t-2 border-ink px-3 py-3 text-right text-lg" data-test="total-bill">{{ formatMoney(totals.bill) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div v-else class="rounded-lg border border-dashed border-line bg-white px-6 py-12 text-center">
                <p class="font-semibold">No bills for this month and building type.</p>
                <p class="mt-1 text-sm text-ink-soft">Pick another month or building type, or clear the search to see every bill.</p>
            </div>
        </div>

        <aside class="self-start rounded-lg border border-line bg-white p-5">
            <h2 class="font-bold">Tariff</h2>
            <div v-for="tariff in tariffs" :key="tariff.building_type" class="mt-4">
                <h3 class="text-sm font-semibold text-ink-soft">{{ tariff.label }}</h3>
                <dl class="mt-1.5 grid gap-1 text-sm">
                    <div v-for="(block, i) in tariff.blocks" :key="i" class="flex items-center justify-between gap-3">
                        <dt class="flex items-center gap-2">
                            <span class="size-2.5 rounded-sm" :class="block.to === null ? 'bg-volt' : 'bg-ink'" aria-hidden="true" />
                            {{ blockRange(block) }}
                        </dt>
                        <dd class="font-semibold">RM {{ block.rate.toFixed(2) }}/kWh</dd>
                    </div>
                </dl>
            </div>
            <p class="mt-4 border-t border-line pt-3 text-xs leading-relaxed text-ink-soft">
                The bar beside each bill shows its usage split between the first-block rate and the higher rate.
            </p>
        </aside>
    </div>
</template>
