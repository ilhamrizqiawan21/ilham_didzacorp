<script setup lang="ts">
defineSlots<{ default?: () => unknown }>();
import { computed } from 'vue';
import InputError from './InputError.vue';
import type { ValidationMessage } from '../../types/forms';

defineOptions({
    inheritAttrs: false,
});

interface SelectOption { value: string | number; label: string; }
interface Props { modelValue?: string | number | boolean; name: string; label?: string; options?: SelectOption[] | Record<string, string>; placeholder?: string; help?: string; error?: ValidationMessage; wrapperClass?: string; required?: boolean; }
const props = withDefaults(defineProps<Props>(), { modelValue: '', label: '', options: () => [], placeholder: '', help: '', error: '', wrapperClass: 'mb-3', required: false });

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const inputId = computed(() => props.name.replaceAll('[', '_').replaceAll(']', '_'));
const helpId = computed(() => props.help ? `${inputId.value}Help` : undefined);
const errorId = computed(() => props.error ? `${inputId.value}Error` : undefined);
const describedBy = computed(() => [helpId.value, errorId.value].filter(Boolean).join(' ') || undefined);
const normalizedOptions = computed<SelectOption[]>(() => Array.isArray(props.options)
    ? props.options
    : Object.entries(props.options).map(([value, label]) => ({ value, label })));
</script>

<template>
    <div :class="wrapperClass">
        <label v-if="label" :for="inputId" class="form-label">
            {{ label }}
            <span v-if="required" class="text-danger">*</span>
        </label>
        <select
            :id="inputId"
            :name="name"
            :value="modelValue"
            :required="required"
            class="form-select"
            :class="{ 'is-invalid': error }"
            :aria-describedby="describedBy"
            :aria-invalid="error ? 'true' : undefined"
            v-bind="$attrs"
            @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
        >
            <option v-if="placeholder" value="">{{ placeholder }}</option>
            <option
                v-for="option in normalizedOptions"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </option>
            <slot />
        </select>
        <InputError :id="errorId" :message="error" />
        <div v-if="help" :id="helpId" class="form-text">{{ help }}</div>
    </div>
</template>
