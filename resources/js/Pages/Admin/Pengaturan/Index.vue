<script setup lang="ts">
import type { PropType } from 'vue';
type SchoolField = 'school_name' | 'school_short_name' | 'address' | 'village' | 'district' | 'city' | 'province' | 'postal_code' | 'phone' | 'whatsapp' | 'email' | 'website' | 'npsn' | 'nsm' | 'accreditation' | 'school_status' | 'principal_name' | 'principal_nip' | 'principal_nuptk' | 'foundation_name' | 'school_year' | 'semester' | 'vision' | 'mission' | 'motto' | 'logo_url' | 'favicon_url';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, MetricStrip, QuickActionBar } from '../../../Components/UI';
import { FileInput, SelectInput, TextareaInput, TextInput } from '../../../Components/Form';

const props = defineProps({
    settings: { type: Object as PropType<Partial<Record<'warna_tema' | 'semester_aktif' | 'mode_kenaikan' | 'penalty_terlambat_poin' | 'whatsapp_template_tugas_terlambat', string>>>, default: () => ({}) },
    tahunAjaranAktif: { type: Object as PropType<{ tahun: string } | null>, default: null },
    schoolSetting: { type: Object as PropType<Record<SchoolField, string | null>>, required: true },
    urls: { type: Object as PropType<Record<'tahun_ajaran' | 'blocked_ips' | 'save_system' | 'save_school', string>>, required: true },
});

const themeOptions = [
    { value: 'hijau', label: 'Hijau Sekolah', note: 'Tenang, edukatif, mudah dibaca.', primary: '#198754', sidebar: '#166534', accent: '#0d6efd' },
    { value: 'biru-azure', label: 'Biru Akademik', note: 'Formal dan bersih untuk institusi.', primary: '#0d6efd', sidebar: '#1d4ed8', accent: '#22c55e' },
    { value: 'biru-aqua', label: 'Aqua Modern', note: 'Segar dan ringan.', primary: '#0891b2', sidebar: '#0e7490', accent: '#14b8a6' },
    { value: 'indigo', label: 'Indigo Digital', note: 'Tegas dengan nuansa teknologi.', primary: '#4f46e5', sidebar: '#3730a3', accent: '#06b6d4' },
    { value: 'marun', label: 'Marun Prestasi', note: 'Hangat dan berwibawa.', primary: '#be123c', sidebar: '#881337', accent: '#f59e0b' },
];

const systemForm = useForm({
    warna_tema: props.settings.warna_tema ?? 'hijau',
    semester_aktif: props.settings.semester_aktif ?? '1',
    mode_kenaikan: props.settings.mode_kenaikan ?? 'manual',
    penalty_terlambat_poin: props.settings.penalty_terlambat_poin ?? '1',
    whatsapp_template_tugas_terlambat: props.settings.whatsapp_template_tugas_terlambat ?? '',
});

const schoolForm = useForm({
    school_name: props.schoolSetting.school_name ?? '',
    school_short_name: props.schoolSetting.school_short_name ?? '',
    address: props.schoolSetting.address ?? '',
    village: props.schoolSetting.village ?? '',
    district: props.schoolSetting.district ?? '',
    city: props.schoolSetting.city ?? '',
    province: props.schoolSetting.province ?? '',
    postal_code: props.schoolSetting.postal_code ?? '',
    phone: props.schoolSetting.phone ?? '',
    whatsapp: props.schoolSetting.whatsapp ?? '',
    email: props.schoolSetting.email ?? '',
    website: props.schoolSetting.website ?? '',
    npsn: props.schoolSetting.npsn ?? '',
    nsm: props.schoolSetting.nsm ?? '',
    accreditation: props.schoolSetting.accreditation ?? '',
    school_status: props.schoolSetting.school_status ?? '',
    principal_name: props.schoolSetting.principal_name ?? '',
    principal_nip: props.schoolSetting.principal_nip ?? '',
    principal_nuptk: props.schoolSetting.principal_nuptk ?? '',
    foundation_name: props.schoolSetting.foundation_name ?? '',
    school_year: props.schoolSetting.school_year ?? '',
    semester: props.schoolSetting.semester ?? 'Ganjil',
    vision: props.schoolSetting.vision ?? '',
    mission: props.schoolSetting.mission ?? '',
    motto: props.schoolSetting.motto ?? '',
    logo: null as File | null,
    favicon: null as File | null,
});

