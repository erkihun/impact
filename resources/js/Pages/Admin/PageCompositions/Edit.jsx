import { Link, router } from '@inertiajs/react';
import { Fragment, useState } from 'react';
import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { Action, Editor, Field, Status, field, label, options, useWorkspace } from '../../../Components/Workspace/UI';
import { canPublishPage, pageTitle } from './Index';
import Icon from '../../../Components/Icon';

function SectionEditor({composition:c,section,registry,eligibleMedia,newType,onDirtyChange}) {
 const {t}=useWorkspace();
 const v=section?.current_version, type=v?.type??newType, definition=registry[type];
 const content=Object.fromEntries(definition.fields.map(key=>[key,key==='items'?JSON.stringify(v?.content?.[key]??[],null,2):v?.content?.[key]??'']));
 const initial={lock_version:c.lock_version,type,variant:v?.variant??definition.default_variant,editor_label:section?.editor_label??'',enabled:v?.enabled??true,visibility_rule:v?.visibility_rule??'always',visible_from:v?.visible_from?.slice(0,16)??'',visible_until:v?.visible_until?.slice(0,16)??'',selection_mode:v?.selection_mode??'',maximum_items:v?.maximum_items??'',media_selection_present:true,media_asset_ids:(v?.media??[]).map(m=>String(m.media_asset_id)),...Object.fromEntries(Object.entries(content).map(([k,value])=>[`content_${k}`,value])),...Object.fromEntries(Object.entries(v?.presentation??{surface_tone:'default',container_width:'standard',spacing_top:'standard'}).map(([k,value])=>[`presentation_${k}`,value]))};
 const transform=data=>{const result={},content={},presentation={};Object.entries(data).forEach(([k,value])=>{if(k.startsWith('content_'))content[k.slice(8)]=value;else if(k.startsWith('presentation_'))presentation[k.slice(13)]=value;else result[k]=value;});return {...result,content,presentation};};
 const advanced=[...(section?[field('editor_label','Editor label','text',{required:true})]:[]),field('variant','Variant','select',{options:definition.variants}),field('visibility_rule','Visibility rule','select',{options:['always','scheduled','when_populated']}),field('visible_from','Visible from','datetime-local'),field('visible_until','Visible until','datetime-local'),...(definition.relations.length?[field('selection_mode','Content selection','select',{options:['','manual','featured','latest','related','category']}),field('maximum_items','Maximum items','number',{min:1,max:24})]:[])];
 const basic=[...(!section?[field('editor_label','Section name','text',{required:true})]:[]),...definition.fields.map(key=>field(`content_${key}`,label(key),['items','body','summary','quote'].includes(key)?'textarea':'text',{required:definition.required.includes(key)})),...(definition.media.length?[field('media_asset_ids','Approved media','select',{options:options(eligibleMedia,'title'),multiple:true})]:[]),field('enabled','Show this section','checkbox')];
 return <Editor action={`/admin/page-compositions/${c.id}/sections${section?'/'+section.id:''}`} method={section?'patch':'post'} initial={initial} transform={transform} onDirtyChange={onDirtyChange} fields={basic} submit={section?'Save section':'Add section'}>{form=><details className="workspace-advanced" open={advanced.some(f=>form.errors[f.name])||Object.keys(form.errors).some(key=>key.startsWith('presentation'))}><summary>{t('Layout and schedule')}</summary><p className="form-help mt-3">{t('Optional layout, scheduling and content selection settings.')}</p><div className="grid gap-4 mt-4">{advanced.map(f=><Field key={f.name} form={form} {...f}/>)}{Object.keys(initial).filter(k=>k.startsWith('presentation_')).map(k=><Field key={k} form={form} name={k} label={label(k.slice(13))}/>)}</div></details>}</Editor>;
}
const transitions={draft:['pages.update',['in_review']],changes_requested:['pages.update',['in_review']],in_review:['pages.approve',['changes_requested','approved']],approved:['pages.publish',['scheduled','published']],scheduled:['pages.publish',['published','archived']],published:['pages.publish',['archived']]};
const workflowLabels={in_review:'Send for review',changes_requested:'Request changes',approved:'Approve page',scheduled:'Schedule publication',published:'Publish now',archived:'Archive page'};
const steps=[['draft','Draft'],['in_review','In review'],['approved','Approved'],['published','Live']];
const stepIndex=state=>({draft:0,changes_requested:0,in_review:1,approved:2,scheduled:2,published:3})[state]??0;

