import { Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { Editor, Field, UnsavedBar, field, useWorkspace } from '../../../Components/Workspace/UI';
import { ActivityItem } from '../Dashboard';
import Icon from '../../../Components/Icon';

// Mirrors ContentSeoValidator::normalizeSlug so the preview matches what is saved.
const slugify = value => String(value ?? '').normalize('NFKD').replace(/[̀-ͯ]/g, '').toLowerCase()
    .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80).replace(/-+$/, '');

const inputTypes = { datetime: 'datetime-local', media: 'select' };

/** One input per line for list fields such as deliverables; stored as newline-separated text. */
function LinesField({ form, definition }) {
    const { t } = useWorkspace();
    const lines = String(form.data[definition.name] ?? '').split('\n');
    const items = lines.length ? lines : [''];
    const update = next => form.setData(definition.name, next.join('\n'));
    const move = (index, offset) => { const next = [...items]; [next[index], next[index + offset]] = [next[index + offset], next[index]]; update(next); };
    const error = form.errors[definition.name];
    const noun = definition.label.replace(/s$/, '').toLowerCase();
    return <fieldset className="grid gap-2">
        <legend className="form-label">{t(definition.label)}</legend>
        <div className="ws-lines">{items.map((value, index) => <div key={index} className="ws-lines-row">
            <span className="flex flex-col"><button type="button" className="ws-icon-button h-5" onClick={() => move(index, -1)} disabled={index === 0} aria-label={t('Move :item up', { item: value || noun })}><Icon name="chevron-up" className="size-4" /></button><button type="button" className="ws-icon-button h-5" onClick={() => move(index, 1)} disabled={index === items.length - 1} aria-label={t('Move :item down', { item: value || noun })}><Icon name="chevron-down" className="size-4" /></button></span>
            <input className="form-input" aria-label={`${t(definition.label)} ${index + 1}`} value={value} onChange={event => update(items.map((line, n) => n === index ? event.target.value : line))} />
            <button type="button" className="ws-icon-button" onClick={() => update(items.filter((_, n) => n !== index))} aria-label={t('Remove :item', { item: value || noun })}><Icon name="close" className="size-4" /></button>
        </div>)}</div>
        <button type="button" className="ws-add" onClick={() => update([...items, ''])}><Icon name="plus" className="size-4" />{t('Add :item', { item: noun })}</button>
        {error && <p className="field-error">{error}</p>}
    </fieldset>;
}

