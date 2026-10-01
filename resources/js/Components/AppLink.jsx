import { Link, usePage } from '@inertiajs/react';
import { forwardRef } from 'react';

// Public pages are Inertia pages, so same-origin links navigate without a full
// reload. Anything else (other origins, downloads, mail/tel, new tabs) stays a
// plain anchor.
function isInternal(href, origin) {
    if (! href || String(href).startsWith('#')) {
        return false;
    }

    try {
        const url = new URL(href, origin);

        return url.origin === new URL(origin).origin
            && ! /^\/(restricted-media|application-files|submission-files)\//.test(url.pathname)
            && ! url.pathname.startsWith('/storage')
            && ! /\.(pdf|docx?|xlsx?|pptx?|zip|xml|txt)$/i.test(url.pathname);
    } catch {
        return false;
    }
}

const AppLink = forwardRef(function AppLink({ href, target, download, children, ...props }, ref) {
    const { site } = usePage().props;
    if (target || (download !== undefined && download !== false) || ! isInternal(href, site.seo.canonicalUrl)) {
        return (
            <a ref={ref} href={href} target={target} download={download} {...props}>
                {children}
            </a>
        );
    }

    return (
        <Link ref={ref} href={href} {...props}>
            {children}
        </Link>
    );
});

export default AppLink;
