import { useForm, usePage } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import { useEffect, useMemo, useRef, useState } from 'react';
import AppLink from '../../Components/AppLink';
import { ErrorSummary } from '../../Components/Public/Common';
import FormField from '../../Components/Public/FormField';
import PublicLayout, { PageIntro } from '../../Layouts/PublicLayout';
import { pad } from '../../lib/format';

const EASE = [0.2, 0.8, 0.2, 1];

function initialData(form) {
    const data = { ...form.hidden, website: '', privacy_acknowledged: false };

    form.steps.flatMap((step) => step.fields ?? []).forEach((field) => {
        if (field.type === 'file') {
            data[field.name] = field.multiple ? [] : null;
        } else if (field.type === 'select') {
            data[field.name] = field.options[0]?.value ?? '';
        } else {
            data[field.name] = '';
        }
    });

    return data;
}

// The first step holding a field that failed server validation.
function stepWithErrors(steps, errors) {
    const failed = Object.keys(errors).map((key) => key.split('.')[0]);
    const index = steps.findIndex((step) => (
        (step.fields ?? []).some((field) => failed.includes(field.name))
        || (step.consent && failed.includes('privacy_acknowledged'))
    ));

    return index === -1 ? 0 : index;
}

function ContextPanel({ context }) {
    return (
        <aside className="form-context-panel lg:sticky lg:top-32" aria-labelledby="engagement-context-title">
            <p className="insight-marker">{context.eyebrow}</p>
            <h2 id="engagement-context-title" className="mt-5 font-editorial text-2xl font-bold text-brand-950">{context.title}</h2>
            <p className="mt-4 text-sm leading-7 text-muted">{context.text}</p>
            {context.items.length > 0 && (
                <ol className="workflow-rail mt-7">
                    {context.items.map((item, index) => (
                        <li key={item} className="py-3">
                            <span className="font-mono text-xs font-bold text-action-700">{pad(index + 1)}</span>
                            <span className="ms-3 text-sm font-semibold text-brand-950">{item}</span>
                        </li>
                    ))}
                </ol>
            )}
            {context.footerTitle && (
                <div className="mt-7 border-t border-slate-300 pt-6">
                    <p className="text-xs font-bold uppercase tracking-[0.14em] text-muted">{context.footerTitle}</p>
                    <p className="mt-3 text-sm leading-7 text-muted">{context.footerText}</p>
                </div>
            )}
            {context.links.length > 0 && (
                <div className="mt-7 grid gap-3 border-t border-slate-300 pt-6">
                    {context.links.map((link) => (
                        <AppLink key={link.href} className={`${link.primary ? 'button-primary' : 'button-secondary'} w-full`} href={link.href}>{link.label}</AppLink>
                    ))}
                </div>
            )}
        </aside>
    );
}

