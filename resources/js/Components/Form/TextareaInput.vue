<script setup lang="ts">
import { computed } from 'vue';
import InputError from './InputError.vue';
import type { ValidationMessage } from '../../types/forms';

defineOptions({
    inheritAttrs: false,
});

interface Props { modelValue?: string; name: string; label?: string; placeholder?: string; help?: string; error?: ValidationMessage; rows?: number; wrapperClass?: string; required?: boolean; }
const props = withDefaults(defineProps<Props>(), { modelValue: '', label: '', placeholder: '', help: '', error: '', rows: 3, wrapperClass: 'mb-3', required: false });

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const inputId = computed(() => props.name.replaceAll('[', '_').replaceAll(']', '_'));
const helpId = computed(() => props.help ? `${inputId.value}Help` : undefined);
const errorId = computed(() => props.error ? `${inputId.value}Error` : undefined);
const describedBy = computed(() => [helpId.value, errorId.value].filter(Boolean).join(' ') || undefined);
</script>

<template>
    <div :class="wrapperClass">
        <label v-if="label" :for="inputId" class="form-label">
            {{ label }}
            <span v-if="required" class="text-danger">*</span>
        </label>
        <textarea
            :id="inputId"
            :name="name"
            :rows="rows"
            :value="modelValue"
            :placeholder="placeholder"
            :required="required"
            class="form-control"
            :class="{ 'is-invalid': error }"
            :aria-describedby="describedBy"
            :aria-invalid="error ? 'true' : undefined"
            v-bind="$attrs"
            @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
        ></textarea>
        <InputError :id="errorId" :message="error" />
        <div v-if="help" :id="helpId" class="form-text">{{ help }}</div>
    </div>
</template>
