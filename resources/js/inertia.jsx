import { createInertiaApp } from '@inertiajs/react';
import { MotionConfig } from 'framer-motion';
import { createRoot, hydrateRoot } from 'react-dom/client';

const pages = import.meta.glob('./Pages/**/*.jsx');

createInertiaApp({
    // SEO head elements are resolved on the server (props.seo.head) and kept
    // in sync on every visit by the head manager.
    serverHead: (page) => page.props.seo?.head ?? [],
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
        const application = (
            <MotionConfig reducedMotion="user">
                <App {...props} />
            </MotionConfig>
        );
        if (el.hasChildNodes()) {
            hydrateRoot(el, application);
        } else {
            createRoot(el).render(application);
        }
    },
    progress: {
        color: '#F28C28',
        showSpinner: false,
    },
});
