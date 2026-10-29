<script setup lang="ts">
import type { PublicAboutPayload } from '#shared/contracts/public-about'

const props = defineProps<{ about: PublicAboutPayload }>()
const links = useMigrationLinks()
const galleryCertificates = computed(() => props.about.certificates.items)
const visibleCertificates = computed(() => {
  const featured = props.about.certificates.items.filter((item) => item.featured)
  return (featured.length ? featured : props.about.certificates.items).slice(0, 3)
})
const certificateDialog = ref<HTMLDialogElement | null>(null)
const certificateCloseButton = ref<HTMLButtonElement | null>(null)
const certificateTrigger = ref<HTMLElement | null>(null)
const certificateGalleryOpen = ref(false)
const activeCertificateIndex = ref(0)
const activeCertificate = computed(() => galleryCertificates.value[activeCertificateIndex.value] ?? null)

async function openCertificate(item: PublicAboutPayload['certificates']['items'][number], event: MouseEvent) {
  const index = galleryCertificates.value.findIndex((candidate) => candidate.id === item.id)
  if (index < 0) return
  certificateTrigger.value = event.currentTarget instanceof HTMLElement ? event.currentTarget : null
  activeCertificateIndex.value = index
  certificateGalleryOpen.value = true
  await nextTick()
  if (certificateDialog.value && !certificateDialog.value.open) certificateDialog.value.showModal()
  certificateCloseButton.value?.focus()
}

function openAllCertificates(event: MouseEvent) {
  const first = galleryCertificates.value[0]
  if (first) void openCertificate(first, event)
}

function previousCertificate() {
  if (activeCertificateIndex.value > 0) activeCertificateIndex.value--
}

function nextCertificate() {
  if (activeCertificateIndex.value < galleryCertificates.value.length - 1) activeCertificateIndex.value++
}

function closeCertificateGallery() {
  if (certificateDialog.value?.open) certificateDialog.value.close()
  else certificateGalleryOpen.value = false
}

function onCertificateDialogClose() {
  certificateGalleryOpen.value = false
  const trigger = certificateTrigger.value
  certificateTrigger.value = null
  if (trigger?.isConnected) nextTick(() => trigger.focus())
}

function onCertificateDialogBackdrop(event: MouseEvent) {
  if (event.target === event.currentTarget) closeCertificateGallery()
}
</script>

