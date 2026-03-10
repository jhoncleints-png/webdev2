/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.twig',
    './assets/**/*.js'
  ],
  theme: {
    extend: {
      colors: {
        primary: '#F2B705',
        secondary: '#1F1F1F',
        tertiary: '#ffffff',
      },
      fontFamily: {
        header: ['Poppins', 'sans-serif'],
        body: ['Libre Baskerville', 'serif'],
      },
      backdropBlur: {
        xs: '2px',
        sm: '4px',
        md: '8px',
        lg: '12px',
        xl: '20px',
      },
    },
  },
  plugins: [],
};
