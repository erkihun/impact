import { useState } from 'react';
import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { Editor, UnsavedBar, label, useWorkspace } from '../../../Components/Workspace/UI';
import Icon from '../../../Components/Icon';

const mainMenus = ['header', 'primary', 'main', 'main_menu'];
const menuName = group => {
    const location = group.split(':').at(-1);
    if (mainMenus.includes(location)) return 'Main menu';
    return location.startsWith('footer_') ? `Footer: ${label(location.slice(7))}` : label(location);
};
const destination = route => {
    if (!route) return '—';
    if (route === 'home') return 'Homepage';
    const [name, action] = route.split('.');
    const names = { rfp: 'Request for proposal', legal: 'Legal' };
    return `${names[name] ?? label(name)} ${action === 'create' ? 'form' : 'page'}`;
};

export default function Edit({ items }) {
    const { t } = useWorkspace();
    // Main menu first, then the footer columns.
    const groups = Object.keys(items).sort((a, b) => Number(!mainMenus.includes(a.split(':').at(-1))) - Number(!mainMenus.includes(b.split(':').at(-1))));
    const [active, setActive] = useState(groups[0]);
    const flat = Object.values(items).flat();
    const initial = { items: flat.map(item => ({ id: item.id, lock_version: item.lock_version, label: item.label, description: item.description ?? '', sort_order: item.sort_order, enabled: !!item.enabled })) };

    return <WorkspaceLayout title="Menus and footer" description="Rename links, change their order or hide them. Changes go live when you save.">
        <nav className="ws-tabs" aria-label={t('Menus')}>{groups.map(group => <a key={group} href={`#menu-${group}`} aria-current={group === active ? 'page' : undefined} onClick={event => { event.preventDefault(); setActive(group); }}>{t(menuName(group))}<span className="ws-count">{items[group].length}</span></a>)}</nav>
        <Editor key={flat.map(item => item.lock_version).join('-')} action="/admin/navigation" method="put" initial={initial} submit={false}>
            {form => {
                const rowsFor = group => items[group]
                    .map(item => ({ item, index: flat.findIndex(entry => entry.id === item.id) }))
                    .sort((a, b) => form.data.items[a.index].sort_order - form.data.items[b.index].sort_order);
                const set = (index, key, value) => form.setData('items', form.data.items.map((row, n) => n === index ? { ...row, [key]: value } : row));
                const swap = (a, b) => form.setData('items', form.data.items.map((row, n) => n === a ? { ...row, sort_order: form.data.items[b].sort_order } : n === b ? { ...row, sort_order: form.data.items[a].sort_order } : row));
                const changes = form.data.items.filter((row, n) => JSON.stringify(row) !== JSON.stringify(initial.items[n])).length;
                const rows = rowsFor(active);

                return <>
                    <UnsavedBar form={form} message={`${changes} unsaved ${changes === 1 ? 'change' : 'changes'}.`} submit="Save menus" />
                    <div className="ws-split">
                        <section className="ws-card ws-wide" id={`menu-${active}`} aria-label={t(menuName(active))}>
                            <div className="ws-menu-head" aria-hidden="true"><span>{t('Order')}</span><span>{t('Label')}</span><span>{t('Goes to')}</span><span>{t('Shown')}</span></div>
                            {rows.map(({ item, index }, position) => {
                                const row = form.data.items[index];
                                const errors = Object.entries(form.errors).filter(([key]) => key.startsWith(`items.${index}.`)).map(([, message]) => message);
                                return <div key={item.id} className={`ws-menu-row ${row.enabled ? '' : 'is-hidden'}`}>
                                    <span className="ws-menu-order">
                                        <button type="button" className="ws-icon-button" disabled={position === 0} onClick={() => swap(index, rows[position - 1].index)} aria-label={t('Move :item up', { item: row.label })}><Icon name="chevron-up" className="size-4" /></button>
                                        <button type="button" className="ws-icon-button" disabled={position === rows.length - 1} onClick={() => swap(index, rows[position + 1].index)} aria-label={t('Move :item down', { item: row.label })}><Icon name="chevron-down" className="size-4" /></button>
                                    </span>
                                    <span className="grid gap-1 min-w-0">
                                        <input className="form-input" aria-label={t('Label for :item', { item: item.label })} value={row.label} required maxLength={120} onChange={event => set(index, 'label', event.target.value)} />
                                        <details><summary className="text-xs ws-muted cursor-pointer">{t(row.description ? 'Description' : 'Add a description')}</summary><input className="form-input mt-2" aria-label={t('Description for :item', { item: item.label })} value={row.description} maxLength={240} onChange={event => set(index, 'description', event.target.value)} /></details>
                                        {errors.length > 0 && <span className="field-error">{errors.join(' ')}</span>}
                                    </span>
                                    <span className="ws-menu-target text-sm ws-muted">{t(destination(item.route_name))}{row.label !== initial.items[index].label && <span className="block text-xs font-semibold text-state-warning">{t('Renamed from “:name”', { name: initial.items[index].label })}</span>}</span>
                                    <label className="ws-switch"><input type="checkbox" checked={row.enabled} onChange={event => set(index, 'enabled', event.target.checked)} /><span className="ws-switch-track" aria-hidden="true" /><span className="sr-only">{t('Show :item', { item: row.label })}</span></label>
                                </div>;
                            })}
                        </section>
                        <aside className="ws-narrow" aria-labelledby="menu-preview">
                            <h2 id="menu-preview" className="ws-section-title">{t('Preview')}</h2>
                            <div className="ws-card">
                                <div className="ws-card-body"><p className="flex flex-wrap gap-x-4 gap-y-1 text-sm font-semibold">{rows.filter(({ index }) => form.data.items[index].enabled).map(({ index }) => <span key={index}>{form.data.items[index].label}</span>)}</p></div>
                            </div>
                            <p className="text-sm ws-muted">{t('Hidden links stay saved. Turn them back on at any time. Submenus for services, industries and insights update when you publish listings.')}</p>
                        </aside>
                    </div>
                </>;
            }}
        </Editor>
    </WorkspaceLayout>;
}
