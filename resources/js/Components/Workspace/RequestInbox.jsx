import { Link, router } from '@inertiajs/react';
import { Pagination, Tabs, label, useWorkspace, when } from './UI';

const views = [['new', 'New'], ['progress', 'In progress'], ['waiting', 'Waiting on client'], ['closed', 'Closed']];
const typeLabels = { rfp: 'RFP', consultation: 'Consultation', partnership: 'Partnership', media: 'Media', contact: 'Contact' };
const query = values => { const params = new URLSearchParams(Object.entries(values).filter(([, value]) => value)); const text = params.toString(); return text ? `?${text}` : ''; };

/** Status tabs and filters for the requests inbox. */
export function RequestFilters({ counts = {}, filters = {}, types = [] }) {
    const { t, can } = useWorkspace();
    const change = (name, value) => router.get('/admin/engagement', Object.fromEntries(Object.entries({ ...filters, [name]: value }).filter(([, v]) => v)), { preserveScroll: true });
    return <div className="flex flex-wrap items-end justify-between gap-3">
        <div className="min-w-0 flex-1"><Tabs label="Request status" items={views.map(([value, text]) => ({ href: `/admin/engagement${query({ ...filters, view: value === 'new' ? '' : value })}`, label: text, count: counts[value] ?? 0, current: filters.view === value }))} /></div>
        <div className="flex flex-wrap gap-2">
            <label className="sr-only" htmlFor="request-type">{t('Request type')}</label>
            <select id="request-type" className="form-input w-auto" value={filters.type ?? ''} onChange={event => change('type', event.target.value)}><option value="">{t('All types')}</option>{types.map(type => <option key={type} value={type}>{t(typeLabels[type] ?? label(type))}</option>)}</select>
            <label className="sr-only" htmlFor="request-assigned">{t('Assigned to')}</label>
            <select id="request-assigned" className="form-input w-auto" value={filters.assigned ?? ''} onChange={event => change('assigned', event.target.value)}><option value="">{t('Anyone')}</option>{can('engagement.update-status') && <option value="me">{t('Me')}</option>}<option value="none">{t('Not assigned')}</option></select>
        </div>
    </div>;
}

/** The list pane; the open request is highlighted. */
export function RequestList({ submissions, filters = {}, current = null }) {
    const { t } = useWorkspace();
    const suffix = query({ view: filters.view === 'new' ? '' : filters.view, type: filters.type, assigned: filters.assigned });
    return <div className="ws-list-pane">
        {submissions.data.length ? <ul className="ws-inbox-list" aria-label={t('Requests')}>{submissions.data.map(item => <li key={item.id}>
            <Link className="ws-inbox-item" href={`/admin/engagement/${item.id}${suffix}`} aria-current={item.id === current ? 'true' : undefined} preserveScroll>
                <span className="ws-inbox-item-top"><span className={`ws-type type-${item.type}`}>{t(typeLabels[item.type] ?? label(item.type))}</span><strong>{item.organization_name || item.contact_name}</strong><small className="ws-muted">{when(item.submitted_at)}</small></span>
                <span className="text-sm ws-muted">{item.organization_name ? `${item.contact_name} · ` : ''}{item.reference_no}</span>
                {item.assignee ? <span className="text-xs ws-muted">{t('Assigned to :name', { name: item.assignee.name })}</span> : <span className="ws-unassigned">{t('Not assigned')}</span>}
            </Link>
        </li>)}</ul> : <div className="ws-card ws-empty"><h3>{t('Nothing here')}</h3><p>{t('New requests from the website appear here.')}</p></div>}
        <Pagination data={submissions} />
    </div>;
}
