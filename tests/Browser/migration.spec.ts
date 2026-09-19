import { expect, test, type Page } from '@playwright/test';

test.beforeEach(async ({ page }) => {
    if (!process.env.BROWSER_BASE_URL?.startsWith('http://127.0.0.1:')) {
        throw new Error('Run npm run test:browser to use isolated fixtures.');
    }
    page.on('pageerror', error => { throw error; });
    page.on('requestfailed', request => console.error('Request failed:', request.url(), request.failure()?.errorText));
});

async function login(page: Page, role: string) {
    await page.goto('/login');
    await page.getByLabel('Username', { exact: true }).fill(`browser-${role}`);
    await page.getByLabel('Password', { exact: true }).fill('browser-test-password');
    await page.getByRole('button', { name: 'Masuk ke LMS' }).click();
    await expect(page.locator('#mainContent')).toBeVisible({ timeout: 15000 });
}

const routes = {
    admin: ['/admin/dashboard', '/admin/kelas-siswa', '/admin/kelas-mapel', '/admin/users/create', '/admin/pengaturan', '/admin/pengumuman', '/admin/pengumuman/1', '/admin/log-login', '/admin/log-error', '/admin/log-akademik', '/admin/rekap/absensi', '/admin/rekap/nilai', '/admin/rekap/sikap', '/admin/rekap/tugas', '/admin/performa-guru'],
    guru: ['/guru/dashboard', '/guru/nilai', '/guru/nilai/1/input', '/guru/absensi?kelas_mapel_id=1', '/guru/sikap', '/guru/sikap/1/input', '/guru/rekap-nilai', '/guru/rekap-sikap', '/guru/rekap-absensi', '/guru/materi/1/list', '/guru/tugas', '/guru/tugas/1/list', '/guru/kelas-mapel/1', '/guru/jadwal-mengajar', '/guru/kelas-daring', '/guru/wali-kelas', '/guru/wali-kelas/1/absensi', '/guru/wali-kelas/1/pertemuan', '/guru/wali-kelas/1/penanganan'],
    siswa: ['/siswa/dashboard', '/siswa/progress', '/siswa/nilai', '/siswa/materi', '/siswa/materi/1', '/siswa/tugas', '/siswa/tugas/1', '/siswa/kelas-mapel/1', '/siswa/jadwal-pelajaran', '/siswa/kelas-daring', '/siswa/pengumuman', '/siswa/pengumuman/1'],
    kepala_sekolah: ['/kepsek/dashboard', '/kepsek/statistik', '/kepsek/laporan/absensi', '/kepsek/laporan/nilai', '/kepsek/laporan/rekap-absensi', '/kepsek/laporan/rekap-sikap', '/kepsek/laporan/rekap-tugas', '/kepsek/laporan/wali-kelas', '/kepsek/laporan/wali-kelas/1', '/kepsek/performa-guru', '/kepsek/pengumuman', '/kepsek/pengumuman/1'],
};

for (const [role, pages] of Object.entries(routes)) {
    test(`${role}: pages and responsive drawer`, async ({ page }, testInfo) => {
        await login(page, role);
        for (const path of pages) {
            const response = await page.goto(path);
            expect(response?.status(), path).toBe(200);
            await expect(page.locator('#mainContent')).toBeVisible();
            await expect(page.locator('#mainContent')).not.toBeEmpty();
        }
        for (const width of [1440, 820, 390]) {
            await page.setViewportSize({ width, height: 1000 });
            await page.goto(pages[0]);
            const toggle = page.locator('.topbar-toggle-btn');
            await expect(toggle).toHaveAttribute('aria-expanded', String(width >= 992));
            if (width < 992) {
                await toggle.click();
                await expect(page.locator('.sidebar-overlay')).toHaveClass(/show/);
                await page.keyboard.press('Escape');
                await expect(toggle).toHaveAttribute('aria-expanded', 'false');
                await toggle.click();
                await page.locator('.sidebar-overlay').click({ position: { x: width - 5, y: 500 } });
                await expect(toggle).toHaveAttribute('aria-expanded', 'false');
            }
            const overflow = await page.evaluate(() => document.documentElement.scrollWidth - innerWidth);
            expect(overflow).toBeLessThanOrEqual(1);
            await page.screenshot({ path: testInfo.outputPath(`${role}-${width}.png`), fullPage: true });
        }
    });
}

