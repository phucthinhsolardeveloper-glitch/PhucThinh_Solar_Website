<script setup lang="ts">
import type { PublicSolutionEquipment } from '../../../shared/contracts/public-solutions'
import type { PublicProductCategoryNode } from '../../../shared/contracts/public-site'
import type { PublicCatalogPayload, PublicCatalogProduct } from '#shared/contracts/public-catalog'
import { resolveEquipmentIcon } from '#shared/catalog-menu-presentation'

const props = defineProps<{ equipment: PublicSolutionEquipment[]; categoryTree?: PublicProductCategoryNode[]; title: string; allProductsUrl: string; allProductsLabel: string }>()
const links = useMigrationLinks()

interface Showcase { products: PublicCatalogProduct[] }

// Showroom data is enrichment only: the section is fully usable (names,
// descriptions, category links) before or without it.
const showcases = ref<Record<string, Showcase>>({})
const active = ref(0)
const rootRef = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | undefined

function categoryPath(url: string): string | null {
  const match = url.match(/^\/danh-muc-san-pham\/([a-z0-9-]+(?:\/[a-z0-9-]+){0,3})\/$/)
  return match ? match[1]! : null
}

function canonicalCategoryUrl(nodes: PublicProductCategoryNode[], slug: string, name: string): string | undefined {
  const normalizedName = name.trim().toLocaleLowerCase('vi-VN')
  for (const node of nodes) {
    if (node.slug === slug && node.name.trim().toLocaleLowerCase('vi-VN') === normalizedName) return node.url
    const child = canonicalCategoryUrl(node.children, slug, name)
    if (child) return child
  }
  return undefined
}

function equipmentCategoryPath(item: PublicSolutionEquipment): string | null {
  const requestedPath = categoryPath(item.url)
  const slug = requestedPath?.split('/').at(-1)
  if (!requestedPath || !slug) return null
  const canonicalUrl = canonicalCategoryUrl(props.categoryTree ?? [], slug, item.name)
  return categoryPath(canonicalUrl || item.url) || requestedPath
}

