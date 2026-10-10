import { Link, useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { Action, Errors, Pagination, Status, useWorkspace, when } from '../../../Components/Workspace/UI';
import Icon from '../../../Components/Icon';

const size = bytes => bytes >= 1048576 ? `${(bytes / 1048576).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`;
const kind = asset => (asset.mime_type?.split('/')[1] ?? 'file').toUpperCase();

function Uploader() {
    const { t } = useWorkspace();
    const input = useRef(null);
    const [dragging, setDragging] = useState(false);
    const form = useForm({ file: null, visibility: 'public', title: '', alt_text: '', locale: 'en' });
    const choose = file => { if (file) form.setData(data => ({ ...data, file, title: data.title || file.name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ') })); };
    const submit = event => { event.preventDefault(); form.post('/admin/media', { forceFormData: true, preserveScroll: true, onSuccess: () => { form.reset(); if (input.current) input.current.value = ''; } }); };
    return <form className="grid gap-4" onSubmit={submit}>
        <Errors errors={form.errors} />
        <label htmlFor="media-file" className={`ws-dropzone ${dragging ? 'is-dragging' : ''}`} onDragOver={event => { event.preventDefault(); setDragging(true); }} onDragLeave={() => setDragging(false)} onDrop={event => { event.preventDefault(); setDragging(false); choose(event.dataTransfer.files?.[0]); }}>
            <span className="ws-icon-tile" aria-hidden="true"><Icon name="upload" /></span>
            <span className="flex-1"><strong className="block font-semibold">{form.data.file ? form.data.file.name : t('Drop an image or PDF here, or choose a file')}</strong><span className="text-sm ws-muted">{t('JPG, PNG, WebP or PDF. Every file is scanned before anyone can use it.')}</span></span>
            <span className="button-secondary">{t(form.data.file ? 'Choose another' : 'Choose file')}</span>
            <input ref={input} id="media-file" type="file" className="sr-only" accept=".jpg,.jpeg,.png,.webp,.pdf" onChange={event => choose(event.target.files?.[0])} />
        </label>
        {form.data.file && <div className="ws-card ws-card-body">
            <div className="ws-toolbar">
                <div className="ws-grow"><label className="form-label" htmlFor="upload-title">{t('Title')}</label><input id="upload-title" className="form-input" maxLength={220} value={form.data.title} onChange={event => form.setData('title', event.target.value)} /></div>
                <div className="ws-grow"><label className="form-label" htmlFor="upload-alt">{t('Description for screen readers')}</label><input id="upload-alt" className="form-input" maxLength={500} value={form.data.alt_text} onChange={event => form.setData('alt_text', event.target.value)} aria-describedby="upload-alt-help" /></div>
                <div><label className="form-label" htmlFor="upload-visibility">{t('Who can see it')}</label><select id="upload-visibility" className="form-input" value={form.data.visibility} onChange={event => form.setData('visibility', event.target.value)}><option value="public">{t('Anyone on the website')}</option><option value="restricted">{t('Staff only')}</option></select></div>
                <button className="button-primary" disabled={form.processing}>{form.processing ? t('Uploading…') : t('Upload')}</button>
            </div>
            <p id="upload-alt-help" className="form-help">{t('Say what the image shows, for people who cannot see it. Leave blank for documents.')}</p>
        </div>}
    </form>;
}

function Details({ asset }) {
    const { t, can } = useWorkspace();
    const form = useForm({ title: asset.title ?? '', alt_text: asset.alt_text ?? '' });
    const checks = [
        [asset.scan_status === 'clean', asset.scan_status === 'clean' ? 'Scanned: no threats found' : `Security scan: ${asset.scan_status.replaceAll('_', ' ')}`],
        [asset.processing_status === 'ready', asset.processing_status === 'ready' ? 'Prepared for phones and desktops' : `Processing: ${asset.processing_status.replaceAll('_', ' ')}`],
        [!asset.needs_approval && asset.scan_status === 'clean', asset.needs_approval ? 'Waiting for approval before use' : 'Approved for use'],
    ];
    return <aside className="ws-card ws-narrow" aria-labelledby="file-title">
        <div className="ws-card-body">
            <div className="ws-thumb ws-thumb-lg">{asset.url && asset.is_image ? <img src={asset.url} alt="" /> : <Icon name={asset.is_image ? 'media' : 'file'} />}</div>
            <div><h2 id="file-title" className="ws-card-title break-words">{asset.title || asset.original_name}</h2><p className="text-xs ws-muted">{kind(asset)} · {size(asset.size_bytes)} · {t('added :when', { when: when(asset.created_at) })} · {t(asset.visibility === 'public' ? 'Anyone on the website' : 'Staff only')}</p></div>
            <ul className="ws-checks">{checks.map(([ok, text]) => <li key={text} className={ok ? 'is-ok' : 'is-warn'}><Icon name={ok ? 'check' : 'clock'} /><span>{t(text)}</span></li>)}</ul>
            {can('media.approve') && asset.needs_approval && <Action href={`/admin/media/${asset.id}/approve`} className="button-primary w-full">{t('Approve for use')}</Action>}
            {can('media.create') ? <form className="grid gap-3" onSubmit={event => { event.preventDefault(); form.patch(`/admin/media/${asset.id}`, { preserveScroll: true }); }}>
                <div><label className="form-label" htmlFor="file-title-input">{t('Title')}</label><input id="file-title-input" className="form-input" maxLength={220} value={form.data.title} onChange={event => form.setData('title', event.target.value)} /></div>
                <div><label className="form-label" htmlFor="file-alt-input">{t('Description for screen readers')}{asset.is_image && asset.visibility === 'public' ? ` (${t('required')})` : ''}</label><textarea id="file-alt-input" className="form-input" rows={2} maxLength={500} value={form.data.alt_text} onChange={event => form.setData('alt_text', event.target.value)} /><p className="form-help">{t('Say what the image shows, for people who cannot see it.')}</p></div>
                <div className="flex flex-wrap gap-2"><button className="button-dark" disabled={form.processing || !form.isDirty}>{t('Save details')}</button>{asset.url && <button type="button" className="button-secondary" onClick={() => navigator.clipboard?.writeText(new URL(asset.url, window.location.origin).href)}>{t('Copy link')}</button>}</div>
            </form> : asset.alt_text && <p className="text-sm">{asset.alt_text}</p>}
        </div>
    </aside>;
}

export default function Index({ assets, counts = {}, filters = {} }) {
    const { t, can } = useWorkspace();
    const [selectedId, setSelectedId] = useState(assets.data[0]?.id ?? null);
    const selected = assets.data.find(asset => asset.id === selectedId) ?? assets.data[0];
    const search = useForm({ q: filters.q ?? '' });
    const href = show => `/admin/media${show ? `?show=${show}` : ''}`;
    const chips = [['', 'All', counts.all], ['images', 'Images', counts.images], ['documents', 'Documents', counts.documents], ['approval', 'Needs approval', counts.approval]];

    return <WorkspaceLayout title="Media" description="Images and documents for the website. Every file is scanned before anyone can use it.">
        {can('media.create') && <Uploader />}
        <div className="flex flex-wrap items-center justify-between gap-3">
            <nav className="ws-chips" aria-label={t('Show')}>{chips.map(([value, text, count]) => <Link key={value} href={href(value)} aria-current={(filters.show ?? '') === value ? 'page' : undefined}>{t(text)}<span>{count ?? 0}</span></Link>)}</nav>
            <form role="search" onSubmit={event => { event.preventDefault(); search.transform(data => ({ ...(data.q ? { q: data.q } : {}), ...(filters.show ? { show: filters.show } : {}) })); search.get('/admin/media', { preserveScroll: true }); }} className="ws-search-field">
                <Icon name="search" className="size-4" /><label htmlFor="media-search" className="sr-only">{t('Search files')}</label><input id="media-search" type="search" placeholder={t('Search files')} value={search.data.q} onChange={event => search.setData('q', event.target.value)} />
            </form>
        </div>
        {assets.data.length ? <div className="ws-split">
            <ul className="ws-media-grid ws-wide" aria-label={t('Files')}>{assets.data.map(asset => <li key={asset.id}>
                <button type="button" className="ws-media-tile" aria-pressed={selected?.id === asset.id} onClick={() => setSelectedId(asset.id)}>
                    <span className="ws-thumb">{asset.url && asset.is_image ? <img src={asset.url} alt="" loading="lazy" /> : <Icon name={asset.is_image ? 'media' : 'file'} />}</span>
                    <span className="ws-media-name">{asset.title || asset.original_name}</span>
                    {asset.needs_approval ? <Status value="new" text="Needs approval" /> : asset.scan_status !== 'clean' || asset.processing_status !== 'ready' ? <Status value="scanning" text="Checking…" /> : <Status value={asset.visibility === 'public' ? 'live' : 'restricted'} text={asset.visibility === 'public' ? 'On the website' : 'Staff only'} />}
                </button>
            </li>)}</ul>
            {selected && <Details key={selected.id} asset={selected} />}
        </div> : <div className="ws-card ws-empty"><h3>{t(filters.show || filters.q ? 'No files match' : 'No files yet')}</h3><p>{t(filters.show || filters.q ? 'Try another filter or search.' : 'Upload images and documents to use them across the website.')}</p></div>}
        <Pagination data={assets} />
    </WorkspaceLayout>;
}
