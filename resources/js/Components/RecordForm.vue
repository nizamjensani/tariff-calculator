<script setup>
import { computed, watch } from 'vue';
import Combobox from './Combobox.vue';
import { bill, blocksFor, breakdown } from '../lib/tariff';
import { blockRange, formatKwh, formatMoney } from '../lib/format';

const props = defineProps({
    form: { type: Object, required: true },
    options: { type: Object, required: true },
    tariffs: { type: Array, default: () => [] },
    submitLabel: { type: String, default: 'Save record' },
});

const emit = defineEmits(['submit']);

const blocks = computed(() => blocksFor(props.tariffs, props.form.building_type));
const usage = computed(() => (props.form.usability_kwh === '' || props.form.usability_kwh === null ? null : Number(props.form.usability_kwh)));
const canEstimate = computed(() => blocks.value.length > 0 && usage.value !== null && Number.isInteger(usage.value) && usage.value >= 0);
const estimate = computed(() => (canEstimate.value ? bill(blocks.value, usage.value) : null));
const lines = computed(() => (canEstimate.value ? breakdown(blocks.value, usage.value) : []));

const errorId = (field) => `${field}-error`;

// Clear a field's server error as soon as the user changes that field.
for (const field of ['customer_name', 'building_type', 'month', 'usability_kwh', 'state']) {
    watch(
        () => props.form[field],
        () => {
            if (!props.form.errors?.[field]) return;
            if (typeof props.form.clearErrors === 'function') props.form.clearErrors(field);
            else delete props.form.errors[field];
        },
    );
}
</script>

<template>
    <form novalidate class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_18rem]" @submit.prevent="emit('submit')">
        <div class="grid gap-5 rounded-lg border border-line bg-white p-5 sm:p-7">
            <div>
                <label for="customer_name" class="field-label">Customer name</label>
                <input
                    id="customer_name"
                    v-model="form.customer_name"
                    type="text"
                    name="customer_name"
                    autocomplete="off"
                    maxlength="255"
                    class="field-input uppercase"
                    :aria-invalid="!!form.errors.customer_name"
                    :aria-describedby="form.errors.customer_name ? errorId('customer_name') : undefined"
                />
                <p v-if="form.errors.customer_name" :id="errorId('customer_name')" class="field-error">{{ form.errors.customer_name }}</p>
            </div>

            <fieldset :aria-describedby="form.errors.building_type ? errorId('building_type') : undefined">
                <legend class="field-label">Building type</legend>
                <div class="flex flex-wrap gap-3">
                    <label
                        v-for="type in options.building_types"
                        :key="type.value"
                        class="flex cursor-pointer items-center gap-2.5 rounded-md border px-4 py-2.5 text-[15px] transition-colors"
                        :class="form.building_type === type.value ? 'border-ink bg-paper font-semibold' : 'border-line hover:border-ink-soft/50'"
                    >
                        <input v-model="form.building_type" type="radio" name="building_type" :value="type.value" class="size-4 accent-ink" />
                        {{ type.label }}
                    </label>
                </div>
                <p v-if="form.errors.building_type" :id="errorId('building_type')" class="field-error">{{ form.errors.building_type }}</p>
            </fieldset>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="month" class="field-label">Month</label>
                    <select
                        id="month"
                        v-model="form.month"
                        name="month"
                        class="field-input"
                        :aria-invalid="!!form.errors.month"
                        :aria-describedby="form.errors.month ? errorId('month') : undefined"
                    >
                        <option value="">Please select</option>
                        <option v-for="month in options.months" :key="month.value" :value="month.value">{{ month.label }}</option>
                    </select>
                    <p v-if="form.errors.month" :id="errorId('month')" class="field-error">{{ form.errors.month }}</p>
                </div>

                <div>
                    <label for="state" class="field-label">State</label>
                    <Combobox
                        id="state"
                        v-model="form.state"
                        :options="options.states"
                        placeholder="Type or select a state"
                        :invalid="!!form.errors.state"
                        :describedby="form.errors.state ? errorId('state') : undefined"
                    />
                    <p v-if="form.errors.state" :id="errorId('state')" class="field-error">{{ form.errors.state }}</p>
                </div>
            </div>

            <div class="sm:max-w-[calc(50%-0.625rem)]">
                <label for="usability_kwh" class="field-label">Usability (kWh)</label>
                <input
                    id="usability_kwh"
                    v-model="form.usability_kwh"
                    type="number"
                    name="usability_kwh"
                    inputmode="numeric"
                    min="0"
                    step="1"
                    placeholder="e.g. 159"
                    class="field-input"
                    :aria-invalid="!!form.errors.usability_kwh"
                    :aria-describedby="form.errors.usability_kwh ? errorId('usability_kwh') : undefined"
                />
                <p v-if="form.errors.usability_kwh" :id="errorId('usability_kwh')" class="field-error">{{ form.errors.usability_kwh }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3 border-t border-line pt-5">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                    {{ form.processing ? 'Saving…' : submitLabel }}
                </button>
                <slot name="actions" />
            </div>
        </div>

        <aside aria-live="polite" class="self-start rounded-lg border border-line bg-white p-5" data-test="estimate">
            <h2 class="text-sm font-semibold text-ink-soft">Estimated bill</h2>
            <p v-if="estimate !== null" class="mt-1 text-4xl font-bold tracking-tight">
                <span class="mr-1 text-lg font-semibold text-ink-soft">RM</span>{{ formatMoney(estimate) }}
            </p>
            <p v-else class="mt-2 text-sm text-ink-soft">Choose a building type and enter the usage to see the bill.</p>

            <dl v-if="lines.length" class="mt-4 grid gap-2 border-t border-line pt-4 text-sm">
                <div v-for="(line, i) in lines" :key="i" class="flex justify-between gap-3">
                    <dt class="text-ink-soft">
                        {{ blockRange(line) }}<br />
                        <span class="text-xs">{{ formatKwh(line.kwh) }} kWh × RM {{ line.rate.toFixed(2) }}</span>
                    </dt>
                    <dd class="font-semibold">{{ formatMoney(line.amountSen / 100) }}</dd>
                </div>
            </dl>
        </aside>
    </form>
</template>
