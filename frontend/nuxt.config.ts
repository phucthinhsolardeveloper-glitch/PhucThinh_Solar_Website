import tailwindcss from '@tailwindcss/vite'

// Migration only. No production route is switched to Nuxt until parity gates pass.
export default defineNuxtConfig({
  ssr: true,
  devtools: { enabled: false },
  css: ['~/assets/css/tokens.css', '~/assets/css/tailwind.css'],
  vite: {
    plugins: [tailwindcss()],
  },
  typescript: { strict: true },
  app: {
    head: {
      htmlAttrs: { lang: 'vi' },
      meta: [{ name: 'theme-color', content: '#0a2251' }],
    },
  },
  routeRules: {
    '/migration-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-site-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
  },
  runtimeConfig: {
    // Override through NUXT_PHP_ORIGIN; never expose this in public runtimeConfig.
    phpOrigin: 'http://127.0.0.1:8080',
    previewMediaProxy: false,
  },
})
