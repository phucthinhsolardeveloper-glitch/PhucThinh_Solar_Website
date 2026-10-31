<script setup lang="ts">
import { publicEmailHref, publicPhoneHref } from '#shared/contact-links'
import type { PublicContactPayload } from '#shared/contracts/public-contact'

const props = defineProps<{ contact: PublicContactPayload }>()
const links = useMigrationLinks()
const emailHref = computed(() => publicEmailHref(props.contact.contact.email))
const phoneHref = computed(() => publicPhoneHref(props.contact.contact.hotline))
</script>

<template>
  <section class="grid min-w-0 overflow-hidden rounded-btn bg-navy-950 lg:grid-cols-[minmax(0,1.15fr)_minmax(360px,.85fr)]" aria-labelledby="contact-hero-title">
    <div v-if="contact.visual" class="relative min-w-0 overflow-hidden bg-navy-900">
      <img
        :src="contact.visual.image"
        alt=""
        class="block aspect-[8/5] w-full object-cover lg:absolute lg:inset-0 lg:h-full lg:aspect-auto"
        width="1400"
        height="875"
        fetchpriority="high"
      >
      <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-navy-950/55 via-transparent to-transparent" />
      <a v-if="phoneHref" :href="phoneHref" class="public-neutral-button absolute bottom-4 left-4 flex min-h-14 items-center gap-3 px-4 py-2.5 text-navy-800 no-underline lg:bottom-6 lg:left-6">
        <span class="flex size-10 flex-none items-center justify-center rounded-full bg-gold-500 text-navy-950"><UIcon name="i-lucide-phone" class="size-5" aria-hidden="true" /></span>
        <span class="flex flex-col leading-tight"><small class="text-xs font-semibold text-slate-500">Hotline tư vấn</small><strong class="mt-1 text-base">{{ contact.contact.hotline }}</strong></span>
      </a>
    </div>

    <div class="flex min-w-0 flex-col justify-center px-5 py-8 text-white tb:px-8 lg:min-h-[460px] lg:px-10 lg:py-12">
      <h2 id="contact-hero-title" class="m-0 max-w-xl text-[clamp(26px,2.8vw,40px)] font-bold leading-tight tracking-tight">
        {{ contact.visual?.name || 'Tư vấn giải pháp cùng Phúc Thịnh Solar' }}
      </h2>
      <p v-if="contact.visual?.description" class="mb-0 mt-4 max-w-xl text-base leading-7 text-blue-100">{{ contact.visual.description }}</p>

      <div v-if="contact.contact.company || contact.contact.hotline || contact.contact.email || contact.contact.workingHours" class="mt-7 border-t border-white/20 pt-6">
        <p v-if="contact.contact.company" class="m-0 text-sm font-semibold leading-6 text-white">{{ contact.contact.company }}</p>
        <ul class="mb-0 mt-4 grid list-none gap-3 p-0 text-sm leading-6 text-blue-100">
          <li v-if="contact.contact.hotline" class="flex items-start gap-3">
            <UIcon name="i-lucide-phone" class="mt-0.5 size-4 flex-none text-gold-400" aria-hidden="true" />
            <a v-if="phoneHref" class="text-inherit underline-offset-2 hover:underline" :href="phoneHref">Hotline: <strong class="text-white">{{ contact.contact.hotline }}</strong></a>
            <span v-else>Hotline: <strong class="text-white">{{ contact.contact.hotline }}</strong></span>
          </li>
          <li v-if="contact.contact.email" class="flex min-w-0 items-start gap-3">
            <UIcon name="i-lucide-mail" class="mt-0.5 size-4 flex-none text-gold-400" aria-hidden="true" />
            <a v-if="emailHref" class="min-w-0 break-words text-inherit underline-offset-2 hover:underline" :href="emailHref">Email: <strong class="text-white">{{ contact.contact.email }}</strong></a>
            <span v-else class="min-w-0 break-words">Email: <strong class="text-white">{{ contact.contact.email }}</strong></span>
          </li>
          <li v-if="contact.contact.workingHours" class="flex items-start gap-3">
            <UIcon name="i-lucide-clock-3" class="mt-0.5 size-4 flex-none text-gold-400" aria-hidden="true" />
            <span>Thời gian làm việc: <strong class="text-white">{{ contact.contact.workingHours }}</strong></span>
          </li>
        </ul>
      </div>

      <UButton
        v-if="contact.visual?.href"
        color="primary"
        size="lg"
        class="mt-8 inline-flex min-h-12 items-center justify-center gap-2 self-start rounded-btn bg-gold-500 px-5 py-3 font-bold text-navy-950 no-underline transition-colors hover:bg-gold-400"
        :href="links.generic(contact.visual.href)"
      >
        Gửi yêu cầu tư vấn <UIcon name="i-lucide-arrow-down" class="size-4" aria-hidden="true" />
      </UButton>
    </div>
  </section>
</template>
