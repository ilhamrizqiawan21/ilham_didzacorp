<script setup lang="ts">
import { computed, markRaw } from 'vue';
import InputError from './InputError.vue';
import type { ValidationMessage } from '../../types/forms';

defineOptions({
    inheritAttrs: false,
});

interface Props { modelValue?: File | File[] | string | null; name: string; label?: string; help?: string; acceptLabel?: string; maxSize?: string; error?: ValidationMessage; wrapperClass?: string; required?: boolean; multiple?: boolean; }
const props = withDefaults(defineProps<Props>(), { modelValue: null, label: '', help: '', acceptLabel: '', maxSize: '', error: '', wrapperClass: 'mb-3', required: false, multiple: false });

const emit = defineEmits<{ 'update:modelValue': [value: File | File[] | null] }>();
const inputId = computed(() => props.name.replaceAll('[', '_').replaceAll(']', '_'));
const meta = computed(() => [props.acceptLabel, props.maxSize ? `Maks. ${props.maxSize}` : ''].filter(Boolean).join(' | '));
const helpText = computed(() => props.help || meta.value);
const helpId = computed(() => helpText.value ? `${inputId.value}Help` : undefined);
const errorId = computed(() => props.error ? `${inputId.value}Error` : undefined);
const describedBy = computed(() => [helpId.value, errorId.value].filter(Boolean).join(' ') || undefined);

function updateFileValue(event: Event) {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []).map((file) => markRaw(file));
    emit('update:modelValue', props.multiple ? files : (files[0] ?? null));
}
</script>

<template>
    <div :class="wrapperClass">
        <label v-if="label" :for="inputId" class="form-label">
            {{ label }}
            <span v-if="required" class="text-danger">*</span>
        </label>
        <div v-if="meta" class="form-file-meta">{{ meta }}</div>
        <input
            :id="inputId"
            type="file"
            :name="name"
            :required="required"
            class="form-control"
            :class="{ 'is-invalid': error }"
            :aria-describedby="describedBy"
            :aria-invalid="error ? 'true' : undefined"
            :multiple="multiple"
            v-bind="$attrs"
            @change="updateFileValue"
        >
        <InputError :id="errorId" :message="error" />
        <div v-if="helpText" :id="helpId" class="form-text">{{ helpText }}</div>
    </div>
</template>
