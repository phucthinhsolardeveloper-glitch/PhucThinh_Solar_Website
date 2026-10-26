<script setup lang="ts">
import type { PublicProductCategory, PublicSitePayload } from '#shared/contracts/public-site'

type MenuMode = 'hover' | 'intent' | null

const props = defineProps<{
  site: PublicSitePayload
}>()

const brandName = computed(() => props.site.brand.name.trim() || 'Phúc Thịnh Solar')
const hotline = computed(() => props.site.brand.hotline.trim())
const searchOpen = ref(false)
const searchKeyword = ref('')
const catalogOpen = ref(false)
const catalogMode = ref<MenuMode>(null)
const mobileOpen = ref(false)
const mobileCatalogOpen = ref(false)
const catalogTrigger = ref<HTMLButtonElement | null>(null)
const mobileTrigger = ref<HTMLButtonElement | null>(null)
const catalogPanel = ref<HTMLElement | null>(null)
const mobilePanel = ref<HTMLElement | null>(null)
const searchInput = ref<HTMLInputElement | null>(null)

const navItems = [
  { label: 'Giới thiệu', to: '/ve-chung-toi/' },
  { label: 'Giải pháp', to: '/giai-phap/' },
  { label: 'Dự án', to: '/du-an-da-thuc-hien/' },
  { label: 'Kiến thức', to: '/tin-tuc/' },
  { label: 'Hợp tác', to: '/dai-ly/' },
]

const groupDefinitions = [
  { id: 'lighting', title: 'Chiếu sáng', matches: (slug: string) => /^den(?:-|$)/.test(slug) },
  {
    id: 'solar',
    title: 'Điện mặt trời',
    matches: (slug: string) => ['tam-pin-nang-luong-mat-troi', 'bien-tan-inverter', 'pin-luu-tru'].includes(slug),
  },
  {
    id: 'devices',
    title: 'Thiết bị ứng dụng',
    matches: (slug: string) => /(?:bom|quat|thiet-bi|phu-kien)/.test(slug),
  },
]

const categoryGroups = computed(() => {
  const groups = groupDefinitions.map((definition) => ({ ...definition, categories: [] as PublicProductCategory[] }))
  const other: PublicProductCategory[] = []

  for (const category of props.site.productCategories) {
    const group = groups.find((candidate) => candidate.matches(category.slug))
    if (group) group.categories.push(category)
    else other.push(category)
  }

  if (other.length) groups.push({ id: 'other', title: 'Danh mục sản phẩm', matches: () => false, categories: other })
  return groups.filter((group) => group.categories.length)
})

const hasCategories = computed(() => props.site.productCategories.length > 0)

function focusFirst(container: HTMLElement | null) {
  if (!container) return
  const first = container.querySelector<HTMLElement>('a, button, input, [tabindex]:not([tabindex="-1"])')
  first?.focus()
}

function syncBodyLock() {
  if (import.meta.client) document.body.classList.toggle('pts-shell-menu-open', catalogOpen.value || mobileOpen.value)
}

function openCatalog(mode: Exclude<MenuMode, null> = 'intent') {
  if (mode === 'hover' && import.meta.client && !window.matchMedia('(hover: hover)').matches) return
  mobileOpen.value = false
  catalogMode.value = mode
  catalogOpen.value = true
  syncBodyLock()
  if (mode === 'intent') nextTick(() => focusFirst(catalogPanel.value))
}

function closeCatalog(restoreFocus = true) {
  const shouldRestore = restoreFocus && catalogOpen.value
  catalogOpen.value = false
  catalogMode.value = null
  syncBodyLock()
  if (shouldRestore) nextTick(() => catalogTrigger.value?.focus())
}

function toggleCatalog() {
  if (catalogOpen.value) closeCatalog()
  else openCatalog('intent')
}

function openMobileMenu() {
  closeCatalog(false)
  mobileOpen.value = true
  syncBodyLock()
  nextTick(() => focusFirst(mobilePanel.value))
}

function closeMobileMenu(restoreFocus = true) {
  const shouldRestore = restoreFocus && mobileOpen.value
  mobileOpen.value = false
  mobileCatalogOpen.value = false
  syncBodyLock()
  if (shouldRestore) nextTick(() => mobileTrigger.value?.focus())
}

function toggleSearch() {
  searchOpen.value = !searchOpen.value
  if (searchOpen.value) nextTick(() => searchInput.value?.focus())
}