// Same fallback as the catalog grid for a source image too large for the thumb service.
const oversized: Record<string, string> = {
  '/upload/product/t-bat-tsys-d53-2-4295.png': '/assets/images/site-visuals/media-fallbacks/brand-tbat-tsys-d53.webp',
}
const failed = ref<Set<number>>(new Set())
function thumb(src: string, size = 480): string {
  const source = oversized[src] || src
  return source.startsWith('/upload/product/') ? phpThumb(source, size, size) : source
}
function hideBroken(id: number) { failed.value = new Set(failed.value).add(id) }
function visible(products: PublicCatalogProduct[]) { return products.filter((product) => !failed.value.has(product.id)) }
// The desktop stage presents isolated product cutouts. Opaque JPEGs stay in
// the mobile product tiles, where their photo background is an intentional surface.
function cutouts(products: PublicCatalogProduct[]) {
  return visible(products.filter((product) => product.media[0] && /\.(?:png|webp|avif)(?:[?#]|$)/i.test(product.media[0]!)))
}

async function loadShowcases() {
  const entries = await Promise.allSettled(props.equipment.map(async (item) => {
    const path = equipmentCategoryPath(item)
    if (!path) return null
    const payload = await $fetch<PublicCatalogPayload>('/_migration/catalog', { query: { category: path, per_page: 6 } })
    const products = payload.products.filter((product) => product.media[0]).slice(0, 3)
    return [item.url, { products }] as const
  }))
  const next: Record<string, Showcase> = {}
  for (const entry of entries) if (entry.status === 'fulfilled' && entry.value) next[entry.value[0]] = entry.value[1]
  showcases.value = next
}

onMounted(() => {
  if (!rootRef.value || !('IntersectionObserver' in window)) { void loadShowcases(); return }
  observer = new IntersectionObserver((records) => {
    if (!records.some((record) => record.isIntersecting)) return
    observer?.disconnect()
    void loadShowcases()
  }, { rootMargin: '600px 0px' })
  observer.observe(rootRef.value)
})
onBeforeUnmount(() => observer?.disconnect())

const current = computed(() => props.equipment[active.value])
const currentShowcase = computed(() => current.value ? showcases.value[current.value.url] : undefined)
const currentCutouts = computed(() => currentShowcase.value ? cutouts(currentShowcase.value.products) : [])
</script>

<template>
  <section v-if="equipment.length" ref="rootRef" class="showroom" aria-labelledby="solutions-equipment-title">
    <div class="showroom-index">
      <header class="showroom-head">
        <h2 id="solutions-equipment-title">{{ title }}</h2>
        <a class="showroom-all" :href="links.generic(allProductsUrl)">{{ allProductsLabel }} <span aria-hidden="true">→</span></a>
      </header>

      <ol class="showroom-list">
        <li
          v-for="(item, index) in equipment"
          :key="item.url"
          class="showroom-row"
          :data-active="index === active"
          @mouseenter="active = index"
        >
          <a class="showroom-link" :href="links.generic(item.url)" @focus="active = index">
            <span class="showroom-icon" aria-hidden="true">
              <CatalogMenuIcon class="size-5" :name="resolveEquipmentIcon(item.name, item.url)" />
            </span>
            <span class="showroom-name">{{ item.name }}</span>
            <span class="showroom-arrow" aria-hidden="true">→</span>
          </a>

          <!-- Small screens have no hover stage: every row carries its own strip. -->
          <div class="showroom-mobile">
            <p v-if="item.description">{{ item.description }}</p>
            <ul v-if="showcases[item.url]?.products.length" class="showroom-strip">
              <li v-for="product in visible(showcases[item.url]!.products)" :key="product.id">
                <a :href="links.generic(product.url)">
                  <img :src="thumb(product.media[0]!, 320)" :alt="product.name" width="160" height="160" loading="lazy" decoding="async" @error="hideBroken(product.id)">
                </a>
              </li>
            </ul>
          </div>
        </li>
      </ol>
    </div>

    <div class="showroom-stage" aria-live="polite">
      <Transition name="stage" mode="out-in">
        <div :key="current?.url" class="showroom-stage-inner">
          <ul v-if="currentCutouts.length" class="showroom-products" :data-count="currentCutouts.length">
            <li v-for="(product, index) in currentCutouts" :key="product.id" :style="{ '--i': index }">
              <a :href="links.generic(product.url)" class="showroom-product">
                <span class="showroom-product-media">
                  <img :src="thumb(product.media[0]!)" :alt="product.name" width="480" height="480" loading="lazy" decoding="async" @error="hideBroken(product.id)">
                </span>
                <span class="showroom-product-name">{{ product.name }}</span>
              </a>
            </li>
          </ul>
          <div v-else class="showroom-placeholder" aria-hidden="true">
            <CatalogMenuIcon v-if="current" class="size-14" :name="resolveEquipmentIcon(current.name, current.url)" />
          </div>

          <div v-if="current" class="showroom-caption">
            <p v-if="current.description">{{ current.description }}</p>
            <a class="showroom-cta" :href="links.generic(current.url)">
              Xem danh mục
              <span aria-hidden="true">→</span>
            </a>
          </div>
        </div>
      </Transition>
    </div>
  </section>
</template>

<style scoped>
.showroom {
  display: grid;
  grid-template-columns: minmax(0, 5fr) minmax(0, 7fr);
  gap: clamp(32px, 5vw, 80px);
  align-items: stretch;
  padding: clamp(48px, 6vw, 88px) 0 clamp(40px, 5vw, 72px);
}
.showroom-head { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 8px 24px; margin-bottom: clamp(20px, 2.5vw, 32px); }
.showroom-head h2 { margin: 0; color: var(--color-navy-800); font-size: clamp(28px, 3vw, 44px); font-weight: 800; line-height: 1.1; letter-spacing: -0.03em; }
.showroom-all { display: inline-flex; min-height: 44px; align-items: center; gap: 6px; color: var(--color-gold-700); font-size: 15px; font-weight: 800; text-decoration: none; }
.showroom-all:hover { color: var(--color-navy-800); }

.showroom-list { margin: 0; padding: 0; list-style: none; }
.showroom-link {
  position: relative;
  display: grid;
  grid-template-columns: 44px minmax(0, 1fr) 24px;
  align-items: center;
  gap: 16px;
  min-height: 76px;
  padding: 12px 0;
  color: var(--color-navy-800);
  text-decoration: none;
}
/* Gold rule grows under the previewed row: shows which category the stage belongs to. */
.showroom-link::after {
  content: '';
  position: absolute;
  inset: auto 0 0;
  height: 2px;
  background: var(--pts-gold-gradient, linear-gradient(90deg, #e0b654, #c9962e));
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}
.showroom-icon { display: grid; width: 44px; height: 44px; place-items: center; border-radius: var(--radius-btn); background: rgb(10 34 81 / 5%); color: var(--color-gold-700); transition: background-color 0.3s ease, color 0.3s ease; }
.showroom-name { font-size: clamp(19px, 1.7vw, 26px); font-weight: 800; line-height: 1.2; letter-spacing: -0.02em; color: #94a3b8; transition: color 0.3s ease; }
.showroom-arrow { color: #94a3b8; font-size: 18px; font-weight: 800; transition: transform 0.3s ease, color 0.3s ease; }
.showroom-row[data-active="true"] .showroom-name { color: var(--color-navy-800); }
.showroom-row[data-active="true"] .showroom-icon { background: var(--color-navy-800); color: var(--color-gold-300); }
.showroom-row[data-active="true"] .showroom-arrow { color: var(--color-gold-700); transform: translateX(4px); }
.showroom-row[data-active="true"] .showroom-link::after { transform: scaleX(1); }
.showroom-link:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 4px; border-radius: var(--radius-btn); }
.showroom-mobile { display: none; }

.showroom-stage {
  position: relative;
  display: flex;
  min-height: 520px;
  overflow: clip;
  border-radius: var(--radius-btn);
  background: radial-gradient(120% 90% at 50% 100%, #fff7e3 0%, #f8fafc 55%, #eef2f7 100%);
  isolation: isolate;
}
.showroom-stage-inner { display: flex; width: 100%; flex-direction: column; justify-content: space-between; gap: 24px; padding: clamp(24px, 3vw, 44px); }

.showroom-products { display: grid; flex: 1; grid-template-columns: repeat(3, minmax(0, 1fr)); align-items: end; gap: clamp(12px, 2vw, 28px); margin: 0; padding: 0; list-style: none; }
.showroom-products[data-count="1"] { grid-template-columns: minmax(0, 1fr); justify-items: center; }
.showroom-products[data-count="2"] { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.showroom-products > li { min-width: 0; animation: product-rise 0.7s cubic-bezier(0.22, 1, 0.36, 1) both; animation-delay: calc(var(--i) * 90ms); }
/* Middle product sits forward: a podium rhythm instead of a flat row. */
.showroom-products[data-count="3"] > li:nth-child(2) { transform: translateY(-8%); }
.showroom-product { display: flex; flex-direction: column; align-items: center; gap: 12px; color: var(--color-navy-800); text-decoration: none; }
.showroom-product-media { display: block; width: 100%; max-width: 260px; aspect-ratio: 1; }
.showroom-product-media img { width: 100%; height: 100%; object-fit: contain; filter: drop-shadow(0 22px 22px rgb(6 21 55 / 18%)); transition: transform 0.5s cubic-bezier(0.22, 1, 0.36, 1); }
.showroom-product:hover .showroom-product-media img { transform: translateY(-6px) scale(1.03); }
.showroom-product-name { display: -webkit-box; max-width: 26ch; overflow: hidden; color: #334155; font-size: 14px; font-weight: 600; line-height: 1.45; text-align: center; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
.showroom-product:hover .showroom-product-name { color: var(--color-navy-800); }
.showroom-product:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 4px; border-radius: var(--radius-btn); }
.showroom-placeholder { display: grid; flex: 1; place-items: center; color: rgb(10 34 81 / 18%); }

.showroom-caption { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 16px 32px; }
.showroom-caption p { flex: 1 1 320px; max-width: 62ch; margin: 0; color: #475569; font-size: 16px; line-height: 1.65; }
.showroom-cta { display: inline-flex; min-height: 44px; align-items: center; gap: 10px; padding: 10px 18px; border-radius: var(--radius-btn); background: var(--pts-gold-gradient); color: var(--color-navy-950); font-weight: 800; text-decoration: none; white-space: nowrap; }
.showroom-cta:hover span { transform: translateX(3px); }
.showroom-cta span { transition: transform 0.25s ease; }
.showroom-cta:focus-visible { outline: 2px solid var(--color-navy-950); outline-offset: 3px; }

/* Leave at once and arrive quickly from partly visible, so tabs never pass through an empty stage. */
.stage-enter-active { transition: opacity 0.16s ease-out; }
.stage-enter-from { opacity: 0.35; }
@keyframes product-rise { from { opacity: 0; translate: 0 28px; scale: 0.94; } to { opacity: 1; translate: 0 0; scale: 1; } }

@media (max-width: 1023px) {
  .showroom { grid-template-columns: minmax(0, 1fr); }
  .showroom-stage { display: none; }
  .showroom-row { padding-bottom: 8px; }
  .showroom-row .showroom-name { color: var(--color-navy-800); }
  .showroom-row .showroom-icon { background: var(--color-navy-800); color: var(--color-gold-300); }
  .showroom-row .showroom-arrow { color: var(--color-gold-700); transform: none; }
  .showroom-link::after { display: none; }
  .showroom-mobile { display: block; padding: 0 0 20px 60px; }
  .showroom-mobile p { display: -webkit-box; margin: 0 0 14px; overflow: hidden; color: #475569; font-size: 16px; line-height: 1.6; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
  .showroom-strip { display: flex; gap: 12px; margin: 0; padding: 0; list-style: none; overflow-x: auto; scrollbar-width: none; }
  .showroom-strip::-webkit-scrollbar { display: none; }
  .showroom-strip li { flex: 0 0 auto; }
  .showroom-strip a { display: block; width: 104px; aspect-ratio: 1; padding: 8px; border-radius: var(--radius-btn); background: #f8fafc; }
  .showroom-strip img { width: 100%; height: 100%; object-fit: contain; }
}
@media (max-width: 520px) {
  .showroom-link { grid-template-columns: 44px minmax(0, 1fr) 20px; gap: 12px; }
  .showroom-arrow { grid-column: 3; grid-row: 1 / span 2; }
  .showroom-mobile { padding-left: 0; }
}
@media (prefers-reduced-motion: reduce) {
  .showroom-products > li { animation: none; }
  .showroom-link::after, .showroom-product-media img, .stage-enter-active, .stage-leave-active { transition: none; }
}
/* Phones: each category is one card on a rail with its full description and three products side by side. */
@media (max-width: 820px) {
  .showroom { gap: 0; padding: 28px 0 20px; }
  .showroom-index { min-width: 0; }
  .showroom-head { align-items: center; margin-bottom: 8px; }
  .showroom-head h2 { font-size: clamp(24px, 6.6vw, 28px); line-height: 1.16; }
  /* min-height keeps the rail from growing when the product strips arrive. */
  .showroom-list {
    display: flex;
    gap: 12px;
    min-height: 276px;
    margin-inline: calc(50% - 50vw);
    padding: 4px calc(50vw - 50%) 8px;
    overflow-x: auto;
    overscroll-behavior-x: contain;
    scroll-snap-type: x mandatory;
    scrollbar-width: none;
  }
  .showroom-list::-webkit-scrollbar { display: none; }
  .showroom-row { display: flex; flex: 0 0 min(84%, 330px); flex-direction: column; padding: 4px 12px 12px; border: 1px solid rgb(10 34 81 / 10%); border-radius: var(--radius-md); background: #fff; box-shadow: var(--pts-shadow-soft); scroll-snap-align: center; }
  .showroom-link { grid-template-columns: 40px minmax(0, 1fr) 20px; gap: 10px; min-height: 52px; padding: 6px 0; }
  .showroom-icon { width: 40px; height: 40px; border-radius: var(--radius-sm); }
  .showroom-name { font-size: 16.5px; line-height: 1.25; }
  .showroom-arrow { grid-column: 3; grid-row: auto; }
  .showroom-mobile { display: flex; flex: 1; flex-direction: column; padding: 0; }
  .showroom-mobile p { display: block; margin: 0 0 10px; overflow: visible; font-size: 14px; line-height: 1.5; -webkit-line-clamp: unset; }
  .showroom-strip { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px; margin-top: auto; overflow: visible; }
  .showroom-strip a { width: 100%; padding: 6px; border-radius: var(--radius-sm); }
}
</style>
