<script setup lang="ts">
import type { PublicProductExtrasPayload } from '#shared/contracts/public-product-extras'

defineProps<{ extras: PublicProductExtrasPayload | null }>()
const { openConsultation } = useStorefrontModal()
</script>

<template>
  <!-- Sits right under the description: a shopper who has just read about the product finds its video before the specs. -->
  <section id="video-san-pham" class="scroll-mt-24 pb-8 pt-4 tb:-mt-8 tb:pb-10 tb:pt-0" aria-label="Video sản phẩm và lắp đặt">
    <template v-if="extras?.videoEmbedUrl">
      <h2 class="mb-4 text-[clamp(22px,2.6vw,30px)] font-black tracking-tight text-navy-900">Video sản phẩm &amp; lắp đặt</h2>
      <div class="aspect-video max-w-[960px] overflow-hidden rounded-chip bg-black">
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
    <div v-else class="pts-note flex flex-wrap items-center justify-between gap-3 px-4 py-3.5 text-slate-600" aria-label="Video đang được cập nhật">
      <div class="flex min-w-0 items-center gap-3">
        <span class="inline-grid size-10 shrink-0 place-items-center rounded-full bg-slate-50 text-sm text-gold-700" aria-hidden="true">▶</span>
        <div class="min-w-0">
          <strong class="block text-[15px] font-bold text-navy-900">Video sản phẩm &amp; lắp đặt</strong>
          <p class="m-0 text-[13px] leading-snug text-slate-500">Video đang được cập nhật · Hỗ trợ gửi video thực tế và sơ đồ đấu nối qua Zalo</p>
        </div>
      </div>
      <button
        type="button"
        class="product-support-link pts-gold-fill inline-flex min-h-10 shrink-0 cursor-pointer items-center gap-1.5 rounded-chip px-4 py-2 text-[13px] font-bold max-tb:w-full max-tb:justify-center"
        @click="openConsultation('Yêu cầu video thực tế & tài liệu kỹ thuật sản phẩm')"
      >
        <span>Nhận tài liệu &amp; video</span>
        <span aria-hidden="true">→</span>
      </button>
    </div>
  </section>
</template>
