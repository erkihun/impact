import { Link, useForm } from '@inertiajs/react';
import { Fragment } from 'react';
import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { Pagination, initials, useWorkspace } from '../../../Components/Workspace/UI';
import Icon from '../../../Components/Icon';

const dayKey = value => (value ? new Date(value) : new Date()).toDateString();
const dayLabel = value => {
    const moment = new Date(value), today = new Date(), yesterday = new Date(Date.now() - 86400000);
    const full = moment.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', ...(moment.getFullYear() === today.getFullYear() ? {} : { year: 'numeric' }) });
    if (moment.toDateString() === today.toDateString()) return `Today · ${full}`;
    if (moment.toDateString() === yesterday.toDateString()) return `Yesterday · ${full}`;
    return full;
};
const time = value => new Date(value).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });

export default function Index({ events, areas = [], people = [], filters = {} }) {
    const { t } = useWorkspace();
    const form = useForm({ action: filters.action ?? '', actor: filters.actor ?? '', from: filters.from ?? '', to: filters.to ?? '' });
    const filtered = Object.values(filters).some(Boolean);
    const days = events.data.reduce((groups, event) => {
        const key = dayKey(event.at);
        const group = groups.find(item => item.key === key);
        group ? group.events.push(event) : groups.push({ key, label: dayLabel(event.at), events: [event] });
        return groups;
    }, []);
    const submit = event => { event.preventDefault(); form.transform(data => Object.fromEntries(Object.entries(data).filter(([, value]) => value))); form.get('/admin/audit-events', { preserveScroll: true }); };

    return <WorkspaceLayout title="Activity log" description="Who changed what, and when. Entries cannot be edited or deleted.">
        <form role="search" className="ws-card ws-card-body" onSubmit={submit}>
            <div className="ws-toolbar">
                <div className="ws-grow"><label className="form-label" htmlFor="audit-area">{t('Area')}</label><select id="audit-area" className="form-input" value={form.data.action} onChange={event => form.setData('action', event.target.value)}><option value="">{t('Everything')}</option>{areas.map(area => <option key={area.value} value={area.value}>{t(area.label)}</option>)}</select></div>
                <div className="ws-grow"><label className="form-label" htmlFor="audit-actor">{t('Person')}</label><select id="audit-actor" className="form-input" value={form.data.actor} onChange={event => form.setData('actor', event.target.value)}><option value="">{t('Everyone')}</option>{people.map(person => <option key={person.value} value={person.value}>{person.label}</option>)}</select></div>
                <div><label className="form-label" htmlFor="audit-from">{t('From')}</label><input id="audit-from" type="date" className="form-input" value={form.data.from} onChange={event => form.setData('from', event.target.value)} /></div>
                <div><label className="form-label" htmlFor="audit-to">{t('To')}</label><input id="audit-to" type="date" className="form-input" value={form.data.to} onChange={event => form.setData('to', event.target.value)} /></div>
                <button className="button-dark" disabled={form.processing}>{t('Show')}</button>
                {filtered && <Link className="button-tertiary" href="/admin/audit-events">{t('Clear')}</Link>}
            </div>
            {Object.keys(form.errors).length > 0 && <p className="field-error">{Object.values(form.errors).join(' ')}</p>}
        </form>

        {days.map(day => <section key={day.key} className="ws-day" aria-label={day.label}>
            <h2 className="ws-section-title">{day.label}</h2>
            <ol className="ws-card ws-rows">{day.events.map(event => <li key={event.id} className="ws-event">
                <time dateTime={event.at}>{time(event.at)}</time>
                <span className={`ws-avatar-sm ${event.system ? 'is-system' : ''}`} aria-hidden="true">{event.system ? <Icon name="clock" /> : initials(event.actor)}</span>
                <div className="ws-event-main">
                    <span><strong>{event.actor}</strong> {event.href ? <Link className="text-link" href={event.href}>{t(event.text)}</Link> : t(event.text)}</span>
                    <details>
                        <summary>{t('Technical details')}</summary>
                        <dl>
                            <dt>{t('Action')}</dt><dd><code>{event.action}</code></dd>
                            {Object.entries(event.details ?? {}).map(([key, value]) => <Fragment key={key}><dt>{key.replaceAll('_', ' ')}</dt><dd>{value}</dd></Fragment>)}
                            {event.reference && <><dt>{t('Reference')}</dt><dd><code>{event.reference}</code></dd></>}
                        </dl>
                    </details>
                </div>
            </li>)}</ol>
        </section>)}
        {!days.length && <div className="ws-card ws-empty"><h3>{t(filtered ? 'Nothing matches these filters' : 'No activity yet')}</h3><p>{t(filtered ? 'Try a wider date range or another area.' : 'Changes will be listed here as people work.')}</p></div>}
        <Pagination data={events} />
    </WorkspaceLayout>;
}
