<script setup lang="ts">
import type { PublicDealerPayload } from '#shared/contracts/public-dealer'
import { isPublicDealerPayload } from '#shared/contracts/public-dealer'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import DealerSections from '~/components/dealer/DealerSections.vue'
import DealerOverview from '~/components/dealer/DealerOverview.vue'

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data, error } = await useAsyncData<unknown>('migration-dealer', () => $fetch('/_migration/dealer'))
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const dealer = computed(() => isPublicDealerPayload(data.value) ? data.value : null)

/**
 * Ba mục nhảy neo trên ảnh production (Đăng ký hợp tác / Sản phẩm phân phối /
 * Quyền lợi đại lý). Đây là khung trang cố định, không phải nội dung CMS —
 * DTO không có trường nào cho nó, giống cách navItems dự phòng của SiteHeader
 * đã xử lý khi thiếu dữ liệu điều hướng.
 */
const quickLinks = [
  { label: 'Đăng ký hợp tác', href: '#dang-ky-dai-ly' },
  { label: 'Sản phẩm phân phối', href: '#nang-luc-phan-phoi' },
  { label: 'Quyền lợi đại lý', href: '#ho-tro-dai-ly' },
]
const links = useMigrationLinks()

useSeoMeta({ title: 'Đại lý — internal preview', robots: 'noindex, nofollow, noarchive' })
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <main>
      <div class="mx-auto w-full max-w-[1766px] px-4">
        <SiteBreadcrumb :items="[{ label: 'Đại lý' }]" />
      </div>

      <div v-if="error || !dealer" class="mx-auto min-h-[45vh] w-full max-w-[1766px] px-4 py-12" role="status">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Đại lý — internal preview</h1>
        <p class="text-slate-500">Không thể tải dữ liệu CMS trong môi trường hiện tại. Preview không hiển thị nội dung đại lý giả.</p>
      </div>

      <template v-else>
        <!--
          Hero full-bleed (không giới hạn container, khác mọi section khác của
          trang): ảnh nền + overlay navy-950/48%, tiêu đề trắng. Đo trên ảnh
          production: overlay gần như phẳng (không phải gradient rõ rệt),
          khớp đúng ý định rgba(6,21,55,.48) của CSS gốc.
        -->
        <section v-if="dealer.hero" class="relative min-h-[clamp(280px,32vw,420px)] overflow-hidden">
          <img :src="dealer.hero.image" :alt="dealer.hero.title" class="absolute inset-0 h-full w-full object-cover">
          <div class="absolute inset-0 bg-navy-950/48" aria-hidden="true" />
          <h1 class="relative z-[1] max-w-[55rem] px-4 pb-8 pt-20 text-[clamp(28px,4vw,44px)] font-black leading-[1.15] text-white nav:pt-28">
            {{ dealer.title }}
          </h1>
        </section>
        <h1 v-else class="mx-auto w-full max-w-[1766px] px-4 py-8 text-[clamp(28px,4vw,44px)] font-black tracking-tight">
          {{ dealer.title }}
        </h1>

        <!-- Hàng neo nhảy mục, nền trắng, ngay dưới hero -->
        <nav class="border-b border-gray-200" aria-label="Điều hướng nhanh trong trang">
          <div class="mx-auto flex w-full max-w-[1766px] flex-wrap gap-6 px-4 py-4">
            <a v-for="link in quickLinks" :key="link.href" class="flex items-center gap-2 font-bold text-navy-800 no-underline hover:text-gold-700" :href="links.generic(link.href)">
              {{ link.label }}
            </a>
          </div>
        </nav>

        <div class="mx-auto w-full max-w-[1766px] px-4">
          <div class="grid grid-cols-1 items-start gap-8 py-10 min-[992px]:grid-cols-[minmax(0,1fr)_minmax(360px,520px)] min-[992px]:gap-[clamp(44px,5vw,84px)] min-[992px]:py-12">
            <DealerOverview :dealer="dealer" class="max-[991px]:order-2" />

            <aside id="dang-ky-dai-ly" class="rounded-btn bg-[linear-gradient(145deg,var(--color-navy-950),var(--color-navy-700))] p-6 text-white min-[992px]:sticky min-[992px]:top-6 min-[992px]:p-8">
              <h2 class="mb-4 text-2xl font-black leading-tight">{{ dealer.form.title }}</h2>
              <p class="mb-5 leading-relaxed text-blue-100">{{ dealer.form.note }}</p>
              <DealerLeadForm />
            </aside>
          </div>
        </div>

        <DealerSections :dealer="dealer" />
      </template>
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
