import { Link } from '@inertiajs/react';
import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { useWorkspace } from '../../../Components/Workspace/UI';
import Icon from '../../../Components/Icon';

export default function Index({ collections = [], experts = null }) {
    const { t } = useWorkspace();
    const cards = [
        ...collections.map(collection => ({ ...collection, href: `/admin/website/${collection.key}` })),
        ...(experts ? [{ key: 'experts', label: 'Experts', description: 'Expert profiles on the Experts page.', icon: 'experts', href: '/admin/experts', ...experts }] : []),
    ];

    return <WorkspaceLayout title="Listings" description="Everything visitors browse on the website: services, industries, experts, case studies, insights, events and jobs.">
        <div className="workspace-task-grid">
            {cards.map(card => <Link key={card.key} href={card.href} className="workspace-task-card">
                <span className="workspace-task-icon"><Icon name={card.icon} className="size-6" /></span>
                <span>
                    <strong>{t(card.label)}</strong>
                    <span className="workspace-task-description">{t(card.description)}</span>
                    <span className="website-collection-counts">
                        <span><strong>{card.live}</strong> {t('on the website')}</span>
                        <span><strong>{card.drafts}</strong> {t(card.drafts === 1 ? 'draft' : 'drafts')}</span>
                    </span>
                </span>
                <Icon name="arrow-right" className="size-4" />
            </Link>)}
        </div>
    </WorkspaceLayout>;
}
