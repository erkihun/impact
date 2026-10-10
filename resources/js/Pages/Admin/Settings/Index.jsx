import { Link, useForm } from '@inertiajs/react';
import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { rows, useWorkspace, when } from '../../../Components/Workspace/UI';
import SettingsNav from '../../../Components/Workspace/SettingsNav';
import Icon from '../../../Components/Icon';

const common = ['general', 'branding', 'homepage', 'content', 'seo', 'appearance', 'localization'];

export default function Index({ categories, navigationGroups, searchResults, recentChanges, environment }) {
    const { t, filters = {} } = useWorkspace();
    const search = useForm({ q: filters.q ?? '' });
    const all = Object.values(categories);
    const everyday = common.map(key => all.find(category => category.category === key)).filter(Boolean);
    const advanced = all.filter(category => !common.includes(category.category));
    const cards = list => <div className="workspace-task-grid">{list.map(category => <Link key={category.category} className="workspace-task-card" href={`/admin/settings/${category.category}`}><span><strong>{t(category.label)}</strong><span className="workspace-task-description">{t(category.description)}</span></span><Icon name="arrow-right" className="size-4" /></Link>)}</div>;

    return <WorkspaceLayout title="Settings" description="Organization details, branding and how the website behaves. Every change is recorded with a reason.">
        <SettingsNav groups={navigationGroups}>
            <form role="search" className="ws-search-field" onSubmit={event => { event.preventDefault(); search.get('/admin/settings', { preserveScroll: true }); }}>
                <Icon name="search" className="size-4" /><label className="sr-only" htmlFor="settings-search">{t('Find a setting')}</label><input id="settings-search" type="search" placeholder={t('Find a setting, for example “logo” or “timezone”')} value={search.data.q} onChange={event => search.setData('q', event.target.value)} />
            </form>
            {rows(searchResults).length > 0 && <section className="ws-card" aria-labelledby="settings-results">
                <div className="ws-card-header"><h2 id="settings-results" className="ws-card-title">{t('Matching settings')}</h2></div>
                <ul className="ws-rows">{rows(searchResults).map(result => <li key={result.key} className="ws-row"><span className="ws-row-main"><Link href={`/admin/settings/${result.category}#${result.key.replaceAll('.', '__')}`}><strong>{t(result.label)}</strong></Link><small className="ws-sub">{t(result.description)}</small></span></li>)}</ul>
            </section>}
            <section aria-labelledby="everyday-title" className="grid gap-3"><h2 id="everyday-title" className="ws-section-title">{t('Everyday website settings')}</h2>{cards(everyday)}</section>
            {advanced.length > 0 && <details className="ws-card ws-card-body"><summary className="font-semibold cursor-pointer">{t('Technical and operational settings')}</summary><div className="mt-4">{cards(advanced)}</div></details>}
            {rows(recentChanges).length > 0 && <section className="ws-card" aria-labelledby="recent-settings">
                <div className="ws-card-header"><h2 id="recent-settings" className="ws-card-title">{t('Recent changes')}</h2><Link className="text-link text-sm" href="/admin/settings/history">{t('Change history')}</Link></div>
                <ul className="ws-rows">{rows(recentChanges).slice(0, 5).map(event => <li key={event.id} className="ws-row"><span className="ws-row-main"><span><strong>{event.actor?.name ?? t('System')}</strong> {t(event.action === 'settings.reset' ? 'reset settings to their defaults' : 'changed settings')}</span><small className="ws-sub">{when(event.created_at)}</small></span></li>)}</ul>
            </section>}
            <details className="ws-card ws-card-body"><summary className="font-semibold cursor-pointer">{t('Environment')}</summary><dl className="ws-dl mt-4">{Object.entries(environment).map(([key, value]) => <div key={key}><dt>{key}</dt><dd>{typeof value === 'object' ? JSON.stringify(value) : String(value ?? '')}</dd></div>)}</dl></details>
        </SettingsNav>
    </WorkspaceLayout>;
}
