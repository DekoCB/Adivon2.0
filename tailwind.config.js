import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Hanken Grotesk', ...defaultTheme.fontFamily.sans],
                display: ['Bricolage Grotesque', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Acento de marca para la firma de códigos (producto, IMEI,
                // comprobante) — distinto del azul institucional, usado con
                // disciplina solo donde hay un código real que mostrar.
                coral: {
                    50: '#fbe9e4',
                    300: '#e8a692',
                    600: '#c6452e',
                    700: '#a83a26',
                    950: '#3a1f16',
                },
            },
        },
    },
    plugins: [],
};