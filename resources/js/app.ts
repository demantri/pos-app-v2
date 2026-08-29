import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
// vue-sonner v2 tidak lagi menyuntikkan gayanya sendiri — tanpa baris ini,
// toast tetap dirender ke DOM tapi tanpa posisi, warna, maupun animasi,
// sehingga notifikasi sukses/gagal seolah-olah tidak pernah muncul.
import 'vue-sonner/style.css';
import { Toaster } from '@/components/ui/sonner';
import { initializeTheme } from '@/composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({
            // expand: notifikasi ditumpuk terbuka, bukan saling menutupi —
            // checkout bisa memunculkan dua sekaligus (transaksi tersimpan +
            // nota gagal dicetak), dan keduanya harus terbaca.
            render: () =>
                h('div', [
                    h(App, props),
                    h(Toaster, { position: 'top-right', richColors: true, expand: true }),
                ]),
        })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
