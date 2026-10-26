import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { h } from 'vue';

vi.mock('@inertiajs/vue3', () => ({
    Link: {
        props: ['href'],
        setup: (props, { slots, attrs }) => () => h('a', { ...attrs, href: props.href }, slots.default?.()),
    },
}));

const { default: SortHeader } = await import('../../resources/js/Components/SortHeader.vue');
const { default: TierBar } = await import('../../resources/js/Components/TierBar.vue');
const { default: BuildingTag } = await import('../../resources/js/Components/BuildingTag.vue');
const { default: Pagination } = await import('../../resources/js/Components/Pagination.vue');
const { default: Combobox } = await import('../../resources/js/Components/Combobox.vue');

function mountHeader(props) {
    return mount(
        { components: { SortHeader }, template: '<table><thead><tr><SortHeader v-bind="$attrs" /></tr></thead></table>' },
        { attrs: { column: 'usability_kwh', label: 'Usability (kWh)', ...props } },
    );
}

describe('SortHeader', () => {
    it('sorts ascending first when the column is not active', async () => {
        const wrapper = mount(SortHeader, { props: { column: 'usability_kwh', label: 'Usability (kWh)' }, attachTo: document.createElement('tr') });

        expect(wrapper.attributes('aria-sort')).toBe('none');
        await wrapper.find('button').trigger('click');
        expect(wrapper.emitted('sort')[0]).toEqual(['usability_kwh', 'asc']);
    });

    it('toggles to descending when already ascending', async () => {
        const wrapper = mount(SortHeader, {
            props: { column: 'usability_kwh', label: 'Usability (kWh)', sort: 'usability_kwh', direction: 'asc' },
            attachTo: document.createElement('tr'),
        });

        expect(wrapper.attributes('aria-sort')).toBe('ascending');
        await wrapper.find('button').trigger('click');
        expect(wrapper.emitted('sort')[0]).toEqual(['usability_kwh', 'desc']);
    });

    it('toggles back to ascending when descending', async () => {
        const wrapper = mount(SortHeader, {
            props: { column: 'building_type', label: 'Building type', sort: 'building_type', direction: 'desc' },
            attachTo: document.createElement('tr'),
        });

        expect(wrapper.attributes('aria-sort')).toBe('descending');
        await wrapper.find('button').trigger('click');
        expect(wrapper.emitted('sort')[0]).toEqual(['building_type', 'asc']);
    });

    it('ignores another column being sorted', () => {
        const wrapper = mountHeader({ sort: 'building_type', direction: 'asc' });

        expect(wrapper.find('th').attributes('aria-sort')).toBe('none');
    });
});

describe('TierBar', () => {
    it('sizes each segment against the scale', () => {
        const wrapper = mount(TierBar, {
            props: {
                scale: 400,
                breakdown: [
                    { from: 1, to: 200, kwh: 200 },
                    { from: 201, to: null, kwh: 100 },
                ],
            },
        });
        const segments = wrapper.findAll('span');

        expect(segments).toHaveLength(2);
        expect(segments[0].attributes('style')).toContain('width: 50%');
        expect(segments[0].classes()).toContain('bg-ink');
        expect(segments[1].attributes('style')).toContain('width: 25%');
        expect(segments[1].classes()).toContain('bg-volt');
    });

    it('skips empty blocks', () => {
        const wrapper = mount(TierBar, {
            props: { scale: 100, breakdown: [{ from: 1, to: 200, kwh: 50 }, { from: 201, to: null, kwh: 0 }] },
        });

        expect(wrapper.findAll('span')).toHaveLength(1);
    });
});

describe('BuildingTag', () => {
    it('shows the label', () => {
        expect(mount(BuildingTag, { props: { type: 'residential', label: 'Residential' } }).text()).toBe('Residential');
    });

    it('colours residential and commercial differently', () => {
        const residential = mount(BuildingTag, { props: { type: 'residential', label: 'Residential' } });
        const commercial = mount(BuildingTag, { props: { type: 'commercial', label: 'Commercial' } });

        expect(residential.classes()).toContain('text-grid');
        expect(commercial.classes()).not.toContain('text-grid');
    });
});

