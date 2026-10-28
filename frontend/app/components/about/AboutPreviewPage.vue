<script setup lang="ts">
import type { PublicAboutPayload } from '#shared/contracts/public-about'

defineProps<{ about: PublicAboutPayload }>()
const links = useMigrationLinks()
</script>

<template>
  <main class="overflow-hidden text-navy-800">
    <section id="ve-phuc-thinh" class="mx-auto w-[min(calc(100%-32px),2200px)] pb-[54px]" aria-labelledby="about-story-title">
      <div class="grid min-h-[470px] grid-cols-[minmax(410px,.88fr)_minmax(0,1.12fr)] gap-0 bg-[linear-gradient(135deg,#061735,#10366a)] max-[800px]:min-h-0 max-[800px]:grid-cols-1">
        <div class="flex flex-col justify-center p-[clamp(38px,4vw,64px)] max-[800px]:block max-[800px]:px-6 max-[800px]:pb-[38px] max-[800px]:pt-[34px] max-[600px]:px-5 max-[600px]:pb-[34px] max-[600px]:pt-[30px]">
          <h1 id="about-story-title" class="m-0 max-w-[620px] text-[clamp(36px,3.6vw,54px)] font-black leading-[1.12] tracking-[-.035em] text-white max-[800px]:text-[34px] max-[600px]:text-[31px]">{{ about.story.title }}</h1>
          <p v-if="about.story.intro" class="mt-[17px] max-w-[690px] text-[17px] leading-[1.72] text-[#e1ebf8] max-[800px]:text-[15.5px]">{{ about.story.intro }}</p>
          <p v-if="about.story.body" class="mt-4 border-0 pt-4 text-base leading-[1.72] text-[#bfcfe3]">{{ about.story.body }}</p>
        </div>
        <figure v-if="about.story.image" class="m-0 h-full min-h-0 overflow-hidden bg-[#102d58] max-[800px]:aspect-[16/10]"><img class="block h-full w-full object-cover" :src="about.story.image" :alt="about.story.title" width="1600" height="900" fetchpriority="high"></figure>
      </div>
    </section>

    <nav class="bg-white" aria-label="Đi nhanh trong trang Giới thiệu">
      <div class="mx-auto flex w-[min(calc(100%-32px),2200px)] gap-[30px] overflow-x-auto pb-5 [scrollbar-width:none] max-[800px]:gap-[22px] max-[800px]:pb-[15px]">
        <a class="flex-none border-b-2 border-transparent pb-[7px] text-[13px] font-extrabold text-navy-800 no-underline hover:border-gold-500 hover:text-gold-500 focus-visible:border-gold-500 focus-visible:text-gold-500" href="#ve-phuc-thinh">{{ about.labels.story }}</a>
        <a v-if="about.history.items.length" class="flex-none border-b-2 border-transparent pb-[7px] text-[13px] font-extrabold text-navy-800 no-underline hover:border-gold-500 hover:text-gold-500 focus-visible:border-gold-500 focus-visible:text-gold-500" href="#lich-su-phat-trien">{{ about.labels.history }}</a>
        <a class="flex-none border-b-2 border-transparent pb-[7px] text-[13px] font-extrabold text-navy-800 no-underline hover:border-gold-500 hover:text-gold-500 focus-visible:border-gold-500 focus-visible:text-gold-500" href="#con-nguoi">{{ about.labels.people }}</a>
        <a v-if="about.compass.items.length" class="flex-none border-b-2 border-transparent pb-[7px] text-[13px] font-extrabold text-navy-800 no-underline hover:border-gold-500 hover:text-gold-500 focus-visible:border-gold-500 focus-visible:text-gold-500" href="#tam-nhin-su-menh">{{ about.labels.compass }}</a>
        <a v-if="about.certificates.items.length" class="flex-none border-b-2 border-transparent pb-[7px] text-[13px] font-extrabold text-navy-800 no-underline hover:border-gold-500 hover:text-gold-500 focus-visible:border-gold-500 focus-visible:text-gold-500" href="#about-certificates">{{ about.labels.certificates }}</a>
      </div>
    </nav>

    <section v-if="about.history.items.length" id="lich-su-phat-trien" class="bg-slate-50 py-16 max-[800px]:py-12" aria-labelledby="about-history-title">
      <div class="mx-auto grid w-[min(calc(100%-32px),2200px)] grid-cols-2 items-stretch gap-[clamp(30px,5.4vw,86px)] max-[800px]:grid-cols-1">
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
        <figure v-if="about.history.image" class="m-0 min-h-[390px] overflow-hidden bg-navy-50 max-[800px]:max-h-[26rem]"><img class="block h-full w-full object-cover" :src="about.history.image" :alt="about.history.title" width="780" height="600" loading="lazy"></figure>
      </div>
    </section>

    <section id="con-nguoi" class="bg-white py-16 max-[800px]:py-12" aria-labelledby="about-people-title">
      <div class="mx-auto w-[min(calc(100%-32px),2200px)]">
        <div class="grid grid-cols-2 items-center gap-[clamp(28px,5.4vw,86px)] max-[800px]:grid-cols-1">
          <figure v-if="about.people.image" class="m-0 aspect-[10/7] overflow-hidden bg-navy-50 max-[800px]:max-h-[26rem]"><img class="block h-full w-full object-cover" :src="about.people.image" :alt="about.people.title" width="780" height="620" loading="lazy"></figure>
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
      <div class="mx-auto grid w-[min(calc(100%-32px),2200px)] grid-cols-[minmax(0,.7fr)_minmax(0,1.3fr)] items-start gap-16 max-[800px]:grid-cols-1 max-[800px]:gap-7">
        <h2 id="about-trust-title" class="m-0 text-[clamp(29px,2.6vw,38px)] font-bold leading-[1.22]">{{ about.trust.title }}</h2>
        <div class="border-t border-[#dce4ef]"><article v-for="(item, index) in about.trust.items" :key="`${item.title}-${index}`" class="grid grid-cols-[4rem_minmax(0,1fr)] items-start gap-5 border-b border-[#dce4ef] py-5"><img v-if="item.image" class="h-[3.25rem] w-[3.25rem] object-contain" :src="item.image" alt="" width="64" height="64" loading="lazy"><span v-else class="flex h-[3.25rem] w-[3.25rem] items-center justify-center rounded-full border border-gold-500/70 text-xs font-extrabold text-gold-500" aria-hidden="true">{{ String(index + 1).padStart(2, '0') }}</span><div><h3 class="text-[1.1rem] font-bold leading-[1.22]">{{ item.title }}</h3><p class="m-0 mt-[.4rem] leading-[1.68] text-[#52627c]">{{ item.description }}</p></div></article></div>
      </div>
    </section>

    <section v-if="about.compass.items.length" id="tam-nhin-su-menh" class="bg-navy-800 py-16 max-[800px]:py-12" aria-labelledby="about-compass-title">
      <div class="mx-auto w-[min(calc(100%-32px),2200px)]"><h2 id="about-compass-title" class="m-0 text-[clamp(29px,2.6vw,38px)] font-bold leading-[1.22] text-white">{{ about.compass.title }}</h2><div class="mt-9 grid grid-cols-3 border-t border-white/20 max-[800px]:grid-cols-1"><article v-for="(item, index) in about.compass.items" :key="`${item.title}-${index}`" class="px-8 pt-7 first:pl-0 max-[800px]:grid max-[800px]:grid-cols-[3.5rem_minmax(0,1fr)] max-[800px]:gap-4 max-[800px]:border-t max-[800px]:border-white/20 max-[800px]:px-0 max-[800px]:py-5 max-[800px]:first:border-t-0"><img v-if="item.image" class="h-12 w-12 object-contain" :src="item.image" alt="" width="56" height="56" loading="lazy"><span v-else class="flex h-[3.25rem] w-[3.25rem] items-center justify-center rounded-full border border-gold-300/70 text-xs font-extrabold text-gold-300" aria-hidden="true">{{ String(index + 1).padStart(2, '0') }}</span><div><h3 class="text-[1.1rem] font-bold leading-[1.22] text-white">{{ item.title }}</h3><p v-if="item.description" class="m-0 mt-[.4rem] leading-[1.68] text-[#d1daea]">{{ item.description }}</p></div></article></div></div>
    </section>

    <section v-if="about.certificates.items.length" id="about-certificates" class="py-16 max-[800px]:py-12" aria-labelledby="about-certificates-title">
      <div class="mx-auto w-[min(calc(100%-32px),2200px)]"><h2 id="about-certificates-title" class="m-0 text-[clamp(29px,2.6vw,38px)] font-bold leading-[1.22]">{{ about.certificates.title }}</h2><p class="mt-3 max-w-[48rem] leading-[1.68] text-[#52627c]">{{ about.certificates.intro }}</p><div class="mt-8 grid grid-cols-3 gap-5 max-[800px]:grid-cols-1"><article v-for="item in about.certificates.items" :key="item.id" class="grid min-w-0 grid-cols-[minmax(6.5rem,42%)_minmax(0,1fr)] items-center gap-4 border-t border-[#dce4ef] pt-4 max-[800px]:pt-5"><a class="block bg-[#f4f7fb]" :href="item.href" :aria-label="`Mở ${item.name}`"><img class="block h-[13.75rem] w-full object-contain" :src="item.image" :alt="item.name" width="260" height="360" loading="lazy"></a><div><span class="text-xs font-extrabold uppercase tracking-[.1em] text-gold-500">{{ about.labels.certLabel }}</span><h3 class="mt-[.45rem] break-words text-[1.1rem] font-bold leading-[1.22]">{{ item.name }}</h3><p v-if="item.description" class="my-[.45rem] text-[.9rem] leading-[1.68] text-[#52627c]">{{ item.description }}</p><a class="font-bold text-navy-800 no-underline" :href="item.href">{{ about.labels.certDetail }} →</a></div></article></div></div>
    </section>

    <section class="pb-16 max-[800px]:px-0 max-[800px]:pb-12" aria-labelledby="about-cta-title"><div class="mx-auto w-[min(calc(100%-32px),2200px)]"><div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-6 bg-navy-800 px-8 py-7 max-[800px]:grid-cols-1 max-[800px]:px-6"><div class="grid grid-cols-[3.5rem_minmax(0,1fr)] items-center gap-4"><span class="flex h-[3.25rem] w-[3.25rem] items-center justify-center rounded-full border border-gold-300 text-xs font-extrabold text-gold-300" aria-hidden="true">PTS</span><div><h2 id="about-cta-title" class="text-[1.4rem] font-bold text-white">{{ about.cta.title }}</h2><p class="m-0 mt-[.3rem] text-[#d1daea]">{{ about.cta.description }}</p></div></div><div class="flex items-center gap-3 max-[800px]:grid"><a v-if="about.cta.hotline" class="inline-flex min-h-11 items-center bg-gold-500 px-4 py-3 font-extrabold text-navy-800 no-underline max-[800px]:justify-center" :href="`tel:${about.cta.hotline.tel}`">{{ about.cta.hotline.text }}</a><a v-if="about.cta.href" class="inline-flex min-h-11 items-center bg-gold-500 px-4 py-3 font-extrabold text-navy-800 no-underline max-[800px]:justify-center" :href="links.generic(about.cta.href)">{{ about.cta.buttonLabel }} →</a><span v-else class="inline-flex min-h-11 items-center border border-white/30 px-4 py-3 text-[#d1daea] max-[800px]:justify-center">{{ about.cta.buttonLabel }}</span></div></div></div></section>
  </main>
</template>
