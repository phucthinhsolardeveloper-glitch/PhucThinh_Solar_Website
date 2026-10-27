<script setup lang="ts">
import type { PublicSolutionsPayload } from '../../shared/contracts/public-solutions'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import SolutionsIntro from '~/components/solutions/SolutionsIntro.vue'
import SolutionCards from '~/components/solutions/SolutionCards.vue'
import SolutionsEquipment from '~/components/solutions/SolutionsEquipment.vue'
import SolutionsCta from '~/components/solutions/SolutionsCta.vue'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'

useSeoMeta({ title: 'Giải pháp — internal preview', robots: 'noindex, nofollow, noarchive' })

/**
 * PHÁT HIỆN: trang này trước đó KHÔNG gọi SiteHeader/SiteFooter/SiteBreadcrumb
 * — chỉ render một <main> trần. Mọi trang khác đã chuyển (danh mục, PDP, tin
 * tức, dự án, đại lý, liên hệ) đều bọc bằng shell dùng chung. Ảnh production
 * (/giai-phap/) có đủ header và breadcrumb "Trang chủ > Giải pháp" — thêm lại
 * cho nhất quán, không phải một lựa chọn thiết kế cố ý.
 */
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)

const { data, error } = await useAsyncData<PublicSolutionsPayload>('migration-solutions', () => $fetch('/_migration/solutions'))
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <main class="mx-auto w-full max-w-[1766px] px-4 py-8 nav:py-12">
      <SiteBreadcrumb :items="[{ label: 'Giải pháp' }]" />

      <div v-if="error || !data" class="min-h-[45vh] py-12">
        <h1 class="mb-4 text-[clamp(28px,4vw,42px)] font-black tracking-tight">Giải pháp — internal preview</h1>
        <p class="max-w-[62ch] leading-relaxed text-slate-500">Không thể tải dữ liệu PHP DTO trong môi trường hiện tại. Preview không hiển thị sản phẩm hoặc nội dung giả.</p>
      </div>

      <template v-else>
        <SolutionsIntro v-if="data.intro" :intro="data.intro" />
        <h1 v-else class="min-h-[45vh] py-12 text-[clamp(28px,4vw,42px)] font-black tracking-tight">{{ data.title }}</h1>

        <SolutionCards :cards="data.cards" :choices-label="data.labels.choices" :empty-label="data.labels.empty" :consult-label="data.labels.consult" />

        <section v-if="data.editorial.content.html" class="pb-[54px]" aria-label="Nội dung tư vấn giải pháp">
          <div
            class="grid gap-8"
            :class="data.editorial.toc.rendered ? 'nav:grid-cols-[240px_minmax(0,1fr)]' : ''"
          >
            <nav
              v-if="data.editorial.toc.rendered"
              class="h-fit rounded-btn border border-gray-200 p-4 nav:sticky nav:top-4"
              aria-label="Mục lục nội dung giải pháp"
            >
              <strong class="mb-3 block text-sm uppercase tracking-wide text-navy-800">Trong bài này</strong>
              <ul class="m-0 list-none space-y-1 p-0">
                <li v-for="item in data.editorial.toc.items" :key="item.id" :class="item.level === 3 ? 'pl-3' : ''">
                  <a :href="'#' + item.id" class="block rounded-chip px-2 py-2 text-sm leading-snug text-slate-600 no-underline hover:bg-slate-50 hover:text-navy-800">
                    {{ item.label }}
                  </a>
                </li>
              </ul>
            </nav>
            <EditorialRichText :content="data.editorial.content" />
          </div>
        </section>

        <SolutionsEquipment :equipment="data.equipment" :title="data.labels.equipment" :all-products-url="data.allProductsUrl" :all-products-label="data.labels.allProducts" />
        <SolutionsCta v-if="data.cta" :cta="data.cta" />
      </template>
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
