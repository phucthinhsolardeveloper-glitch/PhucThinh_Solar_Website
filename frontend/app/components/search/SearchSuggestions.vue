<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'
import type { PublicProductCategoryNode, PublicSitePayload } from '#shared/contracts/public-site'
import type { PublicCatalogProduct } from '#shared/contracts/public-catalog'
import { resolveEquipmentIcon } from '#shared/catalog-menu-presentation'
import CatalogMenuIcon from '~/components/CatalogMenuIcon.vue'
import SearchHighlight from '~/components/search/SearchHighlight.vue'

const props = defineProps<{ keyword: string, site: PublicSitePayload, searchPath: string, idPrefix: string }>()
const emit = defineEmits<{ leave: [term?: string], pickTerm: [term: string] }>()

const links = usePublicLinks()
const { openConsultation } = useStorefrontModal()
const keywordRef = computed(() => props.keyword)
const { result, pending, failed, active, query } = useProductSuggest(keywordRef)
const history = useSearchHistory()

function flatten(nodes: PublicProductCategoryNode[]): PublicProductCategoryNode[] {
  return nodes.flatMap((node) => [node, ...flatten(node.children ?? [])])
}
const categories = computed(() => flatten(props.site.productCategoryTree ?? []))
const browseCategories = computed(() => props.site.productCategories.slice(0, 6))

type Option = { id: string, to: RouteLocationRaw, term?: string }

const matchedCategories = computed(() => {
  const term = foldText(query.value)
  return active.value ? categories.value.filter((item) => foldText(item.name).includes(term)).slice(0, 3) : []
})
const matchedBrands = computed(() => {
  const term = foldText(query.value)
  return active.value ? (result.value?.filters?.brands ?? []).filter((item) => foldText(item.name).includes(term)).slice(0, 3) : []
})
const products = computed<PublicCatalogProduct[]>(() => active.value ? result.value?.products ?? [] : [])
const total = computed(() => result.value?.pagination.total ?? 0)
const searchLink = (term: string): RouteLocationRaw => ({ path: props.searchPath, query: { keyword: term } })

const options = computed<Option[]>(() => [
  ...matchedCategories.value.map((item) => ({ id: `category-${item.id}`, to: links.generic(item.url) })),
  ...matchedBrands.value.map((item) => ({ id: `brand-${item.id}`, to: searchLink(item.name), term: item.name })),
  ...products.value.map((item) => ({ id: `product-${item.id}`, to: links.generic(item.url), term: query.value })),
  ...(active.value && total.value ? [{ id: 'all', to: searchLink(query.value), term: query.value }] : []),
])
const activeIndex = ref(-1)
watch(options, () => { activeIndex.value = -1 })
const optionId = (id: string) => `${props.idPrefix}-${id}`
const activeOptionId = computed(() => {
  const option = options.value[activeIndex.value]
  return option ? optionId(option.id) : undefined
})
const listId = computed(() => `${props.idPrefix}-list`)
const isActive = (id: string) => options.value[activeIndex.value]?.id === id

function choose(term?: string) {
  if (term) history.remember(term)
  emit('leave', term)
}

// The input keeps focus, as a combobox should, and hands its arrow keys here.
async function handleKey(event: KeyboardEvent) {
  if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
    if (!options.value.length) return
    event.preventDefault()
    const step = event.key === 'ArrowDown' ? 1 : -1
    const count = options.value.length
    activeIndex.value = activeIndex.value === -1 && step === -1 ? count - 1 : (activeIndex.value + step + count + (activeIndex.value === -1 ? 1 : 0)) % count
    await nextTick()
    if (activeOptionId.value) document.getElementById(activeOptionId.value)?.scrollIntoView({ block: 'nearest' })
  } else if (event.key === 'Enter' && options.value[activeIndex.value]) {
    event.preventDefault()
    const option = options.value[activeIndex.value]!
    choose(option.term)
    await navigateTo(option.to)
  }
}

const brokenThumbs = ref(new Set<number>())
function markBroken(id: number) {
  brokenThumbs.value = new Set(brokenThumbs.value).add(id)
}

function priceLabel(product: PublicCatalogProduct) {
  const pricing = product.pricing
  if (!pricing || pricing.mode !== 'price') return 'Liên hệ báo giá'
  return `${new Intl.NumberFormat('vi-VN').format(Number(pricing.salePrice ?? pricing.regularPrice ?? 0))}đ`
}

function askEngineer() {
  const term = query.value
  emit('leave')
  openConsultation(`Tìm kiếm sản phẩm: ${term}`)
}

defineExpose({ handleKey, activeOptionId, listId, remember: history.remember })
</script>

