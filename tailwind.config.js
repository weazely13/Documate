import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import plugin from 'tailwindcss/plugin';

/*
 * Container queries: `cq` marks an element whose width its children respond to, and
 * cq-xs / cq-sm / cq-md / cq-lg / cq-xl apply at that container's width rather than the screen's.
 * Used where the main sidebar's open/collapsed state changes the space a page has.
 */
const containerQueries = plugin(({ addUtilities, addVariant }) => {
    addUtilities({ '.cq': { 'container-type': 'inline-size' } });
    addVariant('cq-xs', '@container (min-width: 28rem)');
    addVariant('cq-sm', '@container (min-width: 36rem)');
    addVariant('cq-md', '@container (min-width: 48rem)');
    addVariant('cq-lg', '@container (min-width: 64rem)');
    addVariant('cq-xl', '@container (min-width: 80rem)');
});

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './app/**/*.php',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms, containerQueries],
};
