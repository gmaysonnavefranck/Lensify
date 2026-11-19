import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// A color read from a CSS variable holding 'r g b', so opacity modifiers like bg-gray-100/60 keep working
const themed = (name) => `rgb(var(--${name}) / <alpha-value>)`;
const grayScale = Object.fromEntries(
  [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].map((step) => [step, themed(`gray-${step}`)]),
);

/** @type {import('tailwindcss').Config} */
export default {
  // Dark mode swaps the CSS variables below (see app.css) when <html> has the `dark` class,
  // so existing classes like bg-white, border-gray-200 and text-textPrimary change without dark: variants
  darkMode: 'class',
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
        gray: grayScale,
        slate: grayScale,
      },
      // Card and page surfaces; text-white stays white
      backgroundColor: {
        white: themed('card'),
      },
      textColor: {
        primary: themed('primary-text'),
        textPrimary: themed('text-primary'),
        textSecondary: themed('text-secondary'),
        textLight: '#EEF2F7',
      },
      fontFamily: {
        inter: ['Inter Variable', ...defaultTheme.fontFamily.sans],
        newsreader: ['Newsreader Variable', ...defaultTheme.fontFamily.serif],
        playfair: ['Playfair Display Variable', ...defaultTheme.fontFamily.serif],
      },
    },
  },

  plugins: [forms],
};