<template>
  <div class="sug" :class="{ 'is-pending': pending }">
    <span class="sug-progress" aria-hidden="true" />

    <!-- Before typing: pick up where you left off, or browse. -->
    <div v-if="!active" class="sug-start">
      <section v-if="history.items.value.length" class="sug-group" :aria-label="'Tìm kiếm gần đây'">
        <div class="sug-heading">
          <span>Tìm gần đây</span>
          <button type="button" class="sug-clear" @click="history.clear()">Xóa</button>
        </div>
        <div class="sug-chips">
          <button v-for="term in history.items.value" :key="term" type="button" class="sug-chip" @click="emit('pickTerm', term)">
            <UIcon name="i-lucide-history" class="size-3.5" aria-hidden="true" />{{ term }}
          </button>
        </div>
      </section>
      <section v-if="browseCategories.length" class="sug-group" aria-label="Xem theo nhóm thiết bị">
        <div class="sug-heading"><span>Xem theo nhóm thiết bị</span></div>
        <ul class="sug-tiles">
          <li v-for="item in browseCategories" :key="item.slug">
            <NuxtLink :to="links.generic(item.url)" class="sug-tile" @click="choose()">
              <span class="sug-icon" aria-hidden="true"><CatalogMenuIcon class="size-5" :name="resolveEquipmentIcon(item.name, item.url)" /></span>
              <span>{{ item.name }}</span>
            </NuxtLink>
          </li>
        </ul>
      </section>
    </div>

    <!-- While typing: everything that matches, the closest first. -->
    <div v-else :id="listId" role="listbox" aria-label="Gợi ý tìm kiếm" class="sug-results">
      <section v-if="matchedCategories.length || matchedBrands.length" class="sug-group" role="group" aria-label="Danh mục và thương hiệu">
        <div class="sug-chips">
          <NuxtLink
            v-for="item in matchedCategories"
            :id="optionId(`category-${item.id}`)"
            :key="`category-${item.id}`"
            :to="links.generic(item.url)"
            role="option"
            :aria-selected="isActive(`category-${item.id}`)"
            class="sug-chip sug-chip--link"
            :class="{ 'is-active': isActive(`category-${item.id}`) }"
            @click="choose()"
          >
            <UIcon name="i-lucide-layout-grid" class="size-3.5" aria-hidden="true" /><SearchHighlight :text="item.name" :term="query" />
          </NuxtLink>
          <NuxtLink
            v-for="item in matchedBrands"
            :id="optionId(`brand-${item.id}`)"
            :key="`brand-${item.id}`"
            :to="searchLink(item.name)"
            role="option"
            :aria-selected="isActive(`brand-${item.id}`)"
            class="sug-chip sug-chip--link"
            :class="{ 'is-active': isActive(`brand-${item.id}`) }"
            @click="choose(item.name)"
          >
            <UIcon name="i-lucide-badge-check" class="size-3.5" aria-hidden="true" />Hãng <SearchHighlight :text="item.name" :term="query" />
          </NuxtLink>
        </div>
      </section>

      <NuxtLink
        v-for="item in products"
        :id="optionId(`product-${item.id}`)"
        :key="item.id"
        :to="links.generic(item.url)"
        role="option"
        :aria-selected="isActive(`product-${item.id}`)"
        class="sug-row"
        :class="{ 'is-active': isActive(`product-${item.id}`) }"
        @click="choose(query)"
      >
        <span class="sug-thumb">
          <img v-if="item.media[0] && !brokenThumbs.has(item.id)" :src="optimizedImage(item.media[0], 112, 112)" alt="" width="56" height="56" decoding="async" @error="markBroken(item.id)">
          <UIcon v-else name="i-lucide-image-off" class="size-5" aria-hidden="true" />
        </span>
        <span class="sug-name"><SearchHighlight :text="item.name" :term="query" /></span>
        <span class="sug-price">{{ priceLabel(item) }}</span>
      </NuxtLink>

      <NuxtLink
        v-if="total"
        :id="optionId('all')"
        :to="searchLink(query)"
        role="option"
        :aria-selected="isActive('all')"
        class="sug-all"
        :class="{ 'is-active': isActive('all') }"
        @click="choose(query)"
      >
        Xem tất cả {{ total }} kết quả cho “{{ query }}”
        <UIcon name="i-lucide-arrow-right" class="size-4" aria-hidden="true" />
      </NuxtLink>

      <div v-if="!pending && !failed && result && !total && !matchedCategories.length" class="sug-none">
        <p>Chưa thấy sản phẩm cho “{{ query }}”.</p>
        <button type="button" class="sug-ask" @click="askEngineer">Cần tư vấn tìm sản phẩm</button>
      </div>
      <p v-if="failed" class="sug-note">Chưa tải được gợi ý. Bạn vẫn có thể bấm Tìm.</p>
    </div>
    <p class="sr-only" role="status" aria-live="polite">{{ active && !pending ? `${total} kết quả` : '' }}</p>
  </div>
</template>