export default function Edit(props) {return <CompositionEditor key={props.composition.id} {...props}/>;}
function CompositionEditor({composition:c,registry,previewUrl,eligibleMedia,archivedSections=[]}) {
 const {t,can}=useWorkspace();
 const [type,setType]=useState(Object.keys(registry)[0]);
 const [openId,setOpenId]=useState(c.sections[0]?.id??null);
 const [dirty,setDirty]=useState({}), [pendingHref,setPendingHref]=useState(null);
 const editable=c.editable&&can('pages.update');
 const unsavedSections=Object.entries(dirty).some(([key,value])=>key!=='workflow'&&value);
 const reportDirty=(key,value)=>setDirty(current=>current[key]===value?current:{...current,[key]:value});
 const leave=visit=>{if(!Object.values(dirty).some(Boolean))return true;setPendingHref(visit.url.href);return false;};
 const transition=transitions[c.state], destinations=transition&&can(transition[0])?transition[1]:[];
 const reorder=(index,offset)=>{const ids=c.sections.map(s=>s.id);[ids[index],ids[index+offset]]=[ids[index+offset],ids[index]];return {lock_version:c.lock_version,section_ids:ids};};
 const current=stepIndex(c.state);
 const openSection=id=>{setOpenId(id);requestAnimationFrame(()=>document.getElementById(`section-${id}`)?.scrollIntoView({behavior:'smooth',block:'start'}));};

 return <WorkspaceLayout title={pageTitle(c.page_key)} breadcrumbs={[{label:'Website'},{label:'Pages',href:'/admin/page-compositions'}]} actions={<>
  <a className="button-secondary" href={previewUrl} target="_blank" rel="noreferrer"><Icon name="eye" className="size-4"/>{t('Preview')}<span className="sr-only">{t('(opens in a new tab)')}</span></a>
  {!c.editable&&can('pages.create')&&<Action href={`/admin/page-compositions/${c.id}/drafts`} className="button-primary">{t('Edit this page')}</Action>}
 </>}>
  {pendingHref&&<div className="ws-unsaved" role="alert"><span>{t('You have unsaved changes. Leave without saving?')}</span><button type="button" className="button-secondary" onClick={()=>setPendingHref(null)}>{t('Keep editing')}</button><button type="button" className="button-primary" onClick={()=>{setPendingHref(null);router.visit(pendingHref);}}>{t('Discard changes')}</button></div>}

  <section className="ws-card ws-card-body" aria-label={t('Publishing progress')}>
   <div className="flex flex-wrap items-center justify-between gap-4">
    <ol className="ws-steps">{steps.map(([state,text],index)=><Fragment key={state}>{index>0&&<li className="ws-step-line" aria-hidden="true"/>}<li className={index<current||(index===current&&c.state==='published')?'is-done':index===current?'is-current':''} aria-current={index===current?'step':undefined}><span>{index<current||(index===current&&c.state==='published')?<Icon name="check" className="size-3"/>:index+1}</span>{t(text)}</li></Fragment>)}</ol>
    <span className="flex flex-wrap items-center gap-3 text-sm ws-muted"><Status value={c.state}/>{t('Version :n',{n:c.version_no})} · {t(c.sections.length===1?'1 section':':count sections',{count:c.sections.length})}</span>
   </div>
   {c.state==='changes_requested'&&<p className="status-warning">{t('A reviewer asked for changes. Update the sections, then send the page for review again.')}</p>}
   {!c.editable&&<p className="status-information">{t('This version is live and read-only. Choose “Edit this page” to start a new draft; the live page stays online until you publish.')}</p>}
  </section>

  {canPublishPage(c.state,can)&&<section className="ws-card ws-card-body"><div className="flex flex-wrap items-center justify-between gap-5"><div><h2 className="ws-card-title">{t('Ready to go live?')}</h2><p className="form-help">{t(unsavedSections?'Save your section changes first, then publish.':'Publish your saved changes to the website in one click.')}</p></div><Editor key={c.lock_version} action={`/admin/page-compositions/${c.id}/publish`} initial={{lock_version:c.lock_version}} disabled={unsavedSections} submit="Publish now" /></div></section>}

  <div className="ws-split">
   <nav className="ws-list-pane ws-card" aria-label={t('Page sections')}>
    <div className="ws-card-header"><h2 className="ws-card-title">{t('Sections')}</h2></div>
    <ol className="ws-rows">{c.sections.map((section,index)=>{const hidden=section.current_version?.enabled===false;return <li key={section.id} className="ws-row py-2"><button type="button" className="flex w-full items-center gap-3 text-left" onClick={()=>openSection(section.id)} aria-current={openId===section.id?'true':undefined}><span className="workspace-section-number">{index+1}</span><span className="ws-row-main"><strong className={openId===section.id?'text-state-warning':''}>{section.editor_label}</strong><small className="ws-sub">{t(registry[section.current_version?.type]?.name??'Section')}{hidden?` · ${t('hidden')}`:''}</small></span>{hidden&&<Icon name="eye-off" className="size-4 ws-muted"/>}</button></li>;})}</ol>
    {!c.sections.length&&<p className="ws-card-body text-sm ws-muted">{t('No sections yet.')}</p>}
    {editable&&<div className="ws-card-body pt-0"><button type="button" className="ws-add w-full justify-center" onClick={()=>openSection('new')}><Icon name="plus" className="size-4"/>{t('Add a section')}</button></div>}
   </nav>

   <div className="ws-wide grid gap-4 content-start">
    <Link className="text-link text-sm" href="/admin/page-compositions" onBefore={leave}>{t('Back to all pages')}</Link>
    {c.sections.map((section,index)=><details id={`section-${section.id}`} className="admin-panel workspace-section-editor" key={`${section.id}-${c.lock_version}`} open={openId===section.id} onToggle={event=>{if(event.currentTarget.open)setOpenId(section.id);else if(openId===section.id)setOpenId(null);}}><summary><span className="workspace-section-number">{index+1}</span><span>{section.editor_label}</span><span className="workspace-section-type">{t(registry[section.current_version?.type]?.name??'Section')}</span></summary><div className="pt-5">{editable?<>
     <SectionEditor composition={c} section={section} registry={registry} eligibleMedia={eligibleMedia} onDirtyChange={value=>reportDirty(section.id,value)}/>
     <details className="workspace-advanced mt-5"><summary>{t('Move, copy or archive')}</summary><div className="mt-4 flex flex-wrap gap-3">{index>0&&<Action href={`/admin/page-compositions/${c.id}/sections/order`} method="put" data={reorder(index,-1)}>{t('Move up')}</Action>}{index<c.sections.length-1&&<Action href={`/admin/page-compositions/${c.id}/sections/order`} method="put" data={reorder(index,1)}>{t('Move down')}</Action>}<Action href={`/admin/page-compositions/${c.id}/sections/${section.id}/duplicate`} data={{lock_version:c.lock_version}}>{t('Duplicate')}</Action>{can('pages.delete')&&!section.required&&!section.locked&&<Action href={`/admin/page-compositions/${c.id}/sections/${section.id}`} method="delete" data={{lock_version:c.lock_version}} confirm="Archive this section?" className="button-danger">{t('Archive')}</Action>}</div></details>
    </>:<div className="whitespace-pre-wrap">{Object.values(section.current_version?.content??{}).filter(v=>typeof v==='string').join('\n\n')}</div>}</div></details>)}
    {!c.sections.length&&<p className="empty-state">{t('No sections yet. Add a section to start building this page.')}</p>}
    {editable&&<details id="section-new" className="admin-panel workspace-section-editor" open={openId==='new'} onToggle={event=>{if(event.currentTarget.open)setOpenId('new');}}><summary><span className="workspace-section-number"><Icon name="plus" className="size-4"/></span><span>{t('Add a section')}</span></summary><div className="pt-5"><label className="form-label" htmlFor="new-section-type">{t('Section type')}</label><select id="new-section-type" className="form-input mb-5" value={type} onChange={e=>setType(e.target.value)}>{Object.entries(registry).map(([value,d])=><option key={value} value={value}>{t(d.name)}</option>)}</select><SectionEditor key={`${type}-${c.lock_version}`} composition={c} registry={registry} eligibleMedia={eligibleMedia} newType={type} onDirtyChange={value=>reportDirty('new',value)}/></div></details>}
    {destinations.length>0&&c.state==='published'&&<details className="ws-card ws-card-body"><summary className="font-semibold cursor-pointer">{t('Archive this page')}</summary><p className="form-help mt-2">{t('Archiving takes the page off the website. Its address will tell visitors it is gone.')}</p><Editor key={c.state} action={`/admin/page-compositions/${c.id}/transitions`} initial={{to:'archived',comment:''}} fields={[field('comment','Reason','textarea')]} submit="Archive page" confirm="Take this page off the website?" className="mt-4"/></details>}
    {destinations.length>0&&c.state!=='published'&&<section className="ws-card" aria-labelledby="next-step-title"><div className="ws-card-header"><h2 id="next-step-title" className="ws-card-title">{t('Next step')}</h2></div><div className="ws-card-body"><p className="text-sm ws-muted">{t('Save your sections before sending this page to the next step.')}</p><Editor key={c.state} disabled={unsavedSections} action={`/admin/page-compositions/${c.id}/transitions`} initial={{to:destinations[0],comment:''}} onDirtyChange={value=>reportDirty('workflow',value)} fields={[field('to','Next step','select',{options:destinations.map(value=>({value,label:workflowLabels[value]}))}),field('comment','Comment for the reviewer','textarea')]} submit="Continue">{unsavedSections&&<p className="status-warning" role="status">{t('Save your sections before sending this page to the next step.')}</p>}</Editor></div></section>}
    {editable&&archivedSections.length>0&&<details className="ws-card ws-card-body"><summary className="font-semibold cursor-pointer">{t('Archived sections')} ({archivedSections.length})</summary><ul className="ws-rows mt-3">{archivedSections.map(s=><li key={s.id} className="ws-row px-0"><span className="ws-row-main"><strong>{s.editor_label}</strong></span><Action href={`/admin/page-compositions/${c.id}/sections/${s.id}/restore`} data={{lock_version:c.lock_version}}>{t('Restore')}</Action></li>)}</ul></details>}
   </div>
  </div>
 </WorkspaceLayout>;
}
