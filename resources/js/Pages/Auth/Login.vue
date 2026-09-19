<script setup lang="ts">
import type { PropType } from 'vue';
import type { AppPageProps } from '../../types/inertia';
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    branding: { type: Object as PropType<{ school_short_name: string; school_name: string; school_motto: string; school_address: string; logo_url: string; support_contact: string | null }>, required: true },
    loginUrl: { type: String, required: true },
    publicAnnouncements: { type: Array as PropType<{ id: number; judul: string; isi: string; creator_name: string | null; created_at: string | null; attachment: { url: string; name: string; size: number | null } | null }[]>, default: () => [] },
    year: { type: [String, Number], required: true },
});

const page = usePage<AppPageProps>();
const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const flash = computed(() => page.props.flash ?? {});
const title = computed(() => `Login - ${props.branding.school_short_name} ${props.branding.school_name}`);
const forgotPasswordUrl = computed(() => {
    const rawContact = String(props.branding.support_contact ?? '').replace(/[^\d+]/g, '');

    if (!rawContact) {
        return null;
    }

    const digits = rawContact.replace(/\D/g, '');
    const whatsappNumber = digits.startsWith('0') ? `62${digits.slice(1)}` : digits;
    const message = encodeURIComponent('Assalamu\'alaikum, saya lupa password. Mohon bantu saya. Nama: ........ Kelas: .........');

    return `https://wa.me/${whatsappNumber}?text=${message}`;
});

function formatDate(value: string | null) {
    return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
}

function formatFileSize(bytes: number | null) {
    if (!bytes) return '';
    const kb = bytes / 1024;
    return kb >= 1024 ? `${(kb / 1024).toFixed(1)} MB` : `${Math.ceil(kb)} KB`;
}

