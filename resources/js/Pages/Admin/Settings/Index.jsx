import { Link } from '@inertiajs/react';
import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { Filters, Panel, Table, date, field, rows, useWorkspace } from '../../../Components/Workspace/UI';
import SettingsNav from '../../../Components/Workspace/SettingsNav';
const common=['general','branding','homepage','content','seo','appearance','localization'];
export default function Index({categories,navigationGroups,searchResults,recentChanges,environment}) {
 const {t}=useWorkspace();
 const all=Object.values(categories), everyday=common.map(key=>all.find(c=>c.category===key)).filter(Boolean), advanced=all.filter(c=>!common.includes(c.category));
 const cards=list=><div className="workspace-task-grid">{list.map(c=><Link key={c.category} className="workspace-task-card" href={`/admin/settings/${c.category}`}><span><strong>{t(c.label)}</strong><span className="workspace-task-description">{t(c.description)}</span></span></Link>)}</div>;
 return <WorkspaceLayout title="Settings Center" description="Start with everyday website settings. Technical controls are grouped below.">
  <Filters fields={[field('q','Search settings','search')]}/>
  {rows(searchResults).length>0&&<Panel title={t('Search results')}><ul className="grid gap-4">{rows(searchResults).map(result=><li key={result.key}><Link className="text-link" href={`/admin/settings/${result.category}#${result.key.replaceAll('.','__')}`}>{t(result.label)}</Link><p>{t(result.description)}</p></li>)}</ul></Panel>}
  <section><h2 className="heading-3 mb-4">{t('Everyday website settings')}</h2>{cards(everyday)}</section>
  {advanced.length>0&&<details className="admin-panel"><summary className="font-semibold">{t('Technical and operational settings')}</summary><div className="mt-5">{cards(advanced)}</div></details>}
  <SettingsNav groups={navigationGroups}/>
  <details className="admin-panel"><summary className="font-semibold">{t('Recent changes')}</summary><div className="mt-5"><Table data={recentChanges} columns={[{label:'Action',key:'action'},{label:'Actor',render:e=>e.actor?.name},{label:'Date',render:e=>date(e.created_at)}]}/></div></details>
  <details className="admin-panel"><summary className="font-semibold">{t('Environment')}</summary><dl className="grid gap-3 mt-5">{Object.entries(environment).map(([key,value])=><div key={key}><dt className="font-bold">{key}</dt><dd>{typeof value==='object'?JSON.stringify(value):String(value??'')}</dd></div>)}</dl></details>
 </WorkspaceLayout>;
}