<style scoped>
.sug { --sug-ink: var(--color-navy-950); --sug-muted: #5b6b85; --sug-hover: #f1f4f9; --sug-line: rgb(10 34 81 / 10%); --sug-price: #b91c1c; --sug-tile: #fff; position: relative; display: grid; gap: 14px; max-height: min(62dvh, 560px); overflow-y: auto; overscroll-behavior: contain; color: var(--sug-ink); }
.sug-progress { position: sticky; top: 0; z-index: 1; display: block; height: 2px; margin-bottom: -14px; border-radius: 2px; background: var(--pts-gold-gradient); opacity: 0; transform: scaleX(.15); transform-origin: left; transition: opacity .2s ease, transform 0s .2s; }
.sug.is-pending .sug-progress { opacity: 1; transform: scaleX(.85); transition: opacity .2s ease, transform 1.2s cubic-bezier(.22, 1, .36, 1); }
.sug-start, .sug-results { display: grid; gap: 14px; animation: sug-in .18s ease both; }
.sug-results { gap: 2px; }
.sug-group { display: grid; gap: 8px; }
.sug-results .sug-group { margin-bottom: 8px; }
.sug-heading { display: flex; align-items: center; justify-content: space-between; color: var(--sug-muted); font-size: 13px; font-weight: 700; }
.sug-clear { min-height: 32px; padding: 0 8px; border: 0; background: none; color: var(--sug-muted); font: inherit; text-decoration: underline; cursor: pointer; }
.sug-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.sug-chip { display: inline-flex; min-height: 40px; align-items: center; gap: 6px; padding: 0 12px; border: 1px solid var(--sug-line); border-radius: var(--radius-btn); background: var(--sug-tile); color: var(--sug-ink); font-size: 13.5px; font-weight: 700; text-decoration: none; cursor: pointer; transition: background-color .15s ease, border-color .15s ease; }
.sug-chip:hover, .sug-chip.is-active { border-color: var(--color-gold-500); background: var(--sug-hover); }
.sug-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 210px), 1fr)); gap: 8px; margin: 0; padding: 0; list-style: none; }
.sug-tile { display: flex; min-height: 56px; align-items: center; gap: 10px; padding: 8px 12px; border: 1px solid var(--sug-line); border-radius: var(--radius-sm); background: var(--sug-tile); color: var(--sug-ink); font-size: 14px; font-weight: 700; line-height: 1.3; text-decoration: none; transition: background-color .15s ease, border-color .15s ease, transform .15s ease; }
.sug-tile > span:last-child { min-width: 0; overflow-wrap: anywhere; }
.sug-tile:hover { border-color: var(--color-gold-500); background: var(--sug-hover); transform: translateY(-1px); }
.sug-icon { display: grid; width: 36px; height: 36px; flex: none; place-items: center; border-radius: var(--radius-btn); background: rgb(228 168 73 / 14%); color: var(--color-gold-700); }
.sug-row { display: grid; min-height: 64px; grid-template-columns: 48px minmax(0, 1fr) auto; align-items: center; gap: 12px; padding: 8px; border-radius: var(--radius-sm); color: var(--sug-ink); text-decoration: none; transition: background-color .12s ease; }
.sug-row:hover, .sug-row.is-active { background: var(--sug-hover); }
.sug-thumb { display: grid; width: 48px; height: 48px; overflow: hidden; place-items: center; border-radius: var(--radius-btn); background: #fff; color: #94a3b8; }
.sug-thumb img { width: 100%; height: 100%; object-fit: contain; }
.sug-name { display: -webkit-box; overflow: hidden; -webkit-box-orient: vertical; -webkit-line-clamp: 2; font-size: 14px; font-weight: 600; line-height: 1.35; }
.sug-price { color: var(--sug-price); font-size: 14px; font-weight: 800; white-space: nowrap; font-variant-numeric: tabular-nums; }
.sug-all { display: flex; min-height: 48px; align-items: center; justify-content: center; gap: 6px; margin-top: 6px; padding: 8px 12px; border-top: 1px solid var(--sug-line); color: var(--sug-ink); font-size: 14px; font-weight: 800; text-align: center; text-decoration: none; }
.sug-all:hover, .sug-all.is-active { background: var(--sug-hover); }
.sug-none { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 8px; font-size: 14px; }
.sug-none p, .sug-note { margin: 0; color: var(--sug-muted); }
.sug-note { padding: 8px; font-size: 13.5px; }
.sug-ask { min-height: 44px; padding: 0 16px; border: 0; border-radius: var(--radius-btn); background: var(--pts-gold-gradient); color: var(--color-navy-950); font: inherit; font-weight: 800; cursor: pointer; }
@keyframes sug-in { from { opacity: 0; transform: translateY(4px); } }
@media (max-width: 640px) {
  .sug-row { grid-template-columns: 48px minmax(0, 1fr); }
  .sug-price { grid-column: 2; font-size: 13.5px; }
  .sug-tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (prefers-reduced-motion: reduce) {
  .sug-start, .sug-results { animation: none; }
  .sug-progress, .sug.is-pending .sug-progress, .sug-tile, .sug-tile:hover { transition: none; transform: none; }
}
</style>

<style>
/* Inside the phone's navy glass sheet the list takes the sheet's light-on-dark colours. */
@media (max-width: 820px) {
  .m-sheet-glass .sug { --sug-ink: #fff; --sug-muted: #c9d5e5; --sug-hover: rgb(255 255 255 / 12%); --sug-line: rgb(255 255 255 / 14%); --sug-price: var(--color-gold-300); --sug-tile: rgb(255 255 255 / 8%); }
  .m-sheet-glass .sug-icon { color: var(--color-gold-300); }
}
</style>
