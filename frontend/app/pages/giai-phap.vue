<script setup lang="ts">
import type { PublicSolutionsPayload } from '#shared/contracts/public-solutions'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { SOLUTIONS_PUBLIC_PATH } from '#shared/public-route-gate'
import SolutionCards from '~/components/solutions/SolutionCards.vue'
import SolutionsEquipment from '~/components/solutions/SolutionsEquipment.vue'
import SolutionsCta from '~/components/solutions/SolutionsCta.vue'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'
import GridSurface from '~/components/experience-v3/GridSurface.vue'
import EnergyModeSwitch, { type EnergyMode } from '~/components/experience-v3/EnergyModeSwitch.vue'
import EnergySceneStage from '~/components/experience-v3/EnergySceneStage.vue'
import EnergyInspector, { type Device, type DeviceId } from '~/components/experience-v3/EnergyInspector.vue'
import { editorialPublicHubResponseStatus } from '#shared/editorial-public-route'

definePageMeta({
  layout: false,
  middleware: 'solutions-public-route',
})

const config = useRuntimeConfig()
const route = useRoute()
const isHubRoute = computed(() => route.path === SOLUTIONS_PUBLIC_PATH || route.path === SOLUTIONS_PUBLIC_PATH.slice(0, -1))
const indexable = computed(() => isHubRoute.value && config.public.solutionsPublicRouteIndexable === true && !!data.value && !error.value)

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)

const { data, error } = await useAsyncData<PublicSolutionsPayload | null>(
  'public-solutions',
  () => isHubRoute.value ? $fetch('/_migration/solutions') : Promise.resolve(null),
  { watch: [isHubRoute] },
)
const responseStatus = editorialPublicHubResponseStatus(
  isHubRoute.value,
  (error.value as { statusCode?: unknown } | null)?.statusCode,
  !data.value,
)
if (import.meta.server && responseStatus) setResponseStatus(responseStatus, responseStatus === 404 ? 'Not Found' : 'Bad Gateway')

const pageTitle = computed(() => data.value?.seo?.title || data.value?.title || 'Giải pháp — Phúc Thịnh Solar')
const pageDescription = computed(() => data.value?.seo?.description || data.value?.intro?.description || '')
const pageKeywords = computed(() => data.value?.seo?.keywords || '')
const pageImage = computed(() => data.value?.seo?.image || data.value?.intro?.image || undefined)
const links = useMigrationLinks()
useSeoMeta({
  title: computed(() => isHubRoute.value ? pageTitle.value : undefined),
  description: computed(() => isHubRoute.value ? pageDescription.value : undefined),
  robots: computed(() => isHubRoute.value ? (indexable.value && !Object.keys(route.query).length ? 'index, follow' : 'noindex, nofollow, noarchive') : undefined),
})
useHead({ meta: computed(() => isHubRoute.value && pageKeywords.value ? [{ name: 'keywords', content: pageKeywords.value }] : []) })
usePublicSocialMeta({
  enabled: computed(() => isHubRoute.value && !!data.value && !error.value),
  title: pageTitle,
  description: pageDescription,
  canonicalPath: '/giai-phap/',
  image: pageImage,
})

const mode = ref<EnergyMode>('day')
const withBattery = ref(true)
const selected = ref<DeviceId>('inverter')
const devices: Device[] = [
  { id: 'pv', title: 'Mái pin mặt trời', icon: 'i-lucide-sun' },
  { id: 'inverter', title: 'Inverter', icon: 'i-lucide-cpu' },
  { id: 'battery', title: 'Pin lưu trữ', icon: 'i-lucide-battery-charging' },
  { id: 'grid', title: 'Điện lưới', icon: 'i-lucide-utility-pole' },
  { id: 'load', title: 'Ngôi nhà & phụ tải', icon: 'i-lucide-house' },
]

function changeBattery(next: boolean) {
  withBattery.value = next
  if (!next && mode.value === 'backup') mode.value = 'night'
  if (!next && selected.value === 'battery') selected.value = 'inverter'
}

