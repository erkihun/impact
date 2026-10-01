import { fieldId } from './Common';

// One labelled control with help text and an error message wired up through
// aria-describedby / aria-invalid, matching the Blade form conventions.
export default function FormField({ field, value, error, onChange, requiredLabel, className = '' }) {
    const id = fieldId(field.name);
    const helpId = field.help ? `${id}-help` : null;
    const errorId = error ? `${id}-error` : null;
    const describedBy = [helpId, errorId].filter(Boolean).join(' ') || undefined;
    const common = {
        id,
        name: field.type === 'file' && field.multiple ? `${field.name}[]` : field.name,
        required: field.required || undefined,
        'aria-describedby': describedBy,
        'aria-invalid': error ? 'true' : undefined,
    };

    let control;

    if (field.type === 'textarea') {
        control = (
            <textarea
                {...common}
                className="form-input min-h-48"
                value={value ?? ''}
                minLength={field.minlength ?? undefined}
                maxLength={field.maxlength ?? undefined}
                onChange={(event) => onChange(event.target.value)}
            />
        );
    } else if (field.type === 'select') {
        control = (
            <select {...common} className="form-input" value={value ?? ''} onChange={(event) => onChange(event.target.value)}>
                {field.options.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
            </select>
        );
    } else if (field.type === 'file') {
        control = (
            <div className="file-upload-control">
                <svg className="size-6 shrink-0 text-action-700" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
                    <path d="M12 16V4m0 0 4 4m-4-4L8 8M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4" />
                </svg>
                <input
                    {...common}
                    className="min-w-0 max-w-full flex-1 overflow-hidden text-sm file:me-4 file:min-h-11 file:rounded-lg file:border-0 file:bg-brand-950 file:px-4 file:font-bold file:text-white"
                    type="file"
                    accept={field.accept ?? undefined}
                    multiple={field.multiple || undefined}
                    onChange={(event) => onChange(field.multiple ? [...event.target.files] : event.target.files[0] ?? null)}
                />
            </div>
        );
    } else {
        control = (
            <input
                {...common}
                className="form-input"
                type={field.type}
                value={value ?? ''}
                minLength={field.minlength ?? undefined}
                maxLength={field.maxlength ?? undefined}
                autoComplete={field.autocomplete ?? undefined}
                onChange={(event) => onChange(event.target.value)}
            />
        );
    }

    return (
        <div className={`${field.wide ? 'sm:col-span-2' : ''} ${className}`}>
            <label className="form-label" htmlFor={id}>
                {field.label}
                {field.required && <> <span className="form-required">({requiredLabel})</span></>}
            </label>
            {field.help && <p className="form-help mb-2" id={helpId}>{field.help}</p>}
            {control}
            {error && <p className="field-error" id={errorId}>{error}</p>}
        </div>
    );
}
