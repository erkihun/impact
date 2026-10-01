import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { MotionConfig } from 'framer-motion';
import { renderToString } from 'react-dom/server';

const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });

createServer(page => createInertiaApp({
    page,
    render: renderToString,
    resolve: name => {
        const component = pages[`./Pages/${name}.jsx`];
        if (!component) throw new Error(`Unknown Inertia page: ${name}`);
        return component.default;
    },
    setup: ({ App, props }) => <MotionConfig reducedMotion="user"><App {...props} /></MotionConfig>,
}), { host: '127.0.0.1', port: Number(process.env.INERTIA_SSR_PORT ?? 13714) });