async function submitSearch() {
  const keyword = searchKeyword.value.trim()
  if (!keyword) return
  await navigateTo({ path: '/tim-kiem/', query: { keyword } })
}

function trapFocus(event: KeyboardEvent, container: HTMLElement | null) {
  if (event.key !== 'Tab' || !container) return
  const focusable = [...container.querySelectorAll<HTMLElement>('a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])')]
  if (!focusable.length) return
  const first = focusable[0]!
  const last = focusable[focusable.length - 1]!
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault()
    last.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first.focus()
  }
}

function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    if (mobileOpen.value) closeMobileMenu()
    else if (catalogOpen.value) closeCatalog()
    return
  }
  if (mobileOpen.value) trapFocus(event, mobilePanel.value)
  else if (catalogOpen.value) trapFocus(event, catalogPanel.value)
}

function handleShellLeave() {
  if (catalogMode.value === 'hover') closeCatalog(false)
}

onMounted(() => document.addEventListener('keydown', handleKeydown))
onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.body.classList.remove('pts-shell-menu-open')
})
</script>

<template>
  <div class="pts-shell" @mouseleave="handleShellLeave">
    <header class="pts-site-header">
      <div class="pts-container pts-site-header__row">
        <button
          ref="mobileTrigger"
          class="pts-icon-button pts-mobile-trigger"
          type="button"
          :aria-expanded="mobileOpen"
          aria-controls="ptsMobileMenu"
          aria-label="Mở danh mục điều hướng"
          @click="mobileOpen ? closeMobileMenu() : openMobileMenu()"
        >
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" /></svg>
        </button>

        <NuxtLink class="pts-brand" to="/" :aria-label="`${brandName} - Trang chủ`">
          <span>{{ brandName }}</span>
        </NuxtLink>

        <nav class="pts-desktop-nav" aria-label="Điều hướng chính">
          <div class="pts-catalog-control" @mouseenter="openCatalog('hover')">
            <button
              ref="catalogTrigger"
              class="pts-catalog-trigger"
              type="button"
              :aria-expanded="catalogOpen"
              aria-controls="ptsCatalogMenu"
              @click="toggleCatalog"
            >
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
              <span>Danh mục sản phẩm</span>
              <svg class="pts-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5" /></svg>
            </button>
          </div>
          <NuxtLink v-for="item in navItems" :key="item.to" class="pts-nav-link" :to="item.to">{{ item.label }}</NuxtLink>
        </nav>

        <div class="pts-site-header__actions">
          <button
            class="pts-icon-button"
            type="button"
            :aria-expanded="searchOpen"
            aria-controls="ptsHeaderSearch"
            aria-label="Mở tìm kiếm"
            @click="toggleSearch"
          >
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.5" /><path d="m16 16 4.5 4.5" /></svg>
          </button>
          <a v-if="hotline" class="pts-header-phone" :href="`tel:${hotline.replace(/[^+\d]/g, '')}`" :aria-label="`Gọi ${hotline}`">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.5 3.5 9 3l2 5-2 1.7a14 14 0 0 0 5.3 5.3L16 13l5 2-.5 2.5c-.3 1.5-1.7 2.5-3.2 2.2C10.8 18.5 5.5 13.2 4.3 6.7 4 5.2 5 3.8 6.5 3.5Z" /></svg>
            <span>{{ hotline }}</span>
          </a>
          <NuxtLink class="pts-icon-button pts-cart-link" to="/gio-hang/" aria-label="Giỏ hàng" title="Giỏ hàng">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h2l1.5 11h10l2-8H7M9 20h.01M17 20h.01" /></svg>
          </NuxtLink>
          <NuxtLink class="pts-consult-link" to="/lien-he/#yeu-cau-tu-van">Nhận tư vấn</NuxtLink>
        </div>
      </div>

      <div v-if="searchOpen" id="ptsHeaderSearch" class="pts-header-search">
        <div class="pts-container">
          <form role="search" @submit.prevent="submitSearch">
            <label for="ptsHeaderSearchInput">Tìm kiếm sản phẩm hoặc giải pháp</label>
            <input id="ptsHeaderSearchInput" ref="searchInput" v-model="searchKeyword" type="search" placeholder="Bạn cần tìm sản phẩm hoặc giải pháp nào?" autocomplete="off">
            <button type="submit" aria-label="Tìm kiếm">
              <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.5" /><path d="m16 16 4.5 4.5" /></svg>
            </button>
          </form>
        </div>
      </div>
    </header>

    <div v-if="catalogOpen" class="pts-catalog-overlay" aria-hidden="false">
      <button class="pts-catalog-scrim" type="button" aria-label="Đóng danh mục sản phẩm" @click="closeCatalog()" />
      <section id="ptsCatalogMenu" ref="catalogPanel" class="pts-catalog-panel" role="dialog" aria-modal="true" aria-labelledby="ptsCatalogTitle" @mouseenter="catalogMode = 'hover'">
        <div class="pts-catalog-heading">
          <div>
            <h2 id="ptsCatalogTitle">Danh mục sản phẩm</h2>
            <p>Dữ liệu danh mục đang được cung cấp từ API public.</p>
          </div>
          <NuxtLink class="pts-catalog-all-link" to="/san-pham/" @click="closeCatalog(false)">Xem tất cả sản phẩm</NuxtLink>
          <button class="pts-icon-button pts-catalog-close" type="button" aria-label="Đóng danh mục sản phẩm" @click="closeCatalog()">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
          </button>
        </div>

        <div v-if="hasCategories" class="pts-catalog-columns">
          <section v-for="group in categoryGroups" :key="group.id" class="pts-catalog-group">
            <h3>{{ group.title }}</h3>
            <ul>
              <li v-for="category in group.categories" :key="category.id">
                <NuxtLink :to="category.url" @click="closeCatalog(false)">
                  <span>{{ category.name }}</span>
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7" /></svg>
                </NuxtLink>
              </li>
            </ul>
          </section>
        </div>
        <p v-else class="pts-shell-empty">Danh mục sản phẩm chưa có dữ liệu.</p>
      </section>
    </div>

    <div v-if="mobileOpen" class="pts-mobile-menu-layer">
      <button class="pts-mobile-menu-scrim" type="button" aria-label="Đóng danh mục điều hướng" @click="closeMobileMenu()" />
      <aside id="ptsMobileMenu" ref="mobilePanel" class="pts-mobile-menu" role="dialog" aria-modal="true" aria-labelledby="ptsMobileTitle">
        <div class="pts-mobile-menu__heading">
          <h2 id="ptsMobileTitle">Điều hướng</h2>
          <button class="pts-icon-button" type="button" aria-label="Đóng danh mục điều hướng" @click="closeMobileMenu()">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
          </button>
        </div>
        <form class="pts-mobile-search" role="search" @submit.prevent="submitSearch">
          <label for="ptsMobileSearchInput">Tìm kiếm sản phẩm hoặc giải pháp</label>
          <input id="ptsMobileSearchInput" ref="mobileSearchInput" v-model="searchKeyword" type="search" placeholder="Tìm kiếm sản phẩm" autocomplete="off">
          <button type="submit" aria-label="Tìm kiếm">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.5" /><path d="m16 16 4.5 4.5" /></svg>
          </button>
        </form>
        <nav aria-label="Điều hướng mobile">
          <NuxtLink v-for="item in navItems" :key="item.to" class="pts-mobile-link" :to="item.to" @click="closeMobileMenu(false)">{{ item.label }}</NuxtLink>
          <button class="pts-mobile-catalog-toggle" type="button" :aria-expanded="mobileCatalogOpen" @click="mobileCatalogOpen = !mobileCatalogOpen">
            <span>Danh mục sản phẩm</span>
            <svg class="pts-chevron" :class="{ 'is-open': mobileCatalogOpen }" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5" /></svg>
          </button>
          <div v-if="mobileCatalogOpen" class="pts-mobile-catalog-list">
            <NuxtLink to="/san-pham/" @click="closeMobileMenu(false)">Xem tất cả sản phẩm</NuxtLink>
            <NuxtLink v-for="category in props.site.productCategories" :key="category.id" :to="category.url" @click="closeMobileMenu(false)">{{ category.name }}</NuxtLink>
            <span v-if="!hasCategories" class="pts-shell-empty">Danh mục sản phẩm chưa có dữ liệu.</span>
          </div>
        </nav>
        <NuxtLink class="pts-consult-link pts-mobile-consult" to="/lien-he/#yeu-cau-tu-van" @click="closeMobileMenu(false)">Nhận tư vấn</NuxtLink>
      </aside>
    </div>
  </div>
</template>
