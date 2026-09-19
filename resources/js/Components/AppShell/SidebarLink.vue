<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { SidebarItem } from '../../types';

interface Props { entry: SidebarItem; active?: boolean; }
withDefaults(defineProps<Props>(), { active: false });
</script>

<template>
    <Link
        v-if="entry.inertia"
        :href="entry.href"
        prefetch="hover"
        class="nav-link"
        :class="{ active }"
    >
        <i class="bi" :class="entry.icon" aria-hidden="true"></i>
        <span>{{ entry.label }}</span>
        <small v-if="entry.hint">{{ entry.hint }}</small>
    </Link>
    <a v-else :href="entry.href" class="nav-link" :class="{ active }">
        <i class="bi" :class="entry.icon" aria-hidden="true"></i>
        <span>{{ entry.label }}</span>
        <small v-if="entry.hint">{{ entry.hint }}</small>
    </a>
</template>
