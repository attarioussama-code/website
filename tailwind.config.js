import defaultTheme from 'tailwindcss/defaultTheme'

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './app/Http/Livewire/**/*.php',
        './public/js/widgets/**/*.js',
        './vendor/filament/**/*.blade.php',
        './resources/views/filament/**/*.blade.php',],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: '#f0f9ff',
                    100: '#e0f2fe',
                    500: '#3b82f6',
                    600: '#2563eb',
                },
                orange: {
                    500: '#f97316',
                    600: '#ea580c',
                },
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
        require('@tailwindcss/line-clamp'),
    ],
    corePlugins: {
        preflight: true,
    }
};