describe('Pagination', () => {
    const links = [
        { url: null, label: '&laquo; Previous', active: false },
        { url: '/records?page=1', label: '1', active: true },
        { url: '/records?page=2', label: '2', active: false },
        { url: '/records?page=2', label: 'Next &raquo;', active: false },
    ];

    it('renders links and marks the current page', () => {
        const wrapper = mount(Pagination, { props: { links } });
        const anchors = wrapper.findAll('a');

        expect(anchors).toHaveLength(3);
        expect(anchors[0].attributes('aria-current')).toBe('page');
        expect(wrapper.text()).toContain('‹ Previous');
        expect(wrapper.text()).toContain('Next ›');
    });

    it('hides itself when there is only one page', () => {
        const wrapper = mount(Pagination, {
            props: {
                links: [
                    { url: null, label: '&laquo; Previous', active: false },
                    { url: '/records?page=1', label: '1', active: true },
                    { url: null, label: 'Next &raquo;', active: false },
                ],
            },
        });

        expect(wrapper.find('nav').exists()).toBe(false);
    });
});

describe('Combobox', () => {
    const options = [
        { value: 'Johor', label: 'Johor' },
        { value: 'Selangor', label: 'Selangor' },
        { value: 'W.P. Kuala Lumpur', label: 'W.P. Kuala Lumpur' },
    ];

    function mountCombobox(modelValue = '') {
        return mount(Combobox, { props: { id: 'state', options, modelValue, 'onUpdate:modelValue': (v) => wrapper.setProps({ modelValue: v }) } });
    }

    let wrapper;
    const visibleOptions = () => wrapper.findAll('[role="option"]').map((o) => o.text());
    const lastEmitted = () => wrapper.emitted('update:modelValue')?.at(-1)?.[0];

    it('shows the selected label', () => {
        wrapper = mountCombobox('Selangor');

        expect(wrapper.find('input').element.value).toBe('Selangor');
        expect(wrapper.find('input').attributes('aria-expanded')).toBe('false');
    });

    it('filters the options as the user types', async () => {
        wrapper = mountCombobox();

        await wrapper.find('input').setValue('lumpur');

        expect(wrapper.find('input').attributes('aria-expanded')).toBe('true');
        expect(visibleOptions()).toEqual(['W.P. Kuala Lumpur']);
    });

    it('picks the highlighted option with the keyboard', async () => {
        wrapper = mountCombobox();
        const input = wrapper.find('input');

        await input.setValue('o');
        await input.trigger('keydown', { key: 'ArrowDown' });
        await input.trigger('keydown', { key: 'Enter' });

        expect(lastEmitted()).toBe('Selangor');
        expect(input.element.value).toBe('Selangor');
    });

    it('picks an option on click', async () => {
        wrapper = mountCombobox();

        await wrapper.find('input').trigger('click');
        await wrapper.findAll('[role="option"]')[0].trigger('mousedown');

        expect(lastEmitted()).toBe('Johor');
    });

    it('accepts an exactly typed name on blur', async () => {
        wrapper = mountCombobox();

        await wrapper.find('input').setValue('johor');
        await wrapper.find('input').trigger('blur');

        expect(lastEmitted()).toBe('Johor');
        expect(wrapper.find('input').element.value).toBe('Johor');
    });

    it('restores the previous choice when the text matches nothing', async () => {
        wrapper = mountCombobox('Johor');

        await wrapper.find('input').setValue('Atlantis');
        await wrapper.find('input').trigger('blur');

        expect(lastEmitted()).toBeUndefined();
        expect(wrapper.find('input').element.value).toBe('Johor');
    });

    it('clears the value when the text is emptied', async () => {
        wrapper = mountCombobox('Johor');

        await wrapper.find('input').setValue('');
        await wrapper.find('input').trigger('blur');

        expect(lastEmitted()).toBe('');
    });
});
