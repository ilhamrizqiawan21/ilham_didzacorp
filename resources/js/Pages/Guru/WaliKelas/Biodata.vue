<script setup lang="ts">
import type { PropType } from 'vue';
type BioField = 'nama_lengkap' | 'nama_panggilan' | 'alamat' | 'tempat_lahir' | 'tanggal_lahir' | 'hobi' | 'cita_cita' | 'nama_ayah' | 'pekerjaan_ayah' | 'nama_ibu' | 'pekerjaan_ibu' | 'penghasilan_orangtua' | 'nama_wali' | 'pekerjaan_wali' | 'penyakit_kronis' | 'teman_dekat_sekolah' | 'teman_dekat_luar_sekolah' | 'jarak_rumah_km' | 'transportasi' | 'kegiatan_luar_sekolah';
type StudentBio = Record<BioField, string | null> & { id: number; nis: string; update_url: string; total_fields: number; completed_fields: number };
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { TextareaInput, TextInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, EmptyState } from '../../../Components/UI';

const props = defineProps({
    waliKelas: { type: Object as PropType<{ kelas: string; tahun_ajaran: string; back_url: string }>, required: true },
    students: { type: Array as PropType<StudentBio[]>, default: () => [] },
});

const search = ref('');
const selectedId = ref(props.students[0]?.id ?? null);

function formValues(student: Partial<StudentBio> = {}) {
    return {
        nama_lengkap: student.nama_lengkap ?? '',
        nama_panggilan: student.nama_panggilan ?? '',
        alamat: student.alamat ?? '',
        tempat_lahir: student.tempat_lahir ?? '',
        tanggal_lahir: student.tanggal_lahir ?? '',
        hobi: student.hobi ?? '',
        cita_cita: student.cita_cita ?? '',
        nama_ayah: student.nama_ayah ?? '',
        pekerjaan_ayah: student.pekerjaan_ayah ?? '',
        nama_ibu: student.nama_ibu ?? '',
        pekerjaan_ibu: student.pekerjaan_ibu ?? '',
        penghasilan_orangtua: student.penghasilan_orangtua ?? '',
        nama_wali: student.nama_wali ?? '',
        pekerjaan_wali: student.pekerjaan_wali ?? '',
        penyakit_kronis: student.penyakit_kronis ?? '',
        teman_dekat_sekolah: student.teman_dekat_sekolah ?? '',
        teman_dekat_luar_sekolah: student.teman_dekat_luar_sekolah ?? '',
        jarak_rumah_km: student.jarak_rumah_km ?? '',
        transportasi: student.transportasi ?? '',
        kegiatan_luar_sekolah: student.kegiatan_luar_sekolah ?? '',
    };
}

const form = useForm(formValues(props.students[0]));

const currentStudent = computed(() => (
    props.students.find((student) => student.id === selectedId.value) ?? null
));

const filteredStudents = computed(() => {
    const keyword = search.value.trim().toLocaleLowerCase('id-ID');

    if (!keyword) {
        return props.students;
    }

    return props.students.filter((student) => (
        [student.nama_lengkap, student.nama_panggilan, student.nis]
            .filter(Boolean)
            .some((value) => String(value).toLocaleLowerCase('id-ID').includes(keyword))
    ));
});

function selectStudent(student: StudentBio) {
    selectedId.value = student.id;
    form.clearErrors();
    Object.assign(form, formValues(student));
}

function save() {
    if (!currentStudent.value || form.processing) {
        return;
    }

    form.put(currentStudent.value.update_url, {
        preserveScroll: true,
    });
}

function completionColor(student: StudentBio) {
    const ratio = student.total_fields ? student.completed_fields / student.total_fields : 0;

    if (ratio === 1) return 'success';
    if (ratio >= 0.5) return 'warning text-dark';

    return 'secondary';
}
</script>

