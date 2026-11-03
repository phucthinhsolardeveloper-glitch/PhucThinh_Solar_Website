<script setup lang="ts">
import type { PublicHomeAboutPoint, PublicHomePayload } from '#shared/contracts/public-home'
import { motion, useReducedMotion } from 'motion-v'

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
const reducedMotion = useReducedMotion()
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
  if (value === selected.value) return
  solutionPanelInteracted.value = true
  selected.value = value
}
</script>

<template>
  <section
    v-if="data.solutions.length"
    id="giai-phap"
    class="py-[clamp(56px,5vw,84px)]"
    aria-labelledby="home-solutions-title"
  >
    <div class="pts-container">
      <header class="mb-8 max-w-[760px]">
        <div class="mb-2 inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gold-600">
          <span class="inline-block size-1.5 rounded-full bg-gold-500" /> Hệ thống ứng dụng
        </div>
        <h2 id="home-solutions-title" class="m-0 text-[clamp(28px,2.4vw,40px)] font-black leading-[1.2] text-navy-950">
          Giải pháp cho từng nhu cầu
        </h2>
        <p class="mt-2.5 text-[clamp(15px,1.05vw,18px)] leading-[1.65] text-slate-500">
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
            list: 'mb-8 flex gap-6 overflow-x-auto border-b border-slate-200 px-1 py-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden',
            indicator: '-bottom-px h-[3px] rounded-none bg-gold-500',
            trigger: 'min-h-[46px] flex-none justify-start rounded-none px-1 font-bold whitespace-nowrap text-slate-600 transition-colors data-[state=active]:text-navy-950 data-[state=active]:font-extrabold',
            content: 'rounded-none outline-offset-4',
          }"
          @update:model-value="selectSolution"
        >
          <template #solution="{ item }">
            <motion.article
              v-if="item.solution"
              :initial="solutionPanelInteracted && !reducedMotion ? { opacity: 0.72 } : false"
              :animate="{ opacity: 1 }"
              :transition="{ duration: 0.16, ease: 'easeOut' }"
              class="grid grid-cols-[minmax(0,.9fr)_minmax(0,1.1fr)] items-center gap-[clamp(28px,3vw,48px)] max-md:grid-cols-1"
            >
              <div>
                <h3 class="m-0 text-[clamp(26px,2.2vw,38px)] font-black leading-[1.25] text-navy-950">
                  {{ item.solution.name }}
                </h3>
                <p class="my-3.5 mb-5 max-w-[650px] text-[clamp(16px,1.1vw,19px)] leading-[1.68] text-slate-600">
                  {{ item.solution.description }}
                </p>
                <div v-if="item.chips.length" class="-mt-1 mb-6 flex flex-wrap gap-2" aria-label="Thiết bị liên quan">
                  <a
                    v-for="chip in item.chips"
                    :key="chip"
                    class="inline-flex min-h-[36px] items-center rounded-[3px] border border-slate-200 bg-white px-3 py-1.5 text-[13.5px] font-semibold text-navy-900 no-underline shadow-2xs transition-all hover:border-gold-500 hover:bg-gold-50 hover:text-navy-950"
                    :href="links.generic(chipHref(chip, item.solution))"
                  >
                    {{ chip }}
                  </a>
                </div>
                <div class="flex flex-wrap items-center gap-[14px]">
                  <UButton :href="links.generic(item.solution.href)" color="primary" size="xl" class="min-h-[48px] rounded-[4px] border border-gold-500 bg-gold-400 px-[24px] font-bold text-navy-950 no-underline shadow-2xs hover:bg-gold-500">
                    Xem giải pháp
                  </UButton>
                  <UButton href="#yeu-cau-tu-van" color="secondary" variant="link" class="min-h-11 rounded-none px-2 font-bold text-navy-950 no-underline hover:text-gold-700" @click="requestConsultation(item.solution.name)">
                    Nhận tư vấn <span aria-hidden="true">→</span>
                  </UButton>
                </div>
              </div>

              <div class="relative flex aspect-[8/5] items-end overflow-hidden rounded-[6px] border border-slate-200/80 bg-navy-950 text-white shadow-2xs">
                <img class="absolute inset-0 h-full w-full object-cover" :src="editorialImageCandidate(item.solution.image, 1200, 750)" :srcset="editorialImageSrcSet(item.solution.image)" sizes="(min-width: 1024px) 45vw, 100vw" :alt="item.solution.name" width="1200" height="750" loading="lazy" decoding="async">
                <span class="relative z-10 w-full bg-gradient-to-t from-navy-950/90 to-transparent p-4 pt-16 text-sm font-bold text-white">{{ item.solution.label }}</span>
              </div>
            </motion.article>
          </template>
        </UTabs>
      </div>
    </div>
  </section>

  <!-- 5-Step Precision Engineering Process -->
  <section
    v-if="data.process.length"
    class="bg-hdr-surface py-[clamp(56px,5vw,84px)]"
    aria-labelledby="home-process-title"
  >
    <div class="pts-container">
      <header class="mb-8 max-w-[760px]">
        <div class="mb-2 inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gold-600">
          <span class="inline-block size-1.5 rounded-full bg-gold-500" /> Quy trình dịch vụ
        </div>
        <h2 id="home-process-title" class="m-0 text-[clamp(28px,2.4vw,40px)] font-black leading-[1.2] text-navy-950">
          Đồng hành từ tư vấn đến vận hành
        </h2>
        <p class="mt-2.5 text-[clamp(15px,1.05vw,18px)] leading-[1.65] text-slate-500">
          Phúc Thịnh Solar tư vấn giải pháp phù hợp, thi công chuyên nghiệp và tận tâm hỗ trợ sau bàn giao, giúp khách hàng an tâm sử dụng lâu dài.
        </p>
      </header>
      <ol class="grid grid-cols-5 gap-[clamp(16px,2vw,28px)] max-[860px]:grid-cols-3 max-[640px]:grid-cols-2 max-[360px]:grid-cols-1">
        <li v-for="step in data.process" :key="step.number" class="group min-w-0 rounded-[6px] border-t-2 border-slate-300 bg-white p-4 shadow-2xs transition-all duration-200 hover:border-gold-500 hover:shadow-sm">
          <b class="font-mono text-sm font-black text-gold-600">{{ step.number }}</b>
          <h3 class="mt-2 text-[15.5px] font-bold leading-[1.4] text-navy-950">{{ step.name }}</h3>
          <p class="mt-1.5 text-[13px] leading-[1.6] text-slate-500">{{ step.description }}</p>
        </li>
      </ol>
    </div>
  </section>

  <!-- Dealer Partnership Stage -->
  <section class="bg-navy-950 py-[clamp(52px,5vw,72px)] text-[#d8e1ea]" aria-labelledby="home-dealer-title">
    <div class="pts-container grid grid-cols-[minmax(0,1fr)_minmax(420px,480px)] items-center gap-[clamp(28px,4vw,56px)] max-[860px]:grid-cols-1">
      <div>
        <div class="mb-2 inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gold-400">
          <span class="inline-block size-1.5 rounded-full bg-gold-400" /> Hợp tác kinh doanh
        </div>
        <h2 id="home-dealer-title" class="m-0 text-[clamp(28px,2.4vw,40px)] font-black leading-[1.2] text-white">
          {{ data.dealer.title }}
        </h2>
        <p class="my-3.5 mb-5 max-w-[760px] text-[clamp(16px,1.1vw,19px)] leading-[1.65] text-slate-300">
          {{ data.dealer.description }}
        </p>
        <ul class="m-0 grid list-none gap-2.5 p-0">
          <li class="flex items-start gap-2.5 text-[15.5px] leading-[1.55]">
            <svg class="mt-0.5 h-4 w-4 flex-none text-gold-400" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m2.5 8.5 3.4 3.4 7.6-7.8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" /></svg>
            Danh mục sản phẩm phù hợp nhu cầu thị trường.
          </li>
          <li class="flex items-start gap-2.5 text-[15.5px] leading-[1.55]">
            <svg class="mt-0.5 h-4 w-4 flex-none text-gold-400" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m2.5 8.5 3.4 3.4 7.6-7.8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" /></svg>
            Hệ thống kho tại TP.HCM, Đà Nẵng và Gia Lai.
          </li>
          <li class="flex items-start gap-2.5 text-[15.5px] leading-[1.55]">
            <svg class="mt-0.5 h-4 w-4 flex-none text-gold-400" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m2.5 8.5 3.4 3.4 7.6-7.8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" /></svg>
            Hỗ trợ tư vấn sản phẩm, kỹ thuật và bảo hành theo chính sách.
          </li>
        </ul>
      </div>
      <div class="grid w-full grid-cols-1 gap-3 min-[861px]:grid-cols-2 min-[861px]:gap-2.5 max-[860px]:max-w-[480px]">
        <UButton type="button" color="primary" size="xl" class="min-h-[50px] w-full justify-center rounded-[4px] border border-gold-500 bg-gold-400 px-5 text-sm font-bold text-navy-950 no-underline hover:bg-gold-500 shadow-2xs" @click="openDealer()">
          Đăng ký làm đại lý
          <UIcon name="i-lucide-arrow-right" class="size-4" aria-hidden="true" />
        </UButton>
        <a class="inline-flex min-h-[50px] w-full items-center justify-center gap-2 rounded-[4px] border border-white/20 bg-white/5 px-5 text-sm font-bold text-white no-underline transition-colors hover:bg-white/10 hover:text-gold-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-400" href="#yeu-cau-tu-van" @click="requestConsultation('Hợp tác đại lý')">
          Tư vấn đại lý
          <UIcon name="i-lucide-arrow-right" class="size-4 text-gold-400" aria-hidden="true" />
        </a>
      </div>
    </div>
  </section>

  <!-- About Section (Architectural Monograph Style) -->
  <section v-if="!data.about.configured || data.about.visible" class="py-[clamp(56px,5vw,84px)] pb-[clamp(44px,4vw,64px)]" aria-labelledby="home-about-title">
    <div class="pts-container grid grid-cols-[minmax(0,1.15fr)_minmax(360px,.85fr)] items-start gap-[clamp(40px,5vw,80px)] max-[860px]:grid-cols-1">
      <div>
        <div class="mb-2 inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gold-600">
          <span class="inline-block size-1.5 rounded-full bg-gold-500" /> Về thương hiệu
        </div>
        <h2 id="home-about-title" class="m-0 text-[clamp(28px,2.4vw,40px)] font-black leading-[1.2] text-navy-950">
          {{ data.about.title }}
        </h2>
        <p class="mt-3.5 max-w-[780px] text-[clamp(16px,1.1vw,18px)] leading-[1.7] text-slate-600">
          {{ data.about.description }}
        </p>
        <a class="mt-4 inline-flex items-center gap-1.5 text-sm font-bold text-navy-950 no-underline transition-colors hover:text-gold-700" :href="links.generic(data.about.href)">
          {{ data.about.action }} <span aria-hidden="true">→</span>
        </a>
      </div>
      <ul class="m-0 grid list-none grid-cols-2 gap-x-6 gap-y-6 p-0 pt-1 max-[860px]:grid-cols-1">
        <li v-for="(point, index) in aboutPoints" :key="point.label" class="relative min-w-0 pl-9 text-navy-950">
          <img v-if="point.image" class="absolute left-0 top-0.5 h-6 w-6 rounded-full object-cover" :src="aboutPointImage(point.image)" alt="" width="48" height="48" loading="lazy" decoding="async">
          <span v-else class="absolute left-0 top-0.5 flex h-6 w-6 items-center justify-center rounded-[4px] bg-gold-50 text-gold-700 border border-gold-200/80" aria-hidden="true">
            <svg v-if="index === 0" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6" /><path d="m8 13-1 9 5-3 5 3-1-9" /><path d="m10 8 1.3 1.3L14 6.5" /></svg>
            <svg v-else-if="index === 1" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a5 5 0 0 0-6.4 6.4L3 18l3 3 5.3-5.3a5 5 0 0 0 6.4-6.4L14 13l-3-3z" /><path d="m15 3 6 6" /></svg>
            <svg v-else-if="index === 2" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 20 5v6c0 5-3.4 9-8 11-4.6-2-8-6-8-11V5z" /><path d="m8.5 12 2.2 2.2 4.8-5" /></svg>
            <svg v-else class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 10 9-7 9 7" /><path d="M5 9v12h14V9M9 21v-7h6v7M8 11h2m4 0h2" /></svg>
          </span>
          <strong class="block text-[15px] font-bold leading-[1.35]">{{ point.label }}</strong>
          <small v-if="point.description" class="mt-1 block text-[13.5px] leading-[1.55] text-slate-600">{{ point.description }}</small>
        </li>
      </ul>
    </div>
  </section>
</template>
