import { Link, useForm, usePage } from '@inertiajs/react';
import { useId, useEffect, useRef } from 'react';

export const label = (value) => String(value ?? '').replace(/[_-]/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
export const date = value => value ? String(value).replace('T', ' ').slice(0, 16) : '—';
export const rows = value => Array.isArray(value) ? value : (value?.data ?? Object.values(value ?? {}));
export function useWorkspace() {
    const { workspace = {}, flash = {}, errors = {} } = usePage().props;
    const t = (key, values = {}) => Object.entries(values).reduce((text, [name, value]) => text.replaceAll(`:${name}`, String(value)), workspace.text?.[key] ?? key);
    return { ...workspace, flash, errors, t, can: permission => workspace.permissions?.includes(permission) ?? false };
}
export function Status({ value }) { const { t } = useWorkspace(); return <span className="status-badge status-badge-neutral">{t(label(value))}</span>; }
export function Panel({ title, children, className = '' }) { return <section className={`admin-panel min-w-0 ${className}`}>{title && <h2 className="heading-3 mb-5">{title}</h2>}{children}</section>; }
export function Errors({ errors }) {
    const ref = useRef(null); const { t } = useWorkspace();
    useEffect(() => { if (Object.keys(errors ?? {}).length) ref.current?.focus(); }, [errors]);
    return Object.keys(errors ?? {}).length ? <div className="error-summary mb-5" role="alert" tabIndex={-1} ref={ref}><p className="font-bold">{t('Please correct the following fields before continuing.')}</p><ul>{Object.entries(errors).map(([key, value]) => <li key={key}>{value}</li>)}</ul></div> : null;
}
export function Field({ form, name, label: title, type = 'text', options = [], help, ...props }) {
    const generated = useId(); const id = props.id ?? `field-${name}-${generated}`; const { t } = useWorkspace();
    const value = form.data[name]; const error = form.errors[name];
    const opts = (Array.isArray(options) ? options.map(o => typeof o === 'object' ? o : ({ value: o, label: label(o) })) : Object.entries(options).map(([value, label]) => ({ value, label })));
    const common = { ...props, id, name, 'aria-invalid': error ? true : undefined, 'aria-describedby': [help && `${id}-help`, error && `${id}-error`].filter(Boolean).join(' ') || undefined };
    let input;
    if (type === 'checkbox') input = <input {...common} type="checkbox" checked={!!value} onChange={e => form.setData(name, e.target.checked)} className="rounded" />;
    else if (type === 'checks') input = <div className="grid gap-3 sm:grid-cols-2">{opts.map(o => <label key={o.value} className="flex min-h-11 items-center gap-3 border border-edge rounded-lg p-3"><input type="checkbox" checked={(value ?? []).map(String).includes(String(o.value))} onChange={e => form.setData(name, e.target.checked ? [...(value ?? []), String(o.value)] : value.filter(v => String(v) !== String(o.value)))} disabled={props.disabled} /><span>{t(o.label)}</span></label>)}</div>;
    else if (type === 'select') input = <select {...common} className="form-input" value={value ?? (props.multiple ? [] : '')} onChange={e => form.setData(name, props.multiple ? [...e.target.selectedOptions].map(o => o.value) : e.target.value)}>{opts.map(o => <option key={o.value} value={o.value}>{t(o.label)}</option>)}</select>;
    else if (type === 'textarea') input = <textarea {...common} className="form-input min-h-28" value={value ?? ''} onChange={e => form.setData(name, e.target.value)} />;
    else if (type === 'file') input = <input {...common} className="form-input" type="file" onChange={e => form.setData(name, props.multiple ? [...e.target.files] : e.target.files[0] ?? null)} />;
    else input = <input {...common} className="form-input" type={type} value={value ?? ''} onChange={e => form.setData(name, e.target.value)} />;
    return <div className="min-w-0 grid gap-2">{type === 'checks' ? <fieldset><legend className="form-label">{t(title)}</legend>{input}</fieldset> : type === 'checkbox' ? <label className="flex min-h-11 items-center gap-3" htmlFor={id}>{input}<span>{t(title)}</span></label> : <><label className="form-label" htmlFor={id}>{t(title)}{props.required ? ` (${t('required')})` : ''}</label>{input}</>}{help && <p id={`${id}-help`} className="form-help">{t(help)}</p>}{error && <p id={`${id}-error`} className="field-error">{error}</p>}</div>;
}
export function Editor({ action, method = 'post', initial = {}, fields = [], fieldGroups = [], children, submit = 'Save changes', confirm, transform, errorBag, onSuccess, onDirtyChange, disabled = false, className = '' }) {
    const form = useForm(initial); const { t } = useWorkspace();
    const dirtyCallback = useRef(onDirtyChange);
    dirtyCallback.current = onDirtyChange;
    useEffect(() => { dirtyCallback.current?.(form.isDirty); }, [form.isDirty]);
    const send = event => {
        event.preventDefault(); if (disabled || form.processing || (confirm && !window.confirm(t(confirm)))) return;
        form.transform(data => transform ? transform(data) : data);
        form.submit(method, action, { preserveScroll: true, errorBag, onSuccess: () => onSuccess?.(form) });
    };
    return <form className={`grid gap-5 ${className}`} onSubmit={send} aria-busy={form.processing || undefined}><Errors errors={form.errors} />{fields.map(field => <Field key={field.name} form={form} {...field} />)}{fieldGroups.map(group => <details className="workspace-advanced" key={group.title} open={group.open || group.fields.some(field => Object.keys(form.errors).some(key => key === field.name || key.startsWith(`${field.name}.`)))}><summary>{t(group.title)}</summary>{group.description && <p className="form-help mt-3">{t(group.description)}</p>}<div className="grid gap-5 mt-4">{group.fields.map(field => <Field key={field.name} form={form} {...field} />)}</div></details>)}{typeof children === 'function' ? children(form) : children}{submit && <div><button className="button-primary" disabled={disabled || form.processing} type="submit">{form.processing ? t('Saving…') : t(submit)}</button></div>}</form>;
}
export function Action({ href, method = 'post', data = {}, children, confirm, className = 'button-secondary' }) {
    const form = useForm(data); const { t } = useWorkspace();
    return <form onSubmit={e => {e.preventDefault(); if (!form.processing && (!confirm || window.confirm(t(confirm)))) form.submit(method, href, { preserveScroll: true });}}><Errors errors={form.errors} /><button className={className} disabled={form.processing}>{children}</button></form>;
}
export function Table({ columns, data, empty = 'No records found.' }) {
    const { t } = useWorkspace();
    return rows(data).length ? <div className="overflow-x-auto rounded-lg border border-edge"><table className="w-full text-left text-sm"><thead className="bg-quiet"><tr>{columns.map((c,i) => <th className="p-4" scope="col" key={i}>{t(c.label)}</th>)}</tr></thead><tbody className="divide-y divide-edge bg-white">{rows(data).map((row,i) => <tr key={row.id ?? i}>{columns.map((c,j) => <td className="p-4 align-top" key={j}>{c.render ? c.render(row) : String(row[c.key] ?? '—')}</td>)}</tr>)}</tbody></table></div> : <div className="empty-state"><p>{t(empty)}</p></div>;
}
export function Pagination({ data }) {
    const { t } = useWorkspace(); if (!data?.total) return null;
    return <div className="mt-5 flex flex-wrap items-center gap-2"><p className="me-auto text-sm text-muted">{data.from}–{data.to} / {data.total}</p><nav aria-label={t('Pagination')} className="flex flex-wrap gap-2">{(data.links ?? []).map((link,i) => {const text=link.label.replace(/&laquo;/g,'«').replace(/&raquo;/g,'»');return link.url ? <Link key={i} className={link.active ? 'button-primary' : 'button-secondary'} href={link.url} aria-current={link.active ? 'page' : undefined}>{text}</Link> : <span key={i} className="button-secondary opacity-50">{text}</span>;})}</nav></div>;
}
export function Filters({ fields }) {
    const { filters = {}, t } = useWorkspace(); const { url } = usePage(); const action = url.split('?')[0];
    const active = fields.some(f => filters[f.name] !== undefined && filters[f.name] !== null && String(filters[f.name]) !== '');
    return <details className="admin-panel workspace-filters" open={active}><summary className="font-semibold">{t('Filter results')}{active && <span className="ms-2 text-sm text-muted">{t('Filters applied')}</span>}</summary><Editor key={url} action={action} method="get" initial={Object.fromEntries(fields.map(f => [f.name, filters[f.name] ?? '']))} fields={fields} submit="Apply filters" className="md:grid-cols-3 mt-5"><Link className="text-link" href={action}>{t('Clear filters')}</Link></Editor></details>;
}
export const field = (name, title, type = 'text', extra = {}) => ({ name, label: title, type, ...extra });
export const options = (items, name = 'name') => rows(items).map(item => ({value: String(item.id), label: item[name] ?? item.original_name ?? item.id}));
