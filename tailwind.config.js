import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
               sans: ['system-ui', 'sans-serif'],
            },

            colors: {
                primary: {
                    DEFAULT: '#DC2626',
                    light: '#EF4444',
                    dark: '#B91C1C',
                },

                secondary: {
                    DEFAULT: '#FFFFFF',
                    dark: '#111827',
                },

                success: '#16A34A',

                danger: '#DC2626',

                warning: '#F59E0B',

                surface: '#F9FAFB',

                border: '#E5E7EB',
            },
        },
    },

    plugins: [forms],
};