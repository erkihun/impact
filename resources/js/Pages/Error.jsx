import { Head } from '@inertiajs/react';

const messages = {
    403: ['Access denied', 'You do not have permission to open this page.'],
    404: ['Page not found', 'The page you requested could not be found.'],
    410: ['Page removed', 'This page has been permanently removed. Use the links below to continue.'],
    409: ['Changes could not be saved', 'Reload the page to get the latest version before trying again.'],
    419: ['Session expired', 'Reload the page and try again.'],
    429: ['Too many requests', 'Please wait a moment before trying again.'],
    500: ['Something went wrong', 'Please try again later.'],
    503: ['Temporarily unavailable', 'Please try again later.'],
};

export default function Error({ status, message, correlation_id, workspace, site }) {
    const [title, description] = messages[status] ?? messages[500];
    const t = text => workspace?.text?.[text] ?? text;
    return <main className="min-h-screen grid place-items-center bg-quiet p-6">
        <Head title={t(title)}><meta head-key="robots" name="robots" content="noindex,nofollow" /></Head>
        <section className="admin-panel w-full max-w-xl">
            <p className="eyebrow">{status}</p>
            <h1 className="heading-2 my-4">{t(title)}</h1>
            <p>{message ?? t(description)}</p>
            {correlation_id && <p className="text-sm text-muted mt-4 break-all">{t('Reference')}: {correlation_id}</p>}
            <div className="flex flex-wrap gap-3 mt-6">
                <a className="button-primary" href={site?.routes?.home ?? '/'}>{t('Home')}</a>
                <button className="button-secondary" onClick={() => window.history.back()}>{t('Back')}</button>
            </div>
        </section>
    </main>;
}
