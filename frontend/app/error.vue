<script setup lang="ts">
import SiteHeader from '~/components/SiteHeader.vue'
import SiteFooter from '~/components/SiteFooter.vue'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import type { PublicSitePayload } from '#shared/contracts/public-site'

defineProps<{ error: { statusCode?: number; statusMessage?: string } }>()
const fallback: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data } = await useFetch<unknown>('/_migration/site', { default: () => fallback })
const site = computed(() => isPublicSitePayload(data.value) ? data.value : fallback)
const { openConsultation } = useStorefrontModal()
const searchDraft = ref('')
const popularKeywords = ['Đèn đường', 'Blue Carbon', 'LONGi', 'SolaX', 'Pin lưu trữ', 'Bơm nước']

function submitErrorSearch(term?: string) {
  const q = (term ?? searchDraft.value).trim()
  if (term !== undefined) searchDraft.value = term
  return navigateTo({ path: '/tim-kiem/', query: q ? { keyword: q } : {} })
}
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <SiteHeader :site="site" />
    <main class="pts-container py-12 nav:py-16">
      <div class="rounded-[4px] border-l-[4px] border-gold-500 bg-slate-50 p-6 sm:p-10">
        <div class="flex flex-wrap items-center gap-3">
          <span class="rounded-[3px] bg-navy-950 px-2.5 py-1 font-mono text-xs font-extrabold text-gold-400">
            Mã lỗi {{ error.statusCode || 500 }}
          </span>
          <span class="text-xs font-semibold text-slate-500">Điều hướng nhanh Phúc Thịnh Solar</span>
        </div>

        <h1 class="mt-3 mb-3 text-[clamp(30px,5vw,46px)] font-black tracking-tight text-navy-950">
          {{ error.statusCode === 404 ? 'Không tìm thấy trang' : 'Trang chưa thể hiển thị' }}
        </h1>
        <p class="mb-6 max-w-[64ch] text-base leading-relaxed text-slate-600">
          Đường dẫn có thể đã thay đổi hoặc nội dung không còn được công bố. Bạn có thể tìm kiếm trực tiếp thiết bị bên dưới hoặc chọn nhanh các chuyên mục chính:
        </p>

        <form class="mb-6 max-w-2xl" role="search" @submit.prevent="submitErrorSearch()">
          <div class="flex flex-col gap-2.5 sm:flex-row">
            <input
              v-model="searchDraft"
              type="search"
              placeholder="Nhập tên thiết bị, công suất (VD: 500W, 6kW) hoặc thương hiệu…"
              aria-label="Tìm kiếm sản phẩm"
              class="h-12 flex-1 rounded-[4px] border border-slate-300 bg-white px-4 text-sm text-navy-950 placeholder:text-slate-400 focus:border-gold-600 focus:outline-none"
            >
            <button
              type="submit"
              class="inline-flex h-12 shrink-0 cursor-pointer items-center justify-center gap-2 rounded-[4px] bg-gold-500 px-6 text-sm font-extrabold text-navy-950 transition-colors hover:bg-gold-400"
            >
              <UIcon name="i-lucide-search" class="size-4" aria-hidden="true" />
              <span>Tìm sản phẩm</span>
            </button>
          </div>
          <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
            <span class="mr-1 text-xs font-semibold text-slate-500">Tra cứu nhanh:</span>
            <button
              v-for="kw in popularKeywords"
              :key="kw"
              type="button"
              class="cursor-pointer rounded-[3px] border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-navy-800 transition-colors hover:border-gold-500 hover:text-navy-950"
              @click="submitErrorSearch(kw)"
            >
              {{ kw }}
            </button>
          </div>
        </form>

        <div class="flex flex-wrap items-center gap-3">
          <NuxtLink to="/" class="inline-flex min-h-11 items-center justify-center rounded-[4px] bg-navy-900 px-5 py-2.5 text-sm font-bold text-white no-underline transition-colors hover:bg-navy-800">
            Về trang chủ
          </NuxtLink>
          <NuxtLink to="/san-pham/" class="inline-flex min-h-11 items-center justify-center rounded-[4px] border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-navy-800 no-underline transition-colors hover:border-gold-500">
            Xem tất cả sản phẩm
          </NuxtLink>
          <NuxtLink to="/lien-he/" class="inline-flex min-h-11 items-center justify-center rounded-[4px] border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-navy-800 no-underline transition-colors hover:border-gold-500">
            Liên hệ hỗ trợ
          </NuxtLink>
          <button
            type="button"
            class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-1.5 rounded-[4px] bg-gold-500/20 px-4 py-2.5 text-sm font-extrabold text-navy-950 transition-colors hover:bg-gold-500/35"
            @click="openConsultation('Hỗ trợ tìm kiếm sản phẩm / nội dung')"
          >
            <span>Nhận tư vấn kỹ thuật</span>
            <span aria-hidden="true">→</span>
          </button>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-4 border-t border-slate-200/80 pt-6 sm:grid-cols-2 lg:grid-cols-4">
          <NuxtLink to="/san-pham/" class="group block rounded-[4px] bg-navy-950 p-5 text-white no-underline transition-colors hover:bg-navy-900">
            <span class="font-mono text-xs font-extrabold text-gold-400">01</span>
            <strong class="mt-1.5 block text-base font-bold text-white group-hover:text-gold-300">Danh mục thiết bị</strong>
            <p class="m-0 mt-1 text-xs leading-relaxed text-white/75">Đèn năng lượng mặt trời, tấm pin, biến tần, pin lưu trữ BESS và bơm mặt trời.</p>
          </NuxtLink>
          <NuxtLink to="/giai-phap/" class="group block rounded-[4px] bg-navy-950 p-5 text-white no-underline transition-colors hover:bg-navy-900">
            <span class="font-mono text-xs font-extrabold text-gold-400">02</span>
            <strong class="mt-1.5 block text-base font-bold text-white group-hover:text-gold-300">Giải pháp năng lượng</strong>
            <p class="m-0 mt-1 text-xs leading-relaxed text-white/75">Cấu hình điện mặt trời hộ gia đình, nhà xưởng doanh nghiệp và chiếu sáng công trình.</p>
          </NuxtLink>
          <NuxtLink to="/du-an-da-thuc-hien/" class="group block rounded-[4px] bg-navy-950 p-5 text-white no-underline transition-colors hover:bg-navy-900">
            <span class="font-mono text-xs font-extrabold text-gold-400">03</span>
            <strong class="mt-1.5 block text-base font-bold text-white group-hover:text-gold-300">Dự án đã thực hiện</strong>
            <p class="m-0 mt-1 text-xs leading-relaxed text-white/75">Hồ sơ năng lực thi công và các công trình thực tế đã bàn giao trên toàn quốc.</p>
          </NuxtLink>
          <NuxtLink to="/tin-tuc/" class="group block rounded-[4px] bg-navy-950 p-5 text-white no-underline transition-colors hover:bg-navy-900">
            <span class="font-mono text-xs font-extrabold text-gold-400">04</span>
            <strong class="mt-1.5 block text-base font-bold text-white group-hover:text-gold-300">Kiến thức & Chính sách</strong>
            <p class="m-0 mt-1 text-xs leading-relaxed text-white/75">Cẩm nang kỹ thuật điện mặt trời, hướng dẫn lắp đặt và chính sách bảo hành chính hãng.</p>
          </NuxtLink>
        </div>
      </div>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>
