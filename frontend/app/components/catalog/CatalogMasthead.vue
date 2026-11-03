<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'
import type { PublicCatalogHero, PublicCatalogProduct } from '#shared/contracts/public-catalog'
import CatalogMenuIcon from '~/components/CatalogMenuIcon.vue'

export interface CatalogMastheadBrand { id: number; name: string; to: RouteLocationRaw; active: boolean }

const props = withDefaults(defineProps<{
  heading: string
  description: string
  hero: PublicCatalogHero | null
  products?: PublicCatalogProduct[]
  total?: number | null
  brands?: CatalogMastheadBrand[]
}>(), { products: () => [], total: null, brands: () => [] })

const links = useMigrationLinks()
const phpResizableCatalogImage = /^\/upload\/(?:product|news|photo)\/[A-Za-z0-9][A-Za-z0-9._/-]*\.(?:jpe?g|png|webp)$/i

function catalogHeroCandidate(src: string, width: 960 | 1800): string {
  if (!phpResizableCatalogImage.test(src)) return src
  const height = Math.round(width / 3)
  return `/thumbs/${width}x${height}x1${src}`
}

function catalogHeroSrcSet(src: string): string | undefined {
  if (!phpResizableCatalogImage.test(src)) return undefined
  return `${catalogHeroCandidate(src, 960)} 960w, ${catalogHeroCandidate(src, 1800)} 1800w`
}

// Same oversized-source fallback as CatalogProductGrid.
const oversized: Record<string, string> = {
  '/upload/product/t-bat-tsys-d53-2-4295.png': '/assets/images/site-visuals/media-fallbacks/brand-tbat-tsys-d53.webp',
}
const failed = ref<Set<number>>(new Set())
function productThumb(src: string): string {
  const source = oversized[src] || src
  return source.startsWith('/upload/product/') ? `/thumbs/480x480x1${source}` : source
}
// Prefer transparent PNG/WebP cutouts and spread picks (first, middle, last) across distinct models.
const showcase = computed(() => {
  const all = props.products.filter((product) => product.media[0] && !failed.value.has(product.id))
  const cutouts = all.filter((product) => /\.(?:png|webp)$/i.test(product.media[0] || ''))
  const pool = cutouts.length >= 3 ? cutouts : all
  if (pool.length <= 3) return pool
  const picks = [0, Math.floor(pool.length / 2), pool.length - 1]
  return picks.map((index) => pool[index]!)
})
function hideProduct(id: number) { failed.value = new Set(failed.value).add(id) }

const heroFailed = ref(false)
function onHeroError() { heroFailed.value = true }
watch(() => props.hero?.image, () => { heroFailed.value = false })

const brandCount = computed(() => props.brands.length)
const visibleBrands = computed(() => props.brands.slice(0, 6))

// Pointer parallax: products drift against the cursor; disabled for reduced motion and touch.
const stageRef = ref<HTMLElement | null>(null)
const tilt = reactive({ x: 0, y: 0 })
let frame = 0
function onPointerMove(event: PointerEvent) {
  if (event.pointerType !== 'mouse' || !stageRef.value) return
  const rect = stageRef.value.getBoundingClientRect()
  cancelAnimationFrame(frame)
  frame = requestAnimationFrame(() => {
    tilt.x = ((event.clientX - rect.left) / rect.width - 0.5) * 2
    tilt.y = ((event.clientY - rect.top) / rect.height - 0.5) * 2
  })
}
function onPointerLeave() { cancelAnimationFrame(frame); tilt.x = 0; tilt.y = 0 }
onBeforeUnmount(() => cancelAnimationFrame(frame))
</script>

