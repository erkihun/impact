import Reveal from '../../Components/Reveal';
import { ClosedNotice, TaskForm } from '../../Components/Public/TaskPanel';
import PublicLayout, { PageIntro } from '../../Layouts/PublicLayout';

export default function Vacancy({ breadcrumbs, composition, header, vacancy, copy }) {
    return (
        <PublicLayout breadcrumbs={breadcrumbs}>
            <article>
                <PageIntro composition={composition} header={header} />

                <div className="public-section impact-editorial-surface">
                    <div className="public-action-layout content-container">
                        <Reveal className="public-content-canvas prose max-w-none">
                            <h2>{copy.opportunity}</h2>
                            <p className="whitespace-pre-line">{vacancy.description}</p>
                            <h2>{copy.requirements}</h2>
                            <p className="whitespace-pre-line">{vacancy.requirements}</p>
                            {vacancy.closesAt && (
                                <p>
                                    <strong>{copy.closingDate}:</strong>{' '}
                                    <time dateTime={vacancy.closesAtIso}>{vacancy.closesAt}</time>
                                </p>
                            )}
                        </Reveal>

                        <aside className="task-panel task-panel-brand" aria-labelledby="application-heading">
                            {vacancy.acceptsApplications ? (
                                <>
                                    <p className="eyebrow">{copy.secure}</p>
                                    <h2 id="application-heading" className="heading-3 mt-3">{copy.apply}</h2>
                                    <p className="mt-3 text-sm leading-6 text-muted">{copy.applyNote}</p>
                                    <TaskForm
                                        action={vacancy.applicationUrl}
                                        multipart
                                        fields={[
                                            { name: 'applicant_name', label: copy.fullName, type: 'text', required: true, autocomplete: 'name' },
                                            { name: 'email', label: copy.email, type: 'email', required: true, autocomplete: 'email' },
                                            { name: 'phone', label: copy.phone, type: 'tel', autocomplete: 'tel' },
                                            { name: 'cv', label: copy.cv, type: 'file', required: true, accept: '.pdf,.docx', help: copy.cvHelp },
                                            { name: 'cover_letter', label: copy.coverLetter, type: 'textarea', maxlength: 10000 },
                                        ]}
                                        consent={copy.privacy}
                                        submitLabel={copy.submit}
                                        copy={copy}
                                    />
                                </>
                            ) : (
                                <ClosedNotice
                                    badge={copy.unavailable}
                                    title={copy.closed}
                                    text={copy.closedNote}
                                    linkLabel={copy.current}
                                    href={vacancy.indexUrl}
                                    headingId="application-heading"
                                />
                            )}
                        </aside>
                    </div>
                </div>
            </article>
        </PublicLayout>
    );
}
