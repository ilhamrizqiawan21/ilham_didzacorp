<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PaginationLink } from '../../types';

interface Props { links?: PaginationLink[]; }
const props = withDefaults(defineProps<Props>(), { links: () => [] });

function cleanLabel(label: string): string {
    return String(label)
        .replace('&laquo;', '‹')
        .replace('&raquo;', '›')
        .replace(/<[^>]*>/g, '');
}
</script>

<template>
    <nav v-if="props.links.length > 3" aria-label="Navigasi halaman">
        <ul class="pagination mb-0">
            <li
                v-for="(link, index) in props.links"
                :key="`${link.label}-${index}`"
                class="page-item"
                :class="{ active: link.active, disabled: !link.url }"
            >
                <Link
                    v-if="link.url"
                    class="page-link"
                    :href="link.url"
                    preserve-scroll
                >
                    {{ cleanLabel(link.label) }}
                </Link>
                <span v-else class="page-link">{{ cleanLabel(link.label) }}</span>
            </li>
        </ul>
    </nav>
</template>
