import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { Editor, Field, Panel, label, useWorkspace } from '../../../Components/Workspace/UI';
export default function Edit({items}) {
 const {t}=useWorkspace();
 const flat=Object.values(items).flat();
 return <WorkspaceLayout title="Menus and footer" description="Open a menu, update its labels and order, then save."><Panel><Editor key={flat.map(i=>i.lock_version).join('-')} action="/admin/navigation" method="put" initial={{items:flat.map(i=>({id:i.id,lock_version:i.lock_version,label:i.label,description:i.description??'',sort_order:i.sort_order,enabled:!!i.enabled}))}} submit="Save navigation">{form=>{
  let index=-1;
  return Object.entries(items).map(([group,list],groupIndex)=>{
   const start=index+1;
   const cards=list.map(item=>{
    const i=++index;
    const nested={data:form.data.items[i],errors:Object.fromEntries(Object.entries(form.errors).filter(([key])=>key.startsWith(`items.${i}.`)).map(([key,value])=>[key.split('.').at(-1),value])),setData:(key,value)=>form.setData('items',form.data.items.map((row,n)=>n===i?{...row,[key]:value}:row))};
    return <div key={item.id} className="workspace-menu-item"><Field form={nested} name="label" label="Label" required/><Field form={nested} name="sort_order" label="Order" type="number" min="0"/><Field form={nested} name="enabled" label="Enabled" type="checkbox"/><details className="workspace-advanced"><summary>{t('Description')}</summary><div className="mt-4"><Field form={nested} name="description" label="Description"/></div></details></div>;
   });
   const errors=Object.keys(form.errors).some(key=>list.some((_,offset)=>key.startsWith(`items.${start+offset}.`)));
   return <details key={group} className="workspace-setting-group" open={groupIndex===0||errors}><summary className="font-semibold">{label(group)} <span className="text-sm text-muted">({list.length})</span></summary><div className="grid gap-4 mt-5">{cards}</div></details>;
  });
 }}</Editor></Panel></WorkspaceLayout>;
}