const metrics = computed(() => [
    { label: 'Tema', value: themeOptions.find((theme) => theme.value === systemForm.warna_tema)?.label ?? 'Default', icon: 'bi-palette', tone: 'primary' },
    { label: 'Semester LMS', value: `Semester ${systemForm.semester_aktif}`, icon: 'bi-calendar3', tone: 'info' },
    { label: 'Tahun Ajaran', value: props.tahunAjaranAktif?.tahun ?? 'Belum diatur', icon: 'bi-calendar-check', tone: 'success', href: props.urls.tahun_ajaran },
    { label: 'Mode Kenaikan', value: systemForm.mode_kenaikan === 'auto' ? 'Otomatis' : 'Manual', icon: 'bi-arrow-up-circle', tone: 'warning' },
    { label: 'Penalti terlambat', value: `${systemForm.penalty_terlambat_poin || 0} poin/hari`, icon: 'bi-clock-history', tone: 'danger' },
]);

const quickActions = [
    { label: 'Tahun Ajaran', href: props.urls.tahun_ajaran, icon: 'bi-calendar-event', color: 'light' },
    { label: 'IP Diblokir', href: props.urls.blocked_ips, icon: 'bi-shield-fill-x', color: 'light' },
    { label: 'Log Login', href: '/admin/log-login', icon: 'bi-clock-history', color: 'light' },
];

function saveSystem() {
    if (systemForm.processing) {
        return;
    }

    systemForm.post(props.urls.save_system, {
        preserveScroll: true,
    });
}

function saveSchool() {
    if (schoolForm.processing) {
        return;
    }

    schoolForm
        .transform((data) => ({
            ...data,
            _method: 'PUT',
        }))
        .post(props.urls.save_school, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                schoolForm.logo = null;
                schoolForm.favicon = null;
            },
        });
}
</script>

