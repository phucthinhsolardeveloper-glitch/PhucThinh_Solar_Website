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

/**
 * `overview.description` là một chuỗi phẳng, không có cấu trúc list trong DTO.
 * Ảnh production cho thấy phần này là đoạn mở đầu + danh sách gạch đầu dòng
 * (Chip LED CREE..., Pin LiFePO4..., Vỏ nhôm...). Nếu CMS nhập mỗi ý trên một
 * dòng (textarea nhiều dòng) thì tách ra sẽ cho đúng hình dạng đó; nếu CMS chỉ
 * nhập một đoạn văn liền thì hàm này trả về nguyên một dòng, vẫn đúng.
 * Không bịa cấu trúc list khi chuỗi không có dấu xuống dòng.
 */
function descriptionLines(text: string): string[] {
  return text.split(/\r?\n/).map((line) => line.trim()).filter(Boolean)
}
</script>

<template>
  <section
    v-if="dealer.overview.title || dealer.overview.description || dealer.overview.products.length"
    id="nang-luc-phan-phoi"
    class="py-12 nav:py-16"
  >
    <h2 v-if="dealer.overview.title" class="mb-5 text-[clamp(26px,3vw,40px)] font-black leading-tight tracking-tight text-navy-800">
      {{ dealer.overview.title || dealer.labels.overview }}
    </h2>

    <ul v-if="dealer.overview.description" class="mb-8 max-w-[70ch] list-none space-y-2 p-0">
      <li
        v-for="(line, index) in descriptionLines(dealer.overview.description)"
        :key="index"
        class="flex items-start gap-2.5 leading-relaxed text-slate-600"
      >
        <!-- Kim cương vàng, khớp bullet "◆" đo được trên ảnh production. -->
        <svg class="mt-[7px] h-2 w-2 flex-none text-gold-500" viewBox="0 0 10 10" aria-hidden="true"><path d="M5 0 10 5 5 10 0 5Z" fill="currentColor" /></svg>
        {{ line }}
      </li>
    </ul>

    <div v-if="dealer.overview.products.length" class="grid grid-cols-2 gap-5 tb:grid-cols-3">
      <a
        v-for="product in dealer.overview.products"
        :key="product.title + product.image"
        class="min-w-0 text-inherit no-underline"
        :href="product.href ? links.generic(product.href) : undefined"
        :target="links.active ? undefined : '_blank'"
        :rel="links.active ? undefined : 'noopener noreferrer'"
      >
        <img
          v-if="product.image"
          :src="cardImage(product.image)"
          :alt="product.title"
          class="mb-3 block aspect-[8/5] w-full rounded-chip object-cover"
          loading="lazy"
        >
        <span class="font-semibold text-navy-800">{{ product.title }}</span>
      </a>
    </div>
  </section>

  <!--
    Dải hỗ trợ đại lý: nền navy đặc, số lượng thẻ phụ thuộc CMS (ảnh production
    có 4 thẻ một hàng ở 1920px). CSS preview cũ cố định 3 cột — với 4 phần tử
    sẽ đẩy thẻ thứ 4 xuống dòng riêng, lệch khỏi ảnh. Dùng 4 cột ở desktop.
  -->
  <section v-if="dealer.support.title || dealer.support.items.length" id="ho-tro-dai-ly" class="bg-navy-950 px-4 py-12 text-white nav:py-16">
    <div class="mx-auto w-full max-w-(--container-page)">
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
    <h2 class="mb-8 text-[clamp(24px,2.6vw,34px)] font-black tracking-tight text-navy-800">{{ dealer.labels.testimonials }}</h2>
    <div class="grid grid-cols-1 gap-8 nav:grid-cols-3">
      <article v-for="item in dealer.testimonials" :key="item.title" class="min-w-0">
        <p class="mb-4 leading-relaxed text-slate-600">“{{ item.quote || item.description }}”</p>
        <div class="flex items-center gap-3">
          <!--
            PHÁT HIỆN: `item.image` (avatar người đánh giá) có sẵn trong DTO
            nhưng component trước đó không render — ảnh production có avatar
            tròn rõ ràng. Thêm lại, không phải bịa trường mới.
          -->
          <img v-if="item.image" :src="cardImage(item.image)" :alt="item.title" class="h-12 w-12 flex-none rounded-full object-cover" loading="lazy">
          <div>
            <strong class="block text-navy-800">{{ item.title }}</strong>
            <small v-if="item.description" class="block text-sm text-slate-500">{{ item.description }}</small>
          </div>
        </div>
      </article>
    </div>
  </section>

  <!--
    Dải thống kê: gradient navy nhẹ (không phải navy đặc), nhãn
    "TỰ HÀO PHÚC THỊNH SOLAR" chính là dealer.labels.stats — không phải chữ
    rời, CSS preview cũ bỏ sót nhãn này.
  -->
  <section v-if="dealer.stats.length" class="bg-[linear-gradient(120deg,var(--color-navy-950),var(--color-navy-800))] px-4 py-12 text-center text-white nav:py-16" :aria-label="dealer.labels.stats">
    <p class="mb-8 text-xs font-bold uppercase tracking-[.08em] text-blue-200">{{ dealer.labels.stats }}</p>
    <div class="mx-auto grid w-full max-w-(--container-page) grid-cols-1 gap-8 ph:grid-cols-3">
      <div v-for="stat in dealer.stats" :key="stat.label">
        <strong class="block text-4xl font-black">{{ stat.value }}+</strong>
        <span class="mt-1 block text-sm text-blue-200">{{ stat.label }}</span>
      </div>
    </div>
  </section>
</template>
