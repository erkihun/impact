import { Link } from '@inertiajs/react';
import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { Editor, Field, field, useWorkspace } from '../../../Components/Workspace/UI';
import { roleSummaries } from './Index';
import Icon from '../../../Components/Icon';

export default function Edit({ managedUser: user, roles, statuses }) {
    const { t } = useWorkspace();
    return <WorkspaceLayout title={user.name} description={user.email} breadcrumbs={[{ label: 'Organization' }, { label: 'Team and access', href: '/admin/users' }]} actions={<Link className="button-secondary" href="/admin/users">{t('Back to team')}</Link>}>
        <Editor key={user.updated_at} action={`/admin/users/${user.id}`} method="patch" submit={false} initial={{ name: user.name, email: user.email, locale: 'en', status: user.status, expires_at: user.expires_at?.slice(0, 10) ?? '', roles: user.roles.map(role => String(role.id)) }}>
            {form => <div className="ws-split">
                <section className="ws-card ws-wide" aria-labelledby="person-details">
                    <div className="ws-card-header"><h2 id="person-details" className="ws-card-title">{t('Details and access')}</h2></div>
                    <div className="ws-card-body">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field form={form} {...field('name', 'Name', 'text', { required: true })} />
                            <Field form={form} {...field('email', 'Email address', 'email', { required: true })} />
                            <Field form={form} {...field('status', 'Account', 'select', { options: statuses })} />
                            <Field form={form} {...field('expires_at', 'Access ends', 'date', { help: 'Optional. Use for contractors and auditors.' })} />
                        </div>
                        <fieldset className="ws-radio-cards">
                            <legend>{t('Roles')}</legend>
                            <div className="grid gap-2 sm:grid-cols-2">{roles.map(role => <label key={role.id} className="ws-radio-card">
                                <input type="checkbox" checked={form.data.roles.includes(String(role.id))} onChange={event => form.setData('roles', event.target.checked ? [...form.data.roles, String(role.id)] : form.data.roles.filter(id => id !== String(role.id)))} />
                                <span><strong>{role.name}</strong>{roleSummaries[role.code] && <small>{t(roleSummaries[role.code])}</small>}</span>
                            </label>)}</div>
                            {form.errors.roles && <p className="field-error">{form.errors.roles}</p>}
                        </fieldset>
                    </div>
                </section>
                <aside className="ws-narrow">
                    <div className="ws-callout"><Icon name="shield" /><p>{t('Saving signs this person out everywhere. They will need to sign in again.')}</p></div>
                    <button type="submit" className="button-primary w-full" disabled={form.processing}>{form.processing ? t('Saving…') : t('Save and sign them out')}</button>
                </aside>
            </div>}
        </Editor>
    </WorkspaceLayout>;
}