<template>
    <Head title="Pengaturan Sistem" />

    <AppShell title="Pengaturan Sistem">
        <PageHeader
            title="Pengaturan Sistem"
            subtitle="Kelola identitas sekolah, tampilan LMS, dan konfigurasi akademik."
            icon="bi-gear-fill"
        >
            <template #actions>
                <QuickActionBar :actions="quickActions" />
            </template>
        </PageHeader>

        <MetricStrip :items="metrics" />

        <form @submit.prevent="saveSchool">
            <div class="dashboard-grid settings-grid">
                <section class="workspace-panel settings-main">
                    <header class="workspace-panel-header">
                        <span class="workspace-panel-title">
                            <i class="bi bi-buildings-fill" aria-hidden="true"></i>
                            Identitas Sekolah
                        </span>
                        <Button type="submit" color="primary" icon="bi-save" :disabled="schoolForm.processing">
                            {{ schoolForm.processing ? 'Menyimpan...' : 'Simpan Sekolah' }}
                        </Button>
                    </header>
                    <div class="workspace-panel-body">
                        <div class="row">
                            <div class="col-md-8">
                                <TextInput v-model="schoolForm.school_name" name="school_name" label="Nama Sekolah" required :error="schoolForm.errors.school_name" />
                            </div>
                            <div class="col-md-4">
                                <TextInput v-model="schoolForm.school_short_name" name="school_short_name" label="Nama Singkat" required :error="schoolForm.errors.school_short_name" />
                            </div>
                            <div class="col-12">
                                <TextInput v-model="schoolForm.address" name="address" label="Alamat" required :error="schoolForm.errors.address" />
                            </div>
                            <div class="col-md-6">
                                <TextInput v-model="schoolForm.village" name="village" label="Desa/Kelurahan" :error="schoolForm.errors.village" />
                            </div>
                            <div class="col-md-6">
                                <TextInput v-model="schoolForm.district" name="district" label="Kecamatan" :error="schoolForm.errors.district" />
                            </div>
                            <div class="col-md-4">
                                <TextInput v-model="schoolForm.city" name="city" label="Kota/Kabupaten" :error="schoolForm.errors.city" />
                            </div>
                            <div class="col-md-4">
                                <TextInput v-model="schoolForm.province" name="province" label="Provinsi" :error="schoolForm.errors.province" />
                            </div>
                            <div class="col-md-4">
                                <TextInput v-model="schoolForm.postal_code" name="postal_code" label="Kode Pos" :error="schoolForm.errors.postal_code" />
                            </div>
                        </div>
                    </div>
                </section>

                <aside class="settings-side">
                    <Card title="Brand Preview" icon="bi-image-fill">
                        <div class="brand-preview">
                            <div class="brand-preview-logo">
                                <img v-if="schoolSetting.logo_url" :src="schoolSetting.logo_url" alt="Logo sekolah" width="72" height="72" decoding="async">
                                <i v-else class="bi bi-buildings" aria-hidden="true"></i>
                            </div>
                            <div>
                                <strong>{{ schoolForm.school_short_name || '-' }}</strong>
                                <span>{{ schoolForm.school_name || '-' }}</span>
                            </div>
                        </div>
                        <FileInput v-model="schoolForm.logo" name="logo" label="Logo" accept=".jpg,.jpeg,.png,.webp" accept-label="JPG, PNG, WEBP" max-size="2MB" :error="schoolForm.errors.logo" />
                        <div class="favicon-preview">
                            <img v-if="schoolSetting.favicon_url" :src="schoolSetting.favicon_url" alt="Favicon sekolah" width="28" height="28" decoding="async">
                            <i v-else class="bi bi-bookmark-star" aria-hidden="true"></i>
                            <FileInput v-model="schoolForm.favicon" name="favicon" label="Favicon" accept=".ico,.png,.jpg,.jpeg,.webp" accept-label="ICO, PNG, JPG, WEBP" max-size="1MB" :error="schoolForm.errors.favicon" wrapper-class="mb-0 flex-fill" />
                        </div>
                    </Card>
                </aside>
            </div>

            <div class="dashboard-grid settings-grid">
                <section class="workspace-panel settings-main">
                    <header class="workspace-panel-header">
                        <span class="workspace-panel-title">
                            <i class="bi bi-person-badge-fill" aria-hidden="true"></i>
                            Legal dan Kepala Sekolah
                        </span>
                    </header>
                    <div class="workspace-panel-body">
                        <div class="row">
                            <div class="col-md-6"><TextInput v-model="schoolForm.npsn" name="npsn" label="NPSN" :error="schoolForm.errors.npsn" /></div>
                            <div class="col-md-6"><TextInput v-model="schoolForm.nsm" name="nsm" label="NSM" :error="schoolForm.errors.nsm" /></div>
                            <div class="col-md-6"><TextInput v-model="schoolForm.accreditation" name="accreditation" label="Akreditasi" :error="schoolForm.errors.accreditation" /></div>
                            <div class="col-md-6"><TextInput v-model="schoolForm.school_status" name="school_status" label="Status Sekolah" :error="schoolForm.errors.school_status" /></div>
                            <div class="col-md-6"><TextInput v-model="schoolForm.principal_name" name="principal_name" label="Nama Kepala Sekolah" required :error="schoolForm.errors.principal_name" /></div>
                            <div class="col-md-3"><TextInput v-model="schoolForm.principal_nip" name="principal_nip" label="NIP" :error="schoolForm.errors.principal_nip" /></div>
                            <div class="col-md-3"><TextInput v-model="schoolForm.principal_nuptk" name="principal_nuptk" label="NUPTK" :error="schoolForm.errors.principal_nuptk" /></div>
                            <div class="col-12"><TextInput v-model="schoolForm.foundation_name" name="foundation_name" label="Nama Yayasan" :error="schoolForm.errors.foundation_name" /></div>
                        </div>
                    </div>
                </section>

                <aside class="settings-side">
                    <Card title="Kontak" icon="bi-telephone-fill">
                        <TextInput v-model="schoolForm.phone" name="phone" label="Telepon" :error="schoolForm.errors.phone" />
                        <TextInput v-model="schoolForm.whatsapp" name="whatsapp" label="WhatsApp" :error="schoolForm.errors.whatsapp" />
                        <TextInput v-model="schoolForm.email" type="email" name="email" label="Email" :error="schoolForm.errors.email" />
                        <TextInput v-model="schoolForm.website" type="url" name="website" label="Website" placeholder="https://example.sch.id" :error="schoolForm.errors.website" wrapper-class="mb-0" />
                    </Card>
                </aside>
            </div>

            <div class="dashboard-grid settings-grid">
                <section class="workspace-panel settings-main">
                    <header class="workspace-panel-header">
                        <span class="workspace-panel-title">
                            <i class="bi bi-flag-fill" aria-hidden="true"></i>
                            Profil Singkat
                        </span>
                    </header>
                    <div class="workspace-panel-body">
                        <TextareaInput v-model="schoolForm.vision" name="vision" label="Visi" :rows="3" :error="schoolForm.errors.vision" />
                        <TextareaInput v-model="schoolForm.mission" name="mission" label="Misi" :rows="4" :error="schoolForm.errors.mission" />
                        <TextInput v-model="schoolForm.motto" name="motto" label="Motto" :error="schoolForm.errors.motto" wrapper-class="mb-0" />
                    </div>
                </section>

                <aside class="settings-side">
                    <Card title="Akademik Sekolah" icon="bi-calendar-check-fill">
                        <TextInput v-model="schoolForm.school_year" name="school_year" label="Tahun Ajaran" required :error="schoolForm.errors.school_year" />
                        <SelectInput
                            v-model="schoolForm.semester"
                            name="semester"
                            label="Semester"
                            required
                            :options="[{ value: 'Ganjil', label: 'Ganjil' }, { value: 'Genap', label: 'Genap' }]"
                            :error="schoolForm.errors.semester"
                            wrapper-class="mb-0"
                        />
                    </Card>
                    <Button type="submit" color="primary" icon="bi-save" class="w-100" :disabled="schoolForm.processing">
                        {{ schoolForm.processing ? 'Menyimpan...' : 'Simpan Pengaturan Sekolah' }}
                    </Button>
                </aside>
            </div>
        </form>

        <section class="workspace-panel">
            <header class="workspace-panel-header">
                <span class="workspace-panel-title">
                    <i class="bi bi-sliders" aria-hidden="true"></i>
                    Pengaturan Sistem
                </span>
                <Button type="button" color="primary" icon="bi-save" :disabled="systemForm.processing" @click="saveSystem">
                    {{ systemForm.processing ? 'Menyimpan...' : 'Simpan Sistem' }}
                </Button>
            </header>
            <div class="workspace-panel-body">
                <div class="theme-option-grid">
                    <label
                        v-for="theme in themeOptions"
                        :key="theme.value"
                        class="theme-option"
                        :class="{ 'is-active': systemForm.warna_tema === theme.value }"
                    >
                        <input v-model="systemForm.warna_tema" class="form-check-input" type="radio" name="warna_tema" :value="theme.value">
                        <span class="theme-option-preview" aria-hidden="true">
                            <span class="theme-option-sidebar" :style="{ background: theme.sidebar }"></span>
                            <span class="theme-option-screen">
                                <span :style="{ background: theme.primary }"></span>
                                <span :style="{ background: theme.accent }"></span>
                            </span>
                        </span>
                        <span class="theme-option-body">
                            <span class="theme-option-title">
                                {{ theme.label }}
                                <Badge v-if="theme.value === 'hijau'" color="primary">Default</Badge>
                            </span>
                            <span class="theme-option-note">{{ theme.note }}</span>
                        </span>
                    </label>
                </div>

                <div class="row mt-3">
                    <div class="col-md-4">
                        <SelectInput
                            v-model="systemForm.semester_aktif"
                            name="semester_aktif"
                            label="Semester Aktif LMS"
                            :options="[{ value: '1', label: 'Semester 1 (Ganjil)' }, { value: '2', label: 'Semester 2 (Genap)' }]"
                            :error="systemForm.errors.semester_aktif"
                        />
                    </div>
                    <div class="col-md-4">
                        <TextInput
                            :model-value="tahunAjaranAktif?.tahun ?? 'Belum diatur'"
                            name="tahun_ajaran_aktif"
                            label="Tahun Ajaran Aktif"
                            readonly
                            help="Ganti tahun ajaran melalui menu Tahun Ajaran."
                        />
                    </div>
                    <div class="col-md-4">
                        <SelectInput
                            v-model="systemForm.mode_kenaikan"
                            name="mode_kenaikan"
                            label="Mode Kenaikan Kelas"
                            :options="[{ value: 'manual', label: 'Manual' }, { value: 'auto', label: 'Otomatis' }]"
                            :error="systemForm.errors.mode_kenaikan"
                        />
                    </div>
                    <div class="col-md-4">
                        <TextInput
                            v-model="systemForm.penalty_terlambat_poin"
                            type="number"
                            name="penalty_terlambat_poin"
                            label="Penalti Tugas Terlambat (per hari)"
                            min="0"
                            max="100"
                            step="0.01"
                            help="Jumlah poin yang dipotong dari nilai untuk setiap hari keterlambatan (contoh: 1 hari telat = 1 poin)."
                            :error="systemForm.errors.penalty_terlambat_poin"
                        />
                    </div>
                    <div class="col-md-8 mt-3">
                        <TextareaInput
                            v-model="systemForm.whatsapp_template_tugas_terlambat"
                            name="whatsapp_template_tugas_terlambat"
                            label="Template WhatsApp Tugas Terlambat"
                            :rows="7"
                            help="Placeholder yang tersedia: {{nama_siswa}}, {{daftar_tugas}}, {{total_tugas}}, {{total_hari_terlambat}}, {{url_lms}}. Kosongkan untuk memakai template bawaan."
                            :error="systemForm.errors.whatsapp_template_tugas_terlambat"
                        />
                    </div>
                </div>
            </div>
        </section>
    </AppShell>
