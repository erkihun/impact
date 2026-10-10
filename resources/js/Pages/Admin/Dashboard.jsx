import { Link } from '@inertiajs/react';
import WorkspaceLayout from '../../Layouts/WorkspaceLayout';
import { Status, initials, useWorkspace, when } from '../../Components/Workspace/UI';
import { createActions } from '../../Components/Workspace/Navigation';
import Icon from '../../Components/Icon';

const greeting = () => { const hour = new Date().getHours(); return hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening'; };

export function ActivityItem({ event }) {
    const { t } = useWorkspace();
    return <li className="ws-row">
        <span className={`ws-avatar-sm ${event.system ? 'is-system' : ''}`} aria-hidden="true">{event.system ? <Icon name="clock" /> : initials(event.actor)}</span>
        <span className="ws-row-main"><span><strong>{event.actor}</strong> {event.href ? <Link className="text-link" href={event.href}>{t(event.text)}</Link> : t(event.text)}</span><small className="ws-sub">{when(event.at)}</small></span>
    </li>;
}

export default function Dashboard({ firstName, attention = [], queue = [], health = null, activity = [] }) {
    const { t, can } = useWorkspace();
    const create = createActions(can).slice(0, 6);
    const waiting = attention.filter(tile => tile.count > 0).length;
    const today = new Intl.DateTimeFormat('en-GB', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date());

    return <WorkspaceLayout title={`${greeting()}, ${firstName}`} description={waiting ? `${waiting} ${waiting === 1 ? 'area needs' : 'areas need'} you today. Everything else is up to date.` : 'Everything is up to date. Nothing is waiting for you.'} eyebrow={today}>
        {attention.length > 0 && <section className="ws-stats" aria-label={t('Needs attention')}>
            {attention.map(tile => <Link key={tile.key} href={tile.href} className="ws-stat">
                <span className="ws-stat-top"><span className={`ws-icon-tile tone-${tile.tone}`}><Icon name={tile.icon} /></span><span className="ws-stat-number">{tile.count}</span></span>
                <span><strong>{t(tile.label)}</strong><small>{t(tile.hint)}</small></span>
            </Link>)}
        </section>}

        <div className="ws-split">
            <div className="ws-wide">
                <section className="ws-card" aria-labelledby="queue-title">
                    <div className="ws-card-header"><h2 id="queue-title" className="ws-card-title">{t('Your work queue')}</h2></div>
                    {queue.length ? <ul className="ws-rows">{queue.map(item => <li key={`${item.href}-${item.title}`} className="ws-row">
                        <span className="ws-icon-tile" aria-hidden="true"><Icon name={item.icon} /></span>
                        <span className="ws-row-main"><strong>{item.title}</strong><small className="ws-sub">{t(item.meta)}</small></span>
                        <Status value={item.status} text={item.statusText} />
                        <Link className={item.primary ? 'button-primary' : 'button-secondary'} href={item.href}>{t(item.action)}</Link>
                    </li>)}</ul> : <div className="ws-empty"><h3>{t('You are all caught up')}</h3><p>{t('Reviews, new requests and applications will appear here when they need you.')}</p></div>}
                </section>

                {activity.length > 0 && <section className="ws-card mt-6" aria-labelledby="activity-title">
                    <div className="ws-card-header"><h2 id="activity-title" className="ws-card-title">{t('Recent activity')}</h2><Link className="text-link text-sm" href="/admin/audit-events">{t('View all activity')}</Link></div>
                    <ol className="ws-rows">{activity.map(event => <ActivityItem key={event.id} event={event} />)}</ol>
                </section>}
            </div>

            <div className="ws-narrow">
                {health && <section className="ws-card" aria-labelledby="health-title">
                    <div className="ws-card-header"><h2 id="health-title" className="ws-card-title">{t('Website health')}</h2></div>
                    <div className="ws-card-body">
                        {health.checked ? <>
                            <p className="flex items-baseline gap-3"><span className="ws-health-number">{health.blocking + health.warnings}</span><span className="text-sm ws-muted">{t(health.blocking + health.warnings === 1 ? 'thing to fix' : 'things to fix')}</span></p>
                            <ul className="ws-checks">
                                <li className={health.blocking ? 'is-warn' : 'is-ok'}><Icon name={health.blocking ? 'alert' : 'check'} /><span>{health.blocking ? t(':count problems block publishing', { count: health.blocking }) : t('Nothing blocks publishing')}</span></li>
                                <li className={health.warnings ? 'is-warn' : 'is-ok'}><Icon name={health.warnings ? 'alert' : 'check'} /><span>{health.warnings ? t(':count improvements suggested', { count: health.warnings }) : t('No improvements suggested')}</span></li>
                            </ul>
                            <p className="text-xs ws-muted">{t('Last checked :when', { when: health.checkedAt })}</p>
                        </> : <p className="text-sm ws-muted">{t('No SEO check has been run yet.')}</p>}
                        <Link className="text-link text-sm" href="/admin/seo">{t('Open SEO')}</Link>
                    </div>
                </section>}
                {create.length > 0 && <section className="ws-card" aria-labelledby="create-title">
                    <div className="ws-card-header"><h2 id="create-title" className="ws-card-title">{t('Start something new')}</h2></div>
                    <div className="ws-card-body">
                        <div className="ws-create-grid">{create.map(action => <Link key={action.href} href={action.href}><Icon name={action.icon} />{t(action.label)}</Link>)}</div>
                        <p className="text-xs ws-muted">{t('Everything else is under Create in the top bar.')}</p>
                    </div>
                </section>}
            </div>
        </div>
    </WorkspaceLayout>;
}