test('grade paste and attendance save', async ({ page }) => {
    await login(page, 'guru');
    await page.goto('/guru/nilai');
    const first = page.locator('.score-input').first();
    await first.evaluate(element => {
        const clipboardData = new DataTransfer();
        clipboardData.setData('text/plain', '81,5\t92');
        element.dispatchEvent(new ClipboardEvent('paste', { clipboardData, bubbles: true, cancelable: true }));
    });
    await expect(first).toHaveValue('81.5');
    await expect(page.locator('.score-input').nth(1)).toHaveValue('92');
    await page.getByRole('button', { name: 'Simpan Nilai' }).first().click();
    await expect(page.locator('.toast-item').first()).toContainText('berhasil');
    await page.reload();
    await expect(first).toHaveValue(/81\.5(0)?/);

    await page.goto('/guru/absensi?kelas_mapel_id=1');
    await page.locator('thead select').first().selectOption('izin');
    await expect(page.locator('tbody select').first()).toHaveValue('izin');
    await page.getByRole('button', { name: 'Simpan Absensi' }).click();
    await expect(page.locator('.toast-item').first()).toContainText('berhasil');
    await page.reload();
    await expect(page.locator('tbody select').first()).toHaveValue('izin');
});

test('teacher can create a task from the task menu', async ({ page }) => {
    await login(page, 'guru');
    await page.goto('/guru/tugas/1/list');
    await page.locator('input[name="judul"]').fill('Tugas Browser Baru');
    await page.locator('textarea[name="deskripsi"]').fill('Deskripsi pengujian');
    await page.locator('input[name="batas_waktu"]').fill('2099-12-31');
    await page.getByRole('button', { name: 'Simpan Tugas' }).click();
    await expect(page.locator('.toast-item').first()).toContainText('berhasil');
    await expect(page.locator('.app-table-judul, .app-mobile-list-title').filter({ hasText: 'Tugas Browser Baru' }).first()).toBeVisible();
});

test('notifications and command navigation', async ({ page }) => {
    await login(page, 'kepala_sekolah');
    await page.goto('/kepsek/notifikasi');
    await page.getByRole('button', { name: 'Tandai Semua Sudah Dibaca' }).click();
    await expect(page.getByRole('button', { name: 'Tandai Semua Sudah Dibaca' })).toHaveCount(0);
    await page.getByRole('button', { name: 'Buka akses cepat' }).click();
    await page.locator('.command-palette input').fill('Statistik');
    await page.keyboard.press('Enter');
    await expect(page).toHaveURL(/\/kepsek\/statistik$/);
});

test('command palette keeps Blade navigation native', async ({ page }) => {
    await login(page, 'guru');
    await page.getByRole('button', { name: 'Buka akses cepat' }).click();
    await page.locator('.command-palette input').fill('Rekap Nilai');
    const request = page.waitForRequest(request => request.url().endsWith('/guru/rekap-nilai'));
    await page.keyboard.press('Enter');
    expect((await request).isNavigationRequest()).toBe(true);
    await expect(page).toHaveURL(/\/guru\/rekap-nilai$/);
});

test('student progress chart renders', async ({ page }) => {
    await login(page, 'siswa');
    await page.goto('/siswa/progress');
    await expect.poll(async () => page.locator('canvas').evaluate(canvas => {
        if (!(canvas instanceof HTMLCanvasElement)) return false;
        const pixels = canvas.getContext('2d')?.getImageData(0, 0, canvas.width, canvas.height).data;
        return pixels?.some((value, index) => index % 4 === 3 && value > 0) ?? false;
    })).toBe(true);
});

test('grading serializes autosave and accepts decimal scores', async ({ page }) => {
    await login(page, 'guru');
    await page.goto('/guru/tugas/1/1/pengumpulan');
    const input = page.locator('.js-assignment-score-input').first();
    let requests = 0;
    let releaseFirst: () => void = () => {};
    const gate = new Promise<void>(resolve => { releaseFirst = resolve; });
    await page.route('**/guru/tugas/1/1/siswa/1/nilai', async route => {
        const order = ++requests;
        const response = await route.fetch();
        if (order === 1) await gate;
        await route.fulfill({ response });
    });
    try {
        await input.fill('81.5');
        expect(await input.evaluate(element => (element as HTMLInputElement).checkValidity())).toBe(true);
        await expect.poll(() => requests).toBe(1);
        await input.fill('92');
        // Let the debounce expire while the earlier response is still pending.
        await page.waitForTimeout(900);
        expect(requests).toBe(1);
        releaseFirst();
        await expect.poll(() => requests).toBe(2);
        await expect(page.locator('.autosave-status').first()).toHaveText('Tersimpan');
        await page.reload();
        await expect(input).toHaveValue(/92(\.00)?/);
    } finally {
        releaseFirst();
    }
});