</template>

<style scoped>
.settings-grid {
    grid-template-columns: minmax(0, 1fr) minmax(280px, 360px);
}

.brand-preview {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    margin-bottom: 1rem;
}

.brand-preview-logo {
    display: flex;
    width: 82px;
    height: 82px;
    flex: 0 0 82px;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--modern-border);
    border-radius: 1rem;
    background: var(--surface-muted);
    color: var(--primary-600);
    font-size: 2rem;
}

.brand-preview-logo img {
    max-width: 72px;
    max-height: 72px;
    object-fit: contain;
}

.brand-preview strong,
.brand-preview span {
    display: block;
}

.brand-preview strong {
    color: var(--text-strong);
    font-size: 1rem;
}

.brand-preview span {
    color: var(--text-muted);
    font-size: 0.78rem;
}

.favicon-preview {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.favicon-preview > img,
.favicon-preview > i {
    display: inline-flex;
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--modern-border);
    border-radius: 0.75rem;
    background: var(--surface-muted);
    object-fit: contain;
}

.theme-option-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 0.75rem;
}

.theme-option {
    display: grid;
    grid-template-columns: auto 58px 1fr;
    gap: 0.7rem;
    align-items: center;
    min-height: 96px;
    padding: 0.75rem;
    border: 1px solid var(--modern-border);
    border-radius: 0.85rem;
    background: var(--surface-card);
    cursor: pointer;
    transition: var(--transition-fast);
}

.theme-option:hover,
.theme-option.is-active {
    border-color: var(--primary-500);
    box-shadow: var(--focus-ring);
}

.theme-option-preview {
    display: grid;
    grid-template-columns: 18px 1fr;
    width: 58px;
    height: 44px;
    overflow: hidden;
    border: 1px solid var(--modern-border);
    border-radius: 0.45rem;
    background: var(--surface-muted);
}

.theme-option-screen {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 7px;
}

.theme-option-screen span {
    display: block;
    height: 8px;
    border-radius: 99px;
}

.theme-option-body {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 0.2rem;
}

.theme-option-title {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    color: var(--text-strong);
    font-size: 0.85rem;
    font-weight: 800;
    line-height: 1.25;
}

.theme-option-note {
    color: var(--text-muted);
    font-size: 0.72rem;
    line-height: 1.35;
}

@media(max-width: 900px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
}
</style>
