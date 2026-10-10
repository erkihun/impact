import { Link, useForm } from '@inertiajs/react';
import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { Pagination, Status, Tabs, label, useWorkspace, when } from '../../../Components/Workspace/UI';
import Icon from '../../../Components/Icon';

const RETIRED = ['archived', 'cancelled'];

export default function Collection({ collection, collections = [], items, counts = {}, filters = {} }) {
    const { t } = useWorkspace();
    const form = useForm({ q: filters.q ?? '' });
    const base = `/admin/website/${collection.key}`;
    const status = filters.status ?? '';
    const statusLabel = value => collection.statuses.find(option => option.value === value)?.label ?? label(value);
    const current = Object.entries(counts).filter(([value]) => !RETIRED.includes(value)).reduce((sum, [, count]) => sum + count, 0);
    const chips = [{ value: '', label: 'All current', count: current }, ...collection.statuses.map(option => ({ value: option.value, label: option.label, count: counts[option.value] ?? 0 }))];
    const filtered = !!(filters.q || status);
    const search = event => { event.preventDefault(); form.transform(data => ({ ...(data.q ? { q: data.q } : {}), ...(status ? { status } : {}) })); form.get(base, { preserveScroll: true, preserveState: true }); };

    return <WorkspaceLayout title={collection.label} description={collection.description} actions={<>
        <a className="button-secondary" href={collection.publicPath} target="_blank" rel="noreferrer">{t('View on website')}<Icon name="arrow-up-right" className="size-4" /></a>
        <Link className="button-primary" href={`${base}/create`}><Icon name="plus" className="size-4" />{t('Add :item', { item: collection.singular })}</Link>
    </>}>
        {collections.length > 1 && <Tabs label="Listing types" items={collections.map(item => ({ href: item.href, label: item.label, count: item.count, current: item.key === collection.key }))} />}

        <section className="ws-card" aria-labelledby="listing-title">
            <h2 id="listing-title" className="sr-only">{collection.label}</h2>
            <div className="ws-card-body">
                <form role="search" className="ws-toolbar" onSubmit={search}>
                    <div className="ws-grow"><label className="form-label" htmlFor="listing-search">{t('Find :items', { items: collection.label.toLowerCase() })}</label><div className="ws-search-field"><Icon name="search" className="size-4" /><input id="listing-search" type="search" maxLength={100} placeholder={t('Search by title')} value={form.data.q} onChange={event => form.setData('q', event.target.value)} /></div></div>
                    <button className="button-dark" disabled={form.processing}>{t('Search')}</button>
                    {filtered && <Link className="button-tertiary" href={base}>{t('Clear')}</Link>}
                </form>
                <nav className="ws-chips" aria-label={t('Filter by status')}>
                    {chips.map(chip => <Link key={chip.value} href={chip.value ? `${base}?status=${chip.value}` : base} aria-current={status === chip.value ? 'page' : undefined}>{t(chip.label)}<span>{chip.count}</span></Link>)}
                </nav>
            </div>
            {items.data.length > 0 ? <div className="ws-table-wrap"><table className="ws-table">
                <thead><tr><th scope="col">{t('Title')}</th><th scope="col">{t('Status')}</th><th scope="col">{t('On the website')}</th><th scope="col">{t('Last updated')}</th><th scope="col"><span className="sr-only">{t('Actions')}</span></th></tr></thead>
                <tbody>{items.data.map(item => {
                    const title = item.title || t('Untitled');
                    const edit = `${base}/${item.id}/edit`;
                    return <tr key={item.id}>
                        <td><Link className="ws-strong" href={edit}>{title}</Link><span className="ws-sub">{collection.publicPath}/{item.slug}{item.meta ? ` · ${item.meta}` : ''}</span></td>
                        <td><Status value={item.status} text={statusLabel(item.status)} /></td>
                        <td>{item.liveUrl ? <a className="text-link inline-flex items-center gap-1" href={item.liveUrl} target="_blank" rel="noreferrer" aria-label={t('View :title on the website', { title })}>{t('Live')}<Icon name="arrow-up-right" className="size-4" /></a> : <span className="ws-muted">{t('Not visible')}</span>}</td>
                        <td className="ws-muted">{when(item.updatedAt)}</td>
                        <td className="text-right"><Link className="button-secondary" href={edit} aria-label={t('Edit :title', { title })}>{t('Edit')}</Link></td>
                    </tr>;
                })}</tbody>
            </table></div> : <div className="ws-empty">
                <h3>{t(filtered ? 'Nothing matches your search' : 'Nothing here yet')}</h3>
                <p>{filtered ? t('Try a shorter title or another status.') : t('Add the first :item to show it on the website.', { item: collection.singular })}</p>
                {filtered ? <Link className="button-secondary mt-2" href={base}>{t('Show all')}</Link> : <Link className="button-primary mt-2" href={`${base}/create`}>{t('Add :item', { item: collection.singular })}</Link>}
            </div>}
            {items.data.length > 0 && <p className="ws-card-footer">{t('Showing :from–:to of :total.', { from: items.from, to: items.to, total: items.total })} {!RETIRED.includes(status) && t('Archived items are hidden until you choose Archived.')}</p>}
        </section>
        <Pagination data={items} />
    </WorkspaceLayout>;
}
