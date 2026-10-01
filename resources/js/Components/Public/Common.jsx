import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import AppLink from '../AppLink';

export function Breadcrumbs({ items }) {
    const { ui } = usePage().props;

    if (! items?.length) {
        return null;
    }

    return (
        <div className="breadcrumb-band">
            <div className="content-container py-4">
                <nav className="breadcrumbs" aria-label={ui.breadcrumb}>
                    <ol className="flex flex-wrap items-center gap-2">
                        {items.map((item, index) => {
                            const last = index === items.length - 1;

                            return (
                                <li key={`${item.label}-${index}`} className="flex items-center gap-2">
                                    {! last && item.href ? (
                                        <>
                                            <AppLink href={item.href}>{item.label}</AppLink>
                                            <span aria-hidden="true">/</span>
                                        </>
                                    ) : (
                                        <span aria-current="page" className="font-medium text-brand-900">{item.label}</span>
                                    )}
                                </li>
                            );
                        })}
                    </ol>
                </nav>
            </div>
        </div>
    );
}

export function PageHeader({ eyebrow, title, summary, meta, children }) {
    return (
        <header className="public-page-header m-page-header">
            <div className="content-container">
                {eyebrow && <p className="insight-marker">{eyebrow}</p>}
                <h1>{title}</h1>
                {summary && <p className="m-lead">{summary}</p>}
                {meta && <p className="m-meta">{meta}</p>}
                {children && <div className="m-actions">{children}</div>}
            </div>
        </header>
    );
}

export function EmptyState({ title, description, children }) {
    return (
        <div className="empty-state border-0">
            <span className="mx-auto grid size-12 place-items-center rounded-full bg-action-50 text-action-700" aria-hidden="true">—</span>
            <h2 className="heading-3 mt-5">{title}</h2>
            {description && <p className="mx-auto mt-3 max-w-xl text-sm leading-7 text-muted">{description}</p>}
            {children && <div className="mt-6 flex flex-wrap justify-center gap-3">{children}</div>}
        </div>
    );
}

export function fieldId(name) {
    return String(name).replace(/\./g, '-').replace(/\[\]$/, '');
}

// Lists every validation error with a link to its field, and takes focus so
// screen-reader and keyboard users land on it after a failed submission.
export function ErrorSummary({ errors, title, className = '' }) {
    const ref = useRef(null);
    const entries = Object.entries(errors ?? {});

    useEffect(() => {
        if (entries.length > 0) {
            ref.current?.focus();
        }
    }, [errors]); // eslint-disable-line react-hooks/exhaustive-deps

    if (entries.length === 0) {
        return null;
    }

    return (
        <div ref={ref} className={`error-summary ${className}`} role="alert" aria-live="assertive" tabIndex={-1}>
            <p className="font-bold">{title}</p>
            <ul className="mt-3 list-disc space-y-1 ps-5">
                {entries.map(([field, message]) => (
                    <li key={field}>
                        <a className="font-medium underline underline-offset-2" href={`#${fieldId(field.split('.')[0])}`}>{message}</a>
                    </li>
                ))}
            </ul>
        </div>
    );
}

export function Pagination({ pagination, copy }) {
    if (! pagination?.previous && ! pagination?.next) {
        return null;
    }

    return (
        <nav className="m-pagination" aria-label={copy.pagination}>
            {pagination.previous && <AppLink className="button-secondary" href={pagination.previous} preserveScroll={false}>{copy.previous}</AppLink>}
            {pagination.next && <AppLink className="button-primary" href={pagination.next} preserveScroll={false}>{copy.next}</AppLink>}
        </nav>
    );
}
