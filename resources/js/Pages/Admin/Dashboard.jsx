import { Link } from '@inertiajs/react';
import WorkspaceLayout from '../../Layouts/WorkspaceLayout';
import { Panel, Status, label, useWorkspace } from '../../Components/Workspace/UI';
import Icon from '../../Components/Icon';

const tasks = [
 ['pages.view', 'Website pages', 'Edit website pages and their sections.', '/admin/page-compositions', 'about'],
 ['content.view', 'Content', 'Manage articles, services and published work.', '/admin/content', 'rfp'],
 ['experts.manage', 'Experts', 'Update expert profiles and biographies.', '/admin/experts', 'experts'],
 ['media.view', 'Media library', 'Find and manage website images and files.', '/admin/media', 'media'],
 ['navigation.manage', 'Menus and footer', 'Update website links and footer navigation.', '/admin/navigation', 'menu'],
 ['engagement.view', 'Engagement', 'Review consultation and proposal requests.', '/admin/engagement', 'contact'],
 ['applications.view', 'Applications', 'Review incoming job applications.', '/admin/applications', 'careers'],
];
export default function Dashboard({metrics={},awaitingReview=[],recentWork=[],openEngagements=[],quickActions=[]}) {
 const {t,can}=useWorkspace();
 const visibleTasks=tasks.filter(([permission])=>can(permission));
 return <WorkspaceLayout title="Dashboard" description="Choose what you want to manage.">
  {visibleTasks.length>0&&<section aria-labelledby="workspace-tasks"><h2 id="workspace-tasks" className="heading-3 mb-4">{t('What would you like to do?')}</h2><div className="workspace-task-grid">{visibleTasks.map(([,title,description,href,icon])=><Link key={href} href={href} className="workspace-task-card"><span className="workspace-task-icon"><Icon name={icon} className="size-6" /></span><span><strong>{t(title)}</strong><span className="workspace-task-description">{t(description)}</span></span><Icon name="arrow-right" className="size-4" /></Link>)}</div></section>}
  {quickActions.length>0&&<div className="workspace-quick-actions"><span className="text-sm font-semibold">{t('Quick actions')}</span>{quickActions.map(action=><Link className="button-secondary" key={action.route} href={action.route}>{action.label}</Link>)}</div>}
  <div className="grid gap-6 lg:grid-cols-2">
   {can('content.view')&&<Panel title={t('Awaiting review')}>{awaitingReview.length?awaitingReview.map(item=><article key={item.id} className="workspace-queue-row"><div><Link className="text-link" href={`/admin/content/${item.id}`}>{item.current_version?.title??t('Untitled content')}</Link><p className="text-sm text-muted mt-1">{item.owner?.name}</p></div><Status value={item.status}/></article>):<p className="text-muted text-sm">{t('Nothing is waiting for review. Content submitted for approval will appear here.')}</p>}</Panel>}
   {can('engagement.view')&&<Panel title={t('Open engagement requests')}>{openEngagements.length?openEngagements.map(item=><article key={item.id} className="workspace-queue-row"><div><Link className="text-link" href={`/admin/engagement/${item.id}`}>{item.organization_name||item.contact_name}</Link><p className="text-sm text-muted mt-1">{item.reference_no}</p></div><Status value={item.status}/></article>):<p className="text-muted text-sm">{t('No open requests. New requests will appear here.')}</p>}</Panel>}
  </div>
  {Object.keys(metrics).length>0&&<section aria-labelledby="workspace-overview"><h2 id="workspace-overview" className="heading-3 mb-4">{t('Overview')}</h2><div className="workspace-metrics">{Object.entries(metrics).map(([key,value])=><div key={key}><strong>{value}</strong><span>{t(label(key))}</span></div>)}</div></section>}
  {can('content.view')&&recentWork.length>0&&<Panel title={t('Recently edited')}><div className="grid gap-3">{recentWork.map(item=><Link key={item.id} className="text-link" href={`/admin/content/${item.id}`}>{item.current_version?.title??t('Untitled content')}</Link>)}</div></Panel>}
 </WorkspaceLayout>;
}
