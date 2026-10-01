// Replaces Laravel-style `:placeholder` tokens in server-translated strings.
export function format(template, replacements = {}) {
    return Object.entries(replacements).reduce(
        (text, [key, value]) => text.replaceAll(`:${key}`, String(value)),
        template ?? '',
    );
}

export function pad(number) {
    return String(number).padStart(2, '0');
}
