<script setup lang="ts">
import type { PublicCatalogGroup, PublicProductCategoryNode, PublicSiteLink, PublicSitePayload } from '#shared/contracts/public-site'

type MenuMode = 'hover' | 'intent' | null

const props = defineProps<{
  site: PublicSitePayload
}>()

const brandName = computed(() => props.site.brand.name.trim() || 'Phúc Thịnh Solar')
const hotline = computed(() => props.site.brand.hotline.trim())
const brandLogo = computed(() => props.site.brand.logo || '')
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
const mobileSearchInput = ref<HTMLInputElement | null>(null)

const fallbackNav: PublicSiteLink[] = [
  { label: 'Giới thiệu', href: '/gioi-thieu/' },
  { label: 'Giải pháp', href: '/giai-phap/' },
  { label: 'Dự án', href: '/du-an-da-thuc-hien/' },
  { label: 'Kiến thức', href: '/tin-tuc/' },
  { label: 'Hợp tác', href: '/dai-ly/' },
]
const navItems = computed(() => (props.site.navigation?.length ? props.site.navigation : fallbackNav).filter((item) => item.label !== 'Danh mục sản phẩm'))
const catalogGroups = computed(() => {
  if (props.site.catalog) return Object.entries(props.site.catalog).map(([id, group]) => ({ id, ...group })).filter((group) => group.items.length)
  if (props.site.productCategoryTree?.length) return [{ id: 'catalog', label: 'Danh mục sản phẩm', href: '/san-pham/', items: props.site.productCategoryTree.map((node) => ({ label: node.name, href: node.url, children: [] })) }]
  if (props.site.productCategories.length) return [{ id: 'catalog', label: 'Danh mục sản phẩm', href: '/san-pham/', items: props.site.productCategories.map((category) => ({ label: category.name, href: category.url, children: [] })) }]
  return []
})
const hasCategories = computed(() => catalogGroups.value.length > 0)
const mobileCategories = computed(() => props.site.productCategoryTree ?? [])

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

function categoryChildren(node: PublicProductCategoryNode): PublicProductCategoryNode[] { return node.children }

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

        <a class="pts-brand" href="/" :aria-label="`${brandName} - Trang chủ`">
          <img v-if="brandLogo" :src="brandLogo" :alt="brandName" width="260" height="56">
          <span v-else>{{ brandName }}</span>
        </a>

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
          <NuxtLink external v-for="item in navItems" :key="item.href" class="pts-nav-link" :to="item.href">{{ item.label }}</NuxtLink>
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
          <NuxtLink external class="pts-icon-button pts-cart-link" to="/gio-hang/" aria-label="Giỏ hàng" title="Giỏ hàng">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h2l1.5 11h10l2-8H7M9 20h.01M17 20h.01" /></svg>
          </NuxtLink>
          <NuxtLink external class="pts-consult-link" to="/lien-he/#yeu-cau-tu-van">Nhận tư vấn</NuxtLink>
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
          <NuxtLink external class="pts-catalog-all-link" to="/san-pham/" @click="closeCatalog(false)">Xem tất cả sản phẩm</NuxtLink>
          <button class="pts-icon-button pts-catalog-close" type="button" aria-label="Đóng danh mục sản phẩm" @click="closeCatalog()">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
          </button>
        </div>

        <div v-if="hasCategories" class="pts-catalog-columns">
          <section v-for="group in catalogGroups" :key="group.id" class="pts-catalog-group">
            <h3><NuxtLink external :to="group.href" @click="closeCatalog(false)">{{ group.label }}</NuxtLink></h3>
            <ul>
              <li v-for="item in group.items" :key="item.href + item.label">
                <NuxtLink external :to="item.href" @click="closeCatalog(false)">
                  <span>{{ item.label }}</span>
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7" /></svg>
                </NuxtLink>
                <ul v-if="item.children.length" class="pts-catalog-sublist">
                  <li v-for="child in item.children" :key="child.href + child.label"><NuxtLink external :to="child.href" @click="closeCatalog(false)">{{ child.label }}</NuxtLink></li>
                </ul>
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
          <NuxtLink external v-for="item in navItems" :key="item.href" class="pts-mobile-link" :to="item.href" @click="closeMobileMenu(false)">{{ item.label }}</NuxtLink>
          <button class="pts-mobile-catalog-toggle" type="button" :aria-expanded="mobileCatalogOpen" @click="mobileCatalogOpen = !mobileCatalogOpen">
            <span>Danh mục sản phẩm</span>
            <svg class="pts-chevron" :class="{ 'is-open': mobileCatalogOpen }" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5" /></svg>
          </button>
          <div v-if="mobileCatalogOpen" class="pts-mobile-catalog-list">
            <NuxtLink external to="/san-pham/" @click="closeMobileMenu(false)">Xem tất cả sản phẩm</NuxtLink>
            <template v-if="mobileCategories.length">
              <template v-for="category in mobileCategories" :key="category.id">
                <NuxtLink external :to="category.url" @click="closeMobileMenu(false)">{{ category.name }}</NuxtLink>
                <NuxtLink external v-for="child in category.children" :key="child.id" :to="child.url" @click="closeMobileMenu(false)">{{ child.name }}</NuxtLink>
              </template>
            </template>
            <NuxtLink external v-else v-for="category in props.site.productCategories" :key="category.id" :to="category.url" @click="closeMobileMenu(false)">{{ category.name }}</NuxtLink>
            <span v-if="!hasCategories" class="pts-shell-empty">Danh mục sản phẩm chưa có dữ liệu.</span>
          </div>
        </nav>
        <NuxtLink external class="pts-consult-link pts-mobile-consult" to="/lien-he/#yeu-cau-tu-van" @click="closeMobileMenu(false)">Nhận tư vấn</NuxtLink>
      </aside>
    </div>
  </div>
</template>
