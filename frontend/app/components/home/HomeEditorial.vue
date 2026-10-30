<script setup lang="ts">
import type { PublicHomeAboutPoint, PublicHomePayload } from '#shared/contracts/public-home'
import { motion } from 'motion-v'

const props = defineProps<{ data: PublicHomePayload }>()
const solutionChips = [
  ['Điện mặt trời mái nhà', 'Biến tần (Inverter)', 'Tấm pin điện mặt trời'],
  ['Điện mặt trời mái nhà', 'Biến tần (Inverter)', 'Pin lưu trữ'],
  ['Đèn năng lượng mặt trời', 'Trụ đèn và phụ kiện'],
  ['Bơm nước năng lượng mặt trời', 'Tấm pin điện mặt trời'],
]

type SolutionTab = {
  label: string
  value: number
  slot: 'solution'
  solution: PublicHomePayload['solutions'][number]
  chips: string[]
}

const selected = ref<string | number>(0)
const solutionTabs = computed<SolutionTab[]>(() => props.data.solutions.map((solution, index) => ({
  label: solution.label,
  value: index,
  slot: 'solution',
  solution,
  chips: solutionChips[index] || [],
})))
const solutionPanelInteracted = ref(false)
const links = useMigrationLinks()
const leadIntent = useState<string>('public-home-lead-intent', () => '')
const { openDealer } = useStorefrontModal()
const phpResizableEditorialImage = /^\/(?:upload\/news|assets\/images\/site-visuals)\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png)$/i

function editorialImageCandidate(src: string, width: 760 | 1200, height: 475 | 750): string {
  if (!phpResizableEditorialImage.test(src)) return src
  return `/thumbs/${width}x${height}x1${src}`
}

function editorialImageSrcSet(src: string): string | undefined {
  if (!phpResizableEditorialImage.test(src)) return undefined
  return `${editorialImageCandidate(src, 760, 475)} 760w, ${editorialImageCandidate(src, 1200, 750)} 1200w`
}

function aboutPointImage(src: string): string {
  return phpResizableEditorialImage.test(src) ? `/thumbs/48x48x1${src}` : src
}

const fallbackAboutPoints: PublicHomeAboutPoint[] = [
  { label: 'Sản phẩm chính hãng', description: 'Thông tin thương hiệu, thông số kỹ thuật và chính sách bảo hành rõ ràng.', image: '' },
  { label: 'Tư vấn và thi công', description: 'Đề xuất thiết bị và phương án lắp đặt phù hợp với điều kiện thực tế của từng công trình.', image: '' },
  { label: 'Hỗ trợ sau bàn giao', description: 'Hướng dẫn sử dụng, tiếp nhận yêu cầu bảo hành và hỗ trợ kỹ thuật trong quá trình vận hành.', image: '' },
  { label: 'Hệ thống kho ba khu vực', description: 'Kho tại TP.HCM, Đà Nẵng và Gia Lai, hỗ trợ giao hàng toàn quốc.', image: '' },
]

const aboutPoints = computed(() => (props.data.about.points?.length ? props.data.about.points : fallbackAboutPoints).filter((point) => point.label))

function chipHref(chip: string, currentSolution: PublicHomePayload['solutions'][number]) {
  const label = chip.trim().toLocaleLowerCase()
  if (label.includes('mái nhà') || label.includes('mai nha')) return currentSolution.href || '/giai-phap/'

  const needles = label.includes('biến tần') || label.includes('inverter')
    ? ['bien-tan-inverter', 'inverter']
    : label.includes('lưu trữ') || label.includes('pin lưu trữ') || label.includes('battery')
      ? ['pin-luu-tru', 'luu-tru', 'battery']
      : label.includes('tấm pin') || label.includes('solar panel')
        ? ['tam-pin', 'solar-panel']
        : label.includes('bơm') || label.includes('bom') || label.includes('pump')
          ? ['bien-tan-bom', 'bom-nang-luong', 'bom', 'pump']
          : label.includes('đèn') || label.includes('den') || label.includes('trụ') || label.includes('tru')
            ? ['den-nlmt']
            : []

  return props.data.categories.find((category) => needles.some((needle) => category.slug.toLocaleLowerCase().includes(needle)))?.url
    || currentSolution.href
    || '/giai-phap/'
}

function requestConsultation(subject: string) {
  leadIntent.value = subject
}

