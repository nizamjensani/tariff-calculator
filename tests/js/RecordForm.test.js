import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { reactive } from 'vue';
import RecordForm from '../../resources/js/Components/RecordForm.vue';

const options = {
    building_types: [
        { value: 'commercial', label: 'Commercial' },
        { value: 'residential', label: 'Residential' },
    ],
    months: [
        { value: 1, label: 'January' },
        { value: 6, label: 'June' },
    ],
    states: [
        { value: 'Johor', label: 'Johor' },
        { value: 'Selangor', label: 'Selangor' },
    ],
};

const tariffs = [
    { building_type: 'residential', label: 'Residential', blocks: [{ from: 1, to: 200, rate: 0.45 }, { from: 201, to: null, rate: 0.97 }] },
    { building_type: 'commercial', label: 'Commercial', blocks: [{ from: 1, to: 200, rate: 0.89 }, { from: 201, to: null, rate: 1.13 }] },
];

// Stand-in for Inertia's useForm(): the component only reads fields, errors and processing.
function makeForm(overrides = {}) {
    return reactive({
        customer_name: '',
        building_type: '',
        month: '',
        usability_kwh: '',
        state: '',
        errors: {},
        processing: false,
        ...overrides,
    });
}

function mountForm(form = makeForm(), props = {}) {
    return mount(RecordForm, { props: { form, options, tariffs, ...props } });
}

describe('RecordForm', () => {
    it('renders every field from Form 1', () => {
        const wrapper = mountForm();

        expect(wrapper.find('#customer_name').exists()).toBe(true);
        expect(wrapper.findAll('input[type="radio"][name="building_type"]')).toHaveLength(2);
        expect(wrapper.find('#month').exists()).toBe(true);
        expect(wrapper.find('#usability_kwh').exists()).toBe(true);
        expect(wrapper.find('#state').exists()).toBe(true);
    });

    it('starts the selects on "Please select"', () => {
        const wrapper = mountForm();

        expect(wrapper.find('#month option').text()).toBe('Please select');
        expect(wrapper.findAll('#month option')).toHaveLength(3);
        expect(wrapper.find('#state').element.value).toBe('');
        expect(wrapper.findAll('#state-listbox [role="option"]')).toHaveLength(2);
    });

    it('binds the inputs to the form', async () => {
        const form = makeForm();
        const wrapper = mountForm(form);

        await wrapper.find('#customer_name').setValue('John');
        await wrapper.find('input[value="commercial"]').setValue();
        await wrapper.find('#month').setValue(6);
        await wrapper.find('#usability_kwh').setValue('210');
        await wrapper.find('#state').setValue('selangor');
        await wrapper.find('#state').trigger('blur');

        expect(form.customer_name).toBe('John');
        expect(form.building_type).toBe('commercial');
        expect(form.month).toBe(6);
        expect(Number(form.usability_kwh)).toBe(210);
        expect(form.state).toBe('Selangor');
    });

    it('emits submit instead of posting the page', async () => {
        const wrapper = mountForm();

        await wrapper.find('form').trigger('submit');

        expect(wrapper.emitted('submit')).toHaveLength(1);
    });

    it('shows server validation errors next to their fields', () => {
        const form = makeForm({ errors: { customer_name: 'The customer name field is required.', state: 'Select a state.' } });
        const wrapper = mountForm(form);

        expect(wrapper.find('#customer_name-error').text()).toBe('The customer name field is required.');
        expect(wrapper.find('#customer_name').attributes('aria-invalid')).toBe('true');
        expect(wrapper.find('#customer_name').attributes('aria-describedby')).toBe('customer_name-error');
        expect(wrapper.find('#state-error').text()).toBe('Select a state.');
        expect(wrapper.find('#month-error').exists()).toBe(false);
    });

    it('clears a field error once that field changes', async () => {
        const form = makeForm({ errors: { customer_name: 'The customer name field is required.', state: 'Select a state.' } });
        const wrapper = mountForm(form);

        await wrapper.find('#customer_name').setValue('John');

        expect(wrapper.find('#customer_name-error').exists()).toBe(false);
        expect(wrapper.find('#state-error').exists()).toBe(true);
    });

    it('asks for input before showing an estimate', () => {
        const wrapper = mountForm();

        expect(wrapper.find('[data-test="estimate"]').text()).toContain('Choose a building type and enter the usage');
    });

    it('shows a live bill estimate with the block breakdown', async () => {
        const form = makeForm({ building_type: 'commercial', usability_kwh: 210 });
        const wrapper = mountForm(form);
        const estimate = wrapper.find('[data-test="estimate"]');

        expect(estimate.text()).toContain('189.30');
        expect(estimate.text()).toContain('200 kWh × RM 0.89');
        expect(estimate.text()).toContain('10 kWh × RM 1.13');

        form.building_type = 'residential';
        await wrapper.vm.$nextTick();
        expect(wrapper.find('[data-test="estimate"]').text()).toContain('99.70'); // 90.00 + 9.70
    });

    it('does not estimate invalid usage', () => {
        const wrapper = mountForm(makeForm({ building_type: 'residential', usability_kwh: -3 }));

        expect(wrapper.find('[data-test="estimate"]').text()).not.toContain('RM');
    });

    it('disables the button while saving', () => {
        const wrapper = mountForm(makeForm({ processing: true }), { submitLabel: 'Update record' });
        const button = wrapper.find('button[type="submit"]');

        expect(button.attributes('disabled')).toBeDefined();
        expect(button.text()).toBe('Saving…');
    });

    it('uses the given submit label', () => {
        const wrapper = mountForm(makeForm(), { submitLabel: 'Update record' });

        expect(wrapper.find('button[type="submit"]').text()).toBe('Update record');
    });
});
