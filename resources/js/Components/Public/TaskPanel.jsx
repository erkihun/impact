import { useForm } from '@inertiajs/react';
import AppLink from '../AppLink';
import { ErrorSummary } from './Common';
import FormField from './FormField';

// Sidebar task form used by event registration and job applications.
export function TaskForm({ action, fields, consent, marketing, submitLabel, copy, multipart = false }) {
    const initial = { website: '', privacy_acknowledged: false };
    fields.forEach((field) => {
        initial[field.name] = field.type === 'file' ? null : '';
    });
    if (marketing) {
        initial.marketing_consent = false;
    }

    const form = useForm(initial);

    const submit = (event) => {
        event.preventDefault();
        form.post(action, {
            forceFormData: multipart,
            preserveScroll: (page) => Object.keys(page.props.errors ?? {}).length > 0,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <form className="mt-7 grid gap-5" onSubmit={submit} encType={multipart ? 'multipart/form-data' : undefined} aria-busy={form.processing || undefined}>
            <div className="hidden" aria-hidden="true">
                <label>Website<input name="website" tabIndex={-1} autoComplete="off" value={form.data.website} onChange={(event) => form.setData('website', event.target.value)} /></label>
            </div>
            <ErrorSummary errors={form.errors} title={copy.errorSummary} />
            {fields.map((field) => (
                <FormField
                    key={field.name}
                    field={field}
                    value={form.data[field.name]}
                    error={form.errors[field.name]}
                    onChange={(value) => form.setData(field.name, value)}
                    requiredLabel={copy.required}
                />
            ))}
            <label className="flex items-start gap-3 text-sm leading-6" htmlFor="privacy_acknowledged">
                <input
                    id="privacy_acknowledged"
                    className="mt-1 rounded"
                    type="checkbox"
                    required
                    checked={form.data.privacy_acknowledged}
                    onChange={(event) => form.setData('privacy_acknowledged', event.target.checked)}
                    aria-invalid={form.errors.privacy_acknowledged ? 'true' : undefined}
                />
                <span>{consent}</span>
            </label>
            {form.errors.privacy_acknowledged && <p className="field-error">{form.errors.privacy_acknowledged}</p>}
            {marketing && (
                <label className="flex items-start gap-3 text-sm leading-6" htmlFor="marketing_consent">
                    <input
                        id="marketing_consent"
                        className="mt-1 rounded"
                        type="checkbox"
                        checked={form.data.marketing_consent}
                        onChange={(event) => form.setData('marketing_consent', event.target.checked)}
                    />
                    <span>{marketing}</span>
                </label>
            )}
            <button className="button-primary w-full" type="submit" disabled={form.processing}>{submitLabel}</button>
        </form>
    );
}

export function ClosedNotice({ badge, title, text, linkLabel, href, headingId }) {
    return (
        <>
            <p className="status-badge status-badge-neutral">{badge}</p>
            <h2 id={headingId} className="heading-3 mt-4">{title}</h2>
            <p className="mt-3 text-sm leading-6 text-muted">{text}</p>
            <AppLink className="text-link mt-5" href={href}>{linkLabel} <span aria-hidden="true">→</span></AppLink>
        </>
    );
}