<template>
    <Head title="Biodata Siswa" />

    <AppShell title="Biodata Siswa">
        <PageHeader
            title="Biodata Siswa"
            :subtitle="`${waliKelas.kelas} · Tahun Ajaran ${waliKelas.tahun_ajaran}`"
            icon="bi-person-vcard"
        >
            <template #actions>
                <Button :href="waliKelas.back_url" color="outline-secondary" icon="bi-arrow-left">
                    Kembali
                </Button>
            </template>
        </PageHeader>

        <div v-if="students.length" class="row g-4 align-items-start">
            <div class="col-12 col-lg-4 col-xl-3">
                <Card title="Daftar Siswa" icon="bi-people" body-class="p-0">
                    <div class="p-3 border-bottom">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </span>
                            <input
                                v-model="search"
                                type="search"
                                class="form-control"
                                placeholder="Cari nama atau NIS..."
                                aria-label="Cari siswa"
                            >
                        </div>
                    </div>

                    <div v-if="filteredStudents.length" class="student-list">
                        <button
                            v-for="student in filteredStudents"
                            :key="student.id"
                            type="button"
                            class="student-item"
                            :class="{ active: selectedId === student.id }"
                            @click="selectStudent(student)"
                        >
                            <span class="student-avatar" aria-hidden="true">
                                {{ student.nama_lengkap?.charAt(0)?.toUpperCase() || '?' }}
                            </span>
                            <span class="min-w-0 flex-grow-1 text-start">
                                <span class="d-block fw-semibold text-truncate">{{ student.nama_lengkap || '-' }}</span>
                                <span class="d-block small text-muted text-truncate">
                                    {{ student.nis }}
                                    <span v-if="student.nama_panggilan"> · {{ student.nama_panggilan }}</span>
                                </span>
                            </span>
                            <Badge :color="completionColor(student)">
                                {{ student.completed_fields }}/{{ student.total_fields }}
                            </Badge>
                        </button>
                    </div>

                    <div v-else class="p-4 text-center text-muted small">
                        Tidak ada siswa yang cocok dengan pencarian.
                    </div>
                </Card>
            </div>

            <div class="col-12 col-lg-8 col-xl-9">
                <form v-if="currentStudent" @submit.prevent="save">
                    <Card :title="currentStudent.nama_lengkap || 'Biodata Siswa'" icon="bi-file-earmark-person">
                        <template #actions>
                            <div class="d-flex align-items-center gap-2">
                                <Badge color="secondary">NIS {{ currentStudent.nis }}</Badge>
                                <Badge :color="completionColor(currentStudent)">
                                    {{ currentStudent.completed_fields }}/{{ currentStudent.total_fields }} terisi
                                </Badge>
                            </div>
                        </template>

                        <div class="alert alert-light border d-flex gap-2 align-items-start small mb-4">
                            <i class="bi bi-shield-lock mt-1" aria-hidden="true"></i>
                            <div>
                                Biodata ini hanya untuk kebutuhan wali kelas. Data keluarga, kesehatan, dan kondisi siswa
                                hendaknya digunakan secara terbatas dan bertanggung jawab.
                            </div>
                        </div>

                        <section class="form-section">
                            <div class="form-section-title">
                                <i class="bi bi-person"></i>
                                <span>Data Pribadi</span>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <TextInput
                                        v-model="form.nama_lengkap"
                                        name="nama_lengkap"
                                        label="Nama Lengkap"
                                        required
                                        wrapper-class="mb-0"
                                        help="Nama ini juga digunakan pada akun siswa."
                                        :error="form.errors.nama_lengkap"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <TextInput
                                        v-model="form.nama_panggilan"
                                        name="nama_panggilan"
                                        label="Nama Panggilan"
                                        wrapper-class="mb-0"
                                        :error="form.errors.nama_panggilan"
                                    />
                                </div>
                                <div class="col-12">
                                    <TextareaInput
                                        v-model="form.alamat"
                                        name="alamat"
                                        label="Alamat"
                                        :rows="2"
                                        wrapper-class="mb-0"
                                        :error="form.errors.alamat"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <TextInput
                                        v-model="form.tempat_lahir"
                                        name="tempat_lahir"
                                        label="Tempat Lahir"
                                        wrapper-class="mb-0"
                                        :error="form.errors.tempat_lahir"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <TextInput
                                        v-model="form.tanggal_lahir"
                                        name="tanggal_lahir"
                                        label="Tanggal Lahir"
                                        type="date"
                                        wrapper-class="mb-0"
                                        :error="form.errors.tanggal_lahir"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <TextInput
                                        v-model="form.hobi"
                                        name="hobi"
                                        label="Hobi"
                                        wrapper-class="mb-0"
                                        :error="form.errors.hobi"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <TextInput
                                        v-model="form.cita_cita"
                                        name="cita_cita"
                                        label="Cita-Cita"
                                        wrapper-class="mb-0"
                                        :error="form.errors.cita_cita"
                                    />
                                </div>
                            </div>
                        </section>

                        <section class="form-section">
                            <div class="form-section-title">
                                <i class="bi bi-people"></i>
                                <span>Orang Tua & Wali</span>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <TextInput
                                        v-model="form.nama_ayah"
                                        name="nama_ayah"
                                        label="Nama Ayah"
                                        wrapper-class="mb-0"
                                        :error="form.errors.nama_ayah"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <TextInput
                                        v-model="form.pekerjaan_ayah"
                                        name="pekerjaan_ayah"
                                        label="Pekerjaan Ayah"
                                        wrapper-class="mb-0"
                                        :error="form.errors.pekerjaan_ayah"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <TextInput
                                        v-model="form.nama_ibu"
                                        name="nama_ibu"
                                        label="Nama Ibu"
                                        wrapper-class="mb-0"
                                        :error="form.errors.nama_ibu"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <TextInput
                                        v-model="form.pekerjaan_ibu"
                                        name="pekerjaan_ibu"
                                        label="Pekerjaan Ibu"
                                        wrapper-class="mb-0"
                                        :error="form.errors.pekerjaan_ibu"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <TextInput
                                        v-model="form.penghasilan_orangtua"
                                        name="penghasilan_orangtua"
                                        label="Penghasilan Orangtua per Bulan"
                                        type="number"
                                        min="0"
                                        step="1000"
                                        placeholder="Contoh: 3000000"
                                        help="Masukkan nominal dalam rupiah tanpa tanda pemisah."
                                        wrapper-class="mb-0"
                                        :error="form.errors.penghasilan_orangtua"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <TextInput
                                        v-model="form.nama_wali"
                                        name="nama_wali"
                                        label="Nama Wali (jika ada)"
                                        wrapper-class="mb-0"
                                        :error="form.errors.nama_wali"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <TextInput
                                        v-model="form.pekerjaan_wali"
                                        name="pekerjaan_wali"
                                        label="Pekerjaan Wali (jika ada)"
                                        wrapper-class="mb-0"
                                        :error="form.errors.pekerjaan_wali"
                                    />
                                </div>
                            </div>
                        </section>

                        <section class="form-section">
                            <div class="form-section-title">
                                <i class="bi bi-heart-pulse"></i>
                                <span>Kesehatan & Relasi</span>
                            </div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <TextareaInput
                                        v-model="form.penyakit_kronis"
                                        name="penyakit_kronis"
                                        label="Penyakit Kronis yang Diderita"
                                        :rows="2"
                                        placeholder="Kosongkan jika tidak ada."
                                        wrapper-class="mb-0"
                                        :error="form.errors.penyakit_kronis"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <TextareaInput
                                        v-model="form.teman_dekat_sekolah"
                                        name="teman_dekat_sekolah"
                                        label="Teman Dekat di Sekolah"
                                        :rows="2"
                                        wrapper-class="mb-0"
                                        :error="form.errors.teman_dekat_sekolah"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <TextareaInput
                                        v-model="form.teman_dekat_luar_sekolah"
                                        name="teman_dekat_luar_sekolah"
                                        label="Teman Dekat di Luar Sekolah"
                                        :rows="2"
                                        wrapper-class="mb-0"
                                        :error="form.errors.teman_dekat_luar_sekolah"
                                    />
                                </div>
                            </div>
                        </section>

                        <section class="form-section mb-0">
                            <div class="form-section-title">
                                <i class="bi bi-geo-alt"></i>
                                <span>Akses Sekolah & Kegiatan</span>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <TextInput
                                        v-model="form.jarak_rumah_km"
                                        name="jarak_rumah_km"
                                        label="Jarak dari Rumah ke Sekolah (KM)"
                                        type="number"
                                        min="0"
                                        max="9999.99"
                                        step="0.01"
                                        wrapper-class="mb-0"
                                        :error="form.errors.jarak_rumah_km"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <TextInput
                                        v-model="form.transportasi"
                                        name="transportasi"
                                        label="Transportasi ke Sekolah"
                                        placeholder="Contoh: jalan kaki, sepeda, motor, angkot"
                                        wrapper-class="mb-0"
                                        :error="form.errors.transportasi"
                                    />
                                </div>
                                <div class="col-12">
                                    <TextareaInput
                                        v-model="form.kegiatan_luar_sekolah"
                                        name="kegiatan_luar_sekolah"
                                        label="Kegiatan di Luar Sekolah"
                                        :rows="3"
                                        wrapper-class="mb-0"
                                        :error="form.errors.kegiatan_luar_sekolah"
                                    />
                                </div>
                            </div>
                        </section>

                        <template #footer>
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <small class="text-muted">
                                    Pilih siswa lain dari daftar untuk berpindah biodata.
                                </small>
                                <Button type="submit" color="primary" icon="bi-save" :disabled="form.processing">
                                    {{ form.processing ? 'Menyimpan...' : 'Simpan Biodata' }}
                                </Button>
                            </div>
                        </template>
                    </Card>
                </form>
            </div>
        </div>

        <Card v-else>
            <EmptyState
                title="Belum ada siswa aktif"
                message="Tidak ada siswa aktif pada kelas wali ini."
                icon="bi-people"
            />
        </Card>
    </AppShell>
