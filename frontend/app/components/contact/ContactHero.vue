<script setup lang="ts">
import { publicEmailHref, publicPhoneHref } from '#shared/contact-links'
import type { PublicContactPayload } from '#shared/contracts/public-contact'

const props = defineProps<{ contact: PublicContactPayload; pageTitle: string }>()
const links = useMigrationLinks()
const emailHref = computed(() => publicEmailHref(props.contact.contact.email))
const phoneHref = computed(() => publicPhoneHref(props.contact.contact.hotline))
</script>

<template>
  <section class="c-hero grid min-w-0 gap-10 py-9 nav:py-12 lg:grid-cols-[minmax(0,1fr)_minmax(380px,.86fr)] lg:items-start lg:gap-14" aria-labelledby="contact-hero-title">
    <div class="flex min-w-0 flex-col justify-center lg:py-6">
      <h1 id="contact-hero-title" class="m-0 max-w-xl text-[clamp(30px,3.4vw,46px)] font-bold leading-[1.1] tracking-tight text-navy-800">
        {{ pageTitle }}
      </h1>
      <p class="mb-0 mt-4 max-w-xl text-base leading-7 text-slate-600">
        {{ contact.visual?.description || 'Để lại thông tin hoặc liên hệ trực tiếp, đội ngũ Phúc Thịnh Solar sẽ tư vấn giải pháp phù hợp với nhu cầu của bạn.' }}
      </p>

      <div v-if="contact.contact.company || contact.contact.hotline || contact.contact.email || contact.contact.address || contact.contact.workingHours" class="c-hero-info mt-7 border-t border-slate-200 pt-6">
        <p v-if="contact.contact.company" class="m-0 text-[15px] font-extrabold text-navy-900">{{ contact.contact.company }}</p>
        <ul class="mb-0 mt-4 grid list-none gap-3.5 p-0 text-[15px] leading-6 text-slate-600">
          <li v-if="contact.contact.hotline" class="flex items-center gap-3.5">
            <span class="grid size-9 flex-none place-items-center rounded-chip bg-gold-500/15 text-gold-700" aria-hidden="true">
              <UIcon name="i-lucide-phone" class="size-4" />
            </span>
            <a v-if="phoneHref" class="text-inherit underline-offset-2 hover:underline" :href="phoneHref">Hotline: <strong class="text-base font-extrabold tabular-nums text-navy-900">{{ contact.contact.hotline }}</strong></a>
            <span v-else>Hotline: <strong class="text-navy-800">{{ contact.contact.hotline }}</strong></span>
          </li>
          <li v-if="contact.contact.email" class="flex min-w-0 items-center gap-3.5">
            <span class="grid size-9 flex-none place-items-center rounded-chip bg-gold-500/15 text-gold-700" aria-hidden="true">
              <UIcon name="i-lucide-mail" class="size-4" />
            </span>
            <a v-if="emailHref" class="min-w-0 break-words text-inherit underline-offset-2 hover:underline" :href="emailHref">Email: <strong class="font-bold text-navy-900">{{ contact.contact.email }}</strong></a>
            <span v-else class="min-w-0 break-words">Email: <strong class="text-navy-800">{{ contact.contact.email }}</strong></span>
          </li>
          <li v-if="contact.contact.address" class="flex items-start gap-3.5">
            <span class="mt-0.5 grid size-9 flex-none place-items-center rounded-chip bg-gold-500/15 text-gold-700" aria-hidden="true">
              <UIcon name="i-lucide-map-pin" class="size-4" />
            </span>
            <span>Trụ sở chính: <strong class="font-bold text-navy-900">{{ contact.contact.address }}</strong></span>
          </li>
          <li v-if="contact.contact.workingHours" class="flex items-center gap-3.5">
            <span class="grid size-9 flex-none place-items-center rounded-chip bg-gold-500/15 text-gold-700" aria-hidden="true">
              <UIcon name="i-lucide-clock-3" class="size-4" />
            </span>
            <span>Thời gian làm việc: <strong class="font-bold text-navy-900">{{ contact.contact.workingHours }}</strong></span>
          </li>
        </ul>
      </div>

      <UButton
        v-if="contact.visual?.href"
        color="primary"
        size="lg"
        class="c-hero-cta mt-8 inline-flex min-h-12 items-center justify-center gap-2 self-start rounded-btn bg-gold-500 px-6 py-3 font-extrabold text-navy-950 no-underline transition-colors hover:bg-gold-400"
        :href="links.generic(contact.visual.href)"
      >
        Gửi yêu cầu tư vấn <UIcon name="i-lucide-arrow-down" class="size-4" aria-hidden="true" />
      </UButton>
    </div>

    <figure v-if="contact.visual" class="relative m-0 min-w-0 overflow-hidden">
      <div class="relative overflow-hidden rounded-btn border-b-[3px] border-gold-500 bg-navy-950">
        <img
          :src="contact.visual.image"
          :alt="contact.visual.name || ''"
          class="c-hero-img block aspect-[8/5] w-full object-cover"
          width="1200"
          height="750"
          fetchpriority="high"
        >
        <a v-if="phoneHref" :href="phoneHref" class="contact-hero-call absolute bottom-3.5 left-3.5 flex min-h-14 items-center gap-3.5 rounded-btn bg-navy-950/92 px-4 py-2.5 text-white no-underline shadow-md backdrop-blur-sm transition-colors hover:bg-navy-900">
          <span class="flex size-10 flex-none items-center justify-center rounded-full bg-gold-500 text-navy-950"><UIcon name="i-lucide-phone-call" class="size-5" aria-hidden="true" /></span>
          <span class="flex flex-col leading-tight"><small class="text-xs font-semibold text-gold-300">Gọi tư vấn trực tiếp</small><strong class="mt-1 text-lg font-black tabular-nums">{{ contact.contact.hotline }}</strong></span>
        </a>
      </div>
      <figcaption v-if="contact.visual.name" class="pt-3 text-sm font-bold text-navy-800">{{ contact.visual.name }}</figcaption>
    </figure>
  </section>
</template>

<style scoped>
/* Phones: one compact block of tappable contact rows, so the form arrives sooner. */
@media (max-width: 820px) {
  .c-hero { grid-template-columns: minmax(0, 1fr); gap: 16px; padding-block: 16px 8px; }
  .c-hero h1 + p { margin-top: 8px; font-size: 15px; line-height: 1.55; }
  .c-hero-info { margin-top: 14px; padding-top: 14px; }
  .c-hero-info > p { font-size: 14px; line-height: 1.35; }
  .c-hero-info ul { margin-top: 8px; gap: 2px; font-size: 14.5px; line-height: 1.45; }
  .c-hero-info li { min-height: 44px; align-items: center; gap: 10px; }
  .c-hero-info li > span:first-child { width: 32px; height: 32px; margin-top: 0; border-radius: var(--radius-sm); }
  .c-hero-info li > a { display: inline-flex; flex-wrap: wrap; align-items: center; column-gap: 4px; min-height: 44px; }
  .c-hero-cta { width: 100%; margin-top: 12px; }
  .c-hero-img { aspect-ratio: 16 / 9; }
  .c-hero figure > div { border-radius: var(--radius-md); }
  .c-hero figcaption { padding-top: 8px; font-size: 13.5px; line-height: 1.45; }
}
</style>
