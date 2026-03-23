/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.twig',
    './templates/**/*.html.twig',
    './assets/**/*.js',
    './src/**/*.php',
  ],
  theme: {
    extend: {
      colors: {
        yellow: {
          500: '#F2B705',
          400: '#F3C107',
          300: '#F5CB2A',
        },
        gray: {
          800: '#1F1F1F',
          900: '#0A0A0A',
          700: '#2D2D2D',
          600: '#3D3D3D',
          500: '#4D4D4D',
          400: '#6B6B6B',
          300: '#8B8B8B',
        },
      },
      fontFamily: {
        'poppins': ['Poppins', 'sans-serif'],
        'baskerville': ['Libre Baskerville', 'serif'],
      },
    },
  },
  plugins: [],
}