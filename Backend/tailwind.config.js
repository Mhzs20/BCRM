/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],

    safelist: [
        'grid-cols-6',
        'col-span-3',
        'text-3xl',
        'font-light',
        'w-20',
        'h-20',
    ],

    theme: {
        extend: {
            colors: {
                'custom-green': 'rgba(33, 82, 66, 1)',
            },
            fontFamily: {
                // Here we create the font families.
                // You can name them anything you want.
                'iransans': ['IRANSansWeb', 'sans-serif'],
                'iranyekan': ['iranyekanweb', 'sans-serif'],
                'peyda': ['PeydaWeb', 'sans-serif'],
            },
        },
    },

    plugins: [],
}
