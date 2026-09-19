<script setup lang="ts">
import type { PropType } from 'vue';

import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { SelectInput, TextInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Button, Card, DashboardHero } from '../../../Components/UI';

const props = defineProps({
    user: { type: Object as PropType<{ username: string; nama_lengkap: string; email: string | null; role_id: number; nip_nis: string | null; jenis_kelamin: string | null; is_active: boolean; update_url: string } | null>, default: null },
    roles: { type: Array as PropType<{ id: number; nama_role: string }[]>, default: () => [] },
    storeUrl: { type: String, default: null },
});

const isEdit = computed(() => Boolean(props.user));
const pageTitle = computed(() => isEdit.value ? 'Edit Guru dan Staf' : 'Tambah Guru dan Staf');
const form = useForm({
    username: props.user?.username ?? '',
    nama_lengkap: props.user?.nama_lengkap ?? '',
    email: props.user?.email ?? '',
    password: '',
    role_id: props.user?.role_id ? String(props.user.role_id) : '',
    nip_nis: props.user?.nip_nis ?? '',
    jenis_kelamin: props.user?.jenis_kelamin ?? '',
    is_active: props.user?.is_active ?? true,
});

const roleOptions = computed(() => props.roles.map((role) => ({
    value: role.id,
    label: role.nama_role.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase()),
})));

function submit() {
    if (form.processing) {
        return;
    }

    if (props.user) {
        form.put(props.user.update_url, { preserveScroll: true });
        return;
    }

    if (!props.storeUrl) return;
    form.post(props.storeUrl, { preserveScroll: true });
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppShell :title="pageTitle">
        <DashboardHero
            eyebrow="Administrasi Sekolah"
            :title="pageTitle"
            :subtitle="isEdit ? 'Perbarui identitas dan akses akun.' : 'Buat akun guru, staf, atau administrator sekolah.'"
            :icon="isEdit ? 'bi-person-gear' : 'bi-person-plus-fill'"
            tone="admin"
        />

        <form @submit.prevent="submit">
            <Card title="Data Akun" icon="bi-person-vcard">
                <div class="row">
                    <div class="col-md-6">
                        <TextInput v-model="form.username" name="username" label="Username" required :error="form.errors.username" autocomplete="username" />
                    </div>
                    <div class="col-md-6">
                        <TextInput v-model="form.nama_lengkap" name="nama_lengkap" label="Nama Lengkap" required :error="form.errors.nama_lengkap" autocomplete="name" />
                    </div>
                    <div class="col-md-6">
                        <TextInput v-model="form.email" name="email" type="email" label="Email" :error="form.errors.email" autocomplete="email" />
                    </div>
                    <div class="col-md-6">
                        <TextInput
                            v-model="form.password"
                            name="password"
                            type="password"
                            label="Password"
                            :help="isEdit ? 'Kosongkan bila password tidak diubah.' : 'Kosongkan untuk memakai password awal 123456.'"
                            :error="form.errors.password"
                            autocomplete="new-password"
                        />
                    </div>
                    <div class="col-md-4">
                        <SelectInput v-model="form.role_id" name="role_id" label="Role" placeholder="Pilih role" required :options="roleOptions" :error="form.errors.role_id" />
                    </div>
                    <div class="col-md-4">
                        <TextInput v-model="form.nip_nis" name="nip_nis" label="NIP atau ID Staf" :error="form.errors.nip_nis" />
                    </div>
                    <div class="col-md-4">
                        <SelectInput
                            v-model="form.jenis_kelamin"
                            name="jenis_kelamin"
                            label="Jenis Kelamin"
                            placeholder="Pilih jenis kelamin"
                            :options="[{ value: 'L', label: 'Laki-laki' }, { value: 'P', label: 'Perempuan' }]"
                            :error="form.errors.jenis_kelamin"
                        />
                    </div>
                </div>

                <div class="form-check form-switch mt-2">
                    <input id="is_active" v-model="form.is_active" class="form-check-input" type="checkbox">
                    <label class="form-check-label" for="is_active">Akun aktif</label>
                </div>

                <template #footer>
                    <div class="d-flex justify-content-end gap-2">
                        <Button href="/admin/users" color="outline-secondary" icon="bi-arrow-left">Kembali</Button>
                        <Button type="submit" color="success" icon="bi-save" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                        </Button>
                    </div>
                </template>
            </Card>
        </form>
    </AppShell>
</template>
