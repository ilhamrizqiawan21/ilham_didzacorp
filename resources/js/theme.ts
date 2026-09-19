const COLOR_MODE_KEY = 'lms.color-mode';
type ColorMode = 'light' | 'dark';
const COLOR_MODES: readonly ColorMode[] = ['light', 'dark'];
let themeToggleReady = false;

function isColorMode(value: string | null): value is ColorMode {
    return value !== null && COLOR_MODES.includes(value as ColorMode);
}

function storedColorMode(): ColorMode | null {
    try {
        const value = window.localStorage.getItem(COLOR_MODE_KEY);
        return isColorMode(value) ? value : null;
    } catch {
        return null;
    }
}

function preferredColorMode(): ColorMode {
    return storedColorMode()
        || (window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
}

function setThemeMeta(mode: ColorMode): void {
    const meta = document.querySelector<HTMLMetaElement>('meta[name="theme-color"]');

    if (!meta) {
        return;
    }

    if (!meta.dataset.lightColor) {
        meta.dataset.lightColor = meta.getAttribute('content') || '#198754';
    }

    meta.setAttribute('content', mode === 'dark' ? '#0f172a' : meta.dataset.lightColor);
}

function syncThemeControls(mode: ColorMode): void {
    document.querySelectorAll<HTMLElement>('[data-theme-toggle]').forEach((button) => {
        const nextMode = mode === 'dark' ? 'light' : 'dark';
        const label = nextMode === 'dark' ? 'Aktifkan mode gelap' : 'Aktifkan mode terang';
        const icon = button.querySelector('[data-theme-toggle-icon]');
        const text = button.querySelector('[data-theme-toggle-label]');

        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
        button.setAttribute('aria-pressed', mode === 'dark' ? 'true' : 'false');
        button.dataset.themeMode = mode;

        if (icon) {
            icon.className = `bi ${mode === 'dark' ? 'bi-sun-fill' : 'bi-moon-stars-fill'}`;
        }

        if (text) {
            text.textContent = mode === 'dark' ? 'Mode terang' : 'Mode gelap';
        }
    });
}

export function applyColorMode(mode: ColorMode, persist = true): void {
    const colorMode = COLOR_MODES.includes(mode) ? mode : preferredColorMode();

    document.documentElement.setAttribute('data-bs-theme', colorMode);
    document.documentElement.style.colorScheme = colorMode;
    setThemeMeta(colorMode);
    syncThemeControls(colorMode);

    if (persist) {
        try {
            window.localStorage.setItem(COLOR_MODE_KEY, colorMode);
        } catch {
            // Ignore storage errors in private or restricted browser contexts.
        }
    }
}

export function initColorMode(): void {
    applyColorMode(preferredColorMode(), false);

    if (themeToggleReady) {
        return;
    }

    themeToggleReady = true;
    document.addEventListener('click', (event) => {
        const button = (event.target as Element | null)?.closest('[data-theme-toggle]');

        if (!button) {
            return;
        }

        const currentMode = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
        applyColorMode(currentMode === 'dark' ? 'light' : 'dark');
    });
}

applyColorMode(preferredColorMode(), false);
