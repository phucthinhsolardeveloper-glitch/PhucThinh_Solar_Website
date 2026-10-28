<script setup lang="ts">
import type { PublicProductExtrasPayload } from '#shared/contracts/public-product-extras'

defineProps<{ extras: PublicProductExtrasPayload }>()
const links = useMigrationLinks()
</script>

<template>
  <section
    v-if="extras.videoEmbedUrl || extras.warranty.summary || extras.warranty.override || extras.warranty.policies.length"
    class="border-t border-gray-200 py-10"
    aria-label="Video và bảo hành sản phẩm"
  >
    <div v-if="extras.videoEmbedUrl" id="video-san-pham" class="scroll-mt-24">
      <h2 class="mb-5 text-[clamp(24px,3vw,34px)] font-black tracking-tight">Video sản phẩm &amp; lắp đặt</h2>
      <div class="aspect-video overflow-hidden rounded-btn bg-black">
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
    </div>

    <div
      v-if="extras.warranty.summary || extras.warranty.override || extras.warranty.policies.length"
      id="chinh-sach-bao-hanh"
      class="scroll-mt-24"
      :class="extras.videoEmbedUrl ? 'mt-12 border-t border-gray-200 pt-10' : ''"
    >
      <h2 class="mb-5 text-[clamp(24px,3vw,34px)] font-black tracking-tight">Chính sách bảo hành</h2>
      <div v-if="extras.warranty.summary" class="mb-5 rounded-btn bg-slate-50 p-5">
        <strong class="block text-lg text-navy-800">Bảo hành {{ extras.warranty.summary }}</strong>
        <span class="mt-1 block text-sm text-slate-500">Thời hạn theo thông tin công bố của sản phẩm.</span>
      </div>
      <div v-if="extras.warranty.override" class="rounded-btn border border-gray-200 p-5">
        <h3 class="mb-2 text-lg font-black">Chính sách riêng của sản phẩm</h3>
        <p class="mb-0 whitespace-pre-line leading-7 text-slate-600">{{ extras.warranty.override }}</p>
      </div>
      <div v-else-if="extras.warranty.policies.length">
        <p class="mb-3 text-slate-500">Áp dụng chính sách bảo hành hiện hành của Phúc Thịnh Solar:</p>
        <div class="grid gap-3 tb:grid-cols-2">
          <NuxtLink
            v-for="policy in extras.warranty.policies"
            :key="policy.href"
            :to="links.generic(policy.href)"
            class="flex min-h-12 items-center justify-between rounded-chip border border-gray-200 px-4 py-3 font-bold text-navy-800 no-underline transition hover:border-gold-500 hover:bg-slate-50"
          >
            <span>{{ policy.label }}</span><span aria-hidden="true">→</span>
          </NuxtLink>
        </div>
      </div>
    </div>
  </section>
</template>
