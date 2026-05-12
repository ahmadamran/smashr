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
            colors: {
                'brand-blue': '#0864C8',
                'brand-blue-dark': '#064C9D',
                'brand-blue-deep': '#053A7A',
                'brand-green': '#249044',
                'brand-green-bright': '#00944C',
                'brand-ink': '#07345F',
                'brand-surface': '#F2F8FC',
                'brand-mist': '#EAF6F5',
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
