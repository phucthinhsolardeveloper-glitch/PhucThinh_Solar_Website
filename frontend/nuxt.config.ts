// Migration only. No production route is switched to Nuxt until parity gates pass.
export default defineNuxtConfig({
  ssr: true,
  devtools: { enabled: false },

  // CODEX 1 — nền tảng Tailwind v4 + Nuxt UI v4.
  // Nuxt UI v4 đã mang sẵn tích hợp Tailwind, nên KHÔNG thêm @nuxtjs/tailwindcss
  // (module v6 dành cho Tailwind v3) và cũng không tự khai plugin
  // @tailwindcss/vite — làm vậy sẽ chạy hai pipeline CSS cạnh tranh nhau.
  modules: ['@nuxt/ui'],

  colorMode: {
    preference: 'light',
    fallback: 'light',
    storage: 'localStorage',
    // Use a fresh key while dark mode is hidden so prior test preferences cannot
    // force users into the unfinished theme.
    storageKey: 'pts-color-mode-light-only',
  },

  /*
   * main.css nạp Tailwind v4, theme và style CMS. tokens.css nạp sau để reset
   * global nhỏ, không nằm trong layer, áp dụng sau Preflight của Tailwind.
   * Không còn shell.css hoặc stylesheet riêng cho migration preview.
   */
  css: ['~/assets/css/main.css', '~/assets/css/tokens.css'],

  /*
   * @nuxt/fonts đi kèm @nuxt/ui và MẶC ĐỊNH tự đi tải mọi font-family nó thấy
   * trong CSS về self-host. Phát hiện khi đo: trang nạp song song 8 file woff2
   * tên băm (bản Google tải về) CỘNG bộ self-host của repo — vừa trùng lặp,
   * vừa khiến `nuxt build` ngầm phụ thuộc mạng ra Google Fonts.
   *
   * Nguy hiểm hơn cho parity: hai nguồn cùng tên "Be Vietnam Pro" không bảo đảm
   * cùng phiên bản, mà metric chữ lệch một chút là lệch chiều rộng nút và điểm
   * xuống dòng so với bản PHP.
   *
   * provider 'none' = tự quản. @font-face thật nằm trong app/assets/css/main.css,
   * trỏ vào frontend/public/fonts/ — chính các file woff2 mà site PHP đang phục vụ.
   */
  fonts: {
    families: [{ name: 'Be Vietnam Pro', provider: 'none' }],
  },

  typescript: { strict: true },

  app: {
    pageTransition: { name: 'page', mode: 'out-in' },
    layoutTransition: { name: 'layout', mode: 'out-in' },
    head: {
      htmlAttrs: { lang: 'vi' },
      meta: [{ name: 'theme-color', content: '#0a2251' }],
      link: [
        { rel: 'icon', type: 'image/png', href: '/assets/images/favicon-pts-mobile.png?v=20261008', sizes: '512x512' },
        // Reserve the exact Vietnamese body/heading font metrics before first paint.
        { rel: 'preload', href: '/fonts/be-vietnam-pro/be-vietnam-pro-400-vi.woff2', as: 'font', type: 'font/woff2', crossorigin: 'anonymous' },
        { rel: 'preload', href: '/fonts/be-vietnam-pro/be-vietnam-pro-400-latin.woff2', as: 'font', type: 'font/woff2', crossorigin: 'anonymous' },
        { rel: 'preload', href: '/fonts/be-vietnam-pro/be-vietnam-pro-700-vi.woff2', as: 'font', type: 'font/woff2', crossorigin: 'anonymous' },
        { rel: 'preload', href: '/fonts/be-vietnam-pro/be-vietnam-pro-700-latin.woff2', as: 'font', type: 'font/woff2', crossorigin: 'anonymous' },
      ],
    },
  },

  /*
   * Mọi trang `migration-*` là preview nội bộ, không bao giờ được index - mỗi
   * component vẫn tự set `useSeoMeta({ robots: 'noindex...' })` cho crawler
   * chạy JS, nhưng header tĩnh này còn áp dụng cho crawler không chạy JS/Vue.
   * Liệt kê rõ từng path (không dùng wildcard) để không vô tình khớp một
   * public route tương lai có tiền tố trùng tên.
   */
  routeRules: {
    '/migration-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-about-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-brand-detail-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-cart-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-catalog-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-checkout-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-contact-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-dealer-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-gallery-detail-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-gallery-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-home-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-hub-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-news-detail-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-news-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-order-success': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-policy-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-product-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-project-detail-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-projects-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-search-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-shell-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-site-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-solution-category-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-solution-detail-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-solutions-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    // Phòng thí nghiệm component của CODEX 1. Không bao giờ được index: đây là
    // trang kiểm thử control, không phải nội dung site.
    '/migration-ui-lab-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
    '/migration-video-preview': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
  },

  runtimeConfig: {
    // Override through NUXT_PHP_ORIGIN; never expose this in public runtimeConfig.
    phpOrigin: 'http://127.0.0.1:8080',
    // Comma-separated exact host[:port] entries. Never use a wildcard: this
    // allowlist protects the server-side PHP bridge from SSRF.
    phpOriginAllowlist: '127.0.0.1:8080,127.0.0.1:8088,localhost:8080,localhost:8088',
    previewMediaProxy: false,
    public: {
      // Source of truth: config.php arrayDomainSSL and the production sitemap.
      // The shared SEO helper still validates this exact origin before output.
      canonicalSiteOrigin: 'https://phucthinhsolar.com',
      homePublicRouteEnabled: false,
      homePublicRouteIndexable: false,
      aboutPublicRouteEnabled: false,
      aboutPublicRouteIndexable: false,
      solutionsPublicRouteEnabled: false,
      solutionsPublicRouteIndexable: false,
      solutionDetailPublicRouteEnabled: false,
      solutionDetailPublicRouteIndexable: false,
      catalogPublicRouteEnabled: false,
      catalogPublicRouteIndexable: false,
      catalogCategoryPublicRouteIndexable: false,
      productDetailPublicRouteEnabled: false,
      productDetailPublicRouteIndexable: false,
      brandDetailPublicRouteEnabled: false,
      brandDetailPublicRouteIndexable: false,
      searchPublicRouteEnabled: false,
      searchPublicRouteIndexable: false,
      newsPublicRouteEnabled: false,
      newsPublicRouteIndexable: false,
      newsDetailPublicRouteEnabled: false,
      newsDetailPublicRouteIndexable: false,
      projectsPublicRouteEnabled: false,
      projectsPublicRouteIndexable: false,
      projectDetailPublicRouteEnabled: false,
      projectDetailPublicRouteIndexable: false,
      contactPublicRouteEnabled: false,
      contactPublicRouteIndexable: false,
      dealerPublicRouteEnabled: false,
      dealerPublicRouteIndexable: false,
      galleryPublicRouteEnabled: false,
      galleryPublicRouteIndexable: false,
      galleryDetailPublicRouteEnabled: false,
      galleryDetailPublicRouteIndexable: false,
      videoPublicRouteEnabled: false,
      videoPublicRouteIndexable: false,
      policyDetailPublicRouteEnabled: false,
      policyDetailPublicRouteIndexable: false,
      cartPublicRouteEnabled: false,
      cartPublicRouteIndexable: false,
      checkoutPublicRouteEnabled: false,
      orderSuccessPublicRouteEnabled: false,
      orderSuccessPublicRouteIndexable: false,
      phpPublicOrigin: 'http://127.0.0.1:8080',
    },
  },
})
