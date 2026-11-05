<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'
import type { PublicCatalogHero } from '#shared/contracts/public-catalog'
import CatalogMenuIcon from '~/components/CatalogMenuIcon.vue'

export interface CatalogMastheadBrand { id: number; name: string; to: RouteLocationRaw; active: boolean }

const props = withDefaults(defineProps<{
  heading: string
  description: string
  hero: PublicCatalogHero | null
  brands?: CatalogMastheadBrand[]
}>(), { brands: () => [] })

const phpResizableCatalogImage = /^\/upload\/(?:product|news|photo)\/[A-Za-z0-9][A-Za-z0-9._/-]*\.(?:jpe?g|png|webp)$/i
const heroVisualOverrides: Record<string, string> = {
  '/assets/images/site-visuals/catalog-products-hero-20261007-v2.png': '/assets/images/site-visuals/catalog-grid-tie-solar-20261008.png',
}

function catalogHeroCandidate(src: string, width: 960 | 1800): string {
  const resolved = heroVisualOverrides[src] || src
  if (!phpResizableCatalogImage.test(resolved)) return resolved
  const height = Math.round(width / 3)
  return `/thumbs/${width}x${height}x1${resolved}`
}

function catalogHeroSrcSet(src: string): string | undefined {
  if (!phpResizableCatalogImage.test(src)) return undefined
  return `${catalogHeroCandidate(src, 960)} 960w, ${catalogHeroCandidate(src, 1800)} 1800w`
}

const heroFailed = ref(false)
function onHeroError() { heroFailed.value = true }
watch(() => props.hero?.image, () => { heroFailed.value = false })

const fallbackHeroImage = '/assets/images/site-visuals/catalog-grid-tie-solar-20261008.png'
const activeHeroImage = computed(() => props.hero?.image || fallbackHeroImage)

const brandLogoByName: Record<string, string> = {
  aesolar: '/assets/images/brand-logos/aesolar.png',
  bluecarbon: '/assets/images/brand-logos/bluecarbon.png',
  canadiansolar: '/assets/images/brand-logos/canadiansolar.png',
  epsivo: '/assets/images/brand-logos/epsivo.png',
  hober: '/assets/images/brand-logos/hober.png',
  huawei: '/assets/images/brand-logos/huawei.png',
  jdjindian: '/assets/images/brand-logos/jdjindian.png',
  jindian: '/assets/images/brand-logos/jdjindian.png',
  longi: '/assets/images/brand-logos/longi.png',
  oriana: '/assets/images/brand-logos/oriana.png',
  sharp: '/assets/images/brand-logos/sharp.png',
  solax: '/assets/images/brand-logos/solax.png',
  sungrow: '/assets/images/brand-logos/sungrow.png',
}

function brandLogo(name: string): string {
  return brandLogoByName[name.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]/g, '')] || ''
}
</script>