</template>

<style scoped>
.student-list {
    max-height: min(68vh, 720px);
    overflow-y: auto;
}

.student-item {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 0.75rem;
    padding: 0.8rem 1rem;
    border: 0;
    border-bottom: 1px solid var(--bs-border-color);
    background: transparent;
    color: inherit;
    transition: background-color 0.15s ease, box-shadow 0.15s ease;
}

.student-item:last-child {
    border-bottom: 0;
}

.student-item:hover {
    background: var(--bs-tertiary-bg);
}

.student-item.active {
    background: var(--bs-primary-bg-subtle);
    box-shadow: inset 3px 0 0 var(--bs-primary);
}

.student-avatar {
    display: inline-flex;
    width: 2.25rem;
    height: 2.25rem;
    flex: 0 0 2.25rem;
    align-items: center;
    justify-content: center;
    border-radius: 0.7rem;
    background: var(--bs-secondary-bg);
    font-weight: 700;
}

.student-item.active .student-avatar {
    background: var(--bs-primary);
    color: var(--bs-white);
}

.min-w-0 {
    min-width: 0;
}

.form-section {
    margin-bottom: 2rem;
}

.form-section-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding-bottom: 0.6rem;
    border-bottom: 1px solid var(--bs-border-color);
    font-weight: 700;
}

@media (min-width: 992px) {
    .student-list {
        max-height: calc(100vh - 22rem);
    }
}
</style>
