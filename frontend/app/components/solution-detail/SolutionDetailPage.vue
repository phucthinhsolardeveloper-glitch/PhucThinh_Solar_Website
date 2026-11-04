<script setup lang="ts">
import type { PublicSolutionDetailPayload } from '../../../shared/contracts/public-solution-detail'
import { buildEditorialTocForFragments } from '#shared/editorial-toc'
import SolutionDetailGallery from './SolutionDetailGallery.vue'
import SolutionsCta from '~/components/solutions/SolutionsCta.vue'
import SolutionsStory from '~/components/solutions/SolutionsStory.vue'
import { publicPhoneHref } from '#shared/contact-links'

const props = defineProps<{ data: PublicSolutionDetailPayload; hotline?: string }>()
const links = useMigrationLinks()
const { openConsultation } = useStorefrontModal()

function equipmentIcon(url: string, name: string): string {
  const haystack = `${url} ${name}`.toLowerCase()
  if (haystack.includes('pin-luu-tru') || haystack.includes('lưu trữ')) return 'i-lucide-battery-charging'
  if (haystack.includes('bien-tan') || haystack.includes('inverter')) return 'i-lucide-cpu'
  if (haystack.includes('den-') || haystack.includes('đèn')) return 'i-lucide-sun-dim'
  return 'i-lucide-sun'
}

function handleConsultClick(event: MouseEvent, href: string) {
  if (href === '#ptsContactModal' || href.includes('#yeu-cau-tu-van')) {
    event.preventDefault()
    openConsultation(`Giải pháp: ${props.data.solution.title}`)
  }
}
const editorial = computed(() => buildEditorialTocForFragments(
  [props.data.solution.content, props.data.solution.extraContent] as const,
  { levels: [2, 3], idPrefix: 'solution-section' },
))
const solutionContent = computed(() => editorial.value.contents[0] ?? props.data.solution.content)
const solutionExtraContent = computed(() => editorial.value.contents[1] ?? props.data.solution.extraContent)

const hotlineText = computed(() => props.data.campaignCta?.hotline?.text || (props.hotline ?? '').trim())
const hotlineHref = computed(() => publicPhoneHref(props.data.campaignCta?.hotline?.tel || hotlineText.value))
const hasContent = computed(() => Boolean(props.data.solution.content.html || props.data.solution.extraContent.html))

const heroRef = ref<HTMLElement | null>(null)
const solutionRailRef = ref<HTMLElement | null>(null)
useActiveHorizontalRail(solutionRailRef)
let frame = 0
function onScroll() {
  cancelAnimationFrame(frame)
  frame = requestAnimationFrame(() => {
    const el = heroRef.value
    if (!el) return
    const y = Math.min(Math.max(window.scrollY, 0), el.offsetHeight)
    el.style.setProperty('--shift', `${Math.round(y * 0.25)}px`)
  })
}

// Switcher: the navy pill flows to the chosen item (stretching across, then settling) before the page swaps.
const pillRef = ref<HTMLElement | null>(null)
const pending = ref('')
const direction = ref<'next' | 'prev'>('next')
const activeIndex = computed(() => props.data.navigation.items.findIndex((item) => item.active))
const currentSlug = computed(() => pending.value || props.data.solution.slug)
let reducedMotion = false

function placePill(animate: boolean) {
  const rail = solutionRailRef.value
  const pill = pillRef.value
  const target = rail?.querySelector<HTMLElement>(`[data-slug="${currentSlug.value}"]`)
  if (!rail || !pill || !target) { if (pill) pill.style.opacity = '0'; return }
  const from = { left: pill.offsetLeft, width: pill.offsetWidth }
  const to = { left: target.offsetLeft, width: target.offsetWidth }
  pill.style.opacity = '1'
  pill.style.left = `${to.left}px`
  pill.style.width = `${to.width}px`
  if (!animate || reducedMotion || !from.width || from.left === to.left) return
  const spanLeft = Math.min(from.left, to.left)
  const spanWidth = Math.max(from.left + from.width, to.left + to.width) - spanLeft
  pill.animate([
    { left: `${from.left}px`, width: `${from.width}px`, transform: 'scaleY(1)' },
    { left: `${spanLeft}px`, width: `${spanWidth}px`, transform: 'scaleY(0.78)', offset: 0.45 },
    { left: `${to.left}px`, width: `${to.width}px`, transform: 'scaleY(1.06)', offset: 0.82 },
    { left: `${to.left}px`, width: `${to.width}px`, transform: 'scaleY(1)' },
  ], { duration: 620, easing: 'cubic-bezier(0.65, 0, 0.35, 1)' })
}

