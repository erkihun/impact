import { Link, usePage } from '@inertiajs/react';
import { useWorkspace } from './UI';

/** Settings pages: categories on the left, the page on the right. */
export default function SettingsNav({ groups = {}, category, children }) {
    const { t } = useWorkspace();
    const path = usePage().url.split('?')[0];
    const link = (href, text, current) => <Link key={href} href={href} aria-current={current ? 'page' : undefined}>{t(text)}</Link>;
    return <div className="ws-split">
        <nav className="ws-settings-nav ws-list-pane" aria-label={t('Settings categories')}>
            <div>{link('/admin/settings', 'Overview', path === '/admin/settings')}</div>
            {Object.values(groups).map(group => <div key={group.label}>
                <p>{t(group.label)}</p>
                {Object.entries(group.categories).map(([key, item]) => link(`/admin/settings/${key}`, item.label, category === key))}
            </div>)}
            <div>
                <p>{t('Records')}</p>
                {link('/admin/settings/history', 'Change history', path === '/admin/settings/history')}
                {link('/admin/settings/diagnostics', 'Check that settings work', path === '/admin/settings/diagnostics')}
            </div>
        </nav>
        <div className="ws-wide grid gap-6 content-start">{children}</div>
    </div>;
}
