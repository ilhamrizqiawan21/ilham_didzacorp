<script setup lang="ts">
import type { ValidationMessage } from '../../types/forms';

interface Props { message?: ValidationMessage; }
withDefaults(defineProps<Props>(), { message: '' });
</script>

<template>
    <div v-if="message && (!Array.isArray(message) || message.length)" class="invalid-feedback d-block">
        <ul v-if="Array.isArray(message) && message.length > 1" class="mb-0 ps-3">
            <li v-for="item in message" :key="item">{{ item }}</li>
        </ul>
        <template v-else>{{ Array.isArray(message) ? message[0] : message }}</template>
    </div>
</template>
