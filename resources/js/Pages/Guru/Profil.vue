<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PageHeader from '../../Components/AppShell/PageHeader.vue';
import TextInput from '../../Components/Form/TextInput.vue';
import AppShell from '../../Layouts/AppShell.vue';
import { Badge, Button, Card } from '../../Components/UI';
interface GuruProfile { username: string; nama_lengkap: string; nip_nis?: string | null; role_label: string; is_active: boolean; }
interface Props { profile: GuruProfile; updateUrl: string; }
interface ProfileForm { username: string; nama_lengkap: string; nip_nis: string; current_password: string; password: string; password_confirmation: string; }

const props = defineProps<Props>();

const form = useForm<ProfileForm>({
    username: props.profile.username ?? '',
    nama_lengkap: props.profile.nama_lengkap ?? '',
    nip_nis: props.profile.nip_nis ?? '',
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submit(): void {
    if (form.processing) {
        return;
    }

    form.put(props.updateUrl, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('current_password', 'password', 'password_confirmation');
        },
    });
}
</script>

<template>
    <Head title="Profil" />

    <AppShell title="Profil">
        <PageHeader title="Profil Guru" icon="bi-person-circle" />

        <div class="row">
            <div class="col-lg-6 mb-4">
                <Card title="Edit Profil" icon="bi-pencil-square">
                    <form @submit.prevent="submit">
                        <TextInput
                            v-model="form.username"
                            name="username"
                            label="Username"
                            required
                            :error="form.errors.username"
                        />

                        <TextInput
                            v-model="form.nama_lengkap"
                            name="nama_lengkap"
                            label="Nama Lengkap"
                            required
                            :error="form.errors.nama_lengkap"
                        />

                        <TextInput
                            v-model="form.nip_nis"
                            name="nip_nis"
                            label="NIP / NIK"
                            :error="form.errors.nip_nis"
                        />

                        <hr>

                        <TextInput
                            v-model="form.current_password"
                            name="current_password"
                            label="Password Saat Ini"
                            type="password"
                            placeholder="Wajib bila mengganti password"
                            autocomplete="current-password"
                            :error="form.errors.current_password"
                        />

                        <TextInput
                            v-model="form.password"
                            name="password"
                            label="Password Baru"
                            type="password"
                            placeholder="Kosongkan jika tidak ingin mengubah"
                            minlength="8"
                            autocomplete="new-password"
                            :error="form.errors.password"
                        />

                        <TextInput
                            v-model="form.password_confirmation"
                            name="password_confirmation"
                            label="Konfirmasi Password Baru"
                            type="password"
                            placeholder="Ketik ulang password"
                            autocomplete="new-password"
                            :error="form.errors.password_confirmation"
                        />

                        <Button
                            type="submit"
                            color="success"
                            icon="bi-save"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </Button>
                    </form>
                </Card>
            </div>

            <div class="col-lg-6 mb-4">
                <Card title="Informasi Akun" icon="bi-info-circle">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 profile-table">
                            <tbody>
                                <tr>
                                    <td>Username</td>
                                    <td><strong>{{ profile.username }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Nama</td>
                                    <td>{{ profile.nama_lengkap }}</td>
                                </tr>
                                <tr>
                                    <td>Role</td>
                                    <td><Badge color="guru">{{ profile.role_label }}</Badge></td>
                                </tr>
                                <tr>
                                    <td>NIP/NIK</td>
                                    <td>{{ profile.nip_nis || '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Status</td>
                                    <td>
                                        <Badge :color="profile.is_active ? 'success' : 'danger'">
                                            {{ profile.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </Badge>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </div>
    </AppShell>
</template>

<style scoped>
.profile-table td:first-child {
    width: 120px;
    color: var(--gray-500);
}
</style>
