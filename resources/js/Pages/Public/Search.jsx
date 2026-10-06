import { router } from '@inertiajs/react';
import { useState } from 'react';
import AppLink from '../../Components/AppLink';
import Reveal from '../../Components/Reveal';
import { EmptyState, Pagination } from '../../Components/Public/Common';
import PublicLayout, { PageIntro } from '../../Layouts/PublicLayout';

export default function Search({ breadcrumbs, composition, header, query, action, results, pagination, links, copy }) {
    const [value, setValue] = useState(query ?? '');

    const submit = (event) => {
        event.preventDefault();
        router.get(action, value.trim() ? { q: value.trim() } : {}, { preserveState: true });
    };

    return (
        <PublicLayout breadcrumbs={breadcrumbs}>
            <PageIntro composition={composition} header={header} />

            <section className="public-section impact-editorial-surface" aria-labelledby="search-results-heading">
                <div className="content-container">
                    <form className="public-filter-panel" role="search" method="GET" action={action} onSubmit={submit}>
                        <label className="form-label" htmlFor="site-search-query">{copy.label}</label>
                        <div className="mt-2 flex flex-col gap-3 sm:flex-row">
                            <input
                                className="form-input min-w-0 flex-1"
                                id="site-search-query"
                                name="q"
                                type="search"
                                value={value}
                                maxLength={200}
                                placeholder={copy.placeholder}
                                enterKeyHint="search"
                                onChange={(event) => setValue(event.target.value)}
                            />
                            <button className="button-primary shrink-0 justify-center" type="submit">{copy.submit}</button>
                        </div>
                    </form>

                    <div className="public-section-grid mt-10 sm:mt-12">
                        <div className="public-section-rail">
                            <h2 id="search-results-heading" className="public-section-rail-title">{copy.resultsHeading}</h2>
                            <div className="editorial-rule-heading mt-6">
                                <p className="text-sm font-bold text-brand-950" role="status" aria-live="polite">{copy.count}</p>
                                {copy.resultsFor && <p className="text-sm text-muted">{copy.resultsFor}</p>}
                            </div>
                        </div>
                        <div>
                            {results.length > 0 ? (
                                <div className="public-register">
                                    {results.map((result, index) => (
                                        <Reveal as="article" key={result.href + result.number} index={index % 4} className="group public-register-row">
                                            <span className="m-index" aria-hidden="true">{result.number}</span>
                                            <div>
                                                <p className="eyebrow">{result.type}</p>
                                                <h3 className="mt-3 text-xl font-semibold leading-tight sm:text-2xl">
                                                    <AppLink href={result.href}>{result.title}</AppLink>
                                                </h3>
                                                {result.summary && <p className="mt-4 max-w-3xl text-sm leading-7 text-muted">{result.summary}</p>}
                                            </div>
                                            <span className="text-xl text-action-700 transition-transform group-hover:translate-x-1" aria-hidden="true">→</span>
                                        </Reveal>
                                    ))}
                                </div>
                            ) : (
                                <EmptyState title={copy.emptyTitle} description={copy.emptyDescription}>
                                    {query && <AppLink className="button-secondary" href={action}>{copy.clear}</AppLink>}
                                    <AppLink className="button-primary" href={links.services}>{copy.exploreServices}</AppLink>
                                    <AppLink className="button-secondary" href={links.insights}>{copy.browseInsights}</AppLink>
                                </EmptyState>
                            )}
                            <Pagination pagination={pagination} copy={copy} />
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
