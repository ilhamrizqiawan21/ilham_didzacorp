<script setup lang="ts">
import type { PropType } from 'vue';
import type { SelectOption } from '../../types/ui';
import type { ValidationMessage } from '../../types/forms';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import InputError from './InputError.vue';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps({
    modelValue: { type: [String, Number, Boolean], default: '' },
    name: { type: String, required: true },
    label: { type: String, default: '' },
    options: { type: [Array, Object] as PropType<SelectOption[] | Record<string, string>>, default: () => [] },
    placeholder: { type: String, default: 'Pilih...' },
    searchPlaceholder: { type: String, default: 'Cari...' },
    emptyText: { type: String, default: 'Tidak ada pilihan' },
    help: { type: String, default: '' },
    error: { type: [String, Array] as PropType<ValidationMessage>, default: '' },
    wrapperClass: { type: String, default: 'mb-3' },
    required: { type: Boolean, default: false },
    clearable: { type: Boolean, default: true },
});

const emit = defineEmits<{ 'update:modelValue': [value: string | number | boolean] }>();

const open = ref(false);
const query = ref('');
const root = ref<HTMLElement | null>(null);
const searchInput = ref<HTMLInputElement | null>(null);
const activeIndex = ref(-1);

const inputId = computed(() => props.name.replaceAll('[', '_').replaceAll(']', '_'));
const helpId = computed(() => props.help ? `${inputId.value}Help` : undefined);
const errorId = computed(() => props.error ? `${inputId.value}Error` : undefined);
const describedBy = computed(() => [helpId.value, errorId.value].filter(Boolean).join(' ') || undefined);
const listboxId = computed(() => `${inputId.value}Listbox`);
const activeOptionId = computed(() => {
    if (activeIndex.value < 0) return undefined;
    if (props.clearable && activeIndex.value === 0) return `${listboxId.value}Clear`;
    const option = filteredOptions.value[activeIndex.value - (props.clearable ? 1 : 0)];
    return option ? optionId(option) : undefined;
});

const normalizedOptions = computed(() => Array.isArray(props.options)
    ? props.options
    : Object.entries(props.options).map(([value, label]) => ({ value, label })));

const selectedOption = computed(() => normalizedOptions.value.find((option) => String(option.value) === String(props.modelValue)));

const filteredOptions = computed(() => {
    const term = query.value.trim().toLowerCase();

    if (!term) {
        return normalizedOptions.value;
    }

    return normalizedOptions.value.filter((option) => String(option.label).toLowerCase().includes(term));
});

watch(open, async (value) => {
    if (value) {
        query.value = '';
        activeIndex.value = -1;
        await nextTick();
        searchInput.value?.focus();
    }
});

function toggle() {
    open.value = !open.value;
}

function close() {
    open.value = false;
}

watch(query, () => { activeIndex.value = -1; });

function selectOption(option: SelectOption) {
    emit('update:modelValue', option.value);
    close();
}

function optionId(option: SelectOption) {
    return `${listboxId.value}-${String(option.value).replaceAll(/[^a-zA-Z0-9_-]/g, '-')}`;
}

function moveActive(direction: number) {
    const offset = props.clearable ? 1 : 0;
    const count = filteredOptions.value.length + offset;
    if (!count) return;
    activeIndex.value = activeIndex.value < 0
        ? (direction > 0 ? 0 : count - 1)
        : (activeIndex.value + direction + count) % count;
}

async function openAndMove(direction: number) {
    open.value = true;
    await nextTick();
    moveActive(direction);
}

function selectActive() {
    if (props.clearable && activeIndex.value === 0) return clearValue();
    const option = filteredOptions.value[activeIndex.value - (props.clearable ? 1 : 0)];
    if (option) selectOption(option);
}

function clearValue() {
    emit('update:modelValue', '');
    close();
}

function handleOutsideClick(event: MouseEvent) {
    if (root.value && event.target instanceof Node && !root.value.contains(event.target)) {
        close();
    }
}

onMounted(() => document.addEventListener('click', handleOutsideClick));

onBeforeUnmount(() => {
    document.removeEventListener('click', handleOutsideClick);
});
</script>

<template>
    <div ref="root" :class="wrapperClass">
        <label v-if="label" :for="inputId" class="form-label">
            {{ label }}
            <span v-if="required" class="text-danger">*</span>
        </label>

        <div class="searchable-select">
            <button
                :id="inputId"
                type="button"
                class="form-select searchable-select-toggle"
                :class="{ 'is-invalid': error }"
                :aria-expanded="open ? 'true' : 'false'"
                :aria-controls="listboxId"
                aria-haspopup="listbox"
                :aria-describedby="describedBy"
                :aria-invalid="error ? 'true' : undefined"
                v-bind="$attrs"
                @click.stop="toggle"
                @keydown.down.prevent="openAndMove(1)"
                @keydown.up.prevent="openAndMove(-1)"
                @keydown.enter.prevent="open ? selectActive() : toggle()"
                @keydown.esc.prevent="close"
            >
                <span :class="{ 'text-muted': !selectedOption }">
                    {{ selectedOption?.label ?? placeholder }}
                </span>
            </button>

            <input
                type="hidden"
                :name="name"
                :value="modelValue"
                :required="required"
            />

            <div v-if="open" class="searchable-select-menu" @click.stop>
                <input
                    ref="searchInput"
                    v-model="query"
                    type="search"
                    class="form-control form-control-sm"
                    :placeholder="searchPlaceholder"
                    role="combobox"
                    :aria-expanded="open ? 'true' : 'false'"
                    :aria-controls="listboxId"
                    :aria-activedescendant="activeOptionId"
                    @keydown.esc.prevent="close"
                    @keydown.down.prevent="moveActive(1)"
                    @keydown.up.prevent="moveActive(-1)"
                    @keydown.enter.prevent="selectActive"
                />

                <div :id="listboxId" class="searchable-select-options" role="listbox">
                    <button
                        v-if="clearable"
                        type="button"
                        class="searchable-select-option text-muted"
                        :class="{ active: activeIndex === 0 }"
                        :id="`${listboxId}Clear`"
                        role="option"
                        :aria-selected="String(modelValue) === ''"
                        @click="clearValue"
                    >
                        {{ placeholder }}
                    </button>
                    <button
                        v-for="(option, index) in filteredOptions"
                        :key="String(option.value)"
                        type="button"
                        class="searchable-select-option"
                        :class="{ active: String(option.value) === String(modelValue) || activeIndex === index + (clearable ? 1 : 0) }"
                        :id="optionId(option)"
                        role="option"
                        :aria-selected="String(option.value) === String(modelValue)"
                        @click="selectOption(option)"
                    >
                        {{ option.label }}
                    </button>
                    <div v-if="filteredOptions.length === 0" class="searchable-select-empty">
                        {{ emptyText }}
                    </div>
                </div>
            </div>
        </div>

        <InputError :id="errorId" :message="error" />
        <div v-if="help" :id="helpId" class="form-text">{{ help }}</div>
    </div>
</template>
