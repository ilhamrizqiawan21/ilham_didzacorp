<script setup lang="ts">
import { computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import PageHeader from '../../Components/AppShell/PageHeader.vue';
import FileInput from '../../Components/Form/FileInput.vue';
import TextInput from '../../Components/Form/TextInput.vue';
import InputError from '../../Components/Form/InputError.vue';
import AppShell from '../../Layouts/AppShell.vue';
import { Badge, Button, Card } from '../../Components/UI';
interface SiswaInfo { nis?: string; kelas?: string; angkatan?: string; status?: string; tinggal_kelas?: boolean; nomor_whatsapp?: string | null; whatsapp_opt_in?: boolean; phone_required?: boolean; }
interface AccountProfile { username?: string; nama_lengkap?: string; email?: string; nip_nis?: string; jenis_kelamin?: string; created_at?: string; foto_url?: string | null; role?: string; role_label?: string; is_active?: boolean; is_password_default?: boolean; siswa?: SiswaInfo | null; }
interface Props { profile: AccountProfile; updateUrl: string; avatarUpdateUrl: string; avatarDeleteUrl: string; phoneUpdateUrl?: string | null; }
interface PasswordForm { current_password: string; password: string; password_confirmation: string; }
interface AvatarForm { foto: File | File[] | null; }

const props = defineProps<Props>();

const phoneForm = useForm({
    nomor_whatsapp: props.profile.siswa?.nomor_whatsapp ?? '',
    whatsapp_opt_in: props.profile.siswa?.whatsapp_opt_in ?? false,
});

function submitPhone(): void {
    if (!props.phoneUpdateUrl || phoneForm.processing) return;
    phoneForm.put(props.phoneUpdateUrl, {
        preserveScroll: true,
        onSuccess: () => {
            phoneForm.nomor_whatsapp = props.profile.siswa?.nomor_whatsapp ?? '';
            phoneForm.whatsapp_opt_in = props.profile.siswa?.whatsapp_opt_in ?? false;
        },
    });
}

const form = useForm<PasswordForm>({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const avatarForm = useForm<AvatarForm>({
    foto: null,
});

const accountRows = computed(() => [
    ['Username', props.profile.username || '-'],
    ['Nama Lengkap', props.profile.nama_lengkap || '-'],
    ['Email', props.profile.email || '-'],
    ['NIP / NIS', props.profile.nip_nis || '-'],
    ['Jenis Kelamin', props.profile.jenis_kelamin || '-'],
    ['Tanggal Dibuat', props.profile.created_at || '-'],
]);

const siswaRows = computed(() => {
    if (!props.profile.siswa) {
        return [];
    }

    return [
        ['NIS', props.profile.siswa.nis || '-'],
        ['Kelas', props.profile.siswa.kelas || '-'],
        ['Angkatan', props.profile.siswa.angkatan || '-'],
        ['Status Siswa', props.profile.siswa.status || '-'],
        ['Tinggal Kelas', props.profile.siswa.tinggal_kelas ? 'Ya' : 'Tidak'],
    ];
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

function submitAvatar(): void {
    if (avatarForm.processing) {
        return;
    }

    avatarForm.post(props.avatarUpdateUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => avatarForm.reset(),
    });
}

async function deleteAvatar(): Promise<void> {
    const confirmed = await window.confirmDialog?.('Hapus foto profil saat ini?', {
        title: 'Hapus Foto',
        confirmText: 'Ya, hapus',
        danger: true,
    });

    if (!confirmed) {
        return;
    }

    router.delete(props.avatarDeleteUrl, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Pengaturan Akun" />

    <AppShell title="Pengaturan Akun">
        <PageHeader title="Pengaturan Akun" icon="bi-person-gear" />

        <section v-if="profile.role === 'siswa'" class="workspace-panel mb-4" aria-labelledby="phoneTitle">
            <header class="workspace-panel-header">
                <h2 id="phoneTitle" class="h6 mb-0"><i class="bi bi-telephone me-2" aria-hidden="true"></i>Nomor Telepon Siswa</h2>
                <Badge v-if="profile.siswa?.phone_required" color="warning">Wajib dilengkapi</Badge>
            </header>
            <div class="workspace-panel-body">
                <div v-if="!profile.siswa" class="alert alert-warning mb-0" role="alert">Data siswa belum tersedia. Hubungi administrator sekolah.</div>
                <form v-else @submit.prevent="submitPhone">
                    <TextInput
                        v-model="phoneForm.nomor_whatsapp"
                        name="nomor_whatsapp"
                        label="Nomor Telepon / WhatsApp"
                        type="tel"
                        autocomplete="tel"
                        inputmode="tel"
                        maxlength="32"
                        placeholder="08xxxxxxxxxx"
                        required
                        :error="phoneForm.errors.nomor_whatsapp"
                    />
                    <div class="form-check mb-3">
                        <input id="whatsapp_opt_in" v-model="phoneForm.whatsapp_opt_in" type="checkbox" class="form-check-input" required :aria-invalid="phoneForm.errors.whatsapp_opt_in ? 'true' : undefined">
                        <label for="whatsapp_opt_in" class="form-check-label">Saya setuju menerima informasi tugas dan pengingat sekolah melalui WhatsApp. <span class="text-danger">*</span></label>
                        <InputError :message="phoneForm.errors.whatsapp_opt_in" />
                    </div>
                    <Button type="submit" color="success" icon="bi-save" :disabled="phoneForm.processing">{{ phoneForm.processing ? 'Menyimpan...' : 'Simpan Nomor Telepon' }}</Button>
                </form>
            </div>
        </section>

        <div class="row">
            <div class="col-xl-7 mb-4">
                <Card title="Data Akun" icon="bi-info-circle">
                    <div class="account-summary">
                        <div class="account-identity">
                            <img
                                v-if="profile.foto_url"
                                :src="profile.foto_url"
                                :alt="`Foto ${profile.nama_lengkap}`"
                                class="account-avatar"
                            >
                            <div v-else class="account-avatar account-avatar-empty" aria-hidden="true">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div>
                                <div class="account-name">{{ profile.nama_lengkap || '-' }}</div>
                                <div class="account-username">@{{ profile.username || '-' }}</div>
                            </div>
                        </div>
                        <div class="account-badges">
                            <Badge :color="profile.role === 'admin' ? 'primary' : profile.role">
                                {{ profile.role_label || '-' }}
                            </Badge>
                            <Badge :color="profile.is_active ? 'success' : 'danger'">
                                {{ profile.is_active ? 'Aktif' : 'Nonaktif' }}
                            </Badge>
                            <Badge :color="profile.is_password_default ? 'warning' : 'secondary'">
                                {{ profile.is_password_default ? 'Password Default' : 'Password Pribadi' }}
                            </Badge>
                        </div>
                    </div>

                    <form class="avatar-form" @submit.prevent="submitAvatar">
                        <FileInput
                            v-model="avatarForm.foto"
                            name="foto"
                            label="Foto Profil"
                            accept=".jpg,.jpeg,.png,.webp"
                            accept-label=".jpg, .jpeg, .png, .webp"
                            max-size="2MB"
                            wrapper-class="mb-2"
                            :error="avatarForm.errors.foto"
                        />
                        <div class="d-flex flex-wrap gap-2">
                            <Button
                                type="submit"
                                color="primary"
                                icon="bi-upload"
                                :disabled="avatarForm.processing || !avatarForm.foto"
                            >
                                {{ avatarForm.processing ? 'Mengunggah...' : 'Upload Foto' }}
                            </Button>
                            <Button
                                v-if="profile.foto_url"
                                type="button"
                                color="outline-danger"
                                icon="bi-trash"
                                @click="deleteAvatar"
                            >
                                Hapus Foto
                            </Button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 profile-table">
                            <tbody>
                                <tr v-for="[label, value] in accountRows" :key="label">
                                    <td>{{ label }}</td>
                                    <td>{{ value }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>

                <Card
                    v-if="siswaRows.length"
                    title="Data Siswa"
                    icon="bi-mortarboard-fill"
                    class="mt-4"
                >
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 profile-table">
                            <tbody>
                                <tr v-for="[label, value] in siswaRows" :key="label">
                                    <td>{{ label }}</td>
                                    <td>{{ value }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <div class="col-xl-5 mb-4">
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
                            label="Konfirmasi Password Baru"
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
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Password' }}
                        </Button>
                    </form>
                </Card>
            </div>
        </div>
    </AppShell>
</template>

<style scoped>
.account-summary {
    display: flex;
    gap: 1rem;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.account-identity {
    align-items: center;
    display: flex;
    gap: 0.85rem;
}

.account-avatar {
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    height: 64px;
    object-fit: cover;
    width: 64px;
}

.account-avatar-empty {
    align-items: center;
    background: var(--gray-100);
    color: var(--gray-500);
    display: inline-flex;
    font-size: 1.8rem;
    justify-content: center;
}

.avatar-form {
    border-bottom: 1px solid var(--gray-100);
    margin-bottom: 1rem;
    padding-bottom: 1rem;
}

.account-name {
    color: var(--gray-900);
    font-size: 1.1rem;
    font-weight: 700;
}

.account-username {
    color: var(--gray-500);
    font-size: 0.9rem;
}

.account-badges {
    align-items: flex-start;
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    justify-content: flex-end;
}

.profile-table td:first-child {
    color: var(--gray-500);
    width: 160px;
}

@media (max-width: 575.98px) {
    .account-summary {
        flex-direction: column;
    }

    .account-badges {
        justify-content: flex-start;
    }

    .profile-table td:first-child {
        width: 130px;
    }
}
</style>
