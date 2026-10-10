import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { Editor, Field, Status, field, label, options, useWorkspace, when } from '../../../Components/Workspace/UI';
import { RequestFilters, RequestList } from '../../../Components/Workspace/RequestInbox';
import Files from '../../../Components/Workspace/Files';

const typeLabels = { rfp: 'Request for proposal', consultation: 'Consultation', partnership: 'Partnership', media: 'Media enquiry', contact: 'Contact' };

export default function Show({ submission: s, assignees, statuses, submissions, counts, filters, types }) {
    const { t, can } = useWorkspace();
    const details = [['Contact', s.contact_name], ['Role', s.role], ['Email', s.email], ['Phone', s.phone_encrypted], ['Timeframe', s.timeframe], ['Budget range', s.budget_range]].filter(([, value]) => value);

    return <WorkspaceLayout title="Requests" description="Consultation, proposal, partnership and contact requests from the website." breadcrumbs={[{ label: 'Inbox' }, { label: 'Requests', href: '/admin/engagement' }]}>
        <RequestFilters counts={counts} filters={filters} types={types} />
        <div className="ws-split">
            <RequestList submissions={submissions} filters={filters} current={s.id} />
            <article className="ws-wide ws-card" aria-labelledby="request-title">
                <header className="ws-card-body border-b border-edge">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="min-w-0">
                            <h2 id="request-title" className="ws-title text-3xl">{s.organization_name || s.contact_name}</h2>
                            <p className="mt-1 text-sm ws-muted">{t(typeLabels[s.type] ?? label(s.type))} · {s.reference_no} · {t('received :when', { when: when(s.submitted_at) })}</p>
                        </div>
                        <Status value={s.status} />
                    </div>
                    {can('engagement.update-status') && statuses.length > 0 ? <Editor key={`${s.id}-${s.status}`} action={`/admin/engagement/${s.id}`} method="patch" submit={false} initial={{ status: statuses[0], assigned_to: can('engagement.assign') ? (s.assigned_to ?? '') : '', note: '' }}>
                        {form => <div className="grid gap-3">
                            <div className="ws-toolbar">
                                <div className="ws-grow"><Field form={form} {...field('assigned_to', 'Assigned to', 'select', { disabled: !can('engagement.assign'), options: [{ value: '', label: 'Not assigned' }, ...options(assignees)] })} /></div>
                                <div className="ws-grow"><Field form={form} {...field('status', 'Move to', 'select', { options: statuses })} /></div>
                                <button type="submit" className="button-primary" disabled={form.processing}>{form.processing ? t('Saving…') : t('Update request')}</button>
                                {s.email && <a className="button-secondary" href={`mailto:${s.email}?subject=${encodeURIComponent(s.reference_no)}`}>{t('Reply by email')}</a>}
                            </div>
                            <Field form={form} {...field('note', 'Internal note', 'textarea', { maxLength: 4000, rows: 2, help: 'Only staff can see notes. They are saved in the history.' })} />
                        </div>}
                    </Editor> : s.email && <a className="button-secondary mt-4" href={`mailto:${s.email}?subject=${encodeURIComponent(s.reference_no)}`}>{t('Reply by email')}</a>}
                </header>
                <div className="ws-card-body">
                    {details.length > 0 && <dl className="ws-dl">{details.map(([name, value]) => <div key={name}><dt>{t(name)}</dt><dd>{name === 'Email' ? <a className="text-link" href={`mailto:${value}`}>{value}</a> : value}</dd></div>)}</dl>}
                    {s.description_encrypted && <section aria-labelledby="request-message"><h3 id="request-message" className="form-label">{t('Message')}</h3><p className="ws-message">{s.description_encrypted}</p></section>}
                    {s.files?.length > 0 && <section aria-labelledby="request-files"><h3 id="request-files" className="form-label">{t('Attachments')}</h3><Files files={s.files} /></section>}
                    <section aria-labelledby="request-history"><h3 id="request-history" className="form-label">{t('History')}</h3>
                        <ol className="ws-timeline">
                            {[...(s.history ?? [])].reverse().map(entry => <li key={entry.id}><span>{t('Moved from :from to :to', { from: t(label(entry.from_status)), to: t(label(entry.to_status)) })}{entry.actor?.name && ` · ${entry.actor.name}`}{entry.note && <span className="block ws-muted">“{entry.note}”</span>}<small className="ws-sub">{when(entry.created_at)}</small></span></li>)}
                            <li><span>{t('Received through the website')}<small className="ws-sub">{when(s.submitted_at)}</small></span></li>
                        </ol>
                    </section>
                </div>
            </article>
        </div>
    </WorkspaceLayout>;
}
