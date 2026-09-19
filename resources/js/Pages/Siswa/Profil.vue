<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PageHeader from '../../Components/AppShell/PageHeader.vue';
import TextInput from '../../Components/Form/TextInput.vue';
import AppShell from '../../Layouts/AppShell.vue';
import { Badge, Button, Card } from '../../Components/UI';
interface StudentProfile { nis: string; nama_lengkap: string; username: string; kelas: string; status: string; }
interface Props { profile: StudentProfile; updateUrl: string; }
interface PasswordForm { current_password: string; password: string; password_confirmation: string; }

const props = defineProps<Props>();

const form = useForm<PasswordForm>({
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
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Profil" />

    <AppShell title="Profil">
        <PageHeader
            title="Profil Siswa"
            icon="bi-person-circle"
        />

        <div class="row">
            <div class="col-lg-6 mb-4">
                <Card title="Informasi Saya" icon="bi-info-circle">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 profile-table">
                            <tbody>
                                <tr>
                                    <td>NIS</td>
                                    <td><strong>{{ profile.nis }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Nama</td>
                                    <td>{{ profile.nama_lengkap }}</td>
                                </tr>
                                <tr>
                                    <td>Username</td>
                                    <td>{{ profile.username }}</td>
                                </tr>
                                <tr>
                                    <td>Kelas</td>
                                    <td>{{ profile.kelas }}</td>
                                </tr>
                                <tr>
                                    <td>Status</td>
                                    <td>
                                        <Badge :color="profile.status === 'aktif' ? 'success' : 'secondary'">
                                            {{ profile.status === 'aktif' ? 'Aktif' : profile.status }}
                                        </Badge>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <div class="col-lg-6 mb-4">
                <Card title="Ganti Password" icon="bi-key-fill">
                    <form @submit.prevent="submit">
                        <TextInput
                            v-model="form.current_password"
                            name="current_password"
                            label="Password Saat Ini"
                            type="password"
                            autocomplete="current-password"
                            required
                            :error="form.errors.current_password"
                        />

                        <TextInput
                            v-model="form.password"
                            name="password"
                            label="Password Baru"
                            type="password"
                            autocomplete="new-password"
                            minlength="8"
                            required
                            :error="form.errors.password"
                        />

                        <TextInput
                            v-model="form.password_confirmation"
                            name="password_confirmation"
                            label="Konfirmasi Password"
                            type="password"
                            autocomplete="new-password"
                            required
                            :error="form.errors.password_confirmation"
                        />

                        <Button
                            type="submit"
                            color="success"
                            icon="bi-save"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Ganti Password' }}
                        </Button>
                    </form>
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
