/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './views/**/*.php',
    './app/views/**/*.php',
    './public/assets/js/**/*.js',
  ],
  theme: {
    extend: {
      fontFamily: {
        'inter': ['Inter', 'sans-serif'],
      },
      width: {
        'sidebar': '272px',
        'sidebar-collapsed': '76px',
      },
      colors: {
        'pide': {
          'primary': '#1e40af',
          'secondary': '#3b82f6',
          'dark': '#0f172a',
          'darker': '#1e293b',
          'light': '#dbeafe',
        },
      },
      transitionTimingFunction: {
        'sidebar': 'cubic-bezier(0.4, 0, 0.2, 1)',
      },
    },
  },
  plugins: [],
}
