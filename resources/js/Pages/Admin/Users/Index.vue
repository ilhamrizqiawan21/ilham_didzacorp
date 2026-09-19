<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { SelectInput, TextInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Card, EmptyState, IconButton, Pagination, TableWrapper } from '../../../Components/UI';
import type { LaravelPaginator } from '../../../types';

interface Role { id: number; nama_role: string }
interface UserRole { nama_role?: string }
interface AdminUser { id: number; username: string; nama_lengkap: string; email?: string | null; role?: UserRole; password_is_default: boolean; password_status: string; is_active: boolean }
interface Filters { search?: string; role_id?: string | number }
interface Props { users?: LaravelPaginator<AdminUser>; roles?: Role[]; filters?: Filters; exportUrl: string }

const props = withDefaults(defineProps<Props>(), { users: () => ({ data: [], links: [], current_page: 1, last_page: 1, per_page: 0, total: 0, from: null, to: null }), roles: () => [], filters: () => ({}) });

const filterForm = reactive({
    search: props.filters.search ?? '',
    role_id: props.filters.role_id ?? '',
});

function roleLabel(role?: string): string {
    return role ? role.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase()) : '-';
}

function passwordStatusColor(isDefault: boolean): string {
    return isDefault ? 'warning text-dark' : 'success';
}

function applyFilters(): void {
    router.get('/admin/users', cleanFilters(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters(): void {
    filterForm.search = '';
    filterForm.role_id = '';
    router.get('/admin/users', {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function cleanFilters(): Record<string, string> {
    return Object.fromEntries(Object.entries(filterForm).filter(([, value]) => value !== '' && value !== null).map(([key, value]) => [key, String(value)]));
}

function exportExcelUrl(): string {
    const params = new URLSearchParams(cleanFilters());
    const query = params.toString();

    return query ? `${props.exportUrl}?${query}` : props.exportUrl;
}

async function toggleActive(user: AdminUser): Promise<void> {
    const action = user.is_active ? 'Nonaktifkan' : 'Aktifkan';
    const confirmed = await window.confirmDialog?.(`${action} user ini?`, {
        title: `${action} User`,
        confirmText: `Ya, ${action.toLowerCase()}`,
    });

    if (!confirmed) {
        return;
    }

    router.post(`/admin/users/${user.id}/toggle-active`, {}, {
        preserveScroll: true,
        preserveState: true,
    });
}

async function resetPassword(user: AdminUser): Promise<void> {
    const confirmed = await window.confirmDialog?.(`Reset password ${user.nama_lengkap} kembali ke 123456?`, {
        title: 'Reset Password',
        confirmText: 'Ya, reset ke 123456',
    });

    if (!confirmed) {
        return;
    }

    router.post(`/admin/users/${user.id}/reset-password`, {}, {
        preserveScroll: true,
        preserveState: true,
    });
}

async function destroy(user: AdminUser): Promise<void> {
    const confirmed = await window.confirmDialog?.('Hapus user ini?', {
        title: 'Hapus User',
        confirmText: 'Ya, hapus',
        danger: true,
    });

    if (!confirmed) {
        return;
    }

    router.delete(`/admin/users/${user.id}`, {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <Head title="Guru dan Staf" />

    <AppShell title="Guru dan Staf">
        <PageHeader
            title="Guru dan Staf"
            subtitle="Kelola akun guru, staf, dan admin sekolah."
            icon="bi-people-fill"
        />

        <Card title="Daftar Guru dan Staf" icon="bi-people-fill">
            <template #actions>
                <a :href="exportExcelUrl()" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel
                </a>
                <Link href="/admin/users/create" class="btn btn-success btn-sm"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Tambah Guru dan Staf</Link>
            </template>

            <form class="row g-2 app-table-filter mb-3" @submit.prevent="applyFilters">
                <div class="col-md-4">
                    <TextInput
                        v-model="filterForm.search"
                        name="search"
                        wrapper-class="mb-0"
                        placeholder="Cari username/nama..."
                    />
                </div>
                <div class="col-md-3">
                    <SelectInput
                        v-model="filterForm.role_id"
                        name="role_id"
                        wrapper-class="mb-0"
                        :options="roles.map((role) => ({ value: role.id, label: roleLabel(role.nama_role) }))"
                        placeholder="Semua Role"
                    />
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-primary w-100" type="submit">
                        <i class="bi bi-search me-1" aria-hidden="true"></i> Cari
                    </button>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-outline-secondary w-100" type="button" @click="resetFilters">
                        <i class="bi bi-x-circle me-1" aria-hidden="true"></i> Reset
                    </button>
                </div>
            </form>

            <TableWrapper v-if="users.data?.length">
                <table class="table table-hover app-table mb-0">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status Password</th>
                            <th class="table-action-column">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id">
                            <td><strong>{{ user.username }}</strong></td>
                            <td>{{ user.nama_lengkap }}</td>
                            <td>{{ user.email ?? '-' }}</td>
                            <td><Badge color="primary">{{ roleLabel(user.role?.nama_role) }}</Badge></td>
                            <td>
                                <Badge :color="passwordStatusColor(user.password_is_default)">
                                    {{ user.password_status }}
                                </Badge>
                            </td>
                            <td class="table-action-column">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <a
                                        :href="`/admin/users/${user.id}/edit`"
                                        class="btn btn-sm btn-warning btn-icon"
                                        :title="`Edit ${user.nama_lengkap}`"
                                        :aria-label="`Edit ${user.nama_lengkap}`"
                                    >
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                    <IconButton
                                        :icon="user.is_active ? 'bi-pause-fill' : 'bi-play-fill'"
                                        :label="`${user.is_active ? 'Nonaktifkan' : 'Aktifkan'} ${user.nama_lengkap}`"
                                        :color="user.is_active ? 'outline-secondary' : 'outline-success'"
                                        @click="toggleActive(user)"
                                    />
                                    <IconButton
                                        icon="bi-key"
                                        :label="`Reset password ${user.nama_lengkap} ke 123456`"
                                        color="outline-warning"
                                        @click="resetPassword(user)"
                                    />
                                    <IconButton
                                        icon="bi-trash"
                                        :label="`Hapus ${user.nama_lengkap}`"
                                        color="outline-danger"
                                        @click="destroy(user)"
                                    />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </TableWrapper>

            <EmptyState v-else title="Tidak ada data guru atau staf" icon="bi-people" />

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                <div class="text-muted small">
                    <template v-if="users.total">
                        Menampilkan {{ users.from }}-{{ users.to }} dari {{ users.total }} data
                    </template>
                </div>
                <Pagination :links="users.links ?? []" />
            </div>
        </Card>
    </AppShell>
</template>