<template>
  <div class="w-full border-b border-slate-200/80">
    <section class="masthead" aria-labelledby="catalog-page-heading">
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
      <img
        v-else-if="!heroFailed"
        :src="activeHeroImage"
        alt=""
        width="1800"
        height="600"
        sizes="100vw"
        class="masthead-photo"
        fetchpriority="high"
        @error="onHeroError"
      >
      <span v-if="!heroFailed" class="masthead-shade" aria-hidden="true" />
      <span v-else class="masthead-sun" aria-hidden="true" />

      <div class="pts-container masthead-inner">
        <div class="masthead-copy">
          <h1 id="catalog-page-heading">{{ heading }}</h1>
          <p>{{ description }}</p>

          <nav v-if="brands.length" class="masthead-brands" aria-label="Lọc nhanh theo thương hiệu">
            <NuxtLink
              v-for="brand in brands"
              :key="brand.id"
              :to="brand.to"
              :aria-label="`Xem thương hiệu ${brand.name}`"
              :aria-current="brand.active ? 'true' : undefined"
              :data-active="brand.active"
            >
              <img v-if="brandLogo(brand.name)" :src="brandLogo(brand.name)" :alt="brand.name" width="120" height="40" loading="lazy" decoding="async">
              <span v-else>{{ brand.name }}</span>
            </NuxtLink>
          </nav>
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
/* Category photo fills the band; the shade keeps the copy column at AA contrast while leaving the right visual clear. */
.masthead-photo {
  position: absolute;
  inset: 0;
  z-index: -2;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: right 38%;
  animation: photo-settle 1.8s cubic-bezier(0.22, 1, 0.36, 1) both;
}
.masthead-shade {
  position: absolute;
  inset: 0;
  z-index: -1;
  background:
    linear-gradient(90deg, rgb(6 21 55 / 95%) 0%, rgb(6 21 55 / 84%) 38%, rgb(6 21 55 / 22%) 68%, rgb(6 21 55 / 5%) 100%),
    linear-gradient(0deg, rgb(6 21 55 / 45%) 0%, transparent 40%);
}
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
  display: flex;
  align-items: center;
  min-height: 310px;
  padding-block: 36px;
}
.masthead-copy {
  width: 100%;
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

.masthead-brands { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 20px; }
.masthead-brands a {
  display: inline-flex;
  min-width: 104px;
  min-height: 40px;
  align-items: center;
  justify-content: center;
  padding: 0 14px;
  border: 1px solid rgb(219 231 249 / 16%);
  border-radius: 6px;
  background: linear-gradient(135deg, rgb(20 45 83 / 46%), rgb(6 21 55 / 38%));
  box-shadow: inset 0 1px 0 rgb(255 255 255 / 8%), 0 6px 20px rgb(3 12 31 / 12%);
  backdrop-filter: blur(12px) saturate(125%);
  -webkit-backdrop-filter: blur(12px) saturate(125%);
  color: #fff;
  font-size: 14px;
  font-weight: 700;
  text-decoration: none;
  transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
}
.masthead-brands a img { display: block; width: auto; max-width: 118px; height: 28px; object-fit: contain; }
.masthead-brands a:hover { border-color: rgb(219 231 249 / 30%); background: linear-gradient(135deg, rgb(30 58 97 / 58%), rgb(9 28 64 / 52%)); transform: translateY(-2px); }
.masthead-brands a[data-active="true"] { border-color: var(--color-gold-400); background: var(--pts-gold-gradient); }
.masthead-brands a:focus-visible { outline: 2px solid var(--color-gold-400); outline-offset: 2px; }

@keyframes photo-settle { from { opacity: 0; transform: scale(1.06); } to { opacity: 1; transform: scale(1); } }
@keyframes sun-rise { from { opacity: 0; transform: translateY(18%); } to { opacity: 1; transform: translateY(0); } }

@media (max-width: 1023px) {
  .masthead-inner { min-height: 0; padding-block: 32px 28px; }
  .masthead-shade { background: linear-gradient(90deg, rgb(6 21 55 / 94%) 0%, rgb(6 21 55 / 78%) 60%, rgb(6 21 55 / 50%) 100%); }
  .masthead-sun { right: -160px; bottom: -70%; width: 420px; }
}
@media (max-width: 640px) {
  .masthead-inner { padding-block: 24px 20px; }
  .masthead-copy h1 { font-size: clamp(28px, 8vw, 34px); }
  .masthead-copy > p { margin-top: 8px; font-size: 16px; }
  .masthead-brands { flex-wrap: nowrap; margin-top: 16px; margin-inline: -16px; padding-inline: 16px; overflow-x: auto; scrollbar-width: none; }
  .masthead-brands::-webkit-scrollbar { display: none; }
  .masthead-brands a { flex: 0 0 auto; min-height: 44px; }
}
@media (prefers-reduced-motion: reduce) {
  .masthead-sun, .masthead-photo { animation: none; }
}
</style>