function choose(slug: string, index: number) {
  if (slug === props.data.solution.slug) return
  direction.value = index >= activeIndex.value ? 'next' : 'prev'
  pending.value = slug
  nextTick(() => placePill(true))
}

watch(() => props.data.solution.slug, () => {
  pending.value = ''
  nextTick(() => placePill(false))
})

function onResize() { placePill(false) }
onMounted(() => {
  reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  placePill(false)
  window.addEventListener('resize', onResize, { passive: true })
  if (!reducedMotion) window.addEventListener('scroll', onScroll, { passive: true })
})
onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  window.removeEventListener('resize', onResize)
  cancelAnimationFrame(frame)
})
</script>

<template>
  <div>
    <article>
      <Transition :name="`swap-${direction}`" mode="out-in">
      <header ref="heroRef" :key="data.solution.slug" class="solution-hero" :class="{ 'has-image': data.solution.image }">
        <img v-if="data.solution.image" :src="data.solution.image" :alt="data.solution.title" class="solution-hero__image" width="1600" height="1000" fetchpriority="high">
        <span class="solution-hero__shade" aria-hidden="true" />
        <div class="pts-container solution-hero__inner">
          <a v-if="data.solution.category" class="solution-hero__category" :href="links.generic(data.navigation.allUrl)">{{ data.solution.category }}</a>
          <h1>{{ data.solution.title }}</h1>
          <p v-if="data.solution.summary" class="solution-hero__summary">{{ data.solution.summary }}</p>
          <div class="solution-hero__actions">
            <a class="solution-hero__primary" :href="links.generic(data.cta.href)" @click="handleConsultClick($event, data.cta.href)">
              {{ data.cta.label }}
              <span aria-hidden="true"><UIcon name="i-lucide-arrow-right" class="size-5" /></span>
            </a>
            <a v-if="hotlineHref" class="solution-hero__call" :href="hotlineHref">
              <UIcon name="i-lucide-phone-call" class="size-5 text-gold-300" aria-hidden="true" />
              <span><small>Gọi hotline</small>{{ hotlineText }}</span>
            </a>
            <a v-if="hasContent" class="solution-hero__more" href="#noi-dung-giai-phap">Tìm hiểu phương án <UIcon name="i-lucide-arrow-down" class="size-4" aria-hidden="true" /></a>
          </div>
        </div>
      </header>
      </Transition>

      <nav class="solution-rail" aria-label="Các giải pháp khác">
        <div ref="solutionRailRef" class="pts-container solution-rail__track">
          <span ref="pillRef" class="solution-rail__pill" aria-hidden="true" />
          <NuxtLink
            v-for="(item, index) in data.navigation.items"
            :key="item.slug"
            class="solution-rail__item"
            :data-slug="item.slug"
            :class="{ 'is-on': currentSlug === item.slug }"
            :aria-current="item.active ? 'page' : undefined"
            :to="links.generic(item.url)"
            @click="choose(item.slug, index)"
          >{{ item.title }}</NuxtLink>
        </div>
      </nav>

      <!--
        Tích hợp sanitizer: content/extraContent là HTML thật qua
        pts_public_richtext.php; SolutionsStory chỉ dựng lại bố cục theo h2/h3.
      -->
      <Transition name="story-swap" mode="out-in">
        <div id="noi-dung-giai-phap" :key="data.solution.slug" class="solution-story">
          <SolutionsStory v-if="hasContent" :contents="[solutionContent, solutionExtraContent]" :show-index="false" />
          <p v-else class="pts-container py-12 text-slate-500">Nội dung chi tiết đang được cập nhật.</p>
        </div>
      </Transition>

      <SolutionDetailGallery :images="data.solution.gallery" :title="data.solution.title" />
    </article>

    <section v-if="data.equipment.length" class="pts-container py-6 nav:py-8" aria-labelledby="solution-equipment-title">
      <header class="mb-5 flex flex-col items-start gap-4 min-[801px]:flex-row min-[801px]:items-end min-[801px]:justify-between">
        <div>
          <h2 id="solution-equipment-title" class="m-0 text-[clamp(22px,2.2vw,28px)] font-black tracking-tight text-navy-800">Thiết bị cho một hệ thống hoàn chỉnh</h2>
          <p class="m-0 mt-1 text-sm text-slate-600">Chọn nhóm thiết bị để xem danh mục sản phẩm và thông số kỹ thuật chi tiết.</p>
        </div>
        <UButton :href="links.generic('/san-pham/')" color="primary" variant="link" size="md" class="min-h-11 justify-start gap-1.5 rounded-none px-0 text-sm font-extrabold text-gold-700 no-underline hover:text-navy-800">
          <span>Xem tất cả sản phẩm</span>
          <span aria-hidden="true">→</span>
        </UButton>
      </header>
      <div class="grid grid-cols-1 gap-2 min-[521px]:grid-cols-2 min-[1101px]:grid-cols-4">
        <a
          v-for="item in data.equipment"
          :key="item.url"
          class="group flex min-w-0 items-center gap-3 px-3.5 py-2.5 pts-note text-navy-800 no-underline transition-colors hover:bg-slate-100 hover:text-gold-700"
          :href="links.generic(item.url)"
        >
          <img v-if="item.image" :src="item.image" alt="" class="h-12 w-12 flex-none object-contain transition-transform duration-300 group-hover:scale-105" width="48" height="48" loading="lazy">
          <span v-else class="grid h-12 w-12 flex-none place-items-center rounded-[4px] bg-navy-950 text-gold-400 transition-colors group-hover:bg-gold-500 group-hover:text-navy-950" aria-hidden="true">
            <UIcon :name="equipmentIcon(item.url, item.name)" class="size-5" />
          </span>
          <span class="min-w-0 flex-1 py-1">
            <strong class="block text-sm font-extrabold leading-snug text-navy-900 transition-colors group-hover:text-gold-700">{{ item.name }}</strong>
            <small v-if="item.description" class="mt-1 block line-clamp-2 text-xs leading-relaxed text-slate-600">{{ item.description }}</small>
          </span>
          <span class="flex-none font-bold text-gold-700 transition-transform group-hover:translate-x-1 motion-reduce:transform-none" aria-hidden="true">→</span>
        </a>
      </div>
    </section>

    <div class="pts-container pb-12">
      <SolutionsCta v-if="data.campaignCta" :cta="data.campaignCta" :subject="`Giải pháp: ${data.solution.title}`" />
    </div>
  </div>
