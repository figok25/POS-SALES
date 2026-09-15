import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

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
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Root theme tokens — values live in resources/css/app.css (:root)
                primary: {
                    DEFAULT: 'var(--color-primary)',
                    hover: 'var(--color-primary-hover)',
                    light: 'var(--color-primary-light)',
                    subtle: 'var(--color-primary-subtle)',
                },
                secondary: {
                    DEFAULT: 'var(--color-secondary)',
                    hover: 'var(--color-secondary-hover)',
                },
                accent: 'var(--color-accent)',
                surface: {
                    DEFAULT: 'var(--color-surface)',
                    hover: 'var(--color-surface-hover)',
                },
                sidebar: {
                    DEFAULT: 'var(--sidebar-bg)',
                    hover: 'var(--sidebar-bg-hover)',
                    active: 'var(--sidebar-bg-active)',
                    foreground: 'var(--sidebar-foreground)',
                    heading: 'var(--sidebar-heading)',
                    border: 'var(--sidebar-border)',
                },
            },
        },
    },

    plugins: [forms],
};