function submit() {
    form.post(props.loginUrl, {
        preserveScroll: true,
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head :title="title" />

    <main class="login-page">
        <div class="login-shell">
            <section class="login-brand" aria-labelledby="login-title">
                <div class="brand-mark">
                    <img
                        :src="branding.logo_url"
                        :alt="`Logo ${branding.school_name}`"
                        width="48"
                        height="48"
                        decoding="async"
                    >
                </div>

                <div class="brand-copy">
                    <div class="product-name">{{ branding.school_short_name }}</div>
                    <h1 id="login-title">{{ branding.school_name }}</h1>
                    <p v-if="branding.school_motto" class="school-motto">{{ branding.school_motto }}</p>
                    <p v-if="branding.school_address" class="school-address">{{ branding.school_address }}</p>
                </div>
            </section>

            <section class="login-card" aria-label="Form login">
                <div class="login-card-heading">
                    <div>
                        <span class="login-eyebrow">Akses LMS</span>
                        <h2>Selamat datang</h2>
                    </div>
                    <p>Masuk menggunakan akun sekolah Anda.</p>
                </div>

                <div v-if="flash.error" class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i>
                    <span>{{ flash.error }}</span>
                </div>
                <div v-if="flash.success" class="alert alert-success d-flex align-items-center" role="status">
                    <i class="bi bi-check-circle me-2" aria-hidden="true"></i>
                    <span>{{ flash.success }}</span>
                </div>

                <form @submit.prevent="submit">
                    <div class="field-group">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-shell" :class="{ 'has-error': form.errors.username }">
                            <i class="bi bi-person" aria-hidden="true"></i>
                            <input
                                id="username"
                                v-model="form.username"
                                type="text"
                                name="username"
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.username }"
                                placeholder="Masukkan username"
                                required
                                autofocus
                                autocomplete="username"
                                :aria-describedby="form.errors.username ? 'username-error' : undefined"
                            >
                        </div>
                        <div v-if="form.errors.username" id="username-error" class="login-error">{{ form.errors.username }}</div>
                    </div>

                    <div class="field-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-shell" :class="{ 'has-error': form.errors.password }">
                            <i class="bi bi-lock" aria-hidden="true"></i>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                name="password"
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.password }"
                                placeholder="Masukkan password"
                                required
                                autocomplete="current-password"
                                :aria-describedby="form.errors.password ? 'password-error' : undefined"
                            >
                        </div>
                        <div v-if="form.errors.password" id="password-error" class="login-error">{{ form.errors.password }}</div>
                    </div>

                    <div class="login-options">
                        <div class="form-check">
                            <input
                                id="remember"
                                v-model="form.remember"
                                type="checkbox"
                                name="remember"
                                class="form-check-input"
                            >
                            <label class="form-check-label" for="remember">Ingat saya</label>
                        </div>

                        <a
                            v-if="forgotPasswordUrl"
                            class="forgot-link"
                            :href="forgotPasswordUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Lupa password?
                        </a>
                    </div>

                    <button type="submit" class="btn-login" :disabled="form.processing">
                        <span>{{ form.processing ? 'Memproses...' : 'Masuk ke LMS' }}</span>
                        <i v-if="!form.processing" class="bi bi-arrow-right" aria-hidden="true"></i>
                        <span v-else class="login-spinner" aria-hidden="true"></span>
                    </button>

                    <p class="login-help" role="note">
                        Jika mengalami kendala akses, hubungi admin sekolah melalui kontak resmi.
                    </p>
                </form>
            </section>

            <section v-if="publicAnnouncements.length" class="public-board" aria-label="Papan informasi">
                <div class="public-board-title">
                    <div>
                        <span class="board-eyebrow">Informasi sekolah</span>
                        <h2>Papan Informasi</h2>
                    </div>
                    <i class="bi bi-megaphone" aria-hidden="true"></i>
                </div>

                <article
                    v-for="announcement in publicAnnouncements"
                    :key="announcement.id"
                    class="public-announcement"
                >
                    <div class="public-announcement-meta">
                        <span>{{ announcement.creator_name || 'Sekolah' }}</span>
                        <span v-if="formatDate(announcement.created_at)">{{ formatDate(announcement.created_at) }}</span>
                    </div>
                    <h3>{{ announcement.judul }}</h3>
                    <p>{{ announcement.isi }}</p>
                    <a
                        v-if="announcement.attachment"
                        class="public-attachment"
                        :href="announcement.attachment.url"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <i class="bi bi-paperclip" aria-hidden="true"></i>
                        <span>{{ announcement.attachment.name }}</span>
                        <small v-if="formatFileSize(announcement.attachment.size)">{{ formatFileSize(announcement.attachment.size) }}</small>
                    </a>
                </article>
            </section>

            <footer class="login-footer">
                <span>&copy; {{ year }} {{ branding.school_name }}</span>
                <span aria-hidden="true">•</span>
                <span>{{ branding.school_short_name }}</span>
            </footer>
        </div>
    </main>
</template>

<style scoped>
.login-page {
    --login-primary: var(--app-primary, #198754);
    --login-primary-dark: var(--app-primary-dark, #166534);
    --login-surface: #ffffff;
    --login-surface-soft: #f8fafc;
    --login-border: #e2e8f0;
    --login-text: #0f172a;
    --login-text-soft: #475569;
    --login-text-muted: #64748b;
    --login-danger-bg: #fff1f2;
    --login-danger-text: #be123c;
    --login-success-bg: #f0fdf4;
    --login-success-text: #166534;
    min-height: 100vh;
    padding: clamp(24px, 5vw, 56px) 20px 28px;
    overflow-x: hidden;
    background:
        radial-gradient(circle at top left, color-mix(in srgb, var(--login-primary) 9%, transparent), transparent 32rem),
        #f6f8fa;
    color: var(--login-text);
    font-family: var(--font-sans);
}

.login-shell {
    width: min(100%, 460px);
    margin: 0 auto;
}

.login-brand {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}

.brand-mark {
    width: 68px;
    height: 68px;
    flex: 0 0 68px;
    display: grid;
    place-items: center;
    border: 1px solid color-mix(in srgb, var(--login-primary) 18%, var(--login-border));
    border-radius: 18px;
    background: var(--login-surface);
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}

.brand-mark img {
    width: 48px;
    height: 48px;
    object-fit: contain;
}

.brand-copy {
    min-width: 0;
}

.product-name,
.login-eyebrow,
.board-eyebrow {
    display: block;
    color: var(--login-primary-dark);
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    line-height: 1.2;
    text-transform: uppercase;
}

.login-brand h1 {
    margin: 4px 0 5px;
    color: var(--login-text);
    font-size: clamp(1.1rem, 4vw, 1.35rem);
    font-weight: 800;
    line-height: 1.25;
}

.school-motto,
.school-address {
    margin: 0;
    color: var(--login-text-muted);
    font-size: 0.78rem;
    line-height: 1.45;
}

.school-address {
    margin-top: 2px;
    font-size: 0.72rem;
}

.login-card,
.public-board {
    border: 1px solid var(--login-border);
    border-radius: 20px;
    background: var(--login-surface);
    box-shadow: 0 14px 40px rgba(15, 23, 42, 0.07);
}

.login-card {
    padding: 28px;
}

.login-card-heading {
    margin-bottom: 24px;
}

.login-card-heading h2 {
    margin: 5px 0 6px;
    color: var(--login-text);
    font-size: 1.35rem;
    font-weight: 800;
    line-height: 1.25;
}

.login-card-heading p {
    margin: 0;
    color: var(--login-text-muted);
    font-size: 0.86rem;
    line-height: 1.5;
}

.field-group {
    margin-bottom: 16px;
}

.form-label {
    margin-bottom: 7px;
    color: var(--login-text-soft);
    font-size: 0.8rem;
    font-weight: 700;
}

.input-shell {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 46px;
    padding: 0 13px;
    border: 1px solid var(--login-border);
    border-radius: 12px;
    background: var(--login-surface);
    transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
}

.input-shell > i {
    flex: 0 0 auto;
    color: #94a3b8;
    font-size: 1rem;
}

.input-shell:focus-within {
    border-color: color-mix(in srgb, var(--login-primary) 70%, #ffffff);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--login-primary) 12%, transparent);
}

.input-shell:focus-within > i {
    color: var(--login-primary);
}

.input-shell.has-error {
    border-color: #fb7185;
}

.form-control {
    min-width: 0;
    height: 44px;
    padding: 0;
    border: 0 !important;
    background: transparent !important;
    color: var(--login-text);
    font-size: 0.9rem;
    box-shadow: none !important;
}

.form-control::placeholder {
    color: #94a3b8;
}

.form-control:focus {
    color: var(--login-text);
}

.form-control.is-invalid {
    background-image: none;
}

.login-error {
    margin-top: 6px;
    color: #e11d48;
    font-size: 0.74rem;
    font-weight: 600;
}

.login-options {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin: 2px 0 20px;
}

.form-check {
    min-height: auto;
    margin: 0;
}

.form-check-input {
    margin-top: 0.19em;
    border-color: #cbd5e1;
}

.form-check-input:checked {
    border-color: var(--login-primary);
    background-color: var(--login-primary);
}

.form-check-input:focus {
    border-color: var(--login-primary);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--login-primary) 12%, transparent);
}

.form-check-label,
.forgot-link {
    color: var(--login-text-muted);
    font-size: 0.8rem;
}

.forgot-link {
    color: var(--login-primary-dark);
    font-weight: 700;
    text-decoration: none;
}

.forgot-link:hover {
    color: var(--login-primary);
    text-decoration: underline;
    text-underline-offset: 3px;
}

.btn-login {
    width: 100%;
    min-height: 46px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    border: 1px solid var(--login-primary);
    border-radius: 12px;
    background: var(--login-primary);
    color: #ffffff;
    padding: 10px 16px;
    font: inherit;
    font-size: 0.86rem;
    font-weight: 800;
    transition: background-color 0.15s ease, border-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
}

.btn-login:hover:not(:disabled) {
    border-color: var(--login-primary-dark);
    background: var(--login-primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 8px 20px color-mix(in srgb, var(--login-primary) 22%, transparent);
}

.btn-login:focus-visible {
    outline: 0;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--login-primary) 20%, transparent);
}

.btn-login:disabled {
    cursor: wait;
    opacity: 0.7;
}

.login-spinner {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.45);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: login-spin 0.7s linear infinite;
}