const scenario = computed(() => {
  if (mode.value === 'day') return {
    title: 'Ánh nắng đi vào hệ thống.',
    body: 'PV tạo điện DC. Inverter chuyển đổi để cấp cho phụ tải theo cấu hình minh họa.',
    route: 'Mái pin → Inverter → Ngôi nhà',
    state: 'Nắng trên mái',
  }
  if (mode.value === 'night') return withBattery.value ? {
    title: 'Khi trời tối, pin có thể tiếp sức.',
    body: 'Trong kịch bản này, pin cấp điện qua inverter. Khả năng cấp điện thực tế phụ thuộc trạng thái pin và cấu hình hệ thống.',
    route: 'Pin lưu trữ → Inverter → Ngôi nhà',
    state: 'Nguồn lưu trữ',
  } : {
    title: 'Không có pin, lưới là nguồn minh họa.',
    body: 'Luồng điện lưới đi qua inverter trước khi tới phụ tải. Hệ thống thực tế có thể vận hành khác theo thiết kế.',
    route: 'Điện lưới → Inverter → Ngôi nhà',
    state: 'Nguồn điện lưới',
  }
  return {
    title: 'Nguồn dự phòng cần một cấu hình riêng.',
    body: 'Pin cấp điện qua inverter/ngõ backup phù hợp tới tải ưu tiên. Mất lưới không đồng nghĩa toàn bộ ngôi nhà luôn được cấp điện.',
    route: 'Pin lưu trữ → Inverter backup → Tải ưu tiên',
    state: 'Tải ưu tiên',
  }
})

const activePathIds = computed(() => {
  if (mode.value === 'day') return ['solar', 'home']
  if (mode.value === 'night' && !withBattery.value) return ['grid', 'home']
  if (mode.value === 'night') return ['discharge', 'home']
  return ['discharge', 'home']
})

const deviceCopy = computed<Record<DeviceId, { role: string; note: string }>>(() => {
  const currentMode = mode.value
  return {
    pv: currentMode === 'day'
      ? { role: 'Đang nhận nắng', note: 'Các tấm PV chuyển ánh sáng thành điện một chiều (DC) khi có nắng.' }
      : { role: 'Không hoạt động', note: 'Không có ánh nắng để chuyển đổi; mái pin ở trạng thái nghỉ trong minh họa này.' },
    inverter: { role: 'Trung tâm chuyển đổi', note: 'Inverter điều phối và chuyển đổi điện theo chức năng của hệ thống minh họa.' },
    battery: !withBattery.value
      ? { role: 'Không có trong cấu hình', note: 'Minh họa này không có pin lưu trữ.' }
      : currentMode === 'day'
        ? { role: 'Chờ hoặc sạc tùy kịch bản', note: 'Pin có thể lưu điện dư khi hệ thống minh họa cho phép.' }
        : currentMode === 'night'
          ? { role: 'Đang cấp điện (minh họa)', note: 'Pin cấp điện qua inverter trong kịch bản ban đêm có pin.' }
          : { role: 'Cấp tải ưu tiên (minh họa)', note: 'Pin cấp điện qua ngõ backup tới tải ưu tiên, không phải toàn bộ nhà.' },
    grid: currentMode === 'night' && !withBattery.value
      ? { role: 'Nguồn bổ sung (minh họa)', note: 'Lưới cấp cho phụ tải qua inverter trong kịch bản này.' }
      : currentMode === 'backup'
        ? { role: 'Bị cách ly', note: 'Minh họa cách ly điện lưới khi mất điện; không biểu diễn phát ngược lên lưới.' }
        : { role: 'Không là nguồn chính', note: 'Nguồn khác đang được minh họa làm nguồn chính ở trạng thái này.' },
    load: currentMode === 'backup'
      ? { role: 'Chỉ tải ưu tiên', note: 'Khi mất lưới, chỉ tải ưu tiên được thiết kế đúng mới có thể được cấp điện trong minh họa.' }
      : { role: 'Nhận điện bình thường', note: 'Thiết bị trong nhà nhận điện qua hệ thống phân phối minh họa.' },
  }
})

const tocContainerRef = ref<HTMLElement | null>(null)
const { activeHeadingId, scrollToHeading } = useTocScrollspy({
  contentSelector: '.cms-content',
  headingSelector: 'h2[id], h3[id]',
  tocContainerRef,
  headerOffset: 100,
})
</script>

