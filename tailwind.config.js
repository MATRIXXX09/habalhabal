/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./assets/**/*.{js,jsx,ts,tsx}",
        "./templates/**/*.{html,html.twig}",
        "./node_modules/tw-elements/dist/js/**/*.js"
    ],
    theme: {
        extend: {},
    },
    plugins: [
        require("tw-elements/dist/plugin")
    ],
    darkMode: "class"
}