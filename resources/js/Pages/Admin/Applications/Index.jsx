import { Link, router } from '@inertiajs/react';
import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { Editor, Field, field, label, useWorkspace, when } from '../../../Components/Workspace/UI';

const stageLabels = { received: 'Received', screening: 'Screening', shortlisted: 'Shortlisted', interview: 'Interview', offered: 'Offered', hired: 'Hired', rejected: 'Not progressing', withdrawn: 'Withdrawn', anonymized: 'Anonymized' };
const stage = value => stageLabels[value] ?? label(value);

function Card({ item, showVacancy }) {
    const { t, can } = useWorkspace();
    return <div className="ws-board-card">
        <Link href={`/admin/applications/${item.id}`}><strong>{item.name}</strong><small className="ws-sub">{t('Applied :when', { when: when(item.submitted_at) })}{item.files ? ` · ${t(item.files === 1 ? '1 file' : ':count files', { count: item.files })}` : ''}</small>{showVacancy && item.vacancy && <small className="ws-sub">{item.vacancy}</small>}</Link>
        {can('applications.update-status') && item.moves.length > 0 && <details>
            <summary>{t('Move')}</summary>
            <Editor action={`/admin/applications/${item.id}`} method="patch" submit={false} initial={{ status: item.moves[0], reason: '' }} className="mt-3 gap-3">
                {form => <>
                    <Field form={form} {...field('status', 'Move to', 'select', { options: item.moves.map(value => ({ value, label: stage(value) })) })} />
                    <Field form={form} {...field('reason', 'Reason', 'textarea', { required: true, rows: 2, help: 'Kept in the applicant’s history.' })} />
                    <button type="submit" className="button-primary" disabled={form.processing}>{form.processing ? t('Saving…') : t('Move to :stage', { stage: t(stage(form.data.status)) })}</button>
                </>}
            </Editor>
        </details>}
    </div>;
}

export default function Index({ columns = [], closedCount = 0, vacancies = [], filters = {} }) {
    const { t, can } = useWorkspace();
    const visit = values => router.get('/admin/applications', Object.fromEntries(Object.entries({ ...filters, ...values }).filter(([, value]) => value)), { preserveScroll: true });

    return <WorkspaceLayout title="Applications" description="Move applicants through each stage. Every move asks for a short reason, kept in their history." actions={<>
        <div><label className="form-label" htmlFor="board-vacancy">{t('Vacancy')}</label><select id="board-vacancy" className="form-input w-auto min-w-64" value={filters.vacancy ?? ''} onChange={event => visit({ vacancy: event.target.value })}><option value="">{t('All vacancies')}</option>{vacancies.map(vacancy => <option key={vacancy.id} value={vacancy.id}>{vacancy.label}</option>)}</select></div>
        {filters.vacancy && can('vacancies.manage') && <Link className="button-secondary self-end" href={`/admin/website/vacancies/${filters.vacancy}/edit`}>{t('Edit vacancy')}</Link>}
    </>}>
        <div className="ws-board">{columns.map(column => <section key={column.status} className="ws-board-column" aria-labelledby={`stage-${column.status}`}>
            <h2 id={`stage-${column.status}`}>{t(stage(column.status))}<span className="ws-count">{column.items.length}</span></h2>
            {column.items.map(item => <Card key={item.id} item={item} showVacancy={!filters.vacancy} />)}
            {!column.items.length && <p className="ws-board-empty">{t('No one here yet')}</p>}
        </section>)}</div>
        {closedCount > 0 && <p className="text-sm ws-muted"><button type="button" className="text-link" onClick={() => visit({ closed: filters.closed ? '' : '1' })}>{t(filters.closed ? 'Hide closed applications' : 'Show :count closed applications', { count: closedCount })}</button> {t('(not progressing, withdrawn or anonymized).')}</p>}
    </WorkspaceLayout>;
}
