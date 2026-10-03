import { Link } from '@inertiajs/react';
import { useState } from 'react';
import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { useWorkspace } from '../../../Components/Workspace/UI';
import { groupPermissions, matchesPermission, moduleLabel, permissionLabel } from '../../../Components/Workspace/RolePermissions';
import Icon from '../../../Components/Icon';

export default function Index({ roles }) {
 const { t }=useWorkspace();
 const [query,setQuery]=useState('');
 const normalized=query.trim().toLocaleLowerCase();
 const visible=roles.filter(role=>`${role.name} ${role.code??''}`.toLocaleLowerCase().includes(normalized)||role.permissions.some(permission=>matchesPermission(permission,normalized,t)));
 return <WorkspaceLayout title="Roles and permissions" description="See who each role applies to, then review what it allows.">
  <section className="admin-panel role-search-panel"><div><label className="form-label" htmlFor="role-search">{t('Find a role or permission')}</label><input id="role-search" className="form-input mt-2" type="search" value={query} onChange={event=>setQuery(event.target.value)} placeholder={t('Search by name or permission')} /></div><p className="text-sm text-muted" role="status">{visible.length} / {roles.length} {t('Roles')}</p></section>
  <div className="role-card-grid">{visible.map(role=>{
   const groups=groupPermissions(role.permissions), modules=Object.keys(groups);
   return <section key={role.id} className="admin-panel role-card"><header><span className="workspace-task-icon"><Icon name="shield" className="size-6" /></span><div><h2 className="heading-3">{role.name}</h2><p className="text-sm text-muted mt-1">{role.users_count} {t('Users')} / {role.permissions.length} {t('Permissions')}</p></div></header><div className="role-module-tags">{modules.slice(0,4).map(module=><span key={module}>{t(moduleLabel(module))}</span>)}{modules.length>4&&<span>+{modules.length-4} {t('More modules')}</span>}</div><details className="workspace-advanced"><summary>{t('View permissions')}</summary><div className="grid gap-4 mt-4">{Object.entries(groups).map(([module,list])=><div key={module}><h3 className="text-sm font-semibold mb-2">{t(moduleLabel(module))}</h3><ul className="role-permission-list">{list.map(permission=><li key={permission.id}>{t(permissionLabel(permission))}</li>)}</ul></div>)}{!role.permissions.length&&<p className="text-sm text-muted">{t('No permissions assigned.')}</p>}</div></details><Link className="button-secondary role-manage-button" href={`/admin/roles/${role.id}/edit`}>{t('Manage role')}<span className="sr-only">: {role.name}</span><Icon name="arrow-right" className="size-4" /></Link></section>;
  })}</div>
  {!visible.length&&<div className="empty-state"><p>{t('No roles match your search.')}</p>{query&&<button type="button" className="button-secondary mt-4" onClick={()=>setQuery('')}>{t('Clear search')}</button>}</div>}
 </WorkspaceLayout>;
}
