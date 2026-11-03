<script setup lang="ts">
import type { PublicDealerPayload } from '#shared/contracts/public-dealer'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'

defineProps<{ dealer: PublicDealerPayload }>()
const links = useMigrationLinks()
const oversizedSupportImages: Record<string, string> = {
  '/upload/news/svgviewer-png-output-3-5470.png': '/assets/images/site-visuals/media-fallbacks/dealer-support-media.webp',
  '/upload/news/svgviewer-png-output-2-4536.png': '/assets/images/site-visuals/media-fallbacks/dealer-support-training.webp',
  '/upload/news/svgviewer-png-output-1-2932.png': '/assets/images/site-visuals/media-fallbacks/dealer-support-technical.webp',
}

// Dùng thumbnail cho ảnh CMS lớn; ba PNG vượt giới hạn proxy có WebP derivative đã kiểm tra.
function cardImage(src: string): string {
  if (oversizedSupportImages[src]) return oversizedSupportImages[src]
  return /^\/upload\/(?:news|photo)\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(src)
    ? '/thumbs/480x300x1' + src
    : src
}

function cleanQuote(raw: string): string {
  return raw.trim().replace(/^[“"']+|[”"']+$/g, '').trim()
}
</script>

<template>
  <!--
    Dải hỗ trợ đại lý: nền navy đặc, số lượng thẻ phụ thuộc CMS (ảnh production
    có 4 thẻ một hàng ở 1920px). CSS preview cũ cố định 3 cột — với 4 phần tử
    sẽ đẩy thẻ thứ 4 xuống dòng riêng, lệch khỏi ảnh. Dùng 4 cột ở desktop.
  -->
  <section v-if="dealer.support.title || dealer.support.descriptionContent.html || dealer.support.description || dealer.support.items.length" id="ho-tro-dai-ly" class="bg-navy-950 py-12 text-white nav:py-16">
    <div class="mx-auto w-[calc(100%-clamp(40px,5vw,96px))] max-w-[1560px]">
      <div class="grid gap-0 min-[992px]:grid-cols-[minmax(300px,.8fr)_minmax(420px,1.2fr)] min-[992px]:gap-[clamp(44px,6vw,96px)]">
        <h2 v-if="dealer.support.title" class="mb-3 max-w-[32ch] text-[clamp(24px,2.6vw,34px)] font-black leading-tight min-[992px]:mb-0 min-[992px]:max-w-[600px] min-[992px]:text-[clamp(30px,2.55vw,42px)] min-[992px]:leading-[1.16]">
          {{ dealer.support.title || dealer.labels.support }}
        </h2>
        <EditorialRichText v-if="dealer.support.descriptionContent.html" :content="dealer.support.descriptionContent" class="cms-content-on-dark mb-8 max-w-[62ch] min-[992px]:mb-0 min-[992px]:mt-0.5 min-[992px]:max-w-[760px] min-[992px]:text-[17px] min-[992px]:leading-[1.78]" />
        <p v-else-if="dealer.support.description" class="mb-8 max-w-[62ch] leading-relaxed text-blue-100 min-[992px]:mb-0 min-[992px]:mt-0.5 min-[992px]:max-w-[760px] min-[992px]:text-[17px] min-[992px]:leading-[1.78]">
          {{ dealer.support.description }}
        </p>
      </div>

      <div class="mt-0 grid grid-cols-1 gap-5 ph:grid-cols-2 nav:grid-cols-4 min-[992px]:mt-[38px]">
        <article v-for="(item, index) in dealer.support.items" :key="item.title" class="relative rounded-btn border border-white/10 border-t-[3px] border-t-gold-500 bg-white/[.06] p-6 transition-colors hover:bg-white/[.09]">
          <div class="mb-4 flex items-center justify-between gap-3">
            <span v-if="item.image" class="flex h-12 w-12 items-center justify-center rounded-chip bg-gold-300 p-2">
              <img :src="cardImage(item.image)" :alt="item.title" class="h-full w-full object-contain" loading="lazy">
            </span>
          </div>
          <h3 class="mb-2.5 text-lg font-extrabold text-white">{{ item.title }}</h3>
          <EditorialRichText v-if="item.descriptionContent.html" :content="item.descriptionContent" class="cms-content-on-dark text-sm leading-relaxed" />
          <p v-else class="m-0 text-sm leading-relaxed text-blue-100">{{ item.description }}</p>
        </article>
      </div>
    </div>
  </section>

  <section v-if="dealer.testimonials.length" class="py-12 nav:py-16">
    <div class="mx-auto w-[calc(100%-clamp(40px,5vw,96px))] max-w-[1560px]">
      <h2 id="dealer-testimonials-heading" class="mb-8 text-[clamp(30px,2.8vw,44px)] font-black leading-[1.2] tracking-tight text-navy-800">{{ dealer.labels.testimonials }}</h2>
      <div
        class="-mr-5 flex snap-x snap-mandatory gap-4 overflow-x-auto overscroll-x-contain pb-3 pr-5 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold-500 min-[768px]:mr-0 min-[768px]:grid min-[768px]:grid-cols-2 min-[768px]:gap-8 min-[768px]:overflow-visible min-[768px]:snap-none min-[768px]:pb-0 min-[768px]:pr-0 min-[992px]:grid-cols-3"
        tabindex="0"
        :aria-label="dealer.labels.testimonials"
      >
        <article v-for="item in dealer.testimonials" :key="item.title" class="relative flex w-[min(86vw,340px)] flex-none snap-start flex-col justify-between gap-5 rounded-[3px] border-t-[3px] border-gold-500 bg-slate-50 p-[28px_26px] min-[768px]:w-auto">
          <div class="relative">
            <span class="select-none font-serif text-5xl font-black leading-none text-gold-500" aria-hidden="true">“</span>
            <p class="mb-4 mt-2 text-[15.5px] leading-[1.72] text-slate-700">{{ cleanQuote(item.quote || item.description) }}</p>
          </div>
          <div class="flex items-center gap-3.5 border-t border-slate-200/80 pt-4">
            <!-- Render the CMS portrait when it exists; the DTO allows an empty image. -->
            <img v-if="item.image" :src="cardImage(item.image)" :alt="item.title" class="h-12 w-12 flex-none rounded-full object-cover shadow-xs" loading="lazy">
            <div v-else class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-gold-50 font-bold text-gold-700 shadow-xs">
              {{ item.title.slice(0, 1) }}
            </div>
            <div class="min-w-0">
              <strong class="block font-extrabold text-navy-950">{{ item.title }}</strong>
              <small v-if="item.description" class="mt-0.5 block text-xs font-semibold text-slate-500">{{ item.description }}</small>
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
      <p class="dealer-stats-eyebrow mb-9 text-sm font-extrabold uppercase tracking-[.12em]">{{ dealer.labels.stats }}</p>
      <div class="grid grid-cols-1 gap-9 ph:grid-cols-3 ph:gap-5">
        <div v-for="stat in dealer.stats" :key="stat.label">
          <strong class="dealer-stat-value">{{ stat.value }}+</strong>
          <span class="dealer-stat-label">{{ stat.label }}</span>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.dealer-stats-eyebrow,
.dealer-stat-value {
  width: fit-content;
  margin-inline: auto;
  background-image: var(--pts-gold-gradient);
  background-clip: text;
  color: transparent;
  -webkit-text-fill-color: transparent;
}

.dealer-stat-value {
  display: block;
  font-size: clamp(3.25rem, 5.2vw, 5.25rem);
  font-weight: 900;
  font-variant-numeric: tabular-nums;
  letter-spacing: -.055em;
  line-height: .98;
}

.dealer-stat-label {
  display: block;
  margin-top: .65rem;
  color: rgb(226 232 240 / .92);
  font-size: clamp(.9rem, 1.1vw, 1rem);
  font-weight: 600;
  line-height: 1.4;
}
</style>