<template>
  <NuxtPage v-if="!isHubRoute" />

  <div v-else class="solutions-energy-page text-navy-800">
    <SiteHeader :site="site" />

    <div class="pts-container">
      <SiteBreadcrumb :items="[{ label: 'Giải pháp' }]" />
    </div>

    <main class="pts-container solution-main pb-6">
      <div v-if="error || !data" class="min-h-[45vh] py-12">
        <h1 class="mb-4 text-[clamp(28px,4vw,42px)] font-black tracking-tight">Giải pháp</h1>
        <p class="max-w-[62ch] leading-relaxed text-slate-500">Không thể tải dữ liệu PHP DTO trong môi trường hiện tại.</p>
      </div>

      <template v-else>
        <GridSurface :size="32">
          <section class="solution-energy-intro" aria-labelledby="solutions-title">
            <div class="solution-energy-intro-copy">
              <h1 id="solutions-title">{{ data.intro?.title || data.title }}</h1>
              <p v-if="data.intro?.description">{{ data.intro.description }}</p>
              <div class="solution-intro-links">
                <a v-if="data.intro" class="solution-primary-link" :href="links.generic(data.intro.links.consult.href === '#ptsContactModal' ? '/lien-he/#yeu-cau-tu-van' : data.intro.links.consult.href)">{{ data.intro.links.consult.label }} <span aria-hidden="true">→</span></a>
                <a v-if="data.intro" class="solution-secondary-link" :href="links.generic(data.intro.links.explore.href)">{{ data.intro.links.explore.label }} <span aria-hidden="true">↓</span></a>
              </div>
            </div>
            <figure v-if="data.intro?.image" class="solution-energy-intro-image">
              <img :src="data.intro.image" :alt="data.intro.title" width="1200" height="750" fetchpriority="high">
            </figure>
          </section>
        </GridSurface>

        <section class="solution-energy-stage" aria-labelledby="energy-stage-title">
          <div class="solution-stage-heading">
            <div>
              <h2 id="energy-stage-title">Khám phá dòng năng lượng</h2>
              <p>Chuyển trạng thái để xem các nguồn và phụ tải thay đổi trong minh họa.</p>
            </div>
            <label class="solution-battery-toggle">
              <input type="checkbox" :checked="withBattery" @change="changeBattery(($event.target as HTMLInputElement).checked)">
              <span class="solution-toggle-track" aria-hidden="true"><i /></span>
              <span>Có pin lưu trữ</span>
            </label>
          </div>
          <EnergyModeSwitch v-model="mode" :with-battery="withBattery" />
          <div class="solution-stage-layout">
            <EnergySceneStage :mode="mode" :with-battery="withBattery" :selected="selected" :active-path-ids="activePathIds" @select="selected = $event" />
            <EnergyInspector :mode="mode" :with-battery="withBattery" :devices="devices" :selected="selected" :scenario="scenario" :device-copy="deviceCopy" @select="selected = $event" />
          </div>
        </section>

        <SolutionCards :cards="data.cards" :choices-label="data.labels.choices" :empty-label="data.labels.empty" :consult-label="data.labels.consult" />

        <section v-if="data.editorial.content.html" class="pb-[54px]" aria-label="Nội dung tư vấn giải pháp">
          <div
            class="grid gap-8 nav:gap-12"
            :class="data.editorial.toc.rendered ? 'nav:grid-cols-[270px_minmax(0,1fr)]' : ''"
          >
            <nav
              v-if="data.editorial.toc.rendered"
              ref="tocContainerRef"
              class="h-fit max-h-[220px] overflow-y-auto overscroll-contain rounded-[3px] bg-navy-800 p-5 shadow-sm nav:sticky nav:top-24 nav:max-h-[calc(100vh-116px)]"
              aria-label="Mục lục nội dung giải pháp"
            >
              <strong class="mb-3 block border-b border-white/12 pb-2.5 text-[13px] font-extrabold uppercase tracking-wider text-gold-300">Trong trang này</strong>
              <ul class="m-0 list-none space-y-1 p-0">
                <li v-for="item in data.editorial.toc.items" :key="item.id" :class="item.level === 3 ? 'pl-3' : ''">
                  <a
                    :data-toc-id="item.id"
                    :href="'#' + item.id"
                    class="flex min-h-11 items-center border-l-2 py-1 pl-3 text-[13px] leading-[1.45] no-underline transition-all duration-200"
                    :class="[
                      item.level === 2 ? 'font-bold' : 'font-semibold',
                      activeHeadingId === item.id
                        ? 'border-[#e4a849] bg-white/10 text-white font-extrabold rounded-r'
                        : 'border-transparent text-[#c9d5e6] hover:border-gold-400 hover:text-white hover:bg-white/5',
                    ]"
                    :aria-current="activeHeadingId === item.id ? 'true' : undefined"
                    @click.prevent="scrollToHeading(item.id)"
                  >
                    {{ item.label }}
                  </a>
                </li>
              </ul>
            </nav>
            <EditorialRichText :content="data.editorial.content" variant="solution" />
          </div>
        </section>

        <SolutionsEquipment :equipment="data.equipment" :title="data.labels.equipment" :all-products-url="data.allProductsUrl" :all-products-label="data.labels.allProducts" />
        <SolutionsCta v-if="data.cta" :cta="data.cta" />
      </template>
    </main>

    <SiteFooter :site="site" />
  </div>
</template>