</template>

<style scoped>
/* One fixed hero height for every solution so switching never resizes the page. */
.solution-hero { --ease: cubic-bezier(0.22, 1, 0.36, 1); position: relative; isolation: isolate; display: flex; height: clamp(560px, 72vh, 700px); align-items: flex-end; overflow: hidden; background: #061537; color: #fff; }
.solution-hero__image { position: absolute; inset: -8% 0 0; z-index: -2; width: 100%; height: 108%; object-fit: cover; transform: translate3d(0, var(--shift, 0), 0) scale(1.04); animation: hero-in 1.1s var(--ease) both; }
.solution-hero__shade { position: absolute; inset: 0; z-index: -1; background: linear-gradient(90deg, rgb(6 21 55 / 92%) 0%, rgb(6 21 55 / 70%) 42%, rgb(6 21 55 / 20%) 100%), linear-gradient(0deg, #061537 0%, rgb(6 21 55 / 0%) 45%); }
.solution-hero:not(.has-image) .solution-hero__shade { background: radial-gradient(60% 90% at 85% 10%, rgb(228 168 73 / 20%), transparent 70%); }
.solution-hero__inner { width: 100%; padding-block: 32px clamp(36px, 4.5vw, 64px); }
.solution-hero__category { display: inline-flex; min-height: 36px; align-items: center; margin-bottom: 18px; padding: 0 14px; border: 1px solid rgb(228 168 73 / 45%); border-radius: 999px; color: var(--color-gold-200); font-size: 14px; font-weight: 700; text-decoration: none; }
.solution-hero__category:hover { background: rgb(228 168 73 / 14%); }
.solution-hero h1 { max-width: 16ch; margin: 0; color: #fff; font-size: clamp(36px, 4vw, 66px); font-weight: 850; line-height: 1.1; letter-spacing: -0.04em; text-wrap: balance; }
.solution-hero__summary { display: -webkit-box; max-width: 62ch; margin: 22px 0 0; overflow: hidden; -webkit-box-orient: vertical; -webkit-line-clamp: 4; color: #d3dcea; font-size: clamp(17px, 1.25vw, 19px); line-height: 1.68; }
.solution-hero__actions { display: flex; flex-wrap: wrap; align-items: center; gap: 14px 28px; margin-top: 32px; }
.solution-hero__primary { display: inline-flex; min-height: 58px; align-items: center; gap: 16px; padding: 0 10px 0 24px; border-radius: 6px; background: var(--pts-gold-gradient); color: var(--color-navy-950); font-size: 16.5px; font-weight: 850; text-decoration: none; transition: filter 0.25s ease; }
.solution-hero__primary > span { display: grid; width: 40px; height: 40px; place-items: center; border-radius: 4px; background: var(--color-navy-950); color: var(--color-gold-300); transition: transform 0.35s var(--ease); }
.solution-hero__primary:hover { filter: brightness(1.06); }
.solution-hero__primary:hover > span { transform: translateX(4px); }
.solution-hero__call { display: inline-flex; min-height: 52px; align-items: center; gap: 12px; color: #fff; font-size: 20px; font-weight: 850; text-decoration: none; font-variant-numeric: tabular-nums; }
.solution-hero__call small { display: block; color: #9fb0c8; font-size: 13px; font-weight: 600; }
.solution-hero__more { display: inline-flex; min-height: 44px; align-items: center; gap: 8px; color: #d3dcea; font-size: 15.5px; font-weight: 700; text-decoration: none; }
.solution-hero__more:hover { color: var(--color-gold-300); }
.solution-hero a:focus-visible { outline: 3px solid var(--color-gold-300); outline-offset: 3px; }

.solution-rail { position: relative; z-index: 2; border-bottom: 1px solid rgb(10 34 81 / 10%); background: #fff; }
.solution-rail__track { position: relative; display: flex; gap: 6px; overflow-x: auto; padding-block: 10px; scrollbar-width: none; }
.solution-rail__pill { position: absolute; top: 10px; left: 0; z-index: 0; width: 0; height: 44px; border-radius: 999px; background: var(--color-navy-950); box-shadow: 0 10px 24px -14px rgb(6 21 55 / 70%); opacity: 0; pointer-events: none; transform-origin: center; }
.solution-rail__track::-webkit-scrollbar { display: none; }
.solution-rail__item { position: relative; z-index: 1; display: inline-flex; min-height: 44px; flex: none; align-items: center; gap: 8px; padding: 0 16px; border-radius: 999px; color: var(--color-navy-800); font-size: 15px; font-weight: 700; white-space: nowrap; text-decoration: none; transition: background-color 0.25s ease, color 0.25s ease; }
.solution-rail__item:not(.is-on):hover { background: #eef2f8; }
.solution-rail__item.is-on { color: var(--color-gold-300); transition-delay: 0.18s; }
.solution-rail__item:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 2px; }
.solution-story :deep(.story-index) { margin-top: 0; }

@keyframes hero-in { from { opacity: 0; transform: scale(1.08); } }

/* Page swap: content slides out toward where it came from and the next solution flows in. */
.swap-next-leave-active, .swap-prev-leave-active { transition: opacity 0.26s ease, transform 0.26s cubic-bezier(0.55, 0, 1, 0.45), filter 0.26s ease; }
.swap-next-enter-active, .swap-prev-enter-active { transition: opacity 0.5s ease, transform 0.6s cubic-bezier(0.22, 1, 0.36, 1), filter 0.5s ease; }
.swap-next-leave-to { opacity: 0; transform: translateX(-4%); filter: blur(6px); }
.swap-next-enter-from { opacity: 0; transform: translateX(5%); filter: blur(8px); }
.swap-prev-leave-to { opacity: 0; transform: translateX(4%); filter: blur(6px); }
.swap-prev-enter-from { opacity: 0; transform: translateX(-5%); filter: blur(8px); }
.story-swap-leave-active { transition: opacity 0.2s ease; }
.story-swap-enter-active { transition: opacity 0.5s ease 0.1s, transform 0.6s cubic-bezier(0.22, 1, 0.36, 1) 0.1s; }
.story-swap-leave-to { opacity: 0; }
.story-swap-enter-from { opacity: 0; transform: translateY(24px); }
@media (max-width: 640px) {
  .solution-hero { height: 660px; }
  .solution-hero h1 { font-size: 34px; }
  .solution-hero__summary { -webkit-line-clamp: 5; }
  .solution-hero__shade { background: linear-gradient(0deg, #061537 8%, rgb(6 21 55 / 78%) 55%, rgb(6 21 55 / 40%) 100%); }
  .solution-hero__actions { align-items: stretch; flex-direction: column; }
  .solution-hero__primary { justify-content: space-between; }
}
@media (prefers-reduced-motion: reduce) {
  .solution-hero__image { animation: none; transform: none; }
  .swap-next-enter-active, .swap-prev-enter-active, .swap-next-leave-active, .swap-prev-leave-active, .story-swap-enter-active, .story-swap-leave-active { transition: opacity 0.2s ease; }
  .swap-next-enter-from, .swap-prev-enter-from, .swap-next-leave-to, .swap-prev-leave-to, .story-swap-enter-from { transform: none; filter: none; }
  .solution-hero__primary > span { transition: none; }
}
</style>