test('confirmation replacement settles the previous promise', async ({ page }) => {
    await login(page, 'admin');
    await page.evaluate(() => {
        void window.confirmDialog?.('Pertama').then(result => { document.body.dataset.firstConfirmation = String(result); });
        void window.confirmDialog?.('Kedua').then(result => { document.body.dataset.secondConfirmation = String(result); });
    });
    await expect(page.locator('body')).toHaveAttribute('data-first-confirmation', 'false');
    await expect(page.getByRole('dialog')).toContainText('Kedua');
    await page.getByRole('button', { name: 'Batal', exact: true }).click();
    await expect(page.locator('body')).toHaveAttribute('data-second-confirmation', 'false');
});

test('assignment paste keeps empty cells aligned with students', async ({ page }) => {
    await login(page, 'guru');
    await page.goto('/guru/tugas/1/1/pengumpulan');
    const inputs = page.locator('.js-assignment-score-input');
    await expect(inputs).toHaveCount(3);
    await inputs.first().evaluate(element => {
        const clipboardData = new DataTransfer();
        clipboardData.setData('text/plain', '81\n\n93\n');
        element.dispatchEvent(new ClipboardEvent('paste', { clipboardData, bubbles: true, cancelable: true }));
    });
    await expect(inputs.nth(0)).toHaveValue('81');
    await expect(inputs.nth(1)).toHaveValue('');
    await expect(inputs.nth(2)).toHaveValue('93');
    await expect(inputs.nth(2).locator('xpath=ancestor::form').locator('.autosave-status')).toHaveText('Tersimpan');
    await page.reload();
    await expect(inputs.nth(2)).toHaveValue(/93(\.00)?/);
});

test('command palette traps focus and closes from menu buttons', async ({ page }) => {
    await login(page, 'admin');
    await page.keyboard.press('/');
    const dialog = page.getByRole('dialog', { name: 'Akses cepat' });
    await expect(dialog.locator('input')).toBeFocused();
    await page.keyboard.press('Shift+Tab');
    await expect(dialog.locator('button').last()).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(dialog.locator('input')).toBeFocused();
    await page.keyboard.press('Tab');
    await page.keyboard.press('Escape');
    await expect(dialog).toBeHidden();
});

test('searchable select opens upward at the last option', async ({ page }) => {
    await login(page, 'admin');
    await page.goto('/admin/kelas-mapel');
    await page.locator('#kelas_id').focus();
    await page.keyboard.press('ArrowUp');
    const search = page.getByRole('combobox').first();
    await expect(search).toBeFocused();
    await page.keyboard.press('Enter');
    await expect(page.locator('input[type="hidden"][name="kelas_id"]')).toHaveValue('1');
});

test('student phone settings validate consent when configured', async ({ page }, testInfo) => {
    await login(page, 'siswa-2');
    await expect(page).toHaveURL(/\/siswa\/dashboard$/);
    await page.goto('/siswa/pengaturan');
    const number = page.getByLabel('Nomor Telepon / WhatsApp');
    const consent = page.locator('#whatsapp_opt_in');
    await expect(consent).not.toBeChecked();
    await expect(consent).toHaveAttribute('required', '');
    await page.goto('/siswa/nilai');
    await expect(page).toHaveURL(/\/siswa\/nilai$/);
    await page.goto('/siswa/pengaturan');
    await number.fill('0812');
    await consent.check();
    await page.getByRole('button', { name: 'Simpan Nomor Telepon' }).click();
    await expect(page.getByText('Masukkan nomor seluler Indonesia yang valid (08, 628, atau +628).', { exact: true })).toBeVisible();
    await number.fill('0812 3456-7891');
    await consent.uncheck();
    await page.getByRole('button', { name: 'Simpan Nomor Telepon' }).click();
    expect(await consent.evaluate(element => (element as HTMLInputElement).validity.valueMissing)).toBe(true);
    await expect(page).toHaveURL(/\/siswa\/pengaturan$/);
    await consent.check();
    await page.getByRole('button', { name: 'Simpan Nomor Telepon' }).click();
    await expect(number).toHaveValue('6281234567891');
    await expect(consent).toBeChecked();
    await page.goto('/siswa/nilai');
    await expect(page).toHaveURL(/\/siswa\/nilai$/);
    await page.goto('/siswa/pengaturan');
    await expect(consent).toBeChecked();
    await consent.uncheck();
    await page.getByRole('button', { name: 'Simpan Nomor Telepon' }).click();
    expect(await consent.evaluate(element => (element as HTMLInputElement).validity.valueMissing)).toBe(true);
    await page.reload();
    await expect(consent).toBeChecked();
    for (const width of [1440, 390]) {
        await page.setViewportSize({ width, height: 1000 });
        await expect(number).toBeVisible();
        await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(1);
        await page.screenshot({ path: testInfo.outputPath(`phone-settings-${width}.png`), fullPage: true });
    }
});
