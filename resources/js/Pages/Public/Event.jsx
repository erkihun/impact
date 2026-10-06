import Reveal from '../../Components/Reveal';
import { ClosedNotice, TaskForm } from '../../Components/Public/TaskPanel';
import { RelatedContent } from '../../Components/Public/Common';
import PublicLayout, { PageIntro } from '../../Layouts/PublicLayout';

export default function Event({ breadcrumbs, composition, header, event, related = [], copy }) {
    return (
        <PublicLayout breadcrumbs={breadcrumbs}>
            <article>
                <PageIntro composition={composition} header={header} />

                <div className="public-section impact-editorial-surface">
                    <div className="public-action-layout content-container">
                        <Reveal className="public-content-canvas prose max-w-none">
                            <h2>{copy.about}</h2>
                            <p className="whitespace-pre-line">{event.description}</p>
                            {event.venue && (
                                <>
                                    <h2>{copy.venue}</h2>
                                    <p>{event.venue}</p>
                                </>
                            )}
                        </Reveal>

                        <aside className="task-panel" aria-labelledby="registration-heading">
                            {event.acceptsRegistrations ? (
                                <>
                                    <p className="eyebrow">{copy.attendance}</p>
                                    <h2 id="registration-heading" className="heading-3 mt-3">{copy.register}</h2>
                                    <p className="mt-3 text-sm leading-6 text-muted">{copy.registerNote}</p>
                                    <TaskForm
                                        action={event.registrationUrl}
                                        fields={[
                                            { name: 'name', label: copy.fullName, type: 'text', required: true, autocomplete: 'name' },
                                            { name: 'email', label: copy.email, type: 'email', required: true, autocomplete: 'email' },
                                        ]}
                                        consent={copy.privacy}
                                        marketing={copy.marketing}
                                        submitLabel={copy.submit}
                                        copy={copy}
                                    />
                                </>
                            ) : (
                                <ClosedNotice
                                    badge={copy.unavailable}
                                    title={copy.closed}
                                    text={copy.closedNote}
                                    linkLabel={copy.upcoming}
                                    href={event.indexUrl}
                                    headingId="registration-heading"
                                />
                            )}
                        </aside>
                    </div>
                </div>
            </article>
            <RelatedContent groups={related} title={copy.related} />
        </PublicLayout>
    );
}
