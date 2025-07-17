/** @type {import('tailwindcss').Config} */
module.exports = {
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
        extend: {},
    },
    plugins: [],
}
