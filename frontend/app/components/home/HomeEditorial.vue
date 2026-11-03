<script setup lang="ts">
import type { PublicHomeAboutPoint, PublicHomePayload } from '#shared/contracts/public-home'
import DirectorStage, { type DirectorStageItem } from '~/components/home/DirectorStage.vue'
import HomeProcess from '~/components/home/HomeProcess.vue'
import HomeDealer from '~/components/home/HomeDealer.vue'

const props = defineProps<{ data: PublicHomePayload }>()
const solutionChips = [
  ['Điện mặt trời mái nhà', 'Biến tần (Inverter)', 'Tấm pin điện mặt trời'],
  ['Điện mặt trời mái nhà', 'Biến tần (Inverter)', 'Pin lưu trữ'],
  ['Đèn năng lượng mặt trời', 'Trụ đèn và phụ kiện'],
  ['Bơm nước năng lượng mặt trời', 'Tấm pin điện mặt trời'],
]

const activeSolution = ref(0)
const solutionItems = computed<DirectorStageItem[]>(() => props.data.solutions.map((solution) => ({
  key: solution.label + solution.href,
  label: solution.label,
  title: solution.name,
  description: solution.description,
  image: editorialImageCandidate(solution.image, 1200, 750),
  href: solution.href,
  linkLabel: 'Xem giải pháp',
})))
const links = useMigrationLinks()
const leadIntent = useState<string>('public-home-lead-intent', () => '')
const phpResizableEditorialImage = /^\/(?:upload\/news|assets\/images\/site-visuals)\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png)$/i

function editorialImageCandidate(src: string, width: 760 | 1200, height: 475 | 750): string {
  if (!phpResizableEditorialImage.test(src)) return src
  return `/thumbs/${width}x${height}x1${src}`
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
</script>

<template>
  <section
    v-if="data.solutions.length"
    id="giai-phap"
    class="py-[clamp(64px,7vw,112px)]"
    aria-labelledby="home-solutions-title"
  >
    <div class="pts-container">
      <DirectorStage v-model:active="activeSolution" :items="solutionItems" list-label="Giải pháp theo nhu cầu" reverse :interval="7000">
        <template #head>
          <h2 id="home-solutions-title" class="m-0 max-w-[14ch] text-[clamp(30px,3vw,50px)] font-black leading-[1.06] tracking-[-.035em] text-navy-950">
            Giải pháp cho từng nhu cầu
          </h2>
          <p class="mt-4 max-w-[40ch] text-[clamp(15px,1.05vw,18px)] leading-[1.65] text-slate-600">
            Chọn lĩnh vực sử dụng để xem phương án và thiết bị đi kèm.
          </p>
        </template>
        <template #caption="{ index }">
          <span v-if="solutionChips[index]?.length" class="mt-1 flex flex-wrap gap-2" aria-label="Thiết bị liên quan">
            <a
              v-for="chip in solutionChips[index]"
              :key="chip"
              class="inline-flex min-h-[34px] items-center rounded-[3px] border border-white/30 bg-white/10 px-3 text-[13px] font-semibold text-white no-underline transition-colors hover:border-gold-400 hover:bg-gold-400 hover:text-navy-950"
              :href="links.generic(chipHref(chip, data.solutions[index]!))"
            >{{ chip }}</a>
          </span>
        </template>
        <template #foot="{ item }">
          <a
            class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-[4px] bg-navy-950 px-6 text-[15px] font-extrabold text-white no-underline transition-colors hover:bg-gold-500 hover:text-navy-950"
            href="#yeu-cau-tu-van"
            @click="requestConsultation(item.title)"
          >
            Nhận tư vấn giải pháp này <span aria-hidden="true">→</span>
          </a>
        </template>
      </DirectorStage>
    </div>
  </section>

  <HomeProcess :steps="data.process" />

  <HomeDealer :dealer="data.dealer" />

  <!-- About Section (Architectural Monograph Style) -->
  <section v-if="!data.about.configured || data.about.visible" class="py-[clamp(56px,5vw,84px)] pb-[clamp(44px,4vw,64px)]" aria-labelledby="home-about-title">
    <div class="pts-container grid grid-cols-[minmax(0,1.15fr)_minmax(360px,.85fr)] items-start gap-[clamp(40px,5vw,80px)] max-[860px]:grid-cols-1">
      <div>
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
