// Migration only. No production route is switched to Nuxt until parity gates pass.
export default defineNuxtConfig({
  ssr: true,
  devtools: { enabled: false },

  // CODEX 1 — nền tảng Tailwind v4 + Nuxt UI v4.
  // Nuxt UI v4 đã mang sẵn tích hợp Tailwind, nên KHÔNG thêm @nuxtjs/tailwindcss
  // (module v6 dành cho Tailwind v3) và cũng không tự khai plugin
  // @tailwindcss/vite — làm vậy sẽ chạy hai pipeline CSS cạnh tranh nhau.
  modules: ['@nuxt/ui'],

  /*
   * Thứ tự nạp là có chủ ý: main.css TRƯỚC tokens.css.
   *
   * Preflight của Tailwind reset margin/border/font ở thẻ gốc. 16 route preview
   * hiện có đang dựa vào shell.css + *-preview.css, nên nếu nạp Tailwind SAU,
   * reset sẽ kéo nền dưới chân chúng và làm hỏng các route đang chạy — trong
   * khi yêu cầu của bước này là các preview cũ vẫn phải load được.
   *
   * Nạp trước thì CSS preview cũ vẫn thắng ở chỗ trùng, còn utility Tailwind và
   * component Nuxt UI vẫn hoạt động vì chúng dùng layer/class riêng.
   * Trạng thái bắc cầu; CODEX 2-4 gỡ dần *-preview.css khi chuyển xong từng trang.
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
    // Phòng thí nghiệm component của CODEX 1. Không bao giờ được index: đây là
    // trang kiểm thử control, không phải nội dung site.
    '/migration-ui-lab-preview': {
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
      aboutPublicRouteEnabled: false,
      aboutPublicRouteIndexable: false,
      solutionsPublicRouteEnabled: false,
      solutionsPublicRouteIndexable: false,
      catalogPublicRouteEnabled: false,
      catalogPublicRouteIndexable: false,
      productDetailPublicRouteEnabled: false,
      productDetailPublicRouteIndexable: false,
      brandDetailPublicRouteEnabled: false,
      brandDetailPublicRouteIndexable: false,
      searchPublicRouteEnabled: false,
      searchPublicRouteIndexable: false,
      cartPublicRouteEnabled: false,
      cartPublicRouteIndexable: false,
      orderSuccessPublicRouteEnabled: false,
      orderSuccessPublicRouteIndexable: false,
      phpPublicOrigin: 'http://127.0.0.1:8080',
    },
  },
})
