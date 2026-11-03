<script setup lang="ts">
import { publicEmailHref, publicPhoneHref } from '#shared/contact-links'
import type { PublicContactPayload } from '#shared/contracts/public-contact'

const props = defineProps<{ contact: PublicContactPayload; pageTitle: string }>()
const links = useMigrationLinks()
const emailHref = computed(() => publicEmailHref(props.contact.contact.email))
const phoneHref = computed(() => publicPhoneHref(props.contact.contact.hotline))
</script>

<template>
  <section class="grid min-w-0 gap-10 py-9 nav:py-12 lg:grid-cols-[minmax(0,1fr)_minmax(380px,.86fr)] lg:items-start lg:gap-14" aria-labelledby="contact-hero-title">
    <div class="flex min-w-0 flex-col justify-center lg:py-6">
      <h1 id="contact-hero-title" class="m-0 max-w-xl text-[clamp(30px,3.4vw,46px)] font-bold leading-[1.1] tracking-tight text-navy-800">
        {{ pageTitle }}
      </h1>
      <p class="mb-0 mt-4 max-w-xl text-base leading-7 text-slate-600">
        {{ contact.visual?.description || 'Để lại thông tin hoặc liên hệ trực tiếp, đội ngũ Phúc Thịnh Solar sẽ tư vấn giải pháp phù hợp với nhu cầu của bạn.' }}
      </p>

      <div v-if="contact.contact.company || contact.contact.hotline || contact.contact.email || contact.contact.workingHours" class="mt-7 border-t border-slate-200 pt-6">
        <p v-if="contact.contact.company" class="m-0 text-sm font-semibold leading-6 text-navy-800">{{ contact.contact.company }}</p>
        <ul class="mb-0 mt-4 grid list-none gap-3 p-0 text-sm leading-6 text-slate-600">
          <li v-if="contact.contact.hotline" class="flex items-start gap-3">
            <UIcon name="i-lucide-phone" class="mt-0.5 size-4 flex-none text-gold-700" aria-hidden="true" />
            <a v-if="phoneHref" class="text-inherit underline-offset-2 hover:underline" :href="phoneHref">Hotline: <strong class="text-navy-800">{{ contact.contact.hotline }}</strong></a>
            <span v-else>Hotline: <strong class="text-navy-800">{{ contact.contact.hotline }}</strong></span>
          </li>
          <li v-if="contact.contact.email" class="flex min-w-0 items-start gap-3">
            <UIcon name="i-lucide-mail" class="mt-0.5 size-4 flex-none text-gold-700" aria-hidden="true" />
            <a v-if="emailHref" class="min-w-0 break-words text-inherit underline-offset-2 hover:underline" :href="emailHref">Email: <strong class="text-navy-800">{{ contact.contact.email }}</strong></a>
            <span v-else class="min-w-0 break-words">Email: <strong class="text-navy-800">{{ contact.contact.email }}</strong></span>
          </li>
          <li v-if="contact.contact.workingHours" class="flex items-start gap-3">
            <UIcon name="i-lucide-clock-3" class="mt-0.5 size-4 flex-none text-gold-700" aria-hidden="true" />
            <span>Thời gian làm việc: <strong class="text-navy-800">{{ contact.contact.workingHours }}</strong></span>
          </li>
        </ul>
      </div>

      <UButton
        v-if="contact.visual?.href"
        color="primary"
        size="lg"
        class="mt-8 inline-flex min-h-12 items-center justify-center gap-2 self-start rounded-[4px] bg-gold-500 px-5 py-3 font-bold text-navy-950 no-underline transition-colors hover:bg-gold-400"
        :href="links.generic(contact.visual.href)"
      >
        Gửi yêu cầu tư vấn <UIcon name="i-lucide-arrow-down" class="size-4" aria-hidden="true" />
      </UButton>
    </div>

    <figure v-if="contact.visual" class="relative m-0 min-w-0 overflow-hidden border border-slate-200 p-2.5 lg:p-3">
      <div class="relative overflow-hidden">
        <img
          :src="contact.visual.image"
          :alt="contact.visual.name || ''"
          class="block aspect-[8/5] w-full object-cover"
          width="1200"
          height="750"
          fetchpriority="high"
        >
        <a v-if="phoneHref" :href="phoneHref" class="absolute bottom-3 left-3 flex min-h-14 items-center gap-3 rounded-[4px] bg-white/95 px-4 py-2.5 text-navy-800 no-underline shadow-sm backdrop-blur-sm transition-colors hover:bg-white">
          <span class="flex size-10 flex-none items-center justify-center rounded-full bg-gold-500 text-navy-950"><UIcon name="i-lucide-phone" class="size-5" aria-hidden="true" /></span>
          <span class="flex flex-col leading-tight"><small class="text-xs font-semibold text-slate-500">Hotline tư vấn</small><strong class="mt-1 text-base">{{ contact.contact.hotline }}</strong></span>
        </a>
      </div>
      <figcaption v-if="contact.visual.name" class="px-1 pb-0.5 pt-3 text-sm font-semibold text-navy-800">{{ contact.visual.name }}</figcaption>
    </figure>
  </section>
</template>
