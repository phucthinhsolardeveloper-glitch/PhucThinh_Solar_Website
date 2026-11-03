<script setup lang="ts">
import type { PublicProductExtrasPayload } from '#shared/contracts/public-product-extras'
import type { PublicProductDocument } from '#shared/contracts/public-product-detail'

const props = defineProps<{ extras: PublicProductExtrasPayload | null; documents: PublicProductDocument[] }>()
const links = useMigrationLinks()
const hasMediaColumn = computed(() => Boolean(props.extras?.videoEmbedUrl) || props.documents.length > 0)

function documentHref(document: PublicProductDocument): string {
  if (document.url.startsWith('https://') || !links.active.value) return document.url
  return `/_migration/product-document?path=${encodeURIComponent(document.url)}`
}
</script>

<template>
  <section
    class="grid gap-8 border-t border-gray-200 py-12 tb:py-16 nav:gap-12"
    :class="hasMediaColumn ? 'nav:grid-cols-[1.25fr_0.75fr]' : 'nav:grid-cols-[1fr_auto] nav:items-start'"
    aria-label="Video, tài liệu và bảo hành sản phẩm"
  >
    <div
      v-if="extras && (extras.warranty.summary || extras.warranty.override || extras.warranty.policies.length)"
      id="chinh-sach-bao-hanh"
      class="min-w-0 scroll-mt-24"
      :class="hasMediaColumn ? 'nav:order-2' : ''"
    >
      <h2 class="mb-5 text-[clamp(24px,3vw,34px)] font-black tracking-tight text-navy-900">Chính sách bảo hành</h2>
      <div v-if="extras.warranty.summary" class="mb-5 flex items-center gap-3.5 border-l-4 border-gold-500 bg-slate-50 px-4 py-3.5">
        <span class="grid size-10 shrink-0 place-items-center rounded-[4px] bg-navy-900 text-gold-300" aria-hidden="true">
          <UIcon name="i-lucide-shield-check" class="size-5" />
        </span>
        <div>
          <strong class="block text-lg font-black text-navy-900">Bảo hành {{ extras.warranty.summary }}</strong>
          <span class="mt-0.5 block text-sm text-slate-600">Thời hạn theo thông tin công bố của sản phẩm.</span>
        </div>
      </div>
      <div v-if="extras.warranty.override" class="max-w-[72ch]">
        <h3 class="mb-2 text-base font-extrabold text-navy-900">Chính sách riêng của sản phẩm</h3>
        <p class="mb-0 whitespace-pre-line text-[15.5px] leading-7 text-slate-600">{{ extras.warranty.override }}</p>
      </div>
      <div v-else-if="extras.warranty.policies.length">
        <p class="mb-3 text-slate-500">Áp dụng chính sách bảo hành hiện hành của Phúc Thịnh Solar:</p>
        <div class="grid gap-3 tb:grid-cols-2">
          <NuxtLink
            v-for="policy in extras.warranty.policies"
            :key="policy.href"
            :to="links.generic(policy.href)"
            class="flex min-h-12 items-center justify-between gap-3 border-b border-gray-200 py-3 font-bold text-navy-800 no-underline transition-colors hover:text-gold-700"
          >
            <span>{{ policy.label }}</span><span aria-hidden="true">→</span>
          </NuxtLink>
        </div>
      </div>
    </div>

    <div class="min-w-0 space-y-8" :class="hasMediaColumn ? 'nav:order-1' : ''">
      <div id="video-san-pham" class="min-w-0 scroll-mt-24">
        <template v-if="extras?.videoEmbedUrl">
          <h2 class="mb-5 text-[clamp(24px,3vw,34px)] font-black tracking-tight text-navy-900">Video sản phẩm &amp; lắp đặt</h2>
          <div class="aspect-video overflow-hidden rounded-chip bg-black">
            <iframe
              :src="extras.videoEmbedUrl"
              title="Video thực tế sản phẩm"
              class="h-full w-full border-0"
              loading="lazy"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen
              referrerpolicy="strict-origin-when-cross-origin"
            />
          </div>
        </template>
        <div v-else class="flex items-center gap-3 rounded-[4px] bg-slate-50 px-4 py-3 text-slate-600" aria-label="Video đang được cập nhật">
          <span class="inline-grid size-9 shrink-0 place-items-center rounded-full bg-white text-xs text-gold-700 shadow-xs" aria-hidden="true">▶</span>
          <div>
            <strong class="block text-sm font-bold text-navy-900">Video sản phẩm &amp; lắp đặt</strong>
            <p class="m-0 text-xs text-slate-500">Video đang được cập nhật</p>
          </div>
        </div>
      </div>

      <div v-if="documents.length" id="tai-lieu-san-pham" class="min-w-0 scroll-mt-24">
        <h2 class="mb-4 text-[clamp(22px,2.5vw,28px)] font-black tracking-tight text-navy-900">Tài liệu sản phẩm</h2>
        <div class="grid gap-2">
          <a
            v-for="document in documents"
            :key="`${document.type}:${document.url}`"
            :href="documentHref(document)"
            target="_blank"
            rel="noopener noreferrer"
            class="flex min-h-12 items-center justify-between gap-3 border-b border-gray-200 py-3 text-sm font-bold text-navy-800 no-underline hover:text-gold-700"
          >
            <span class="min-w-0 truncate">{{ document.title }}</span>
            <span class="shrink-0 text-xs font-semibold text-slate-500" aria-hidden="true">Tải xuống ↗</span>
          </a>
        </div>
      </div>
    </div>
  </section>
</template>