<template>
  <div class="overflow-hidden text-navy-800">
    <section id="ve-phuc-thinh" class="pts-container pt-4 pb-[54px]" aria-labelledby="about-story-title">
      <div class="grid min-h-[470px] grid-cols-[minmax(410px,.88fr)_minmax(0,1.12fr)] gap-0 overflow-hidden rounded-btn border border-navy-800/15 bg-[linear-gradient(135deg,#061735,#10366a)] shadow-md max-[800px]:min-h-0 max-[800px]:grid-cols-1">
        <div class="flex flex-col justify-center p-[clamp(38px,4vw,64px)] max-[800px]:block max-[800px]:px-6 max-[800px]:pb-[38px] max-[800px]:pt-[34px] max-[600px]:px-5 max-[600px]:pb-[34px] max-[600px]:pt-[30px]">
          <h1 id="about-story-title" class="m-0 max-w-[620px] text-[clamp(36px,3.6vw,54px)] font-black leading-[1.12] tracking-[-.035em] text-white max-[800px]:text-[34px] max-[600px]:text-[31px]">{{ about.story.title }}</h1>
          <p v-if="about.story.intro" class="mt-[17px] max-w-[690px] text-[17px] leading-[1.72] text-[#e1ebf8] max-[800px]:text-base">{{ about.story.intro }}</p>
          <p v-if="about.story.body" class="mt-4 border-0 pt-4 text-base leading-[1.72] text-[#bfcfe3]">{{ about.story.body }}</p>
        </div>
        <figure v-if="about.story.image" class="m-0 h-full min-h-0 overflow-hidden bg-[#102d58] max-[800px]:aspect-[16/10]"><img class="block h-full w-full object-cover" :src="about.story.image" :alt="about.story.title" width="1600" height="900" fetchpriority="high"></figure>
      </div>
    </section>

    <nav class="bg-white" aria-label="Đi nhanh trong trang Giới thiệu">
      <div class="pts-container flex gap-[24px] overflow-x-auto border-b border-slate-200/80 pb-4 pt-1 [scrollbar-width:none] max-[800px]:gap-[18px]">
        <a class="inline-flex min-h-11 min-w-11 flex-none items-center justify-center border-b-2 border-transparent px-2 text-[13px] font-extrabold text-navy-800 no-underline hover:border-gold-500 hover:text-gold-500 focus-visible:border-gold-500 focus-visible:text-gold-500" href="#ve-phuc-thinh">{{ about.labels.story }}</a>
        <a v-if="about.history.items.length" class="inline-flex min-h-11 min-w-11 flex-none items-center justify-center border-b-2 border-transparent px-2 text-[13px] font-extrabold text-navy-800 no-underline hover:border-gold-500 hover:text-gold-500 focus-visible:border-gold-500 focus-visible:text-gold-500" href="#lich-su-phat-trien">{{ about.labels.history }}</a>
        <a class="inline-flex min-h-11 min-w-11 flex-none items-center justify-center border-b-2 border-transparent px-2 text-[13px] font-extrabold text-navy-800 no-underline hover:border-gold-500 hover:text-gold-500 focus-visible:border-gold-500 focus-visible:text-gold-500" href="#con-nguoi">{{ about.labels.people }}</a>
        <a v-if="about.compass.items.length" class="inline-flex min-h-11 min-w-11 flex-none items-center justify-center border-b-2 border-transparent px-2 text-[13px] font-extrabold text-navy-800 no-underline hover:border-gold-500 hover:text-gold-500 focus-visible:border-gold-500 focus-visible:text-gold-500" href="#tam-nhin-su-menh">{{ about.labels.compass }}</a>
        <a v-if="about.certificates.items.length" class="inline-flex min-h-11 min-w-11 flex-none items-center justify-center border-b-2 border-transparent px-2 text-[13px] font-extrabold text-navy-800 no-underline hover:border-gold-500 hover:text-gold-500 focus-visible:border-gold-500 focus-visible:text-gold-500" href="#about-certificates">{{ about.labels.certificates }}</a>
      </div>
    </nav>

    <section v-if="about.history.items.length" id="lich-su-phat-trien" class="bg-slate-50 py-16 max-[800px]:py-12" aria-labelledby="about-history-title">
      <div class="pts-container grid grid-cols-2 items-stretch gap-[clamp(30px,5.4vw,86px)] max-[800px]:grid-cols-1">
        <div>
          <h2 id="about-history-title" class="m-0 text-[clamp(29px,2.6vw,38px)] font-bold leading-[1.22]">{{ about.history.title }}</h2>
          <ol class="mt-7 grid list-none gap-5 p-0" :aria-label="about.labels.timeline">
            <li v-for="(item, index) in about.history.items" :key="`${item.year}-${index}`" class="grid grid-cols-[4.2rem_minmax(0,1fr)] gap-x-4">
              <span class="row-span-4 flex h-[3.25rem] w-[3.25rem] items-center justify-center rounded-full border border-gold-500/70 text-xs font-extrabold text-gold-500" aria-hidden="true">{{ String(index + 1).padStart(2, '0') }}</span>
              <strong class="text-[1.4rem] leading-[1.2] text-gold-500">{{ item.year }}</strong>
              <h3 v-if="item.title && about.history.items.length > 1" class="mt-0.5 text-[1.1rem] font-bold leading-[1.22]">{{ item.title }}</h3>
              <p v-if="item.description" class="m-0 mt-[.3rem] leading-[1.68] text-[#52627c]">{{ item.description }}</p>
            </li>
          </ol>
        </div>
        <figure v-if="about.history.image" class="m-0 min-h-[390px] overflow-hidden rounded-btn bg-navy-50 max-[800px]:max-h-[26rem]"><img class="block h-full w-full object-cover" :src="about.history.image" :alt="about.history.title" width="780" height="600" loading="lazy"></figure>
      </div>
    </section>

    <section id="con-nguoi" class="bg-white py-16 max-[800px]:py-12" aria-labelledby="about-people-title">
      <div class="pts-container">
        <div class="grid grid-cols-2 items-center gap-[clamp(28px,5.4vw,86px)] max-[800px]:grid-cols-1">
          <figure v-if="about.people.image" class="m-0 aspect-[10/7] overflow-hidden rounded-btn bg-navy-50 max-[800px]:max-h-[26rem]"><img class="block h-full w-full object-cover" :src="about.people.image" :alt="about.people.title" width="780" height="620" loading="lazy"></figure>
          <div>
            <h2 id="about-people-title" class="m-0 text-[clamp(29px,2.6vw,38px)] font-bold leading-[1.22]">{{ about.people.title }}</h2>
            <p v-if="about.people.intro" class="mt-[1.1rem] text-[1.06rem] leading-[1.68] text-[#52627c]">{{ about.people.intro }}</p>
            <p v-if="about.people.body" class="mt-[1.1rem] border-t border-[#dce4ef] pt-4 leading-[1.68] text-[#52627c]">{{ about.people.body }}</p>
          </div>
        </div>
        <!-- Khớp PHP thật (templates/about/about_tpl.php:95): eyebrow chỉ là
             aria-label trên khối "principles", không render thành text nhìn
             thấy được - PHP không hiện "Con người là nền tảng" dưới dạng chữ. -->
        <div v-if="about.people.principles.length" :aria-label="about.people.eyebrow" class="mt-[3.25rem] grid grid-cols-3 border-t border-[#dce4ef] pt-7 max-[800px]:grid-cols-1 max-[800px]:gap-0">
          <article v-for="(item, index) in about.people.principles" :key="`${item.title}-${index}`" class="grid grid-cols-[3.5rem_minmax(0,1fr)] gap-4 px-7 first:pl-0 max-[800px]:border-t max-[800px]:border-[#dce4ef] max-[800px]:py-5 max-[800px]:first:border-t-0">
            <img v-if="item.image" class="h-[3.25rem] w-[3.25rem] object-contain" :src="item.image" alt="" width="56" height="56" loading="lazy">
            <span v-else class="flex h-[3.25rem] w-[3.25rem] items-center justify-center rounded-full border border-gold-500/70 text-xs font-extrabold text-gold-500" aria-hidden="true">{{ String(index + 1).padStart(2, '0') }}</span>
            <div><h3 class="text-[1.1rem] font-bold leading-[1.22]">{{ item.title }}</h3><p v-if="item.description" class="m-0 mt-[.4rem] leading-[1.68] text-[#52627c]">{{ item.description }}</p></div>
          </article>
        </div>
      </div>
    </section>

    <section v-if="about.trust.items.length" class="bg-[#f4f7fb] py-16 max-[800px]:py-12" aria-labelledby="about-trust-title">
      <div class="pts-container grid grid-cols-[minmax(0,.7fr)_minmax(0,1.3fr)] items-start gap-16 max-[800px]:grid-cols-1 max-[800px]:gap-7">
        <h2 id="about-trust-title" class="m-0 text-[clamp(29px,2.6vw,38px)] font-bold leading-[1.22]">{{ about.trust.title }}</h2>
        <div class="grid grid-cols-2 gap-x-[34px] max-[800px]:grid-cols-1">
          <article v-for="(item, index) in about.trust.items" :key="`${item.title}-${index}`" class="grid grid-cols-[4.25rem_minmax(0,1fr)] items-start gap-[22px] py-[18px]">
            <img v-if="item.image" class="h-16 w-16 object-contain" :src="item.image" alt="" width="64" height="64" loading="lazy">
            <span v-else class="flex h-16 w-16 items-center justify-center rounded-full border border-gold-500/70 text-xs font-extrabold text-gold-500" aria-hidden="true">{{ String(index + 1).padStart(2, '0') }}</span>
            <div><h3 class="text-[1.1rem] font-bold leading-[1.22]">{{ item.title }}</h3><p class="m-0 mt-[.4rem] leading-[1.68] text-[#52627c]">{{ item.description }}</p></div>
          </article>
        </div>
      </div>
    </section>

    <section v-if="about.compass.items.length" id="tam-nhin-su-menh" class="bg-navy-800 py-16 max-[800px]:py-12" aria-labelledby="about-compass-title">
      <div class="pts-container"><h2 id="about-compass-title" class="m-0 text-[clamp(29px,2.6vw,38px)] font-bold leading-[1.22] text-white">{{ about.compass.title }}</h2><div class="mt-9 grid grid-cols-3 border-t border-white/20 max-[800px]:grid-cols-1"><article v-for="(item, index) in about.compass.items" :key="`${item.title}-${index}`" class="px-8 pt-7 first:pl-0 max-[800px]:grid max-[800px]:grid-cols-[3.5rem_minmax(0,1fr)] max-[800px]:gap-4 max-[800px]:border-t max-[800px]:border-white/20 max-[800px]:px-0 max-[800px]:py-5 max-[800px]:first:border-t-0"><img v-if="item.image" class="h-12 w-12 object-contain" :src="item.image" alt="" width="56" height="56" loading="lazy"><span v-else class="flex h-[3.25rem] w-[3.25rem] items-center justify-center rounded-full border border-gold-300/70 text-xs font-extrabold text-gold-300" aria-hidden="true">{{ String(index + 1).padStart(2, '0') }}</span><div><h3 class="text-[1.1rem] font-bold leading-[1.22] text-white">{{ item.title }}</h3><p v-if="item.description" class="m-0 mt-[.4rem] leading-[1.68] text-[#d1daea]">{{ item.description }}</p></div></article></div></div>
    </section>

    <section v-if="about.certificates.items.length" id="about-certificates" class="py-16 max-[800px]:py-12" aria-labelledby="about-certificates-title">
      <div class="pts-container">
        <header class="grid grid-cols-1 items-end gap-5 min-[801px]:grid-cols-[minmax(0,1fr)_auto]">
          <div>
            <h2 id="about-certificates-title" class="m-0 text-[clamp(29px,2.6vw,38px)] font-bold leading-[1.22]">{{ about.certificates.title }}</h2>
            <p class="mt-3 max-w-[48rem] leading-[1.68] text-[#52627c]">{{ about.certificates.intro }}</p>
          </div>
          <button
            v-if="galleryCertificates.length"
            type="button"
            class="mb-1 inline-flex min-h-11 items-center gap-2 justify-self-start font-bold text-navy-800 underline-offset-4 hover:underline min-[801px]:justify-self-end"
            @click="openAllCertificates"
          >
            {{ about.labels.certAll }} <span aria-hidden="true">→</span>
          </button>
        </header>

        <div class="mt-8 grid grid-cols-3 gap-5 max-[800px]:grid-cols-1">
          <article
            v-for="item in visibleCertificates"
            :key="item.id"
            class="min-w-0 border-t border-[#dce4ef] pt-4 max-[800px]:pt-5"
            :class="item.image ? 'grid grid-cols-[minmax(6.5rem,42%)_minmax(0,1fr)] items-center gap-4' : ''"
          >
            <button
              v-if="item.image"
              type="button"
              class="block w-full border-0 bg-[#f4f7fb] p-0"
              :aria-label="`Mở chứng nhận ${item.name}`"
              @click="openCertificate(item, $event)"
            >
              <img class="block h-[13.75rem] w-full object-contain" :src="item.image" :alt="item.name" width="260" height="360" loading="lazy">
            </button>
            <div>
              <span class="text-xs font-extrabold uppercase tracking-[.1em] text-gold-500">{{ about.labels.certLabel }}</span>
              <h3 class="mt-[.45rem] break-words text-[1.1rem] font-bold leading-[1.22]">{{ item.name }}</h3>
              <p v-if="item.description" class="my-[.45rem] text-[.9rem] leading-[1.68] text-[#52627c]">{{ item.description }}</p>
              <button
                v-if="item.image"
                type="button"
                class="inline-flex items-center gap-2 border-0 bg-transparent p-0 font-bold text-navy-800"
                @click="openCertificate(item, $event)"
              >
                {{ about.labels.certDetail }} <span aria-hidden="true">→</span>
              </button>
            </div>
          </article>
        </div>
      </div>
    </section>

    <dialog
      v-if="certificateGalleryOpen && activeCertificate"
      ref="certificateDialog"
      class="fixed inset-0 m-auto max-h-[calc(100dvh-32px)] w-[min(calc(100%-32px),1100px)] max-w-none overflow-visible border-0 bg-transparent p-0 text-navy-800 backdrop:bg-navy-950/80"
      role="dialog"
      aria-modal="true"
      aria-labelledby="about-certificate-dialog-title"
      @close="onCertificateDialogClose"
      @cancel.prevent="closeCertificateGallery"
      @keydown.left.prevent="previousCertificate"
      @keydown.right.prevent="nextCertificate"
      @click="onCertificateDialogBackdrop"
    >
      <div class="max-h-[calc(100dvh-32px)] overflow-y-auto rounded-[4px] bg-white p-5 sm:p-7">
        <div class="mb-5 flex items-start justify-between gap-4">
          <div class="min-w-0">
            <h2 id="about-certificate-dialog-title" class="m-0 break-words text-xl font-bold">{{ activeCertificate.name || about.labels.certLabel }}</h2>
            <p class="mb-0 mt-1 text-sm text-slate-500" aria-live="polite">{{ activeCertificateIndex + 1 }} / {{ galleryCertificates.length }}</p>
          </div>
          <button ref="certificateCloseButton" type="button" class="min-h-11 min-w-11 border border-slate-300 bg-white px-3 font-bold" aria-label="Đóng xem chứng nhận" @click="closeCertificateGallery">Đóng</button>
        </div>
        <img v-if="activeCertificate.image" :key="activeCertificate.id" class="mx-auto block max-h-[min(72dvh,760px)] w-full object-contain" :src="activeCertificate.image" :alt="activeCertificate.name">
        <div v-else class="grid min-h-48 place-items-center bg-slate-50 p-8 text-center text-slate-500" role="status">Ảnh chứng nhận này chưa được cập nhật.</div>
        <p v-if="activeCertificate.description" class="mx-auto mb-0 mt-4 max-w-[70ch] text-center leading-relaxed text-slate-600">{{ activeCertificate.description }}</p>
        <div class="mt-5 flex items-center justify-center gap-5">
          <button type="button" class="min-h-11 border border-slate-300 bg-white px-4 font-bold disabled:cursor-not-allowed disabled:opacity-50" aria-label="Chứng nhận trước" :disabled="activeCertificateIndex === 0" @click="previousCertificate">Trước</button>
          <span class="text-sm text-slate-500" aria-hidden="true">{{ activeCertificateIndex + 1 }} / {{ galleryCertificates.length }}</span>
          <button type="button" class="min-h-11 border border-slate-300 bg-white px-4 font-bold disabled:cursor-not-allowed disabled:opacity-50" aria-label="Chứng nhận tiếp theo" :disabled="activeCertificateIndex === galleryCertificates.length - 1" @click="nextCertificate">Tiếp</button>
        </div>
      </div>
    </dialog>

    <section class="pb-16 max-[800px]:px-0 max-[800px]:pb-12" aria-labelledby="about-cta-title"><div class="pts-container"><div class="relative isolate grid grid-cols-[minmax(0,1fr)_auto] items-center gap-6 overflow-hidden rounded-btn border border-navy-700/60 bg-navy-800 px-8 py-7 shadow-md max-[800px]:grid-cols-1 max-[800px]:px-6"><div v-if="about.cta.image" class="absolute inset-0 z-0" aria-hidden="true"><img class="h-full w-full object-cover" :src="about.cta.image" alt="" loading="lazy"><span class="absolute inset-0 bg-[linear-gradient(90deg,rgba(6,27,70,.97),rgba(6,27,70,.82))]"></span></div><div class="relative z-10 grid grid-cols-[3.5rem_minmax(0,1fr)] items-center gap-4"><span class="flex h-[3.25rem] w-[3.25rem] items-center justify-center rounded-full border border-gold-300 text-xs font-extrabold text-gold-300" aria-hidden="true">PTS</span><div><h2 id="about-cta-title" class="text-[1.4rem] font-bold text-white">{{ about.cta.title }}</h2><p class="m-0 mt-[.3rem] text-[#d1daea]">{{ about.cta.description }}</p></div></div><div class="relative z-10 flex items-center gap-3 max-[800px]:grid"><a v-if="about.cta.hotline" class="inline-flex min-h-11 items-center rounded-btn bg-hdr-gold px-4 py-3 font-extrabold text-navy-950 no-underline shadow-xs transition-colors hover:bg-hdr-gold-hover max-[800px]:justify-center" :href="`tel:${about.cta.hotline.tel}`">{{ about.cta.hotline.text }}</a><a v-if="about.cta.zaloUrl" class="inline-flex min-h-11 items-center rounded-btn border border-white/20 bg-white px-4 py-3 font-extrabold text-[#0877c9] no-underline max-[800px]:justify-center" :href="about.cta.zaloUrl" target="_blank" rel="noopener noreferrer">Zalo</a><a class="inline-flex min-h-11 items-center rounded-btn bg-hdr-gold px-4 py-3 font-extrabold text-navy-950 no-underline shadow-xs transition-colors hover:bg-hdr-gold-hover max-[800px]:justify-center" :href="links.generic(about.cta.href || '/lien-he/#yeu-cau-tu-van')">{{ about.cta.buttonLabel }} →</a></div></div></div></section>
  </div>
</template>
