<script setup lang="ts">
/**
 * Phòng thí nghiệm component — CODEX 1.
 *
 * KHÔNG phải một trang của site. Mục đích duy nhất: chứng minh nền tảng
 * Tailwind v4 + Nuxt UI v4 chạy thật trong SSR, theme thương hiệu áp đúng, và
 * các overlay (modal/drawer/dropdown) mở được bằng cả chuột lẫn bàn phím —
 * trước khi CODEX 2/3 dựa vào nền này để chuyển 16 route thật.
 *
 * Trang này noindex qua routeRules trong nuxt.config.ts.
 */

const modalOpen = ref(false)
const drawerOpen = ref(false)
const textValue = ref('')

const selectItems = [
  'Tấm pin năng lượng mặt trời',
  'Biến tần Inverter',
  'Đèn năng lượng mặt trời',
  'Bơm nước năng lượng mặt trời',
]
const selectValue = ref(selectItems[0])

const menuItems = [
  [
    { label: 'Tất cả sản phẩm', to: '/migration-catalog-preview' },
    { label: 'Dự án đã thực hiện', to: '/migration-projects-preview' },
  ],
  [{ label: 'Liên hệ tư vấn', to: '/migration-contact-preview' }],
]

const tabItems = [
  { label: 'Thông số kỹ thuật', slot: 'specs' as const },
  { label: 'Chính sách mua hàng', slot: 'policy' as const },
  { label: 'Bảo hành', slot: 'warranty' as const },
]

useHead({
  title: 'UI lab — nền tảng Tailwind + Nuxt UI',
  meta: [{ name: 'robots', content: 'noindex, nofollow, noarchive' }],
})
</script>

<template>
  <main class="mx-auto max-w-5xl px-4 py-12 font-sans">
    <header class="mb-10">
      <p class="text-sm font-bold uppercase tracking-wider text-gold-700">
        CODEX 1 · nền tảng
      </p>
      <h1 class="mt-2 text-3xl font-black tracking-tight text-navy-800">
        Phòng thí nghiệm component
      </h1>
      <p class="mt-3 max-w-2xl text-navy-900/70">
        Trang kiểm thử nội bộ, không index. Dùng để xác nhận theme thương hiệu
        và hành vi bàn phím của các control Nuxt UI trước khi chuyển các route
        thật sang Tailwind.
      </p>
    </header>

    <!-- Bảng màu: đối chiếu trực quan với token đã duyệt. Nếu một ô ở đây lệch
         so với site PHP thì lỗi nằm ở @theme trong main.css, không phải ở trang. -->
    <section class="mb-12">
      <h2 class="mb-4 text-xl font-extrabold text-navy-800">Thang màu thương hiệu</h2>
      <div class="grid grid-cols-2 gap-6 sm:grid-cols-2">
        <div>
          <p class="mb-2 text-sm font-bold text-navy-900/70">Navy — bề mặt thương hiệu</p>
          <div class="flex overflow-hidden rounded-[3px]">
            <div class="h-12 flex-1 bg-navy-50" />
            <div class="h-12 flex-1 bg-navy-100" />
            <div class="h-12 flex-1 bg-navy-300" />
            <div class="h-12 flex-1 bg-navy-500" />
            <div class="h-12 flex-1 bg-navy-700" />
            <div class="h-12 flex-1 bg-navy-800" />
            <div class="h-12 flex-1 bg-navy-950" />
          </div>
        </div>
        <div>
          <p class="mb-2 text-sm font-bold text-navy-900/70">Gold — hành động chính</p>
          <div class="flex overflow-hidden rounded-[3px]">
            <div class="h-12 flex-1 bg-gold-50" />
            <div class="h-12 flex-1 bg-gold-200" />
            <div class="h-12 flex-1 bg-gold-300" />
            <div class="h-12 flex-1 bg-gold-400" />
            <div class="h-12 flex-1 bg-gold-500" />
            <div class="h-12 flex-1 bg-gold-700" />
            <div class="h-12 flex-1 bg-gold-900" />
          </div>
        </div>
      </div>
    </section>

    <section class="mb-12">
      <h2 class="mb-4 text-xl font-extrabold text-navy-800">Nút</h2>
      <div class="flex flex-wrap items-center gap-3">
        <UButton label="Mua ngay" />
        <UButton label="Thêm vào giỏ" color="secondary" />
        <UButton label="Liên hệ báo giá" variant="outline" />
        <UButton label="Xem thêm" variant="ghost" />
        <UButton label="Đang xử lý" loading />
        <UButton label="Không khả dụng" disabled />
        <UBadge label="Giảm 18%" />
      </div>
      <p class="mt-3 text-sm text-navy-900/60">
        Nút mặc định là gold vì hệ thiết kế giao vai trò hành động chính cho
        gold; navy là màu phụ cho hành động thứ cấp.
      </p>
    </section>

    <section class="mb-12">
      <h2 class="mb-4 text-xl font-extrabold text-navy-800">Nhập liệu</h2>
      <div class="grid gap-4 sm:grid-cols-2">
        <UInput v-model="textValue" placeholder="Số điện thoại nhận tư vấn" />
        <USelectMenu v-model="selectValue" :items="selectItems" />
      </div>
    </section>

    <section class="mb-12">
      <h2 class="mb-4 text-xl font-extrabold text-navy-800">
        Overlay — kiểm tra bằng bàn phím
      </h2>
      <p class="mb-4 text-sm text-navy-900/60">
        Tab tới từng nút rồi Enter để mở; Escape phải đóng được và trả focus về
        đúng nút vừa bấm. Đây là hành vi mà menu mobile của site thật cần.
      </p>
      <div class="flex flex-wrap gap-3">
        <UDropdownMenu :items="menuItems">
          <UButton label="Danh mục" variant="outline" />
        </UDropdownMenu>

        <UButton label="Mở hộp thoại" variant="outline" @click="modalOpen = true" />
        <UButton label="Mở menu mobile" variant="outline" @click="drawerOpen = true" />
      </div>

      <UModal v-model:open="modalOpen" title="Yêu cầu báo giá">
        <template #body>
          <p class="text-navy-900/80">
            Biểu mẫu thật vẫn do PHP xử lý (CSRF, session). Hộp thoại này chỉ
            kiểm tra overlay và bẫy focus.
          </p>
        </template>
      </UModal>

      <UDrawer v-model:open="drawerOpen" title="Danh mục sản phẩm">
        <template #body>
          <ul class="space-y-2 text-navy-800">
            <li v-for="item in selectItems" :key="item">{{ item }}</li>
          </ul>
        </template>
      </UDrawer>
    </section>

    <section class="mb-12">
      <h2 class="mb-4 text-xl font-extrabold text-navy-800">Tab</h2>
      <UTabs :items="tabItems">
        <template #specs>
          <p class="py-4 text-navy-900/80">Công suất, nguồn sáng, chống nước…</p>
        </template>
        <template #policy>
          <p class="py-4 text-navy-900/80">Nội dung chính sách lấy từ CMS.</p>
        </template>
        <template #warranty>
          <p class="py-4 text-navy-900/80">Thời hạn bảo hành theo từng dòng sản phẩm.</p>
        </template>
      </UTabs>
    </section>
  </main>
</template>
