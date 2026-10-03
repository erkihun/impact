import { Link } from '@inertiajs/react';
import { useState } from 'react';
import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { Editor, field, useWorkspace } from '../../../Components/Workspace/UI';
import { matchesPermission, moduleLabel, permissionLabel } from '../../../Components/Workspace/RolePermissions';

function PermissionPicker({form,role,permissions}) {
 const {t}=useWorkspace();
 const [query,setQuery]=useState(''),[selectedOnly,setSelectedOnly]=useState(false);
 const selected=new Set(form.data.permissions.map(String)),original=new Set(role.permissions.map(p=>String(p.id)));
 const added=[...selected].filter(id=>!original.has(id)).length,removed=[...original].filter(id=>!selected.has(id)).length;
 const groups=Object.entries(permissions).map(([module,list])=>[module,list.filter(p=>(!selectedOnly||selected.has(String(p.id)))&&matchesPermission(p,query,t))]).filter(([,list])=>list.length);
 const toggle=(id,checked)=>form.setData('permissions',checked?[...selected,String(id)]:[...selected].filter(value=>value!==String(id)));
 return <>
  <section className="role-permission-toolbar"><div><label className="form-label" htmlFor="permission-search">{t('Find a permission')}</label><input id="permission-search" className="form-input mt-2" type="search" value={query} onChange={e=>setQuery(e.target.value)} placeholder={t('Search by name or permission')}/></div><label className="role-selected-filter"><input type="checkbox" checked={selectedOnly} onChange={e=>setSelectedOnly(e.target.checked)}/>{t('Selected only')}</label></section>
  <p className="text-sm text-muted" role="status">{selected.size} {t('Permissions selected')} {added>0&&` / +${added} ${t('Added permissions')}`} {removed>0&&` / -${removed} ${t('Removed permissions')}`}</p>
  <div className="grid gap-3">{groups.map(([module,list],index)=>{
   const full=permissions[module],count=full.filter(p=>selected.has(String(p.id))).length;
   return <details className="role-permission-group" key={`${module}-${Boolean(query)}-${selectedOnly}`} open={Boolean(query)||selectedOnly||index===0}><summary><span>{t(moduleLabel(module))}</span><span className="text-sm text-muted">{count} / {full.length}</span></summary><fieldset className="role-permission-options"><legend className="sr-only">{t(moduleLabel(module))}</legend>{list.map(p=><label className="role-permission-option" key={p.id} htmlFor={`permission-${p.id}`}><input id={`permission-${p.id}`} type="checkbox" checked={selected.has(String(p.id))} onChange={e=>toggle(p.id,e.target.checked)}/><span><strong>{t(permissionLabel(p))}</strong><small>{p.code}</small></span></label>)}</fieldset></details>;
  })}</div>
  {!groups.length&&<p className="empty-state">{t('No permissions match your filters.')}</p>}
  {selected.size===0&&<p className="status-warning" role="status">{t('Select at least one permission.')}</p>}<div className="role-save-bar"><div><p className="text-sm font-semibold">{role.users_count??0} {t('Users affected')}</p><p className="form-help">{t('Saving signs out people assigned to this role.')}</p></div><button className="button-primary" type="submit" disabled={form.processing||!form.isDirty||selected.size===0}>{form.processing?t('Saving…'):t('Save permissions and revoke affected sessions')}</button></div>
 </>;
}
export default function Edit({managedRole:role,permissions}) {
 const {t}=useWorkspace();
 return <WorkspaceLayout title={role.name} description="Choose the permissions this role needs. Changes apply to everyone assigned to it." actions={<Link className="button-secondary" href="/admin/roles">{t('Back to roles')}</Link>}>
  <div className="admin-panel"><Editor key={`${role.id}-${role.name}-${role.permissions.map(p=>p.id).join('-')}`} action={`/admin/roles/${role.id}`} method="patch" initial={{name:role.name,permissions:role.permissions.map(p=>String(p.id))}} fields={[field('name','Role name','text',{required:true,maxLength:100})]} submit={null}>{form=><PermissionPicker form={form} role={role} permissions={permissions}/>}</Editor></div>
 </WorkspaceLayout>;
}
