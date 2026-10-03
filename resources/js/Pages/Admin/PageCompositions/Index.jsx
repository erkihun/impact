import { Link } from '@inertiajs/react';
import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { Pagination, Status, Table, label, useWorkspace } from '../../../Components/Workspace/UI';

export const localeLabel = locale => ({ am: 'አማርኛ', en: 'English' }[locale] ?? locale.toUpperCase());

export default function Index({ compositions }) {
    const { t } = useWorkspace();
    return <WorkspaceLayout title="Website pages" description="Manage both languages of each page in one place.">
        <Table data={compositions} columns={[
            { label: 'Page', render: page => <Link className="text-link" href={`/admin/page-compositions/${page.translations[0].id}`}>{label(page.page_key)}</Link> },
            { label: 'Languages', render: page => <div className="flex flex-wrap gap-3">{page.translations.map(translation => <Link key={translation.locale} className="composition-language-card" href={`/admin/page-compositions/${translation.id}`}>
                <strong lang={translation.locale}>{localeLabel(translation.locale)}</strong>
                <Status value={translation.state} />
                <span className="text-xs text-muted">v{translation.version_no} · {translation.sections_count} {t('Sections')}</span>
            </Link>)}</div> },
        ]} />
        <Pagination data={compositions} />
    </WorkspaceLayout>;
}