.login-help {
    margin: 13px 0 0;
    color: var(--login-text-muted);
    font-size: 0.73rem;
    line-height: 1.5;
    text-align: center;
}

.alert {
    border: 0;
    border-radius: 12px;
    padding: 11px 13px;
    margin-bottom: 18px;
    font-size: 0.8rem;
    line-height: 1.45;
}

.alert-danger {
    background: var(--login-danger-bg);
    color: var(--login-danger-text);
}

.alert-success {
    background: var(--login-success-bg);
    color: var(--login-success-text);
}

.public-board {
    margin-top: 16px;
    padding: 22px 24px;
}

.public-board-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 6px;
}

.public-board-title h2 {
    margin: 4px 0 0;
    color: var(--login-text);
    font-size: 1rem;
    font-weight: 800;
}

.public-board-title > i {
    color: var(--login-primary);
    font-size: 1.2rem;
}

.public-announcement {
    padding: 16px 0;
    border-top: 1px solid var(--login-border);
}

.public-announcement:first-of-type {
    border-top: 0;
}

.public-announcement:last-of-type {
    padding-bottom: 0;
}

.public-announcement-meta {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 5px;
    color: var(--login-text-muted);
    font-size: 0.7rem;
}

.public-announcement h3 {
    margin: 0 0 7px;
    color: var(--login-text);
    font-size: 0.9rem;
    font-weight: 800;
    line-height: 1.35;
}

