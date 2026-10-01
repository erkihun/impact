import { createInertiaApp } from '@inertiajs/react';
import { MotionConfig } from 'framer-motion';
import { createRoot } from 'react-dom/client';

const pages = import.meta.glob('./Pages/**/*.jsx');

createInertiaApp({
    resolve: async (name) => {
        const page = pages[`./Pages/${name}.jsx`];

        if (! page) {
            throw new Error(`Unknown Inertia page: ${name}`);
        }

        return (await page()).default;
    },
    setup({ el, App, props }) {
        // reducedMotion="user" honours the visitor's OS-level motion preference
        // for every Framer Motion animation in the app.
        createRoot(el).render(
            <MotionConfig reducedMotion="user">
                <App {...props} />
            </MotionConfig>,
        );
    },
    progress: {
        color: '#F28C28',
        showSpinner: false,
    },
});