<style scoped>
.solutions-energy-page { overflow: clip; }
.solution-main { padding-top: 4px; }
.solution-energy-intro { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(280px, .9fr); align-items: center; gap: clamp(24px, 5vw, 72px); padding: clamp(32px, 5vw, 68px) 0 clamp(30px, 4vw, 54px); }
.solution-energy-intro-copy { max-width: 700px; }
.solution-energy-intro h1 { margin: 0; color: #071b46; font-size: clamp(36px, 5vw, 64px); font-weight: 750; line-height: 1.06; letter-spacing: -.04em; text-wrap: balance; }
.solution-energy-intro-copy > p { max-width: 60ch; margin: 20px 0 0; color: #52627a; font-size: clamp(16px, 1.35vw, 18px); line-height: 1.75; }
.solution-intro-links { display: flex; flex-wrap: wrap; align-items: center; gap: 18px 24px; margin-top: 24px; }
.solution-primary-link, .solution-secondary-link { display: inline-flex; min-height: 44px; align-items: center; gap: 12px; font-weight: 750; text-decoration: none; }
.solution-primary-link { padding: 10px 18px; border-radius: 4px; background: #c9962e; color: #071b46; }
.solution-primary-link:hover { background: #ddb553; }
.solution-secondary-link { color: #0a2251; }
.solution-energy-intro-image { justify-self: end; width: 100%; max-width: 440px; aspect-ratio: 8 / 5; margin: 0; overflow: hidden; border-radius: 3px; }
.solution-energy-intro-image img { display: block; width: 100%; height: 100%; object-fit: cover; }
.solution-energy-stage { margin: 8px 0 18px; padding: clamp(16px, 2.4vw, 30px); background: linear-gradient(155deg, #0a2251 0%, #061537 100%); color: #f4f0e4; }
.solution-stage-heading { display: flex; align-items: end; justify-content: space-between; gap: 18px; margin-bottom: 18px; }
.solution-stage-heading h2 { margin: 0; color: #f4f0e4; font-size: clamp(24px, 2.7vw, 34px); line-height: 1.15; letter-spacing: -.025em; }
.solution-stage-heading p { margin: 8px 0 0; color: #bac8da; line-height: 1.6; }
.solution-battery-toggle { display: inline-flex; min-height: 44px; flex: none; align-items: center; gap: 10px; color: #e8edf4; font-size: 13px; font-weight: 650; cursor: pointer; }
.solution-battery-toggle input { position: absolute; width: 1px; height: 1px; opacity: 0; }
.solution-toggle-track { position: relative; width: 34px; height: 19px; border: 1px solid #8190a2; border-radius: 99px; background: #46566b; transition: background .18s ease; }
.solution-toggle-track i { position: absolute; top: 3px; left: 3px; width: 11px; height: 11px; border-radius: 50%; background: #e8edf4; transition: transform .18s ease; }
.solution-battery-toggle input:checked + .solution-toggle-track { border-color: #e0b654; background: #9c7420; }
.solution-battery-toggle input:checked + .solution-toggle-track i { transform: translateX(14px); }
.solution-battery-toggle input:focus-visible + .solution-toggle-track { outline: 2px solid #f2d281; outline-offset: 3px; }
.solution-stage-layout { display: grid; grid-template-columns: minmax(0, 1.65fr) minmax(260px, .8fr); gap: 0 26px; margin-top: 16px; }
.solution-stage-layout :deep(.scene-stage) { border-radius: 3px; }
.solution-stage-layout :deep(.inspector) { padding-top: 0; }
.solution-energy-page :deep(.mode-switch) { max-width: 590px; }
.solution-energy-page :deep(.mode-switch-button) { min-height: 52px; }
.solution-energy-page :deep(.scene-mood-caption) { bottom: 10px; left: 12px; font-size: 11px; }
@media (max-width: 900px) {
  .solution-energy-intro { grid-template-columns: 1fr; gap: 24px; }
  .solution-energy-intro-image { justify-self: start; max-width: 620px; }
  .solution-stage-heading { align-items: start; flex-direction: column; }
  .solution-stage-layout { grid-template-columns: 1fr; gap: 22px; }
  .solution-stage-layout :deep(.inspector) { padding-top: 0; }
}
@media (max-width: 520px) {
  .solution-main { padding-top: 0; }
  .solution-energy-intro { padding: 28px 0 30px; }
  .solution-energy-intro h1 { font-size: clamp(33px, 10vw, 42px); }
  .solution-intro-links { align-items: stretch; flex-direction: column; gap: 6px; }
  .solution-primary-link, .solution-secondary-link { justify-content: center; }
  .solution-energy-stage { margin-right: -4px; margin-left: -4px; padding: 14px 12px 18px; }
  .solution-stage-layout { gap: 14px; }
}
</style>
