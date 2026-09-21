import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

const approvedCore = {
    brand: '#17324D',
    action: '#2D7A78',
    knowledge: '#2D6C99',
    gold: '#C99A2E',
    ink: '#1F2933',
    muted: '#5D6A74',
    quiet: '#F2F4F6',
    danger: '#C0392B',
};

const runtimeColor = (token) => `rgb(var(--palette-${token}) / <alpha-value>)`;
const runtimeScale = (name) => Object.fromEntries(
    [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950]
        .map((shade) => [shade, runtimeColor(`${name}-${shade}`)]),
);

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', '"Noto Sans Ethiopic"', 'Arial', ...defaultTheme.fontFamily.sans],
                editorial: ['Inter', '"Noto Sans Ethiopic"', 'Arial', ...defaultTheme.fontFamily.sans],
                serif: ['Inter', '"Noto Sans Ethiopic"', 'Arial', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: runtimeScale('brand'),
                action: runtimeScale('action'),
                knowledge: runtimeScale('knowledge'),
                gold: runtimeScale('gold'),
                impact: runtimeScale('action'),
                sand: {
                    50: '#FFFFFF',
                    100: runtimeColor('surface-muted'),
                    200: runtimeColor('border'),
                },
                sun: {
                    300: runtimeColor('gold-300'),
                    400: runtimeColor('gold-500'),
                },
                ink: runtimeColor('text'),
                muted: runtimeColor('muted'),
                quiet: runtimeColor('surface-muted'),
                danger: approvedCore.danger,
                // Semantic roles. Components reference these rather than a raw ramp,
                // so a token change propagates everywhere at once.
                surface: {
                    DEFAULT: '#FFFFFF',
                    muted: runtimeColor('surface-muted'),
                    sidebar: runtimeColor('brand-950'),
                    selected: runtimeColor('action-50'),
                    disabled: '#EEF1F4',
                },
                edge: {
                    DEFAULT: runtimeColor('border'),
                    strong: '#8FA3B5',
                },
                state: {
                    success: '#238636',
                    'success-bg': '#ECF7EE',
                    warning: '#B7791F',
                    'warning-bg': '#FDF6E7',
                    danger: '#C0392B',
                    'danger-bg': '#FDF1EF',
                    info: runtimeColor('knowledge-600'),
                    'info-bg': '#EEF6FB',
                },
                chart: {
                    1: runtimeColor('knowledge-600'),
                    2: runtimeColor('action-500'),
                    3: runtimeColor('brand-900'),
                    4: runtimeColor('gold-500'),
                    neutral: '#8B98A3',
                    grid: '#E3E8ED',
                },
            },
            boxShadow: {
                editorial: '0 1px 2px rgb(15 35 55 / 0.08)',
                overlay: '0 8px 24px rgb(15 35 55 / 0.14)',
            },
        },
    },

    plugins: [forms, typography],
};
