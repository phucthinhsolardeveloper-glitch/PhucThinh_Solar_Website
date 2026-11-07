// Migration only. No production route is switched to Nuxt until parity gates pass.
export default defineNuxtConfig({
  ssr: true,
  devtools: { enabled: false },

  // CODEX 1 — nền tảng Tailwind v4 + Nuxt UI v4.
  // Nuxt UI v4 đã mang sẵn tích hợp Tailwind, nên KHÔNG thêm @nuxtjs/tailwindcss
  // (module v6 dành cho Tailwind v3) và cũng không tự khai plugin
  // @tailwindcss/vite — làm vậy sẽ chạy hai pipeline CSS cạnh tranh nhau.
  modules: ['@nuxt/ui'],
  icon: {
    // Runtime-selected icons from TS helpers must be available in the deployed
    // client bundle without depending on the icon API route.
    clientBundle: {
      scan: { globInclude: ['**/*.{vue,ts,jsx,tsx,md,mdc,mdx,yml,yaml}'] },
    },
  },

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
   */
  css: ['~/assets/css/main.css', '~/assets/css/tokens.css', '~/assets/css/experience-design-system.css'],

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

  // No viewTransition: the API freezes painting from click until the next page has
  // its data (measured 366-1405 ms on a 4x-throttled CPU), which reads as a hang then a flash.
  // Links fetch their page chunk on hover/focus instead of every visible link on load.
  experimental: {
    defaults: { nuxtLink: { prefetchOn: { visibility: false, interaction: true } } },
  },
  app: {
    // No page transition: Suspense keeps the old page until the new one is ready, then swaps in one
    // frame. A fade here exposes the white background between pages, which reads as a flash.
    head: {
      htmlAttrs: { lang: 'vi' },
      // Without JavaScript nothing can unfold, so nothing folds.
      noscript: [{ innerHTML: '<style>[data-fold]{max-height:none!important}</style>' }],
      meta: [{ name: 'theme-color', content: '#0a2251' }],
      link: [
        { key: 'favicon', rel: 'icon', type: 'image/png', href: '/assets/images/favicon-pts-mobile.png?v=20261008', sizes: '512x512' },
        // Reserve the exact Vietnamese body/heading font metrics before first paint.
        { rel: 'preload', href: '/fonts/be-vietnam-pro/be-vietnam-pro-400-vi.woff2', as: 'font', type: 'font/woff2', crossorigin: 'anonymous' },
        { rel: 'preload', href: '/fonts/be-vietnam-pro/be-vietnam-pro-400-latin.woff2', as: 'font', type: 'font/woff2', crossorigin: 'anonymous' },
        { rel: 'preload', href: '/fonts/be-vietnam-pro/be-vietnam-pro-700-vi.woff2', as: 'font', type: 'font/woff2', crossorigin: 'anonymous' },
        { rel: 'preload', href: '/fonts/be-vietnam-pro/be-vietnam-pro-700-latin.woff2', as: 'font', type: 'font/woff2', crossorigin: 'anonymous' },
        // Headings render at 800+; without it the largest text swaps late and shifts the page.
        { rel: 'preload', href: '/fonts/be-vietnam-pro/be-vietnam-pro-800-vi.woff2', as: 'font', type: 'font/woff2', crossorigin: 'anonymous' },
        { rel: 'preload', href: '/fonts/be-vietnam-pro/be-vietnam-pro-800-latin.woff2', as: 'font', type: 'font/woff2', crossorigin: 'anonymous' },
      ],
    },
  },

  // Pre-compress static JS/CSS/JSON so every page does not ship them raw.
  nitro: { compressPublicAssets: { gzip: true, brotli: true } },

  routeRules: {
    // Short shared cache for public read-only API DTOs (no cookies forwarded, bounded cache keys).
    // Session/token routes (cart, checkout, lead-form, order-thanks) and free-text queries
    // (search, catalog, news, route resolve) are deliberately excluded.
    ...Object.fromEntries([
      'site', 'home', 'about', 'dealer', 'contact', 'solutions', 'projects', 'gallery', 'video',
      'policy-detail', 'product-detail', 'solution-detail', 'project-detail', 'gallery-detail',
    ].map((name) => [`/_api/${name}`, { cache: { maxAge: 60, swr: true } }])),
    // Phòng thí nghiệm component của CODEX 1. Không bao giờ được index: đây là
    // trang kiểm thử control, không phải nội dung site.
    '/experience-design-system': {
      headers: { 'X-Robots-Tag': 'noindex, nofollow, noarchive' },
    },
  },

  runtimeConfig: {
    // Laravel API origin, override through NUXT_API_ORIGIN; never expose this in public runtimeConfig.
    apiOrigin: 'http://127.0.0.1:8090',
    // Comma-separated exact host[:port] entries. Never use a wildcard: this
    // allowlist protects the server-side API bridge from SSRF.
    apiOriginAllowlist: '127.0.0.1:8090,localhost:8090,pts-laravel13-local:8000',
    public: {
      // Source of truth: config.php arrayDomainSSL and the production sitemap.
      // The shared SEO helper still validates this exact origin before output.
      canonicalSiteOrigin: 'https://phucthinhsolar.com',
      homePublicRouteIndexable: true,
      aboutPublicRouteIndexable: true,
      solutionsPublicRouteIndexable: true,
      solutionDetailPublicRouteIndexable: true,
      catalogPublicRouteIndexable: true,
      catalogCategoryPublicRouteIndexable: true,
      productDetailPublicRouteIndexable: true,
      brandDetailPublicRouteIndexable: true,
      searchPublicRouteIndexable: false,
      newsPublicRouteIndexable: true,
      newsDetailPublicRouteIndexable: true,
      projectsPublicRouteIndexable: true,
      projectDetailPublicRouteIndexable: true,
      contactPublicRouteIndexable: true,
      dealerPublicRouteIndexable: true,
      galleryPublicRouteIndexable: true,
      galleryDetailPublicRouteIndexable: true,
      videoPublicRouteIndexable: true,
      policyDetailPublicRouteIndexable: true,
      cartPublicRouteIndexable: false,
      orderSuccessPublicRouteIndexable: false,
    },
  },
})
