<script setup lang="ts">
import type { PublicNewsCta } from '#shared/contracts/public-news'

const props = defineProps<{ cta: PublicNewsCta; phoneLabel: string }>()
const links = useMigrationLinks()
const { openConsultation } = useStorefrontModal()
const backgroundImage = computed(() => props.cta.image || '')
</script>

<template>
  <PublicCtaBanner
    class="mt-7 max-[767px]:-mx-4 max-[359px]:-mx-3"
    variant="editorial"
    :image="backgroundImage"
    labelled-by="news-contact-title"
  >
    <h2 id="news-contact-title" class="mb-2 text-[clamp(21px,2.2vw,28px)] font-extrabold leading-[1.25] tracking-[-.025em] text-white">{{ cta.title }}</h2>
    <p v-if="cta.description" class="m-0 max-w-[64ch] leading-[1.65] text-blue-100">{{ cta.description }}</p>
    <template #actions>
      <div class="flex flex-col items-stretch gap-3 min-[541px]:flex-row min-[761px]:flex-col min-[1101px]:flex-row min-[761px]:items-center min-[1101px]:justify-end">
        <a v-if="cta.hotline?.tel" class="flex flex-col whitespace-nowrap text-white no-underline min-[541px]:justify-center" :href="`tel:${cta.hotline.tel}`">
          <small class="text-[11px] text-blue-100">{{ phoneLabel }}</small>
          <strong class="text-[22px] font-extrabold">{{ cta.hotline.text }}</strong>
        </a>
        <a
          class="inline-flex min-h-11 items-center justify-center gap-2 rounded-[4px] bg-hdr-gold px-5 py-3 text-sm font-extrabold text-navy-950 no-underline transition-colors hover:bg-hdr-gold-hover"
          :href="links.generic('/lien-he/#yeu-cau-tu-van')"
          @click.prevent="openConsultation('Kiến thức: ' + cta.title)"
        >{{ cta.buttonLabel || 'Nhận tư vấn ngay' }} <span aria-hidden="true">→</span></a>
      </div>
    </template>
  </PublicCtaBanner>
</template>
