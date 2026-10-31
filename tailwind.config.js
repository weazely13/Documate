import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import plugin from 'tailwindcss/plugin';
import colors from 'tailwindcss/colors';

// Warm paper at the light end (Notion), deep navy ink at the dark end (Stripe)
const neutral = {
    50: '#fafaf9',
    100: '#f5f4f2',
    200: '#e7e5e1',
    300: '#d4d1cb',
    400: '#9a978f',
    500: '#66707f',
    600: '#4a5568',
    700: '#273951',
    800: '#182c44',
    900: '#0d253d',
    950: '#071627',
};

// DocuMate blue, #2A57B4 at 600
const brand = {
    50: '#f0f4fb',
    100: '#dfe7f6',
    200: '#c0d0ee',
    300: '#93aee0',
    400: '#6086cf',
    500: '#3e69c2',
    600: '#2a57b4',
    700: '#24499a',
    800: '#1e4090',
    900: '#1a346f',
    950: '#112247',
};

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
            /*
             * DESIGN.md tokens. Neutrals run from Notion's warm paper greys to Stripe's deep-navy ink,
             * so the existing slate-* and gray-* classes pick up the system without touching the views.
             */
            colors: {
                slate: neutral,
                gray: neutral,
                zinc: neutral,
                blue: brand,
                indigo: brand,
                brand,
                emerald: { ...colors.emerald, 50: '#eef8f2', 100: '#d9f3e1' },
                green: { ...colors.green, 50: '#eef8f2', 100: '#d9f3e1' },
                rose: { ...colors.rose, 50: '#fdf1f6', 100: '#fde0ec' },
                red: { ...colors.red, 50: '#fdf2f1', 100: '#fbe3e0' },
                amber: { ...colors.amber, 50: '#fefbeb', 100: '#fef3c7' },
                sky: { ...colors.sky, 50: '#f0f7fd', 100: '#dcecfa' },
                violet: { ...colors.violet, 50: '#f4f1fb', 100: '#e6e0f5' },
                purple: { ...colors.purple, 50: '#f4f1fb', 100: '#e6e0f5' },
            },
            fontFamily: {
                sans: ['"Inter Variable"', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                xl: '10px',
                '2xl': '12px',
                '3xl': '16px',
            },
            boxShadow: {
                sm: '0 1px 2px rgba(13, 37, 61, 0.06)',
                DEFAULT: '0 1px 3px rgba(0, 55, 112, 0.08), 0 1px 2px rgba(13, 37, 61, 0.04)',
                md: '0 4px 12px rgba(0, 55, 112, 0.07), 0 1px 3px rgba(13, 37, 61, 0.05)',
                lg: '0 8px 24px rgba(0, 55, 112, 0.08), 0 2px 6px rgba(0, 55, 112, 0.04)',
                xl: '0 16px 40px rgba(0, 55, 112, 0.10), 0 4px 10px rgba(0, 55, 112, 0.05)',
                '2xl': '0 24px 64px rgba(0, 55, 112, 0.14), 0 6px 16px rgba(0, 55, 112, 0.06)',
            },
        },
    },

    plugins: [forms, containerQueries],
};