function selectSolution(value: string | number) {
  solutionPanelInteracted.value = true
  selected.value = value
}
</script>

<template>
  <section
    v-if="data.solutions.length"
    id="giai-phap"
    class="py-[clamp(64px,6vw,92px)]"
    aria-labelledby="home-solutions-title"
  >
    <div class="mx-auto w-[min(calc(100%-clamp(40px,5vw,96px)),1560px)] max-[860px]:w-[min(calc(100%-48px),1440px)] max-[640px]:w-[calc(100%-40px)] max-[360px]:w-[calc(100%-32px)] min-[1600px]:w-[min(calc(100%-clamp(96px,8vw,220px)),2200px)]">
      <header class="mb-10 max-w-[760px]">
        <h2 id="home-solutions-title" class="m-0 text-[clamp(31px,2.2vw,42px)] font-bold leading-[1.2] text-navy-950">
          Giải pháp cho từng nhu cầu
        </h2>
        <p class="mt-[13px] text-[clamp(16px,1.1vw,19px)] leading-[1.65] text-slate-500">
          Chọn lĩnh vực sử dụng để tìm hiểu phương án phù hợp.
        </p>
      </header>

      <div role="group" aria-label="Giải pháp theo nhu cầu">
        <UTabs
          :model-value="selected"
          :items="solutionTabs"
          color="primary"
          variant="link"
          size="xl"
          :ui="{
            root: 'w-full',
            list: 'mb-9 flex gap-7 overflow-x-auto border-hdr-line px-1.5 py-1.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden',
            indicator: '-bottom-px h-[3px] rounded-none bg-hdr-gold',
            trigger: 'min-h-[50px] flex-none justify-start rounded-none px-0 font-bold whitespace-nowrap data-[state=active]:text-navy-950 data-[state=inactive]:text-slate-500',
            content: 'rounded-none outline-offset-4',
          }"
          @update:model-value="selectSolution"
        >
          <template #solution="{ item }">
            <motion.article
              v-if="item.solution"
              :initial="solutionPanelInteracted ? { opacity: 0, y: 8 } : false"
              :animate="{ opacity: 1, y: 0 }"
              :transition="{ duration: 0.18, ease: 'easeOut' }"
              class="grid grid-cols-[minmax(0,.9fr)_minmax(0,1.1fr)] items-center gap-[clamp(28px,3vw,48px)] max-md:grid-cols-1"
            >
              <div>
                <h3 class="m-0 text-[clamp(29px,2.3vw,42px)] font-bold leading-[1.25] text-navy-950">
                  {{ item.solution.name }}
                </h3>
                <p class="my-4 mb-6 max-w-[650px] text-[clamp(18px,1.18vw,20px)] leading-[1.68] text-slate-600">
                  {{ item.solution.description }}
                </p>
                <div v-if="item.chips.length" class="-mt-2 mb-7 flex flex-wrap gap-2" aria-label="Thiết bị liên quan">
                  <a
                    v-for="chip in item.chips"
                    :key="chip"
                    class="inline-flex min-h-[38px] items-center rounded-[3px] bg-hdr-surface px-3 py-[7px] text-[14.5px] font-semibold text-navy-950 no-underline transition-colors hover:bg-navy-950 hover:text-white"
                    :href="links.generic(chipHref(chip, item.solution))"
                  >
                    {{ chip }}
                  </a>
                </div>
                <div class="flex flex-wrap items-center gap-[14px]">
                  <UButton :href="links.generic(item.solution.href)" color="primary" size="xl" class="min-h-[50px] rounded-[4px] border border-hdr-gold bg-hdr-gold px-[25px] font-bold text-navy-950 no-underline hover:bg-hdr-gold-hover">
                    Xem giải pháp
                  </UButton>
                  <UButton href="#yeu-cau-tu-van" color="secondary" variant="link" class="min-h-11 rounded-none px-2 font-bold text-navy-950 no-underline hover:text-gold-700" @click="requestConsultation(item.solution.name)">
                    Nhận tư vấn <span aria-hidden="true">→</span>
                  </UButton>
                </div>
              </div>

              <div class="relative flex aspect-[8/5] items-end overflow-hidden bg-navy-950 text-white">
                <img class="absolute inset-0 h-full w-full object-cover" :src="editorialImageCandidate(item.solution.image, 1200, 750)" :srcset="editorialImageSrcSet(item.solution.image)" sizes="(min-width: 1024px) 45vw, 100vw" :alt="item.solution.name" width="1200" height="750" loading="lazy" decoding="async">
                <span class="relative z-10 w-full bg-gradient-to-t from-[#071a2e]/85 to-transparent p-[18px] pt-24 text-sm font-bold">{{ item.solution.label }}</span>
              </div>
            </motion.article>
          </template>
        </UTabs>
      </div>
    </div>
  </section>

  <section
    v-if="data.process.length"
    class="bg-hdr-surface py-[clamp(64px,6vw,92px)]"
    aria-labelledby="home-process-title"
  >
    <div class="mx-auto w-[min(calc(100%-clamp(40px,5vw,96px)),1560px)] max-[860px]:w-[min(calc(100%-48px),1440px)] max-[640px]:w-[calc(100%-40px)] max-[360px]:w-[calc(100%-32px)] min-[1600px]:w-[min(calc(100%-clamp(96px,8vw,220px)),2200px)]">
      <header class="mb-10 max-w-[760px]">
        <h2 id="home-process-title" class="m-0 text-[clamp(31px,2.2vw,42px)] font-bold leading-[1.2] text-navy-950">
          Đồng hành từ tư vấn đến vận hành
        </h2>
        <p class="mt-[13px] text-[clamp(16px,1.1vw,19px)] leading-[1.65] text-slate-500">
          Phúc Thịnh Solar tư vấn giải pháp phù hợp, thi công chuyên nghiệp và tận tâm hỗ trợ sau bàn giao, giúp khách hàng an tâm sử dụng lâu dài.
        </p>
      </header>
      <ol class="grid grid-cols-5 gap-[clamp(18px,2.2vw,34px)] max-[860px]:grid-cols-3 max-[640px]:grid-cols-2 max-[360px]:grid-cols-1">
        <li v-for="step in data.process" :key="step.number" class="min-w-0 border-t-[3px] border-hdr-gold pt-[18px]">
          <b class="text-[13px] text-hdr-gold-deep">{{ step.number }}</b>
          <h3 class="mt-2 text-base font-bold leading-[1.4] text-navy-950">{{ step.name }}</h3>
          <p class="mt-2 text-[13.5px] leading-[1.6] text-slate-500">{{ step.description }}</p>
        </li>
      </ol>
    </div>
  </section>

  <section class="bg-navy-950 py-[clamp(54px,5vw,72px)] text-[#d8e1ea]" aria-labelledby="home-dealer-title">
    <div class="mx-auto grid w-[min(calc(100%-clamp(40px,5vw,96px)),1560px)] max-[860px]:w-[min(calc(100%-48px),1440px)] max-[640px]:w-[calc(100%-40px)] max-[360px]:w-[calc(100%-32px)] min-[1600px]:w-[min(calc(100%-clamp(96px,8vw,220px)),2200px)] grid-cols-[minmax(0,1fr)_minmax(460px,520px)] items-center gap-[clamp(28px,4vw,56px)] max-[860px]:grid-cols-1">
      <div>
        <h2 id="home-dealer-title" class="m-0 text-[clamp(31px,2.2vw,42px)] font-bold leading-[1.2] text-white">
          {{ data.dealer.title }}
        </h2>
        <p class="my-4 mb-6 max-w-[760px] text-[clamp(18px,1.15vw,21px)] leading-[1.65] text-[#c4d0dc]">
          {{ data.dealer.description }}
        </p>
        <ul class="m-0 grid list-none gap-3 p-0">
          <li class="flex items-start gap-[11px] text-[clamp(16px,1vw,18px)] leading-[1.55]">
            <svg class="mt-1 h-4 w-4 flex-none text-hdr-gold" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m2.5 8.5 3.4 3.4 7.6-7.8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" /></svg>
            Danh mục sản phẩm phù hợp nhu cầu thị trường.
          </li>
          <li class="flex items-start gap-[11px] text-[clamp(16px,1vw,18px)] leading-[1.55]">
            <svg class="mt-1 h-4 w-4 flex-none text-hdr-gold" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m2.5 8.5 3.4 3.4 7.6-7.8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" /></svg>
            Hệ thống kho tại TP.HCM, Đà Nẵng và Gia Lai.
          </li>
          <li class="flex items-start gap-[11px] text-[clamp(16px,1vw,18px)] leading-[1.55]">
            <svg class="mt-1 h-4 w-4 flex-none text-hdr-gold" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m2.5 8.5 3.4 3.4 7.6-7.8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" /></svg>
            Hỗ trợ tư vấn sản phẩm, kỹ thuật và bảo hành theo chính sách.
          </li>
        </ul>
      </div>
      <div class="flex gap-2 max-[860px]:max-w-[360px] max-[860px]:flex-col">
        <button type="button" class="inline-flex min-h-[50px] flex-1 cursor-pointer items-center justify-center rounded-[4px] border border-hdr-gold bg-hdr-gold px-5 font-bold text-navy-950 no-underline" @click="openDealer()">
          Đăng ký làm đại lý
        </button>
        <a class="inline-flex min-h-[50px] flex-1 items-center justify-center rounded-[4px] border border-white/55 px-5 font-bold text-white no-underline" href="#yeu-cau-tu-van" @click="requestConsultation('Hợp tác đại lý')">
          Tư vấn chính sách đại lý
        </a>
      </div>
    </div>
  </section>

  <section v-if="!data.about.configured || data.about.visible" class="py-[clamp(64px,6vw,92px)] pb-[clamp(44px,4vw,64px)]" aria-labelledby="home-about-title">
    <div class="mx-auto grid w-[min(calc(100%-clamp(40px,5vw,96px)),1560px)] max-[860px]:w-[min(calc(100%-48px),1440px)] max-[640px]:w-[calc(100%-40px)] max-[360px]:w-[calc(100%-32px)] min-[1600px]:w-[min(calc(100%-clamp(96px,8vw,220px)),2200px)] grid-cols-[minmax(0,1.15fr)_minmax(360px,.85fr)] items-start gap-[clamp(46px,6vw,96px)] max-[860px]:grid-cols-1">
      <div>
        <h2 id="home-about-title" class="m-0 text-[clamp(30px,2.3vw,42px)] font-bold leading-[1.2] text-navy-950">
          {{ data.about.title }}
        </h2>
        <p class="mt-[18px] max-w-[780px] text-[clamp(17px,1.1vw,19px)] leading-[1.7] text-slate-600">
          {{ data.about.description }}
        </p>
        <a class="mt-[23px] inline-flex font-bold text-navy-950 no-underline" :href="links.generic(data.about.href)">
          {{ data.about.action }} <span class="ml-2" aria-hidden="true">→</span>
        </a>
      </div>
      <ul class="m-0 grid list-none grid-cols-2 gap-x-[34px] gap-y-7 p-0 pt-[5px] max-[860px]:grid-cols-1">
        <li v-for="(point, index) in aboutPoints" :key="point.label" class="relative min-w-0 pl-8 text-navy-950">
          <img v-if="point.image" class="absolute left-0 top-0 h-[22px] w-[22px] rounded-full object-cover" :src="aboutPointImage(point.image)" alt="" width="48" height="48" loading="lazy" decoding="async">
          <span v-else class="absolute left-0 top-0 flex h-[22px] w-[22px] items-center justify-center rounded-full bg-[rgba(198,154,69,.17)] text-[#8a6526]" aria-hidden="true">
            <svg v-if="index === 0" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6" /><path d="m8 13-1 9 5-3 5 3-1-9" /><path d="m10 8 1.3 1.3L14 6.5" /></svg>
            <svg v-else-if="index === 1" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a5 5 0 0 0-6.4 6.4L3 18l3 3 5.3-5.3a5 5 0 0 0 6.4-6.4L14 13l-3-3z" /><path d="m15 3 6 6" /></svg>
            <svg v-else-if="index === 2" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 20 5v6c0 5-3.4 9-8 11-4.6-2-8-6-8-11V5z" /><path d="m8.5 12 2.2 2.2 4.8-5" /></svg>
            <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m3 10 9-7 9 7" /><path d="M5 9v12h14V9M9 21v-7h6v7M8 11h2m4 0h2" /></svg>
          </span>
          <strong class="block text-[clamp(15px,1vw,17px)] font-extrabold leading-[1.35]">{{ point.label }}</strong>
          <small v-if="point.description" class="mt-[7px] block text-[clamp(14px,.92vw,16px)] leading-[1.55] text-slate-600">{{ point.description }}</small>
        </li>
      </ul>
    </div>
  </section>
</template>
