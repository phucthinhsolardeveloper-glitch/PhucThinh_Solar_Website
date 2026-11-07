<script setup lang="ts">
import type { PublicHomePayload } from '#shared/contracts/public-home'
import DirectorStage, { type DirectorStageItem } from '~/components/home/DirectorStage.vue'
import HomeProcess from '~/components/home/HomeProcess.vue'
import HomeDealer from '~/components/home/HomeDealer.vue'
import HomeAbout from '~/components/home/HomeAbout.vue'

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
  imageSrcSet: editorialImageSrcSet(solution.image),
  imageSizes: '(min-width: 961px) 60vw, 100vw',
  href: solution.href,
  linkLabel: 'Xem giải pháp',
})))
const links = usePublicLinks()
const leadIntent = useState<string>('public-home-lead-intent', () => '')
const phpResizableEditorialImage = /^\/(?:upload\/news\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|webp)|assets\/images\/site-visuals\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png))$/i

function editorialImageCandidate(src: string, width: 760 | 1200, height: 475 | 750): string {
  if (!phpResizableEditorialImage.test(src)) return src
  return phpThumb(src, width, height)
}

function editorialImageSrcSet(src: string): string | undefined {
  if (!phpResizableEditorialImage.test(src)) return undefined
  return `${editorialImageCandidate(src, 760, 475)} 760w, ${editorialImageCandidate(src, 1200, 750)} 1200w`
}

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
    class="py-[clamp(64px,7vw,112px)] max-tb:py-8"
    aria-labelledby="home-solutions-title"
  >
    <div class="pts-container">
      <DirectorStage v-model:active="activeSolution" :items="solutionItems" list-label="Giải pháp theo nhu cầu" reverse :interval="7000">
        <template #head>
          <h2 id="home-solutions-title" class="m-0 max-w-[14ch] text-[clamp(30px,3vw,50px)] font-black leading-[1.06] tracking-[-.035em] text-navy-950">
            Giải pháp cho từng nhu cầu
          </h2>
          <p class="mt-4 max-tb:mt-2 max-w-[40ch] text-[clamp(15px,1.05vw,18px)] leading-[1.65] text-slate-600">
            Chọn lĩnh vực sử dụng để xem phương án và thiết bị đi kèm.
          </p>
        </template>
        <template #caption="{ index }">
          <span v-if="solutionChips[index]?.length" class="mt-1 flex flex-wrap gap-2" aria-label="Thiết bị liên quan">
            <a
              v-for="chip in solutionChips[index]"
              :key="chip"
              class="inline-flex min-h-[34px] max-tb:min-h-11 items-center rounded-chip border border-white/30 bg-white/10 px-3 text-[13px] font-semibold text-white no-underline transition-colors hover:border-gold-400 hover:bg-gold-400 hover:text-navy-950"
              :href="links.generic(chipHref(chip, data.solutions[index]!))"
            >{{ chip }}</a>
          </span>
        </template>
        <template #foot="{ item }">
          <a
            class="inline-flex min-h-[52px] max-tb:w-full items-center justify-center gap-2 rounded-btn bg-navy-950 px-6 text-[15px] font-extrabold text-white no-underline transition-colors hover:bg-gold-500 hover:text-navy-950"
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

  <HomeAbout :about="data.about" />
</template>