.public-announcement p {
    margin: 0;
    color: var(--login-text-soft);
    font-size: 0.79rem;
    line-height: 1.55;
    overflow-wrap: anywhere;
    white-space: pre-line;
}

.public-attachment {
    max-width: 100%;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    margin-top: 10px;
    padding: 7px 10px;
    border: 1px solid color-mix(in srgb, var(--login-primary) 20%, var(--login-border));
    border-radius: 9px;
    background: color-mix(in srgb, var(--login-primary) 5%, var(--login-surface));
    color: var(--login-primary-dark);
    font-size: 0.75rem;
    font-weight: 700;
    text-decoration: none;
}

.public-attachment:hover {
    border-color: color-mix(in srgb, var(--login-primary) 45%, var(--login-border));
    background: color-mix(in srgb, var(--login-primary) 8%, var(--login-surface));
}

.public-attachment span {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.public-attachment small {
    flex: 0 0 auto;
    color: var(--login-text-muted);
    font-size: 0.68rem;
    font-weight: 600;
}

.login-footer {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 7px;
    margin-top: 18px;
    color: var(--login-text-muted);
    font-size: 0.7rem;
    text-align: center;
}

@keyframes login-spin {
    to {
        transform: rotate(360deg);
    }
}

:global(html[data-bs-theme='dark']) .login-page {
    --login-surface: #111827;
    --login-surface-soft: #172033;
    --login-border: #273449;
    --login-text: #f8fafc;
    --login-text-soft: #cbd5e1;
    --login-text-muted: #94a3b8;
    --login-danger-bg: #3a1520;
    --login-danger-text: #fecdd3;
    --login-success-bg: #123322;
    --login-success-text: #bbf7d0;
    background:
        radial-gradient(circle at top left, color-mix(in srgb, var(--login-primary) 13%, transparent), transparent 32rem),
        #0b1120;
}

:global(html[data-bs-theme='dark']) .brand-mark,
:global(html[data-bs-theme='dark']) .login-card,
:global(html[data-bs-theme='dark']) .public-board {
    box-shadow: 0 16px 44px rgba(0, 0, 0, 0.2);
}

:global(html[data-bs-theme='dark']) .input-shell {
    background: #0f172a;
}

:global(html[data-bs-theme='dark']) .form-control::placeholder {
    color: #64748b;
}

@media (max-width: 520px) {
    .login-page {
        padding: 24px 14px 22px;
    }

    .login-brand {
        gap: 13px;
        margin-bottom: 18px;
    }

    .brand-mark {
        width: 58px;
        height: 58px;
        flex-basis: 58px;
        border-radius: 15px;
    }

    .brand-mark img {
        width: 40px;
        height: 40px;
    }

    .school-motto,
    .school-address {
        display: none;
    }

    .login-card {
        padding: 22px 20px;
        border-radius: 17px;
    }

    .login-card-heading {
        margin-bottom: 20px;
    }

    .login-card-heading h2 {
        font-size: 1.2rem;
    }

    .public-board {
        padding: 18px 20px;
        border-radius: 17px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .btn-login,
    .input-shell,
    .login-spinner {
        transition: none;
        animation: none;
    }
}
</style>
