import { Link } from '@inertiajs/react';
import { forwardRef } from 'react';

// Public pages are Inertia pages, so same-origin links navigate without a full
// reload. Anything else (other origins, downloads, mail/tel, new tabs) stays a
// plain anchor.
function isInternal(href) {
    if (! href || typeof window === 'undefined') {
        return false;
    }

    try {
        const url = new URL(href, window.location.origin);

        return url.origin === window.location.origin
            && ! url.pathname.startsWith('/admin')
            && ! url.pathname.startsWith('/storage')
            && ! /\.(pdf|docx?|xlsx?|pptx?|zip|xml|txt)$/i.test(url.pathname);
    } catch {
        return false;
    }
}

const AppLink = forwardRef(function AppLink({ href, target, download, children, ...props }, ref) {
    if (target || download || ! isInternal(href)) {
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
