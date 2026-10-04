import { Link, useForm } from '@inertiajs/react';
import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { Action, Errors, Pagination, Status, date, label, useWorkspace } from '../../../Components/Workspace/UI';

export const canPublishPage = (state, can) => can('pages.publish') && (['approved', 'scheduled'].includes(state) || (state === 'in_review' && can('pages.approve')) || (['draft', 'changes_requested'].includes(state) && can('pages.update') && can('pages.approve')));

export const localeLabel = () => 'English';

export const pageTitle = key => {
    const [name, type] = key.split('.');
    if (key === 'home') return 'Homepage';
    if (key === 'rfp') return 'Request for proposal';
    if (name === 'legal') return label(type);
    if (name === 'errors') return `${type} error page`;
    if (type === 'index') return `${label(name)} overview`;
    if (type === 'show') return `${label(name)} detail page`;
    return label(key.replaceAll('.', ' '));
};

export default function Index({ compositions, summary, filters = {}, states = [] }) {
    const { can } = useWorkspace();
    const form = useForm({ q: filters.q ?? '', state: filters.state ?? '' });
    const filtered = !!(filters.q || filters.state);
    return <WorkspaceLayout title="Website pages" description="Find a page, open its sections, and manage publication." actions={<Link className="button-secondary" href="/en">View website</Link>}>
        <div className="pages-overview" aria-label="Latest page versions">
            {[['Total pages', summary.total], ['Published', summary.published], ['Work in progress', summary.in_progress]].map(([title, count]) => <div key={title}><span>{title}</span><strong>{count}</strong></div>)}
        </div>
        <section className="pages-directory" aria-labelledby="pages-directory-title">
            <div className="pages-directory-heading"><div><h2 className="heading-3" id="pages-directory-title">Your website pages</h2><p className="form-help">Each page appears once, with its latest English version.</p></div><span className="text-sm text-muted" role="status">{compositions.total} {filtered ? 'matching' : ''} pages</span></div>
            <form className="pages-search" role="search" onSubmit={event => { event.preventDefault(); form.get('/admin/page-compositions', { preserveScroll: true, preserveState: true }); }}>
                <div><label className="form-label" htmlFor="page-search">Find a page</label><input className="form-input" id="page-search" type="search" placeholder="Search by page name…" maxLength={100} value={form.data.q} onChange={event => form.setData('q', event.target.value)} /></div>
                <div><label className="form-label" htmlFor="page-status">Current status</label><select className="form-input" id="page-status" value={form.data.state} onChange={event => form.setData('state', event.target.value)}><option value="">All statuses</option>{states.map(state => <option key={state} value={state}>{label(state)}</option>)}</select></div>
                <button className="button-primary" disabled={form.processing}>Apply filters</button>
                {filtered && <Link className="button-tertiary" href="/admin/page-compositions">Clear</Link>}
            </form>
            <Errors errors={form.errors} />
            <div className="pages-list">
                {compositions.data.map(page => {
                    const version = page.translations[0];
                    const href = `/admin/page-compositions/${version.id}`;
                    return <article className="pages-row" key={page.page_key}>
                        <div className="pages-row-title"><span className="pages-row-mark" aria-hidden="true">{page.page_key === 'home' ? 'H' : pageTitle(page.page_key).slice(0, 1)}</span><div><h3><Link href={href}>{pageTitle(page.page_key)}</Link></h3><p>{page.page_key.includes('.show') ? 'Shared layout for individual entries' : page.page_key.startsWith('errors.') ? 'System page' : 'Website page'}</p></div></div>
                        <div className={`pages-row-state pages-row-state-${version.state}`}><Status value={version.state} /><span>Version {version.version_no}</span></div>
                        <div className="pages-row-sections"><strong>{version.sections_count}</strong><span>{version.sections_count === 1 ? 'section' : 'sections'}</span></div>
                        <div className="pages-row-updated"><span>Updated</span><time dateTime={version.updated_at}>{date(version.updated_at)}</time></div>
                        <div className="pages-row-actions flex flex-wrap items-center gap-2">{canPublishPage(version.state, can) && <Action href={`/admin/page-compositions/${version.id}/publish`} data={{lock_version:version.lock_version}} className="button-primary">Publish now</Action>}<Link className="button-secondary pages-row-action" href={href} aria-label={`${can('pages.update') || can('pages.create') ? 'Manage' : 'View'} ${pageTitle(page.page_key)}`}>{can('pages.update') || can('pages.create') ? 'Manage page' : 'View details'} <span aria-hidden="true">→</span></Link></div>
                    </article>;
                })}
                {!compositions.data.length && <div className="pages-empty"><h3 className="heading-3">{filtered ? 'No pages match your filters' : 'No website pages yet'}</h3><p className="form-help">{filtered ? 'Try a shorter page name or choose another status.' : 'Website pages will appear here when they are configured.'}</p>{filtered && <Link className="button-secondary mt-4" href="/admin/page-compositions">Show all pages</Link>}</div>}
            </div>
        </section>
        <Pagination data={compositions} />
    </WorkspaceLayout>;
}