export default function Engagement({ meta, breadcrumbs, composition, header, form: schema, context, copy }) {
    const { errors } = usePage().props;
    const form = useForm(initialData(schema));
    const steps = schema.steps;
    const multiStep = steps.length > 1;
    const [step, setStep] = useState(() => stepWithErrors(steps, errors));
    const sectionRef = useRef(null);
    const headingRef = useRef(null);
    const firstRender = useRef(true);
    const current = steps[step];
    const final = step === steps.length - 1;

    // Re-open the step that holds a server-side validation error.
    useEffect(() => {
        if (Object.keys(errors).length > 0) {
            setStep(stepWithErrors(steps, errors));
        }
    }, [errors, steps]);

    // Move focus to the new step's heading so the change is announced.
    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        headingRef.current?.focus();
    }, [step]);

    const valid = () => {
        const controls = sectionRef.current ? [...sectionRef.current.querySelectorAll('input, select, textarea')] : [];
        const invalid = controls.find((control) => ! control.checkValidity());

        if (invalid) {
            invalid.reportValidity();
            invalid.focus();

            return false;
        }

        return true;
    };

    const next = () => valid() && setStep((value) => Math.min(value + 1, steps.length - 1));
    const previous = () => setStep((value) => Math.max(value - 1, 0));
    const goTo = (index) => {
        if (index < step || (index === step + 1 && valid())) {
            setStep(index);
        }
    };

    const submit = (event) => {
        event.preventDefault();

        if (! final) {
            next();

            return;
        }

        if (! valid()) {
            return;
        }

        form.post(schema.action, {
            forceFormData: schema.multipart,
            preserveScroll: (page) => Object.keys(page.props.errors ?? {}).length > 0,
            onSuccess: () => {
                form.reset();
                setStep(0);
            },
        });
    };

    const reviewValue = (field) => {
        const value = form.data[field];

        return value && String(value).trim() !== '' ? value : copy.notProvided;
    };

    const fields = useMemo(() => current.fields ?? [], [current]);

    return (
        <PublicLayout title={meta.title} description={meta.description} breadcrumbs={breadcrumbs}>
            <PageIntro composition={composition} header={header} />

            <section className="public-section impact-editorial-surface">
                <div className="public-engagement-layout content-container lg:grid-cols-[minmax(0,1fr)_20rem]">
                    <div>
                        {multiStep && (
                            <ol className="process-tabs" aria-label={schema.progressLabel}>
                                {steps.map((item, index) => (
                                    <li key={item.label}>
                                        <button
                                            className={`flex min-h-11 w-full items-center gap-2 rounded-lg px-2 text-start text-xs font-bold ${index === step ? 'bg-action-500 text-white' : (index < step ? 'bg-action-50 text-action-800' : 'bg-quiet text-muted')}`}
                                            type="button"
                                            disabled={index > step + 1 || form.processing}
                                            onClick={() => goTo(index)}
                                            aria-current={index === step ? 'step' : undefined}
                                        >
                                            <span className="grid size-6 shrink-0 place-items-center rounded-full border border-current">{index + 1}</span>
                                            <span className="sr-only sm:not-sr-only">{item.label}</span>
                                        </button>
                                    </li>
                                ))}
                            </ol>
                        )}

                        <form className="engagement-form-canvas" onSubmit={submit} noValidate={false} encType={schema.multipart ? 'multipart/form-data' : undefined} aria-busy={form.processing || undefined}>
                            <div className="hidden" aria-hidden="true">
                                <label>Website<input name="website" tabIndex={-1} autoComplete="off" value={form.data.website} onChange={(event) => form.setData('website', event.target.value)} /></label>
                            </div>

                            <ErrorSummary className="mb-8" errors={form.errors} title={copy.errorSummary} />
                            {schema.intro && <p className="mb-7 text-sm leading-6 text-muted">{schema.intro}</p>}

                            <AnimatePresence mode="wait" initial={false}>
                                <motion.section
                                    key={step}
                                    ref={sectionRef}
                                    aria-labelledby={current.heading ? `${schema.id}-step-${step + 1}` : undefined}
                                    initial={{ opacity: 0, x: 24 }}
                                    animate={{ opacity: 1, x: 0 }}
                                    exit={{ opacity: 0, x: -24 }}
                                    transition={{ duration: 0.3, ease: EASE }}
                                >
                                    {current.eyebrow && <p className="eyebrow">{current.eyebrow}</p>}
                                    {current.heading && (
                                        <h2 id={`${schema.id}-step-${step + 1}`} ref={headingRef} className="heading-3 mt-3" tabIndex={-1}>{current.heading}</h2>
                                    )}
                                    {current.notice && <div className="status-information mt-5">{current.notice}</div>}

                                    {fields.length > 0 && (
                                        <div className={`grid gap-6 sm:grid-cols-2 ${current.heading ? 'mt-6' : ''}`}>
                                            {fields.map((field) => (
                                                <FormField
                                                    key={field.name}
                                                    field={field}
                                                    value={form.data[field.name]}
                                                    error={form.errors[field.name] ?? form.errors[`${field.name}.0`]}
                                                    onChange={(value) => form.setData(field.name, value)}
                                                    requiredLabel={copy.required}
                                                />
                                            ))}
                                        </div>
                                    )}

                                    {current.review && (
                                        <dl className="mt-6 grid gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 sm:grid-cols-2">
                                            {current.review.map((row) => (
                                                <div key={row.field} className="bg-white p-4">
                                                    <dt className="text-xs font-bold uppercase tracking-wide text-muted">{row.label}</dt>
                                                    <dd className="mt-2 whitespace-pre-line text-sm text-ink">{reviewValue(row.field)}</dd>
                                                </div>
                                            ))}
                                        </dl>
                                    )}

                                    {current.consent && (
                                        <>
                                            <label className="mt-7 flex items-start gap-3 text-sm leading-6 text-slate-700" htmlFor="privacy_acknowledged">
                                                <input
                                                    id="privacy_acknowledged"
                                                    className="mt-1 rounded"
                                                    type="checkbox"
                                                    required
                                                    checked={form.data.privacy_acknowledged}
                                                    onChange={(event) => form.setData('privacy_acknowledged', event.target.checked)}
                                                    aria-invalid={form.errors.privacy_acknowledged ? 'true' : undefined}
                                                />
                                                <span>{current.consent}</span>
                                            </label>
                                            {form.errors.privacy_acknowledged && <p className="field-error">{form.errors.privacy_acknowledged}</p>}
                                        </>
                                    )}

                                    {current.note && <div className="status-information mt-6">{current.note}</div>}
                                </motion.section>
                            </AnimatePresence>

                            <div className="mt-8 flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-between">
                                {multiStep && step > 0 ? (
                                    <button className="button-secondary" type="button" onClick={previous}>{copy.previous}</button>
                                ) : <span className="hidden sm:block" />}
                                {final ? (
                                    <button className="button-primary" type="submit" disabled={form.processing}>{schema.submitLabel} <span aria-hidden="true">→</span></button>
                                ) : (
                                    <button className="button-primary" type="button" onClick={next}>{copy.continue} <span aria-hidden="true">→</span></button>
                                )}
                            </div>
                        </form>
                    </div>

                    <ContextPanel context={context} />
                </div>
            </section>
        </PublicLayout>
    );
}
