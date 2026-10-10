import { Link, useForm } from '@inertiajs/react';
import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { Errors, Pagination, Status, Tabs, initials, useWorkspace, when } from '../../../Components/Workspace/UI';
import Icon from '../../../Components/Icon';

// Plain-language summaries of the seeded roles, shown when inviting someone.
export const roleSummaries = {
    contributor: 'Writes content drafts',
    editor: 'Writes and edits pages and drafts, uploads media',
    translator: 'Edits language versions of content',
    reviewer: 'Approves work or sends it back with comments',
    publisher: 'Puts approved work live or schedules it',
    engagement_officer: 'Answers, assigns and closes requests',
    recruitment_officer: 'Manages vacancies and applications',
    administrator: 'Manages people, settings and website listings',
    security_auditor: 'Reads the activity log and system health',
    super_administrator: 'Can do everything, including security settings',
};
const twoStep = { on: ['active', 'On'], exempt: ['pending', 'Not required'], off: ['new', 'Not set up'] };

function Invite({ roles, locales }) {
    const { t } = useWorkspace();
    const form = useForm({ name: '', email: '', locale: locales[0] ?? 'en', roles: [] });
    const toggle = id => form.setData('roles', form.data.roles.includes(id) ? form.data.roles.filter(value => value !== id) : [...form.data.roles, id]);
    return <section id="invite" className="ws-card ws-narrow" aria-labelledby="invite-title">
        <div className="ws-card-header"><div><h2 id="invite-title" className="ws-card-title">{t('Invite a person')}</h2><p className="text-sm ws-muted">{t('They set a password and turn on two-step verification when they first sign in.')}</p></div></div>
        <form className="ws-card-body" onSubmit={event => { event.preventDefault(); form.post('/admin/users/invitations', { preserveScroll: true, onSuccess: () => form.reset() }); }}>
            <Errors errors={form.errors} />
            <div><label className="form-label" htmlFor="invite-name">{t('Full name')}</label><input id="invite-name" className="form-input" required value={form.data.name} onChange={event => form.setData('name', event.target.value)} /></div>
            <div><label className="form-label" htmlFor="invite-email">{t('Work email')}</label><input id="invite-email" type="email" className="form-input" required value={form.data.email} onChange={event => form.setData('email', event.target.value)} /></div>
            <fieldset className="ws-radio-cards">
                <legend>{t('What can they do?')}</legend>
                {roles.map(role => <label key={role.id} className="ws-radio-card">
                    <input type="checkbox" checked={form.data.roles.includes(String(role.id))} onChange={() => toggle(String(role.id))} />
                    <span><strong>{role.name}</strong>{roleSummaries[role.code] && <small>{t(roleSummaries[role.code])}</small>}</span>
                </label>)}
            </fieldset>
            <button className="button-primary w-full" disabled={form.processing}>{form.processing ? t('Sending…') : t('Send invitation')}</button>
        </form>
    </section>;
}

export default function Index({ users, roles, locales, filters = {} }) {
    const { t, can } = useWorkspace();
    const search = useForm({ q: filters.q ?? '', status: filters.status ?? '' });
    const submit = event => { event.preventDefault(); search.transform(data => Object.fromEntries(Object.entries(data).filter(([, value]) => value))); search.get('/admin/users', { preserveScroll: true }); };

    return <WorkspaceLayout title="Team and access" description="Who can sign in, what they can do, and whether their account is protected.">
        {can('roles.manage') && <Tabs label="Team sections" items={[{ href: '/admin/users', label: 'People', count: users.total, current: true }, { href: '/admin/roles', label: 'Roles', count: roles.length }]} />}
        <div className="ws-split">
            <section className="ws-card ws-wide" aria-label={t('People')}>
                <form role="search" className="ws-card-body ws-toolbar" onSubmit={submit}>
                    <div className="ws-grow"><label className="form-label" htmlFor="people-search">{t('Find a person')}</label><div className="ws-search-field"><Icon name="search" className="size-4" /><input id="people-search" type="search" placeholder={t('Name or email')} value={search.data.q} onChange={event => search.setData('q', event.target.value)} /></div></div>
                    <div><label className="form-label" htmlFor="people-status">{t('Account')}</label><select id="people-status" className="form-input" value={search.data.status} onChange={event => search.setData('status', event.target.value)}><option value="">{t('Any status')}</option>{['active', 'invited', 'suspended', 'locked', 'disabled'].map(value => <option key={value} value={value}>{t(value[0].toUpperCase() + value.slice(1))}</option>)}</select></div>
                    <button className="button-dark" disabled={search.processing}>{t('Search')}</button>
                    {(filters.q || filters.status) && <Link className="button-tertiary" href="/admin/users">{t('Clear')}</Link>}
                </form>
                {users.data.length ? <div className="ws-table-wrap"><table className="ws-table">
                    <thead><tr><th scope="col">{t('Person')}</th><th scope="col">{t('Roles')}</th><th scope="col">{t('Two-step')}</th><th scope="col">{t('Account')}</th><th scope="col">{t('Last sign-in')}</th><th scope="col"><span className="sr-only">{t('Actions')}</span></th></tr></thead>
                    <tbody>{users.data.map(person => <tr key={person.id}>
                        <td><span className="flex items-center gap-3"><span className="ws-avatar-sm" aria-hidden="true">{initials(person.name)}</span><span className="min-w-0"><span className="ws-strong block">{person.name}</span><span className="ws-sub">{person.email}{person.expires_at ? ` · ${t('access ends :date', { date: when(person.expires_at) })}` : ''}</span></span></span></td>
                        <td><span className="role-module-tags">{person.roles.map(role => <span key={role.id}>{role.name}</span>)}{!person.roles.length && <span className="ws-muted">{t('No roles')}</span>}</span></td>
                        <td><Status value={twoStep[person.two_step][0]} text={twoStep[person.two_step][1]} /></td>
                        <td><Status value={person.status} /></td>
                        <td className="ws-muted">{person.last_login_at ? when(person.last_login_at) : t('Never')}</td>
                        <td className="text-right">{can('users.manage') && <Link className="button-secondary" href={`/admin/users/${person.id}/edit`} aria-label={t('Manage :name', { name: person.name })}>{t('Manage')}</Link>}</td>
                    </tr>)}</tbody>
                </table></div> : <div className="ws-empty"><h3>{t('No one matches')}</h3><p>{t('Try another name or status.')}</p></div>}
            </section>
            {can('users.manage') && <Invite roles={roles} locales={locales} />}
        </div>
        <Pagination data={users} />
    </WorkspaceLayout>;
}
