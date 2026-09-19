import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/Browser',
    testMatch: '**/*.spec.ts',
    workers: 1,
    timeout: 60_000,
    use: {
        baseURL: process.env.BROWSER_BASE_URL,
        // The isolated PHP server uses HTTP; production CSP upgrades redirect requests to HTTPS.
        bypassCSP: true,
        viewport: { width: 1440, height: 1000 },
        screenshot: 'only-on-failure',
        launchOptions: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH
            ? { executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH }
            : {},
    },
});
