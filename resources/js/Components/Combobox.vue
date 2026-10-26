<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import Icon from './Icon.vue';

const props = defineProps({
    id: { type: String, required: true },
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, required: true },
    placeholder: { type: String, default: '' },
    invalid: { type: Boolean, default: false },
    describedby: { type: String, default: undefined },
});

const emit = defineEmits(['update:modelValue']);

const input = ref(null);
const list = ref(null);
const open = ref(false);
const typed = ref(false);
const activeIndex = ref(-1);

const listId = computed(() => `${props.id}-listbox`);
const optionId = (i) => `${props.id}-option-${i}`;
const selectedLabel = computed(() => props.options.find((o) => o.value === props.modelValue)?.label ?? '');
const query = ref(selectedLabel.value);

// Show every option until the user types, then only the ones containing the text.
const filtered = computed(() => {
    const needle = query.value.trim().toLowerCase();
    if (!typed.value || needle === '') return props.options;
    return props.options.filter((o) => o.label.toLowerCase().includes(needle));
});

watch(selectedLabel, (label) => {
    query.value = label;
    typed.value = false;
});

watch(activeIndex, async (i) => {
    await nextTick();
    list.value?.querySelector(`#${CSS.escape(optionId(i))}`)?.scrollIntoView?.({ block: 'nearest' });
});

function show() {
    open.value = true;
    activeIndex.value = Math.max(0, filtered.value.findIndex((o) => o.value === props.modelValue));
}

function close() {
    open.value = false;
    activeIndex.value = -1;
}

function choose(option) {
    emit('update:modelValue', option.value);
    query.value = option.label;
    typed.value = false;
    close();
}

function onInput() {
    typed.value = true;
    open.value = true;
    activeIndex.value = filtered.value.length ? 0 : -1;
}

// On leaving the field, keep an exact match, clear on empty text, otherwise restore the last choice.
function commit() {
    const text = query.value.trim().toLowerCase();
    const match = props.options.find((o) => o.label.toLowerCase() === text);
    if (match) choose(match);
    else if (text === '') {
        emit('update:modelValue', '');
        typed.value = false;
        close();
    } else {
        query.value = selectedLabel.value;
        typed.value = false;
        close();
    }
}

function toggle() {
    if (open.value) close();
    else {
        typed.value = false;
        show();
    }
    input.value.focus();
}

function move(step) {
    if (!open.value) return show();
    const count = filtered.value.length;
    if (count) activeIndex.value = (activeIndex.value + step + count) % count;
}

function onKeydown(event) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        move(1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        move(-1);
    } else if (event.key === 'Enter' && open.value && filtered.value[activeIndex.value]) {
        event.preventDefault();
        choose(filtered.value[activeIndex.value]);
    } else if (event.key === 'Escape' && open.value) {
        event.preventDefault();
        query.value = selectedLabel.value;
        typed.value = false;
        close();
    }
}
</script>

<template>
    <div class="relative">
        <input
            :id="id"
            ref="input"
            v-model="query"
            type="text"
            role="combobox"
            autocomplete="off"
            aria-autocomplete="list"
            :aria-expanded="open"
            :aria-controls="listId"
            :aria-activedescendant="open && activeIndex >= 0 ? optionId(activeIndex) : undefined"
            :aria-invalid="invalid"
            :aria-describedby="describedby"
            :placeholder="placeholder"
            class="field-input pr-10"
            @click="open || show()"
            @input="onInput"
            @keydown="onKeydown"
            @blur="commit"
        />
        <button
            type="button"
            tabindex="-1"
            aria-hidden="true"
            class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-ink-soft"
            @mousedown.prevent="toggle"
        >
            <Icon name="chevron" class="transition-transform" :class="{ 'rotate-180': open }" />
        </button>
        <ul
            v-show="open"
            :id="listId"
            ref="list"
            role="listbox"
            class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border border-line bg-white py-1 shadow-lg"
        >
            <li
                v-for="(option, i) in filtered"
                :id="optionId(i)"
                :key="option.value"
                role="option"
                :aria-selected="option.value === modelValue"
                class="cursor-pointer px-3 py-2 text-[15px]"
                :class="{ 'bg-paper': i === activeIndex, 'font-semibold': option.value === modelValue }"
                @mousedown.prevent="choose(option)"
                @mouseenter="activeIndex = i"
            >
                {{ option.label }}
            </li>
            <li v-if="!filtered.length" class="px-3 py-2 text-sm text-ink-soft">No matches</li>
        </ul>
    </div>
</template>
