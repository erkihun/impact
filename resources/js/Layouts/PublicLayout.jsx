import { Head, usePage } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import { useEffect } from 'react';
import { Breadcrumbs, PageHeader } from '../Components/Public/Common';
import ConsentBanner from '../Components/Public/ConsentBanner';
import PageComposition from '../Components/Public/PageComposition';
import SiteFooter from '../Components/Public/SiteFooter';
import SiteHeader from '../Components/Public/SiteHeader';

function FlashMessage({ flash, ui }) {
    const message = flash.status ?? flash.submission?.message;

    return (
        <AnimatePresence>
            {message && (
                <motion.div
                    className="content-container pt-5"
                    role="status"
                    aria-live="polite"
                    initial={{ opacity: 0, y: -12 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0 }}
                >
                    <div className="status-success">
                        {message}
                        {flash.submission?.reference && (
                            <strong className="ms-2">{ui.reference}: {flash.submission.reference}</strong>
                        )}
                    </div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}

// A managed composition replaces the page's built-in header, as in Blade.
export function PageIntro({ composition, header, children }) {
    const { ui } = usePage().props;

    if (composition) {
        return <PageComposition composition={composition} emptyTitle={ui.nothingPublished} />;
    }

    return header ? <PageHeader {...header}>{children}</PageHeader> : null;
}

export default function PublicLayout({ title, description, breadcrumbs, children }) {
    const { site, flash, ui } = usePage().props;

    // Client-side visits keep the document language in step with the page.
    useEffect(() => {
        document.documentElement.lang = site.locale.current;
    }, [site.locale.current]);

    return (
        <>
            <Head>
                <title>{title ? `${title} - ${site.seo.titleSuffix}` : site.seo.defaultTitle}</title>
                <meta head-key="description" name="description" content={description ?? site.seo.defaultDescription} />
            </Head>
            <a href="#main-content" className="skip-link">{ui.skip}</a>

            {site.maintenance.banner_enabled && (
                <div className="status-warning rounded-none border-x-0" role="status">
                    <div className="content-container flex flex-wrap items-center justify-between gap-3">
                        <p>{site.maintenance.banner_message}</p>
                        {site.maintenance.status_page_enabled && (
                            <a className="text-link" href={site.routes.status}>{ui.viewStatus}</a>
                        )}
                    </div>
                </div>
            )}

            <SiteHeader />
            <FlashMessage flash={flash} ui={ui} />

            <main id="main-content" className="public-main" tabIndex={-1}>
                <Breadcrumbs items={breadcrumbs} />
                {children}
            </main>

            <SiteFooter />
            <ConsentBanner />
        </>
    );
}
