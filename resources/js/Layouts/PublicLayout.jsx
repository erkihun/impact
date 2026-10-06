import { usePage } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
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

// Section types that render the page's single <h1>.
const HEADING_SECTIONS = ['page_header', 'form_introduction', 'homepage_hero'];

// A managed composition replaces the page's built-in header, as in Blade.
// The built-in header stays whenever the composition has no heading section,
// so every page keeps exactly one meaningful <h1>.
export function PageIntro({ composition, header, children }) {
    const { ui } = usePage().props;
    const sections = composition?.sections ?? [];
    const ownHeading = sections.some((section) => HEADING_SECTIONS.includes(section.type));
    const builtIn = header && ! ownHeading ? <PageHeader {...header}>{children}</PageHeader> : null;

    if (sections.length) {
        return (
            <>
                {builtIn}
                <PageComposition composition={composition} emptyTitle={ui.nothingPublished} />
            </>
        );
    }

    return builtIn;
}

// Title, description, canonical, robots, social and structured-data tags are
// resolved on the server (props.seo.head) and managed by Inertia's head
// manager, so this layout renders none of them.
export default function PublicLayout({ breadcrumbs, children }) {
    const { site, flash, ui } = usePage().props;

    return (
        <>
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
