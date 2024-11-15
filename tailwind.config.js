import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './resources/js/**/*.vue',
  ],

  theme: {
    extend: {
      colors: {
        primary: '#4F6D9E',
        primaryHover: '#3D5A87',
        primaryActive: '#395076',
        secondary: '#7C8DA1',
        secondaryHover: '#6B7A8C',
        secondaryActive: '#5D6A7A',
        success: '#4A9D7A',
        error: '#D65757',
        warning: '#E6A547',
        surface: '#F8F9FC',
      },
      textColor: {
        textPrimary: '#1A202C',
        textSecondary: '#4A5568',
        textLight: '#EEF2F7',
      },
      fontFamily: {
        inter: ['Inter', ...defaultTheme.fontFamily.sans],
        newsreader: ['Newsreader', ...defaultTheme.fontFamily.serif],
        playfair: ['Playfair Display', ...defaultTheme.fontFamily.serif],
        sans: ['Figtree', ...defaultTheme.fontFamily.sans],
      },
    },
  },

  plugins: [forms],
};
