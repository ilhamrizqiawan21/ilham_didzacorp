import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import '../css/responsive-polish.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h, type DefineComponent } from 'vue';
import { initColorMode } from './theme';

// Resolve the page map once at startup instead of recreating the glob on every navigation.
const pages = import.meta.glob<{ default: DefineComponent }>('./Pages/**/*.vue');

createInertiaApp({
    title: (title) => title ? `${title} - LMS Sekolah` : 'LMS Sekolah',
    resolve: async (name) => {
        const resolvePage = pages[`./Pages/${name}.vue`];
        if (!resolvePage) {
            throw new Error(`Halaman Inertia tidak ditemukan: ${name}`);
        }

        return (await resolvePage()).default;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
        initColorMode();
    },
    progress: {
        color: '#198754',
    },
});
