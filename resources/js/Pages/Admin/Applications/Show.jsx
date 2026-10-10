import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { Editor, Field, Status, field, label, useWorkspace, when } from '../../../Components/Workspace/UI';
import Files from '../../../Components/Workspace/Files';

export default function Show({ application: a, destinations }) {
    const { t, can } = useWorkspace();
    const details = [['Email', a.email], ['Phone', a.phone_encrypted], ['Reference', a.reference_no], ['Applied', when(a.submitted_at)]].filter(([, value]) => value);

    return <WorkspaceLayout title={a.applicant_name} description={a.vacancy?.title} breadcrumbs={[{ label: 'Inbox' }, { label: 'Applications', href: a.vacancy_id ? `/admin/applications?vacancy=${a.vacancy_id}` : '/admin/applications' }]} actions={<Status value={a.status} />}>
        <div className="ws-split">
            <div className="ws-wide grid gap-6">
                <section className="ws-card" aria-labelledby="applicant-details">
                    <div className="ws-card-header"><h2 id="applicant-details" className="ws-card-title">{t('Application')}</h2></div>
                    <div className="ws-card-body">
                        <dl className="ws-dl">{details.map(([name, value]) => <div key={name}><dt>{t(name)}</dt><dd>{name === 'Email' ? <a className="text-link" href={`mailto:${value}`}>{value}</a> : value}</dd></div>)}</dl>
                        {a.cover_letter_encrypted && <section aria-labelledby="cover-letter"><h3 id="cover-letter" className="form-label">{t('Cover letter')}</h3><p className="ws-message">{a.cover_letter_encrypted}</p></section>}
                        {a.files?.length > 0 && <section aria-labelledby="applicant-files"><h3 id="applicant-files" className="form-label">{t('Files')}</h3><Files files={a.files} /></section>}
                    </div>
                </section>
            </div>
            <div className="ws-narrow">
                {can('applications.update-status') && destinations.length > 0 && <section className="ws-card" aria-labelledby="move-title">
                    <div className="ws-card-header"><h2 id="move-title" className="ws-card-title">{t('Move to the next stage')}</h2></div>
                    <div className="ws-card-body">
                        <Editor key={a.status} action={`/admin/applications/${a.id}`} method="patch" initial={{ status: destinations[0], reason: '' }} fields={[field('status', 'Move to', 'select', { options: destinations }), field('reason', 'Reason', 'textarea', { required: true, rows: 3, help: 'Kept in the applicant’s history.' })]} submit="Update status" />
                    </div>
                </section>}
                <section className="ws-card" aria-labelledby="history-title">
                    <div className="ws-card-header"><h2 id="history-title" className="ws-card-title">{t('History')}</h2></div>
                    <div className="ws-card-body">
                        <ol className="ws-timeline">
                            {[...(a.history ?? [])].reverse().map(entry => <li key={entry.id}><span>{entry.from_status ? t('Moved from :from to :to', { from: t(label(entry.from_status)), to: t(label(entry.to_status)) }) : t(label(entry.to_status))}{entry.actor?.name && ` · ${entry.actor.name}`}{entry.reason && <span className="block ws-muted">“{entry.reason}”</span>}<small className="ws-sub">{when(entry.created_at)}</small></span></li>)}
                        </ol>
                    </div>
                </section>
            </div>
        </div>
    </WorkspaceLayout>;
}
