<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import RecordForm from '../../Components/RecordForm.vue';

defineProps({
    options: { type: Object, required: true },
    tariffs: { type: Array, required: true },
});

const form = useForm({
    customer_name: '',
    building_type: '',
    month: '',
    usability_kwh: '',
    state: '',
});

function submit() {
    form.post('/records');
}
</script>

<template>
    <Head title="Add record" />

    <h1 class="mb-6 text-2xl font-bold tracking-tight">Add a meter reading</h1>

    <RecordForm :form="form" :options="options" :tariffs="tariffs" submit-label="Save record" @submit="submit">
        <template #actions>
            <Link href="/records" class="btn btn-secondary">Cancel</Link>
        </template>
    </RecordForm>
</template>