export default function Edit({ collection, collections = [], item, values, media = [], history = [] }) {
    const { t } = useWorkspace();
    const [dirty, setDirty] = useState(false);
    const base = `/admin/website/${collection.key}`;
    const tab = collections.find(entry => entry.key === collection.key);

    // Warn before leaving with unsaved changes; saving itself is not a GET visit.
    useEffect(() => dirty ? router.on('before', event => {
        if (event.detail.visit.method === 'get' && !window.confirm(t('You have unsaved changes. Leave without saving?'))) event.preventDefault();
    }) : undefined, [dirty]);

    const toField = definition => field(definition.name, definition.label, inputTypes[definition.type] ?? definition.type, {
        required: definition.required,
        help: definition.help,
        options: definition.type === 'media' ? [{ value: '', label: 'None' }, ...media] : definition.options,
        ...(['text', 'textarea'].includes(definition.type) && definition.max ? { maxLength: definition.max } : {}),
        ...(definition.type === 'number' ? { min: 0 } : {}),
        ...(definition.type === 'textarea' ? { rows: definition.max && definition.max <= 2000 ? 2 : 5 } : {}),
    });
    const render = (form, definition) => definition.type === 'lines'
        ? <LinesField key={definition.name} form={form} definition={definition} />
        : <Field key={definition.name} form={form} {...toField(definition)} />;
    const content = collection.fields.filter(definition => (definition.group ?? 'content') === 'content');
    const settings = collection.fields.filter(definition => definition.group === 'settings');

    return <WorkspaceLayout
        title={item ? (values[collection.title] || `Edit ${collection.singular}`) : `Add ${collection.singular}`}
        description={item ? null : collection.description}
        breadcrumbs={[{ label: 'Website' }, { label: 'Listings', href: '/admin/website' }, { label: collection.label, href: tab?.href ?? base }]}
        actions={<>
            {item?.liveUrl && <a className="button-secondary" href={item.liveUrl} target="_blank" rel="noreferrer">{t('View on website')}<Icon name="arrow-up-right" className="size-4" /></a>}
            <Link className="button-secondary" href={base}>{t('Back to :items', { items: collection.label.toLowerCase() })}</Link>
        </>}>
        <Editor key={item?.id ?? 'new'} action={item ? `${base}/${item.id}` : base} method={item ? 'put' : 'post'} initial={values} submit={false} onDirtyChange={setDirty}>
            {form => {
                const status = collection.statuses.find(option => option.value === form.data.status);
                const slug = form.data.slug ? slugify(form.data.slug) : slugify(form.data[collection.title]);
                const submit = status?.live ? (item?.liveUrl ? 'Save and update website' : 'Save and publish') : (form.data.status === 'draft' ? 'Save draft' : 'Save');
                const title = String(form.data[collection.title] ?? '');
                const summary = String(form.data[collection.summary] ?? '').trim();
                const checks = [
                    title.length === 0 ? ['warn', 'Add a title.'] : title.length > 60 ? ['warn', 'The title is long, so search engines may cut it off.'] : ['ok', 'The title is a good length.'],
                    summary.length < 50 ? ['warn', 'The summary is short. Add a sentence so search results show more.'] : summary.length > 160 ? ['warn', 'The summary is long, so search results may cut it off.'] : ['ok', 'The summary is a good length.'],
                    slug ? ['ok', 'The web address is short and readable.'] : ['warn', 'Add a title to create the web address.'],
                ];

                return <>
                    <UnsavedBar form={form} submit={submit} />
                    <div className="ws-split">
                        <section className="ws-card ws-wide" aria-labelledby="content-title">
                            <div className="ws-card-header"><h2 id="content-title" className="ws-card-title">{t('Content')}</h2></div>
                            <div className="ws-card-body">{content.map(definition => render(form, definition))}</div>
                        </section>
                        <div className="ws-narrow">
                            <section className="ws-card" aria-labelledby="publish-title">
                                <div className="ws-card-header"><h2 id="publish-title" className="ws-card-title">{t('Publishing')}</h2></div>
                                <div className="ws-card-body">
                                    <fieldset className="ws-radio-cards">
                                        <legend>{t('Status')}</legend>
                                        {collection.statuses.map(option => <label key={option.value} className={`ws-radio-card ${option.live ? 'is-live' : ''}`}>
                                            <input type="radio" name="status" value={option.value} checked={form.data.status === option.value} onChange={() => form.setData('status', option.value)} />
                                            <span><strong>{t(option.label)}</strong><small>{t(option.help)}</small></span>
                                        </label>)}
                                        {form.errors.status && <p className="field-error">{form.errors.status}</p>}
                                    </fieldset>
                                    <div>
                                        <label className="form-label" htmlFor="listing-slug">{t('Web address')}</label>
                                        <div className="ws-prefix-input"><span>{collection.publicPath}/</span><input id="listing-slug" maxLength={200} placeholder={slug || t('created from the title')} value={form.data.slug ?? ''} onChange={event => form.setData('slug', event.target.value)} aria-describedby="listing-slug-help" aria-invalid={form.errors.slug ? true : undefined} /></div>
                                        <p id="listing-slug-help" className="form-help">{item?.liveUrl ? t('If you change it, the old address redirects here automatically.') : t('Leave blank to create it from the title.')}</p>
                                        {form.errors.slug && <p className="field-error">{form.errors.slug}</p>}
                                    </div>
                                    <button type="submit" className="button-primary w-full" disabled={form.processing}>{form.processing ? t('Saving…') : t(submit)}</button>
                                    {item?.reference && <p className="form-help text-center">{t('Reference')}: {item.reference}</p>}
                                </div>
                            </section>
                            <section className="ws-card" aria-labelledby="search-title">
                                <div className="ws-card-header"><h2 id="search-title" className="ws-card-title">{t('How it appears in search')}</h2></div>
                                <div className="ws-card-body">
                                    <div className="ws-serp"><small>{collection.publicPath.slice(1)} › {slug || '…'}</small><strong>{title || t('Your title')}</strong><span>{summary ? (summary.length > 160 ? `${summary.slice(0, 157)}…` : summary) : t('Your summary appears here.')}</span></div>
                                    <ul className="ws-checks">{checks.map(([state, text]) => <li key={text} className={state === 'ok' ? 'is-ok' : 'is-warn'}><Icon name={state === 'ok' ? 'check' : 'alert'} /><span>{t(text)}</span></li>)}</ul>
                                </div>
                            </section>
                            {settings.length > 0 && <section className="ws-card" aria-labelledby="settings-title">
                                <div className="ws-card-header"><h2 id="settings-title" className="ws-card-title">{t('Settings')}</h2></div>
                                <div className="ws-card-body">{settings.map(definition => render(form, definition))}</div>
                            </section>}
                            {history.length > 0 && <section className="ws-card" aria-labelledby="history-title">
                                <div className="ws-card-header"><h2 id="history-title" className="ws-card-title">{t('Recent changes')}</h2></div>
                                <ol className="ws-rows">{history.map(event => <ActivityItem key={event.id} event={{ ...event, href: null }} />)}</ol>
                            </section>}
                        </div>
                    </div>
                </>;
            }}
        </Editor>
    </WorkspaceLayout>;
}