<template>
  <div class="w-full border-b border-slate-200/80">
    <section class="masthead" aria-labelledby="catalog-page-heading" @pointermove="onPointerMove" @pointerleave="onPointerLeave">
      <img
        v-if="hero?.image && !heroFailed"
        :src="catalogHeroCandidate(hero.image, 960)"
        :srcset="catalogHeroSrcSet(hero.image)"
        alt=""
        width="1800"
        height="600"
        sizes="100vw"
        class="masthead-photo"
        fetchpriority="high"
        @error="onHeroError"
      >
      <span v-if="hero?.image && !heroFailed" class="masthead-shade" aria-hidden="true" />
      <span v-else class="masthead-sun" aria-hidden="true" />

      <div class="pts-container masthead-inner">
        <div class="masthead-copy">
          <h1 id="catalog-page-heading">{{ heading }}</h1>
          <p>{{ description }}</p>

          <dl v-if="total || brandCount" class="masthead-facts">
            <div v-if="total">
              <dt>Sản phẩm</dt>
              <dd>{{ total.toLocaleString('vi-VN') }}</dd>
            </div>
            <div v-if="brandCount">
              <dt>Thương hiệu</dt>
              <dd>{{ brandCount }}</dd>
            </div>
          </dl>

          <nav v-if="visibleBrands.length" class="masthead-brands" aria-label="Lọc nhanh theo thương hiệu">
            <NuxtLink
              v-for="brand in visibleBrands"
              :key="brand.id"
              :to="brand.to"
              :aria-current="brand.active ? 'true' : undefined"
              :data-active="brand.active"
            >{{ brand.name }}</NuxtLink>
          </nav>
        </div>

        <div
          v-if="showcase.length"
          ref="stageRef"
          class="masthead-stage"
          :style="{ '--tx': tilt.x, '--ty': tilt.y }"
        >
          <a
            v-for="(product, index) in showcase"
            :key="product.id"
            class="masthead-product"
            :data-pos="index"
            :href="links.generic(product.url)"
            :title="product.name"
          >
            <img :src="productThumb(product.media[0]!)" :alt="product.name" width="480" height="480" decoding="async" @error="hideProduct(product.id)">
          </a>
        </div>
      </div>
    </section>

    <!-- 4 Value Props Bar -->
    <div class="bg-white border-b border-slate-100" aria-label="Lợi ích khi chọn sản phẩm">
      <div class="pts-container grid grid-cols-2 gap-x-6 gap-y-0 py-3.5 max-[769px]:gap-x-3 max-[769px]:py-2 min-[769px]:grid-cols-4">
        <div class="flex min-h-[56px] items-center justify-center gap-3 max-[769px]:min-h-11 max-[769px]:justify-start max-[769px]:gap-2 max-[769px]:py-1">
          <span class="flex h-9 w-9 flex-none items-center justify-center rounded-[4px] bg-gold-50 text-gold-700 max-[769px]:h-6 max-[769px]:w-6">
            <CatalogMenuIcon class="h-6 w-6 max-[769px]:h-4 max-[769px]:w-4" name="light" />
          </span>
          <span><strong class="block text-[13.5px] font-bold text-navy-950 max-[769px]:text-[12px]">Tư vấn lựa chọn</strong><small class="block text-[12px] text-slate-500 max-[769px]:hidden">Đúng nhu cầu, hiệu quả</small></span>
        </div>
        <div class="flex min-h-[56px] items-center justify-center gap-3 max-[769px]:min-h-11 max-[769px]:justify-start max-[769px]:gap-2 max-[769px]:py-1">
          <span class="flex h-9 w-9 flex-none items-center justify-center rounded-[4px] bg-gold-50 text-gold-700 max-[769px]:h-6 max-[769px]:w-6">
            <CatalogMenuIcon class="h-6 w-6 max-[769px]:h-4 max-[769px]:w-4" name="cog" />
          </span>
          <span><strong class="block text-[13.5px] font-bold text-navy-950 max-[769px]:text-[12px]">Hỗ trợ kỹ thuật</strong><small class="block text-[12px] text-slate-500 max-[769px]:hidden">Trong suốt quá trình sử dụng</small></span>
        </div>
        <div class="flex min-h-[56px] items-center justify-center gap-3 max-[769px]:min-h-11 max-[769px]:justify-start max-[769px]:gap-2 max-[769px]:py-1">
          <span class="flex h-9 w-9 flex-none items-center justify-center rounded-[4px] bg-gold-50 text-gold-700 max-[769px]:h-6 max-[769px]:w-6">
            <CatalogMenuIcon class="h-6 w-6 max-[769px]:h-4 max-[769px]:w-4" name="shield" />
          </span>
          <span><strong class="block text-[13.5px] font-bold text-navy-950 max-[769px]:text-[12px]">Chính sách rõ ràng</strong><small class="block text-[12px] text-slate-500 max-[769px]:hidden">Minh bạch, dễ hiểu</small></span>
        </div>
        <div class="flex min-h-[56px] items-center justify-center gap-3 max-[769px]:min-h-11 max-[769px]:justify-start max-[769px]:gap-2 max-[769px]:py-1">
          <span class="flex h-9 w-9 flex-none items-center justify-center rounded-[4px] bg-gold-50 text-gold-700 max-[769px]:h-6 max-[769px]:w-6">
            <CatalogMenuIcon class="h-6 w-6 max-[769px]:h-4 max-[769px]:w-4" name="truck" />
          </span>
          <span><strong class="block text-[13.5px] font-bold text-navy-950 max-[769px]:text-[12px]">Giao hàng toàn quốc</strong><small class="block text-[12px] text-slate-500 max-[769px]:hidden">Nhanh chóng, an toàn</small></span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.masthead {
  position: relative;
  isolation: isolate;
  overflow: clip;
  background: linear-gradient(115deg, var(--color-navy-950) 0%, #0a2251 55%, #123a7a 100%);
  color: #fff;
}
/* Category photo fills the band; the shade keeps the copy column at AA contrast. */
.masthead-photo {
  position: absolute;
  inset: 0;
  z-index: -2;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: right 40%;
  animation: photo-settle 1.8s cubic-bezier(0.22, 1, 0.36, 1) both;
}
.masthead-shade {
  position: absolute;
  inset: 0;
  z-index: -1;
  background:
    linear-gradient(90deg, rgb(6 21 55 / 96%) 0%, rgb(6 21 55 / 88%) 38%, rgb(6 21 55 / 35%) 70%, rgb(6 21 55 / 15%) 100%),
    linear-gradient(0deg, rgb(6 21 55 / 55%) 0%, transparent 45%);
}
/* Rising sun: the brand mark, behind the floating products. */
.masthead-sun {
  position: absolute;
  right: clamp(-120px, 4vw, 120px);
  bottom: -62%;
  z-index: -1;
  width: clamp(420px, 42vw, 720px);
  aspect-ratio: 1;
  border-radius: 50%;
  background: radial-gradient(circle, rgb(224 182 84 / 70%) 0%, rgb(201 150 46 / 38%) 38%, rgb(201 150 46 / 0%) 70%);
  animation: sun-rise 1.6s cubic-bezier(0.22, 1, 0.36, 1) both;
}
.masthead-inner {
  display: grid;
  grid-template-columns: minmax(0, 1.05fr) minmax(0, 0.95fr);
  align-items: center;
  gap: clamp(24px, 4vw, 64px);
  min-height: 340px;
  padding-block: 40px;
}
.masthead-copy h1 {
  max-width: 18ch;
  margin: 0;
  color: #fff;
  font-size: clamp(32px, 3.4vw, 52px);
  font-weight: 800;
  line-height: 1.06;
  letter-spacing: -0.03em;
  text-wrap: balance;
}
.masthead-copy > p { max-width: 54ch; margin: 14px 0 0; color: rgb(226 232 240 / 92%); font-size: 17px; line-height: 1.6; }

.masthead-facts { display: flex; gap: clamp(24px, 3vw, 48px); margin: 24px 0 0; }
.masthead-facts div { display: flex; flex-direction: column-reverse; }
.masthead-facts dt { color: rgb(203 213 225 / 85%); font-size: 14px; }
.masthead-facts dd { margin: 0; color: var(--color-gold-400); font-size: clamp(28px, 2.6vw, 38px); font-weight: 800; line-height: 1; font-variant-numeric: tabular-nums; letter-spacing: -0.02em; }

.masthead-brands { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 22px; }
.masthead-brands a {
  display: inline-flex;
  min-height: 40px;
  align-items: center;
  padding: 0 14px;
  border: 1px solid rgb(255 255 255 / 22%);
  border-radius: 4px;
  color: #fff;
  font-size: 14px;
  font-weight: 700;
  text-decoration: none;
  transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
}
.masthead-brands a:hover { border-color: var(--color-gold-400); color: var(--color-gold-300); }
.masthead-brands a[data-active="true"] { border-color: transparent; background: linear-gradient(180deg, var(--color-gold-400), var(--color-gold-500)); color: var(--color-navy-950); }
.masthead-brands a:focus-visible { outline: 2px solid var(--color-gold-400); outline-offset: 2px; }

.masthead-stage { position: relative; height: 280px; }
.masthead-product {
  position: absolute;
  display: block;
  aspect-ratio: 1;
  transition: transform 0.6s cubic-bezier(0.22, 1, 0.36, 1);
  animation: product-float 6s ease-in-out infinite;
}
.masthead-product img { width: 100%; height: 100%; object-fit: contain; filter: drop-shadow(0 26px 24px rgb(2 8 23 / 55%)); }
.masthead-product:focus-visible { outline: 2px solid var(--color-gold-400); outline-offset: 4px; border-radius: 4px; }
/* Depth: front product moves most with the pointer, back ones less. */
.masthead-product[data-pos="0"] { left: 31%; top: 2%; z-index: 3; width: 42%; translate: calc(var(--tx) * 14px) calc(var(--ty) * 10px); }
.masthead-product[data-pos="1"] { left: 0; top: 28%; z-index: 2; width: 31%; translate: calc(var(--tx) * 8px) calc(var(--ty) * 6px); animation-delay: -2s; }
.masthead-product[data-pos="2"] { right: 0; top: 32%; z-index: 1; width: 29%; translate: calc(var(--tx) * 5px) calc(var(--ty) * 4px); animation-delay: -4s; }
.masthead-product:hover { transform: scale(1.06); }

@keyframes product-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
@keyframes photo-settle { from { opacity: 0; transform: scale(1.06); } to { opacity: 1; transform: scale(1); } }
@keyframes sun-rise { from { opacity: 0; transform: translateY(18%); } to { opacity: 1; transform: translateY(0); } }

@media (max-width: 1023px) {
  .masthead-inner { grid-template-columns: minmax(0, 1fr); min-height: 0; padding-block: 32px 28px; }
  .masthead-stage { display: none; }
  .masthead-shade { background: linear-gradient(90deg, rgb(6 21 55 / 94%) 0%, rgb(6 21 55 / 78%) 60%, rgb(6 21 55 / 50%) 100%); }
  .masthead-sun { right: -160px; bottom: -70%; width: 420px; }
}
@media (max-width: 640px) {
  .masthead-inner { padding-block: 24px 20px; }
  .masthead-copy h1 { font-size: clamp(28px, 8vw, 34px); }
  .masthead-copy > p { margin-top: 8px; font-size: 16px; }
  .masthead-facts { margin-top: 16px; }
  .masthead-brands { flex-wrap: nowrap; margin-top: 16px; margin-inline: -16px; padding-inline: 16px; overflow-x: auto; scrollbar-width: none; }
  .masthead-brands::-webkit-scrollbar { display: none; }
  .masthead-brands a { flex: 0 0 auto; min-height: 44px; }
}
@media (prefers-reduced-motion: reduce) {
  .masthead-sun, .masthead-product, .masthead-photo { animation: none; }
  .masthead-product { translate: none !important; transition: none; }
}
</style>
