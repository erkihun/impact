/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./index.html', './assets/js/app.js'],
  theme: {
    extend: {
      colors: {
        brand: {
          blue: '#123B75',
          green: '#16A067',
          orange: '#E89B24',
          gray: '#4F6578',
          ink: '#0A1F3D',
          mist: '#F4F7FB',
          line: '#D9E1EA',
        },
      },
      fontFamily: {
        sans: ['Inter', 'Noto Sans Ethiopic', 'ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
        ethiopic: ['Noto Sans Ethiopic', 'Abyssinica SIL', 'Nyala', 'Kefa', 'Inter', 'sans-serif'],
      },
      boxShadow: {
        card: '0 1px 2px rgba(10,31,61,.06), 0 8px 24px -12px rgba(10,31,61,.18)',
        paper: '0 2px 4px rgba(10,31,61,.08), 0 24px 48px -24px rgba(10,31,61,.35)',
      },
    },
  },
  plugins: [],
};
