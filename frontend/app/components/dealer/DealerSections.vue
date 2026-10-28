<script setup lang="ts">
import type { PublicDealerPayload } from '#shared/contracts/public-dealer'

defineProps<{ dealer: PublicDealerPayload }>()
const links = useMigrationLinks()

// Dùng lại thumbnail PHP có sẵn cho ảnh CMS lớn; không nâng giới hạn 8 MiB của media proxy.
function cardImage(src: string): string {
  return /^\/upload\/(?:news|photo)\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(src)
    ? '/thumbs/480x300x1' + src
    : src
}

</script>

<template>
  <!--
    Dải hỗ trợ đại lý: nền navy đặc, số lượng thẻ phụ thuộc CMS (ảnh production
    có 4 thẻ một hàng ở 1920px). CSS preview cũ cố định 3 cột — với 4 phần tử
    sẽ đẩy thẻ thứ 4 xuống dòng riêng, lệch khỏi ảnh. Dùng 4 cột ở desktop.
  -->
  <section v-if="dealer.support.title || dealer.support.items.length" id="ho-tro-dai-ly" class="bg-navy-950 py-12 text-white nav:py-16">
    <div class="mx-auto w-[calc(100%-clamp(40px,5vw,96px))] max-w-[1560px]">
      <h2 v-if="dealer.support.title" class="mb-3 max-w-[32ch] text-[clamp(24px,2.6vw,34px)] font-black leading-tight">
        {{ dealer.support.title || dealer.labels.support }}
      </h2>
      <p v-if="dealer.support.description" class="mb-8 max-w-[62ch] leading-relaxed text-blue-100">
        {{ dealer.support.description }}
      </p>

      <div class="grid grid-cols-1 gap-5 ph:grid-cols-2 nav:grid-cols-4">
        <article v-for="item in dealer.support.items" :key="item.title" class="rounded-btn border border-white/10 bg-white/5 p-5">
          <img v-if="item.image" :src="cardImage(item.image)" :alt="item.title" class="mb-4 h-11 w-11 rounded-chip object-cover" loading="lazy">
          <h3 class="mb-2 font-bold text-white">{{ item.title }}</h3>
          <p class="m-0 text-sm leading-relaxed text-blue-100">{{ item.description }}</p>
        </article>
      </div>
    </div>
  </section>

  <section v-if="dealer.testimonials.length" class="py-12 nav:py-16">
    <div class="mx-auto w-[calc(100%-clamp(40px,5vw,96px))] max-w-[1560px]">
      <h2 class="mb-8 text-[clamp(24px,2.6vw,34px)] font-black tracking-tight text-navy-800">{{ dealer.labels.testimonials }}</h2>
      <div class="grid grid-cols-1 gap-8 nav:grid-cols-3">
        <article v-for="item in dealer.testimonials" :key="item.title" class="min-w-0">
          <p class="mb-4 leading-relaxed text-slate-600">“{{ item.quote || item.description }}”</p>
          <div class="flex items-center gap-3">
            <!-- Render the CMS portrait when it exists; the DTO allows an empty image. -->
            <img v-if="item.image" :src="cardImage(item.image)" :alt="item.title" class="h-12 w-12 flex-none rounded-full object-cover" loading="lazy">
            <div>
              <strong class="block text-navy-800">{{ item.title }}</strong>
              <small v-if="item.description" class="block text-sm text-slate-500">{{ item.description }}</small>
            </div>
          </div>
        </article>
      </div>
    </div>
  </section>

  <!--
    Dải thống kê: gradient navy nhẹ (không phải navy đặc), nhãn
    "TỰ HÀO PHÚC THỊNH SOLAR" chính là dealer.labels.stats — không phải chữ
    rời, CSS preview cũ bỏ sót nhãn này.
  -->
  <section v-if="dealer.stats.length" class="bg-[linear-gradient(120deg,var(--color-navy-950),var(--color-navy-800))] py-12 text-center text-white nav:py-16" :aria-label="dealer.labels.stats">
    <div class="mx-auto w-[calc(100%-clamp(40px,5vw,96px))] max-w-[1560px]">
      <p class="mb-8 text-xs font-bold uppercase tracking-[.08em] text-blue-200">{{ dealer.labels.stats }}</p>
      <div class="grid grid-cols-1 gap-8 ph:grid-cols-3">
        <div v-for="stat in dealer.stats" :key="stat.label">
          <strong class="block text-4xl font-black">{{ stat.value }}+</strong>
          <span class="mt-1 block text-sm text-blue-200">{{ stat.label }}</span>
        </div>
      </div>
    </div>
  </section>
</template>
