<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import RecordForm from '../../Components/RecordForm.vue';

const props = defineProps({
    record: { type: Object, required: true },
    options: { type: Object, required: true },
    tariffs: { type: Array, required: true },
});

const form = useForm({
    customer_name: props.record.customer_name,
    building_type: props.record.building_type,
    month: props.record.month,
    usability_kwh: props.record.usability_kwh,
    state: props.record.state,
});

function submit() {
    form.put(`/records/${props.record.id}`);
}

function destroy() {
    if (window.confirm(`Delete the record for ${props.record.customer_name}? This can't be undone.`)) {
        router.delete(`/records/${props.record.id}`);
    }
}
</script>

<template>
    <Head :title="`Edit ${record.customer_name}`" />

    <h1 class="mb-6 text-2xl font-bold tracking-tight">Edit record for {{ record.customer_name }}</h1>

    <RecordForm :form="form" :options="options" :tariffs="tariffs" submit-label="Update record" @submit="submit">
        <template #actions>
            <Link href="/records" class="btn btn-secondary">Cancel</Link>
            <button type="button" class="btn btn-danger ml-auto" @click="destroy">Delete record</button>
        </template>
    </RecordForm>
</template>
