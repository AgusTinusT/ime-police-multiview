import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Automatically track Inertia SPA page navigation in Google Analytics
router.on('navigate', () => {
    if (typeof window.gtag === 'function') {
        window.gtag('event', 'page_view', {
            page_title: document.title,
            page_location: window.location.href,
            page_path: window.location.pathname + window.location.search,
        });
    }
});

// GA4 Engagement Heartbeat Ping (Every 2 Minutes / 120,000ms)
// Keeps passive multiview stream viewers counted in GA4 Realtime (5-min window)
// and prevents GA4 session timeouts (30-min threshold) during long patrol watching sessions.
const GA_HEARTBEAT_INTERVAL_MS = 120000; // 2 minutes

setInterval(() => {
    if (document.visibilityState === 'visible') {
        if (typeof window.gtag === 'function') {
            window.gtag('event', 'stream_heartbeat', {
                event_category: 'engagement',
                event_label: 'active_watching',
                non_interaction: false, // Ensures user is counted as active in GA4
            });
        } else if (window.dataLayer && Array.isArray(window.dataLayer)) {
            window.dataLayer.push({
                event: 'stream_heartbeat',
                event_category: 'engagement',
                event_label: 'active_watching',
                non_interaction: false,
            });
        }
    }
}, GA_HEARTBEAT_INTERVAL_MS);

createInertiaApp({
    title: (title) => title ? title : 'IME RP — SASP Police Duty',
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

