import type { Config } from 'tailwindcss'

export default {
  darkMode: 'class',
  content: [
    './components/**/*.{js,vue,ts}',
    './layouts/**/*.vue',
    './pages/**/*.vue',
    './plugins/**/*.{js,ts}',
    './app.vue',
    './error.vue'
  ],
  theme: {
    extend: {
      colors: {
        'error-container': '#93000a',
        'surface-container-low': '#1b1b1b',
        outline: '#b18780',
        'surface-variant': '#353535',
        'on-tertiary-fixed-variant': '#70362d',
        'inverse-surface': '#e2e2e2',
        'surface-container': '#1f1f1f',
        'primary-fixed': '#ffdad4',
        'on-tertiary-fixed': '#390c07',
        'surface-container-highest': '#353535',
        'primary-container': '#ff5540',
        primary: '#ffb4a8',
        'on-background': '#e2e2e2',
        'on-primary-fixed': '#410000',
        'surface-container-lowest': '#0e0e0e',
        surface: '#131313',
        error: '#ffb4ab',
        'inverse-primary': '#c00100',
        'tertiary-container': '#c77d72',
        background: '#131313',
        tertiary: '#ffb4a8',
        'tertiary-fixed': '#ffdad4',
        'on-tertiary-container': '#4b1a13',
        'surface-dim': '#131313',
        'on-surface-variant': '#ebbbb4',
        'surface-container-high': '#2a2a2a',
        'on-secondary-fixed': '#410000',
        'on-error': '#690005',
        'secondary-container': '#8a1b11',
        'on-surface': '#e2e2e2',
        'on-error-container': '#ffdad6',
        'on-secondary-fixed-variant': '#8a1b11',
        secondary: '#ffb4a8',
        'on-tertiary': '#542019',
        'outline-variant': '#603e39',
        'on-secondary-container': '#ff9a8a',
        'on-primary-container': '#5c0000',
        'surface-tint': '#ffb4a8',
        'on-secondary': '#690000',
        'on-primary-fixed-variant': '#930100',
        'on-primary': '#690100',
        'primary-fixed-dim': '#ffb4a8',
        'secondary-fixed': '#ffdad4',
        'tertiary-fixed-dim': '#ffb4a8',
        'inverse-on-surface': '#303030',
        'surface-bright': '#393939',
        'secondary-fixed-dim': '#ffb4a8'
      },
      borderRadius: {
        DEFAULT: '0',
        lg: '0',
        xl: '0',
        full: '0'
      },
      fontFamily: {
        headline: ['"Noto Serif"', 'serif'],
        body: ['Inter', 'system-ui', 'sans-serif'],
        label: ['Inter', 'system-ui', 'sans-serif']
      }
    }
  },
  plugins: []
} as Config
