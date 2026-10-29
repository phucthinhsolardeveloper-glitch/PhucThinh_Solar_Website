<script setup lang="ts">
import type { PublicCatalogGroup, PublicProductCategoryNode, PublicSiteLink, PublicSitePayload } from '#shared/contracts/public-site'
import {
  catalogGroupMeta,
  catalogItemIcon,
  catalogMenuSearchText,
  sortCatalogGroups,
} from '#shared/catalog-menu-presentation'
import { isRuntimeFlagEnabled } from '#shared/public-route-gate'

type MenuMode = 'hover' | 'intent' | null

const props = defineProps<{
  site: PublicSitePayload
}>()

const route = useRoute()
const { active: isMigrationPreview, generic: shellHref, external: isExternalLink } = useMigrationLinks()
const { cart: migrationCart, refresh: refreshMigrationCart } = useMigrationCart()
const config = useRuntimeConfig()
const cartCountAvailable = computed(() => isMigrationPreview.value || [
  config.public.homePublicRouteEnabled,
  config.public.catalogPublicRouteEnabled,
  config.public.productDetailPublicRouteEnabled,
  config.public.brandDetailPublicRouteEnabled,
  config.public.searchPublicRouteEnabled,
  config.public.cartPublicRouteEnabled,
].some(isRuntimeFlagEnabled))

const brandName = computed(() => props.site.brand.name.trim() || 'Phúc Thịnh Solar')
const hotline = computed(() => props.site.brand.hotline.trim())
const technicalHotline = computed(() => props.site.brand.technicalHotline?.trim() ?? '')
const brandLogo = computed(() => props.site.brand.logo || '')
const brandLogoSrc = computed(() => {
  const src = brandLogo.value
  return /^\/upload\/photo\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(src) ? `/thumbs/248x35x1${src}` : src
})
const quickCategories = computed(() => props.site.productCategories.slice(0, 4))

const searchOpen = ref(false)
const searchKeyword = ref('')
const catalogOpen = ref(false)
const catalogMode = ref<MenuMode>(null)
const catalogKeyword = ref('')
const catalogSearchInput = ref<HTMLInputElement | null>(null)
const mobileOpen = ref(false)
const mobileCatalogOpen = ref(false)
const mobileNavOpen = ref<string | null>(null)
const navDropdownOpen = ref<string | null>(null)
const catalogTrigger = ref<HTMLButtonElement | null>(null)
const mobileTrigger = ref<HTMLButtonElement | null>(null)
const catalogPanel = ref<HTMLElement | null>(null)
const mobilePanel = ref<HTMLElement | null>(null)
const searchInput = ref<HTMLInputElement | null>(null)
const mobileSearchInput = ref<HTMLInputElement | null>(null)
const searchTrigger = ref<HTMLButtonElement | null>(null)
const searchPanel = ref<HTMLElement | null>(null)
const navDropdownTrigger = ref<HTMLElement | null>(null)
let bodyOverflowBeforeLock: string | null = null

const fallbackNav: PublicSiteLink[] = [
  { label: 'Giới thiệu', href: '/gioi-thieu/' },
  { label: 'Giải pháp', href: '/giai-phap/' },
  { label: 'Dự án', href: '/du-an-da-thuc-hien/' },
  { label: 'Kiến thức', href: '/tin-tuc/' },
  { label: 'Hợp tác', href: '/dai-ly/' },
]
const navItems = computed(() => fallbackNav.map((fallback) => {
  const sourceItem = props.site.navigation?.find((item) => item.href === fallback.href)
  return sourceItem?.children === undefined
    ? fallback
    : { ...fallback, children: sourceItem.children }
}))

function isActiveNavItem(item: PublicSiteLink): boolean {
  const path = route.path
  if (item.href === '/gioi-thieu/') return path.startsWith('/gioi-thieu') || path.startsWith('/ve-chung-toi') || path.includes('about-preview') || path.startsWith('/lien-he') || path.includes('contact-preview')
  if (item.href === '/giai-phap/') return path.startsWith('/giai-phap') || path.includes('solution')
  if (item.href === '/du-an-da-thuc-hien/') return path.startsWith('/du-an') || path.startsWith('/danh-muc-du-an') || path.includes('project')
  if (item.href === '/tin-tuc/') return path.startsWith('/tin-tuc') || path.includes('news')
  if (item.href === '/dai-ly/') return path.startsWith('/dai-ly') || path.startsWith('/he-thong-dai-ly') || path.includes('dealer')
  return path === item.href
}

type RenderedCatalogGroup = PublicCatalogGroup & { id: string }
type FlatSiteLink = { link: PublicSiteLink; depth: number; key: string }

function categoryNodeLink(node: PublicProductCategoryNode): PublicSiteLink {
  return { label: node.name, href: node.url, children: node.children.map(categoryNodeLink) }
}

function categoryNodeItem(node: PublicProductCategoryNode) {
  return { label: node.name, href: node.url, children: node.children.map(categoryNodeLink) }
}

function flattenLinks(links: PublicSiteLink[], depth = 0, prefix = ''): FlatSiteLink[] {
  return links.flatMap((link, index) => {
    const key = `${prefix}${index}-${link.href}`
    return [{ link, depth, key }, ...flattenLinks(link.children ?? [], depth + 1, `${key}-`)]
  })
}

function catalogGroupLinks(group: RenderedCatalogGroup): FlatSiteLink[] {
  const items: PublicSiteLink[] = group.items.map((item) => ({ ...item }))
  return flattenLinks(items, 0, `catalog-${group.id}-`)
}

const catalogGroups = computed<RenderedCatalogGroup[]>(() => {
  const dtoGroups = props.site.catalog
    ? Object.entries(props.site.catalog)
      .map(([id, group]) => ({ id, ...group }))
      .filter((group) => group.items.length)
    : []
  if (dtoGroups.length) return sortCatalogGroups(dtoGroups)
  if (props.site.productCategoryTree?.length) {
    return [{
      id: 'taxonomy',
      label: 'Danh mục sản phẩm',
      href: '/san-pham/',
      items: props.site.productCategoryTree.map(categoryNodeItem),
    }]
  }
  if (props.site.productCategories.length) {
    return [{
      id: 'categories',
      label: 'Danh mục sản phẩm',
      href: '/san-pham/',
      items: props.site.productCategories.map((category) => ({ label: category.name, href: category.url, children: [] })),
    }]
  }
  return []
})
const hasCategories = computed(() => catalogGroups.value.some((group) => group.items.length > 0))

type RenderedCatalogItem = PublicCatalogGroup['items'][number] & { searchText: string; icon: ReturnType<typeof catalogItemIcon> }
type RenderedCatalogColumn = RenderedCatalogGroup & {
  meta: ReturnType<typeof catalogGroupMeta>
  groupHref: string
  visibleItems: RenderedCatalogItem[]
}

const catalogColumns = computed<RenderedCatalogColumn[]>(() => {
  const query = catalogKeyword.value.trim().toLocaleLowerCase()
  return catalogGroups.value.map((group) => {
    const meta = catalogGroupMeta(group.id)
    const items = group.items.map((item) => ({
      ...item,
      searchText: catalogMenuSearchText(item.label, item.children),
      icon: catalogItemIcon(item.label),
    }))
    const visibleItems = query
      ? items.filter((item) => `${item.label} ${item.searchText}`.toLocaleLowerCase().includes(query))
      : items
    return {
      ...group,
      meta,
      groupHref: meta.href || group.href || '/san-pham/',
      visibleItems,
    }
  }).filter((group) => group.visibleItems.length > 0)
})

function focusFirst(container: HTMLElement | null) {
  if (!container) return
  const first = container.querySelector<HTMLElement>('a, button, input, [tabindex]:not([tabindex="-1"])')
  first?.focus()
}

function syncBodyLock() {
  if (!import.meta.client) return
  const overlayOpen = searchOpen.value || catalogOpen.value || mobileOpen.value
  if (overlayOpen) {
    if (bodyOverflowBeforeLock === null) bodyOverflowBeforeLock = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    return
  }
  if (bodyOverflowBeforeLock !== null) {
    document.body.style.overflow = bodyOverflowBeforeLock
    bodyOverflowBeforeLock = null
  }
}

function openCatalog(mode: Exclude<MenuMode, null> = 'intent') {
  if (mode === 'hover' && import.meta.client && !window.matchMedia('(hover: hover)').matches) return
  closeSearch(false)
  closeNavDropdown(false)
  closeMobileMenu(false)
  catalogMode.value = mode
  catalogOpen.value = true
  syncBodyLock()
  if (mode === 'intent') {
    nextTick(() => {
      if (catalogSearchInput.value) catalogSearchInput.value.focus()
      else focusFirst(catalogPanel.value)
    })
  }
}

function closeCatalog(restoreFocus = true) {
  const shouldRestore = restoreFocus && catalogOpen.value
  catalogOpen.value = false
  catalogMode.value = null
  catalogKeyword.value = ''
  syncBodyLock()
  if (shouldRestore) nextTick(() => catalogTrigger.value?.focus())
}

async function submitCatalogSearch() {
  const keyword = catalogKeyword.value.trim()
  if (!keyword) return
  closeCatalog(false)
  await navigateTo({ path: shellHref('/tim-kiem/'), query: { keyword } })
}

function toggleCatalog() {
  if (catalogOpen.value) closeCatalog()
  else openCatalog('intent')
}

function openMobileMenu() {
  closeSearch(false)
  closeCatalog(false)
  closeNavDropdown(false)
  mobileOpen.value = true
  syncBodyLock()
  nextTick(() => focusFirst(mobilePanel.value))
}

function closeMobileMenu(restoreFocus = true) {
  const shouldRestore = restoreFocus && mobileOpen.value
  mobileOpen.value = false
  mobileCatalogOpen.value = false
  mobileNavOpen.value = null
  syncBodyLock()
  if (shouldRestore) nextTick(() => mobileTrigger.value?.focus())
}

function closeSearch(restoreFocus = true) {
  const shouldRestore = restoreFocus && searchOpen.value
  searchOpen.value = false
  syncBodyLock()
  if (shouldRestore) nextTick(() => searchTrigger.value?.focus())
}

function toggleSearch() {
  if (searchOpen.value) {
    closeSearch()
    return
  }
  closeCatalog(false)
  closeMobileMenu(false)
  closeNavDropdown(false)
  searchOpen.value = true
  syncBodyLock()
  nextTick(() => searchInput.value?.focus())
}

async function submitSearch() {
  const keyword = searchKeyword.value.trim()
  if (!keyword) return
  closeSearch(false)
  closeMobileMenu(false)
  await navigateTo({ path: shellHref('/tim-kiem/'), query: { keyword } })
}

function openNavDropdown(item: PublicSiteLink, event: Event) {
  if (!item.children?.length) return
  const currentTarget = event.currentTarget
  navDropdownTrigger.value = currentTarget instanceof HTMLElement ? currentTarget.querySelector('a') : null
  navDropdownOpen.value = item.href
}

function closeNavDropdown(restoreFocus = false) {
  const shouldRestore = restoreFocus && navDropdownOpen.value !== null
  const trigger = navDropdownTrigger.value
  navDropdownOpen.value = null
  navDropdownTrigger.value = null
  if (shouldRestore) nextTick(() => trigger?.focus())
}

function handleNavFocusout(event: FocusEvent) {
  const currentTarget = event.currentTarget
  const relatedTarget = event.relatedTarget
  if (currentTarget instanceof HTMLElement && (!relatedTarget || !currentTarget.contains(relatedTarget as Node))) closeNavDropdown()
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
    if (searchOpen.value) closeSearch()
    else if (mobileOpen.value) closeMobileMenu()
    else if (catalogOpen.value) closeCatalog()
    else if (navDropdownOpen.value) closeNavDropdown(true)
    return
  }
  if (mobileOpen.value) trapFocus(event, mobilePanel.value)
  else if (catalogOpen.value) trapFocus(event, catalogPanel.value)
  else if (searchOpen.value) trapFocus(event, searchPanel.value)
}

function handleShellLeave() {
  if (catalogMode.value === 'hover') closeCatalog(false)
}

function telHrefFor(value: string): string {
  return `tel:${value.replace(/[^+\d]/g, '')}`
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
  if (cartCountAvailable.value) void refreshMigrationCart()
})
onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeydown)
  if (import.meta.client && bodyOverflowBeforeLock !== null) document.body.style.overflow = bodyOverflowBeforeLock
})
</script>

<template>
  <!-- Tầng 1: Thanh tiện ích đầu trang (cuộn tự nhiên để thanh điều hướng sticky bên dưới gọn gàng) -->
  <div v-if="hotline || technicalHotline || cartCountAvailable" class="border-b border-hdr-line bg-slate-50 text-xs text-slate-600">
    <div class="mx-auto flex min-h-9 w-full max-w-[1680px] flex-wrap items-center justify-between gap-x-6 gap-y-1 px-4 py-1 max-[359px]:px-3 tb:px-6 nav:px-(--spacing-gutter)">
      <div class="flex flex-wrap items-center gap-x-5 gap-y-1">
        <a
          v-if="hotline"
          class="inline-flex items-center gap-1.5 text-slate-600 no-underline transition-colors hover:text-gold-700"
          :href="telHrefFor(hotline)"
        >
          <svg class="h-3.5 w-3.5 flex-none text-gold-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.5 3.5 9 3l2 5-2 1.7a14 14 0 0 0 5.3 5.3L16 13l5 2-.5 2.5c-.3 1.5-1.7 2.5-3.2 2.2C10.8 18.5 5.5 13.2 4.3 6.7 4 5.2 5 3.8 6.5 3.5Z" /></svg>
          <span>Tư vấn bán hàng: <strong class="font-bold text-navy-800">{{ hotline }}</strong></span>
        </a>
        <template v-if="hotline && technicalHotline">
          <span class="hidden text-slate-300 tb:inline" aria-hidden="true">|</span>
        </template>
        <a
          v-if="technicalHotline"
          class="hidden items-center gap-1.5 text-slate-600 no-underline transition-colors hover:text-gold-700 tb:inline-flex"
          :href="telHrefFor(technicalHotline)"
        >
          <svg class="h-3.5 w-3.5 flex-none text-gold-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a2 2 0 0 0-2 2v2.17a2 2 0 0 1-.59 1.42L7.59 9.41A2 2 0 0 0 7 10.83V13a2 2 0 0 0 2 2h.17a2 2 0 0 1 1.42.59l1.82 1.82a2 2 0 0 0 1.42.59H16a2 2 0 0 0 2-2v-2.17a2 2 0 0 1 .59-1.42L20 10.41" /></svg>
          <span>Hỗ trợ kỹ thuật: <strong class="font-bold text-navy-800">{{ technicalHotline }}</strong></span>
        </a>
      </div>

      <div class="hidden items-center gap-5 ph:flex">
        <NuxtLink
          :external="isExternalLink(shellHref('/gio-hang/'))"
          class="inline-flex items-center gap-1.5 text-slate-700 no-underline transition-colors hover:text-gold-700"
          :to="shellHref('/gio-hang/')"
        >
          <svg class="h-3.5 w-3.5 flex-none text-navy-800" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h2l1 12h11l1.6-8H7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /><circle cx="9" cy="20" r="1.5" fill="currentColor" /><circle cx="16" cy="20" r="1.5" fill="currentColor" /></svg>
          <span>Giỏ hàng<template v-if="cartCountAvailable && migrationCart">: <b class="font-bold text-gold-700">{{ migrationCart.count }}</b> sản phẩm</template></span>
        </NuxtLink>
        <NuxtLink
          :external="isExternalLink(shellHref('/lien-he/#yeu-cau-tu-van'))"
          class="rounded-chip bg-navy-800 px-3 py-1 text-xs font-bold tracking-wide text-gold-400 no-underline transition-colors hover:bg-navy-950 hover:text-gold-300"
          :to="shellHref('/lien-he/#yeu-cau-tu-van')"
        >
          Yêu cầu khảo sát
        </NuxtLink>
      </div>
    </div>
  </div>

  <!-- Tầng 2: Thanh điều hướng chính sticky gọn gàng + tìm kiếm nhanh + mega-menu -->
  <div class="sticky top-0 z-[1000]" @mouseleave="handleShellLeave">
    <header
      :inert="mobileOpen ? true : undefined"
      class="relative z-[1000] border-b border-hdr-line bg-white/96 shadow-[0_1px_4px_rgba(10,34,81,0.06)] backdrop-blur-md"
    >
      <div
        class="mx-auto flex min-h-[66px] w-full max-w-[1680px] items-center justify-between gap-3 px-4 py-2 max-[359px]:px-3
               max-ph:min-h-[60px] max-ph:gap-2 max-ph:py-1.5
               tb:px-6
               nav:min-h-[78px] nav:gap-5 nav:px-(--spacing-gutter) nav:py-3"
      >
        <!-- Hamburger: dưới mốc nav (1321px), vùng chạm >= 44x44px -->
        <button
          ref="mobileTrigger"
          class="order-first relative flex h-11 w-11 flex-none items-center justify-center rounded-chip border
                 border-[#d8dee6] bg-white text-hdr-ink transition-colors hover:bg-hdr-surface nav:hidden"
          type="button"
          :aria-expanded="mobileOpen"
          aria-controls="ptsMobileMenu"
          :aria-label="mobileOpen ? 'Đóng danh mục điều hướng' : 'Mở danh mục điều hướng'"
          @click="mobileOpen ? closeMobileMenu() : openMobileMenu()"
        >
          <span class="absolute h-[2px] w-5 rounded-full bg-current transition-transform duration-200" :class="mobileOpen ? 'rotate-45' : '-translate-y-[6px]'" aria-hidden="true" />
          <span class="absolute h-[2px] w-5 rounded-full bg-current transition-opacity duration-150" :class="mobileOpen ? 'opacity-0' : 'opacity-100'" aria-hidden="true" />
          <span class="absolute h-[2px] w-5 rounded-full bg-current transition-transform duration-200" :class="mobileOpen ? '-rotate-45' : 'translate-y-[6px]'" aria-hidden="true" />
        </button>

        <!-- Thương hiệu -->
        <a class="mr-auto flex min-w-0 flex-1 items-center nav:mr-0 nav:flex-none" :href="shellHref('/')" :aria-label="`${brandName} - Trang chủ`">
          <img
            v-if="brandLogo"
            :src="brandLogoSrc"
            :alt="brandName"
            width="248"
            height="35"
            class="block h-auto w-auto object-contain max-w-[min(228px,100%)] max-h-[48px]
                   max-ph:max-w-[min(198px,100%)] max-ph:max-h-[42px]
                   tb:max-w-[min(238px,100%)] tb:max-h-[50px]
                   nav:max-w-[min(248px,100%)] nav:max-h-[54px]
                   wd:max-w-[min(268px,100%)] wd:max-h-[58px]"
          >
          <span v-else class="truncate text-xl font-black tracking-tight text-hdr-ink">{{ brandName }}</span>
        </a>

        <!-- Ô tìm kiếm trực tuyến + lối tắt danh mục thực tế trên màn hình rộng (>=1501px) -->
        <div class="mx-2 hidden min-w-0 max-w-[390px] flex-1 flex-col wd:flex ul:max-w-[480px]">
          <form class="relative flex w-full items-center" role="search" @submit.prevent="submitSearch">
            <label class="sr-only" for="ptsInlineSearchInput">Tìm kiếm giải pháp hoặc sản phẩm</label>
            <input
              id="ptsInlineSearchInput"
              v-model="searchKeyword"
              class="h-10 w-full rounded-btn border border-hdr-line bg-slate-50/90 pl-3.5 pr-10 text-[13px] font-medium text-hdr-ink outline-none transition-colors placeholder:text-hdr-muted focus:border-hdr-gold focus:bg-white"
              type="search"
              placeholder="Tìm kiếm giải pháp hoặc sản phẩm..."
              autocomplete="off"
            >
            <button
              type="submit"
              aria-label="Tìm kiếm"
              class="absolute right-1 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-chip text-slate-500 transition-colors hover:text-gold-700"
            >
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.5" /><path d="m16 16 4.5 4.5" /></svg>
            </button>
          </form>
          <div v-if="quickCategories.length" class="mt-1 flex items-center gap-1.5 overflow-hidden pl-1 text-[11px] text-slate-500">
            <span class="flex-none text-slate-400">Tìm nhanh:</span>
            <template v-for="(cat, idx) in quickCategories" :key="cat.id">
              <span v-if="idx > 0" class="flex-none text-slate-300" aria-hidden="true">•</span>
              <NuxtLink
                :external="isExternalLink(shellHref(cat.url))"
                class="truncate text-slate-500 no-underline transition-colors hover:text-gold-700"
                :to="shellHref(cat.url)"
              >
                {{ cat.name.replace(/\s*năng lượng mặt trời/i, ' NLMT') }}
              </NuxtLink>
            </template>
          </div>
        </div>

        <!-- Điều hướng chính desktop (Danh mục sản phẩm + đúng 5 nhóm IA chuẩn) -->
        <nav class="hidden min-w-0 nav:block" aria-label="Điều hướng chính">
          <ul class="flex items-center justify-center gap-1">
            <li class="mr-1.5 flex flex-none items-center" @mouseenter="openCatalog('hover')">
              <button
                ref="catalogTrigger"
                class="flex min-h-11 items-center gap-2 whitespace-nowrap rounded-btn bg-hdr-gold px-3.5 text-[13.5px] font-bold
                       text-hdr-ink transition-colors hover:bg-hdr-gold-hover hover:text-white wd:px-4 wd:text-[14px]"
                type="button"
                :aria-expanded="catalogOpen"
                aria-controls="ptsCatalogMenu"
                @click="toggleCatalog"
              >
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                <span>Danh mục sản phẩm</span>
                <svg class="h-[7px] w-[7px]" viewBox="0 0 10 10" aria-hidden="true"><path d="M1 3l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
              </button>
            </li>
            <li
              v-for="(item, index) in navItems"
              :key="item.href"
              class="relative flex items-center"
              @mouseenter="openNavDropdown(item, $event)"
              @mouseleave="closeNavDropdown()"
              @focusin="openNavDropdown(item, $event)"
              @focusout="handleNavFocusout"
            >
              <NuxtLink
                :external="isExternalLink(shellHref(item.href))"
                class="flex min-h-11 items-center whitespace-nowrap border-b-2 px-2.5 text-[14px] leading-tight no-underline transition-colors wd:px-3 wd:text-[14.5px]"
                :class="isActiveNavItem(item)
                  ? 'border-gold-500 font-bold text-navy-800'
                  : 'border-transparent font-semibold text-hdr-text hover:bg-hdr-surface hover:text-navy-800'"
                :to="shellHref(item.href)"
                :aria-expanded="item.children?.length ? navDropdownOpen === item.href : undefined"
                :aria-controls="item.children?.length ? `ptsNavMenu-${index}` : undefined"
              >
                {{ item.label }}
                <svg v-if="item.children?.length" class="ml-1.5 h-[7px] w-[7px] text-hdr-muted" viewBox="0 0 10 10" aria-hidden="true"><path d="M1 3l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
              </NuxtLink>
              <div
                v-if="item.children?.length && navDropdownOpen === item.href"
                :id="`ptsNavMenu-${index}`"
                class="absolute left-0 top-full z-20 min-w-[250px] rounded-btn border border-hdr-line bg-white p-2 shadow-[0_14px_28px_rgba(16,36,58,.12)]"
                role="group"
                :aria-label="`${item.label} - menu con`"
              >
                <NuxtLink
                  v-for="entry in flattenLinks(item.children)"
                  :key="entry.key"
                  :external="isExternalLink(shellHref(entry.link.href))"
                  class="flex min-h-11 items-center rounded-chip px-3 py-2 text-sm text-hdr-ink no-underline transition-colors hover:bg-hdr-surface hover:text-gold-700"
                  :style="entry.depth ? { paddingInlineStart: `${12 + entry.depth * 12}px` } : undefined"
                  :to="shellHref(entry.link.href)"
                >
                  {{ entry.link.label }}
                </NuxtLink>
              </div>
            </li>
          </ul>
        </nav>

        <!-- Cụm thao tác bên phải -->
        <div class="flex min-w-0 items-center justify-end gap-1.5 max-ph:gap-1">
          <button
            ref="searchTrigger"
            class="relative inline-flex h-11 w-11 items-center justify-center rounded-chip text-hdr-ink
                   transition-colors hover:bg-hdr-surface hover:text-hdr-gold-deep wd:hidden"
            type="button"
            :aria-expanded="searchOpen"
            aria-controls="ptsHeaderSearch"
            :aria-label="searchOpen ? 'Đóng tìm kiếm' : 'Mở tìm kiếm'"
            @click="toggleSearch"
          >
            <svg class="h-[19px] w-[19px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.5" /><path d="m16 16 4.5 4.5" /></svg>
          </button>

          <NuxtLink
            :external="isExternalLink(shellHref('/gio-hang/'))"
            class="relative inline-flex h-11 w-11 items-center justify-center rounded-chip text-hdr-ink
                   transition-colors hover:bg-hdr-surface hover:text-hdr-gold-deep"
            :to="shellHref('/gio-hang/')"
            :aria-label="cartCountAvailable && migrationCart ? `Giỏ hàng có ${migrationCart.count} sản phẩm` : 'Giỏ hàng'"
            title="Giỏ hàng"
          >
            <span
              v-if="cartCountAvailable && migrationCart?.count"
              class="absolute right-0.5 top-0.5 grid min-h-4 min-w-4 place-items-center rounded-full bg-gold-500 px-1 text-[10px] font-black leading-4 text-navy-950"
            >{{ migrationCart.count }}</span>
            <svg class="h-[22px] w-[22px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h2l1 12h11l1.6-8H7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /><circle cx="9" cy="20" r="1.5" fill="currentColor" /><circle cx="16" cy="20" r="1.5" fill="currentColor" /></svg>
          </NuxtLink>

          <NuxtLink
            :external="isExternalLink(shellHref('/lien-he/#yeu-cau-tu-van'))"
            class="ml-1 inline-flex min-h-11 items-center justify-center whitespace-nowrap rounded-btn border
                   border-hdr-gold bg-hdr-gold px-4 text-[13.5px] font-bold text-hdr-ink no-underline transition-colors
                   hover:border-hdr-gold-deep hover:bg-hdr-gold-deep hover:text-white max-ph:hidden"
            :to="shellHref('/lien-he/#yeu-cau-tu-van')"
          >
            Nhận tư vấn
          </NuxtLink>
        </div>
      </div>

      <!-- Hộp thoại tìm kiếm thả xuống cho màn hình <1501px -->
      <div
        v-if="searchOpen"
        id="ptsHeaderSearch"
        ref="searchPanel"
        class="absolute inset-x-0 top-full z-[25] w-full border-t border-hdr-line-soft border-b border-b-hdr-line
               bg-white px-4 pt-4 pb-4 shadow-[0_14px_28px_rgba(16,36,58,.1)] max-[359px]:px-3 max-ph:py-3"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ptsHeaderSearchTitle"
      >
        <div class="mx-auto w-full max-w-[1680px]">
          <div class="flex items-center justify-between gap-3">
            <h2 id="ptsHeaderSearchTitle" class="sr-only">Tìm kiếm</h2>
            <form class="flex min-w-0 flex-1 items-center gap-3" role="search" @submit.prevent="submitSearch">
              <label class="sr-only" for="ptsHeaderSearchInput">Tìm kiếm sản phẩm hoặc giải pháp</label>
              <input
                id="ptsHeaderSearchInput"
                ref="searchInput"
                v-model="searchKeyword"
                class="min-h-[46px] w-full max-w-[720px] rounded-btn border border-hdr-line px-4 text-[14.5px]
                       text-hdr-ink outline-none placeholder:text-hdr-muted focus:border-hdr-gold"
                type="search"
                placeholder="Bạn cần tìm sản phẩm hoặc giải pháp nào?"
                autocomplete="off"
              >
              <button
                class="inline-flex min-h-[46px] items-center justify-center rounded-btn bg-hdr-gold px-5
                       font-bold text-hdr-ink transition-colors hover:bg-hdr-gold-deep hover:text-white"
                type="submit"
                aria-label="Tìm kiếm"
              >
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.5" /><path d="m16 16 4.5 4.5" /></svg>
              </button>
            </form>
            <button
              class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-chip text-hdr-ink transition-colors hover:bg-hdr-surface hover:text-gold-700"
              type="button"
              aria-label="Đóng tìm kiếm"
              @click="closeSearch()"
            >
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mega-menu Danh mục sản phẩm: neo sát ngay dưới thanh header sticky -->
      <div
        v-if="catalogOpen"
        class="absolute inset-x-0 top-full z-[100] h-[calc(100dvh-100%)] min-h-[calc(100vh-80px)] overflow-hidden bg-[rgb(8_18_34_/_58%)]"
        aria-hidden="false"
      >
        <button
          class="fixed inset-0 h-full w-full cursor-default border-0 bg-transparent"
          type="button"
          aria-label="Đóng danh mục sản phẩm"
          @click="closeCatalog()"
        />
        <section
          id="ptsCatalogMenu"
          ref="catalogPanel"
          class="relative z-[1] mx-auto my-3 max-h-[calc(100dvh-116px)] w-[calc(100vw-24px)]
                 overflow-auto rounded-btn border border-[#e5e7eb] bg-white px-[18px] pt-5 text-navy-800
                 shadow-[0_18px_48px_rgba(6,21,55,.22)]
                 wd:my-3 wd:w-[min(1220px,calc(100vw-64px))] wd:px-8 wd:pt-7"
          role="dialog"
          aria-modal="true"
          aria-labelledby="ptsCatalogTitle"
          @mouseenter="catalogMode = 'hover'"
        >
          <div
            class="grid grid-cols-[minmax(0,1fr)_40px] items-center gap-[13px] pb-[17px]
                   wd:grid-cols-[minmax(0,1fr)_400px_36px] wd:gap-6 wd:pb-4"
          >
            <div class="min-w-0">
              <h2 id="ptsCatalogTitle" class="m-0 text-[21px] font-bold leading-[1.25] text-navy-800 wd:text-[24px]">
                Khám phá sản phẩm
              </h2>
              <p class="mt-1 text-[13px] leading-normal text-slate-500 wd:text-[15px]">
                Chọn thiết bị phù hợp với nhu cầu của bạn
              </p>
            </div>
            <form
              class="col-span-2 row-start-2 flex h-11 min-w-0 items-center gap-3 rounded-btn border border-[#d6dce4]
                     bg-slate-50/70 px-3.5 text-navy-800 focus-within:border-gold-700 focus-within:bg-white focus-within:shadow-[0_0_0_2px_rgba(201,150,46,.16)]
                     wd:col-span-1 wd:row-start-1 wd:h-11"
              role="search"
              @submit.prevent="submitCatalogSearch"
            >
              <label class="sr-only" for="ptsCatalogKeyword">Tìm sản phẩm, mã sản phẩm</label>
              <svg class="h-[18px] w-[18px] flex-none text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.5" /><path d="m16 16 4.5 4.5" /></svg>
              <input
                id="ptsCatalogKeyword"
                ref="catalogSearchInput"
                v-model="catalogKeyword"
                class="h-full w-full min-w-0 border-0 bg-transparent text-[15px] font-medium text-navy-800 outline-none
                       placeholder:text-slate-500"
                type="search"
                placeholder="Tìm sản phẩm, mã sản phẩm..."
                autocomplete="off"
              >
            </form>
            <button
              class="inline-flex h-11 w-11 items-center justify-center justify-self-end rounded-chip text-navy-800 transition-colors
                     hover:bg-hdr-surface wd:justify-self-auto"
              type="button"
              aria-label="Đóng danh mục sản phẩm"
              @click="closeCatalog()"
            >
              <svg class="mx-auto h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
            </button>
          </div>

          <div
            v-if="hasCategories && catalogColumns.length"
            class="grid grid-cols-1 gap-[22px] py-1 pb-6 wd:grid-cols-3 wd:gap-6"
          >
            <section
              v-for="(group, groupIndex) in catalogColumns"
              :key="group.id"
              :aria-labelledby="`ptsCatalogGroup-${group.id}`"
              class="relative min-w-0 wd:[&:not(:first-child)]:before:absolute wd:[&:not(:first-child)]:before:inset-y-0
                     wd:[&:not(:first-child)]:before:left-[-12px] wd:[&:not(:first-child)]:before:border-l
                     wd:[&:not(:first-child)]:before:border-[#e5e7eb] wd:[&:not(:first-child)]:before:content-['']"
              :class="groupIndex === catalogColumns.length - 1 ? 'catalog-group-last' : ''"
            >
              <h3 :id="`ptsCatalogGroup-${group.id}`" class="mb-1 min-h-[54px]">
                <NuxtLink
                  :external="isExternalLink(shellHref(group.groupHref))"
                  class="flex min-h-[54px] w-full items-center gap-3 text-[17px] font-bold uppercase leading-[1.25]
                         text-gold-700 no-underline hover:text-navy-800"
                  :to="shellHref(group.groupHref)"
                  @click="closeCatalog(false)"
                >
                  <span class="flex h-11 w-12 flex-none items-center justify-center rounded-btn bg-gold-50 text-gold-700">
                    <CatalogMenuIcon class="h-7 w-7" :name="group.meta.icon" />
                  </span>
                  <span class="min-w-0">
                    {{ group.label }}
                    <small v-if="group.meta.subtitle" class="mt-[3px] block text-[12.5px] font-normal normal-case leading-[1.35] text-slate-500">
                      {{ group.meta.subtitle }}
                    </small>
                  </span>
                </NuxtLink>
              </h3>
              <ul class="m-0 list-none p-0">
                <li
                  v-for="item in group.visibleItems"
                  :key="item.href + item.label"
                  class="group/item relative border-b border-[#edf0f3] last:border-b-0 hover:z-[2] focus-within:z-[2]"
                  :data-search="item.searchText"
                >
                  <NuxtLink
                    :external="isExternalLink(shellHref(item.href))"
                    class="flex min-h-11 items-center gap-3 px-2 py-1 text-[15px] font-medium leading-6 text-[#10243a]
                           no-underline hover:bg-[#fff3d8] hover:text-navy-800 hover:shadow-[inset_3px_0_#b58223]
                           focus-visible:bg-[#fff3d8] focus-visible:shadow-[inset_3px_0_#b58223]
                           group-focus-within/item:bg-[#fff3d8] group-focus-within/item:shadow-[inset_3px_0_#b58223]"
                    :to="shellHref(item.href)"
                    @click="closeCatalog(false)"
                  >
                    <span class="flex h-7 w-7 flex-none items-center justify-center text-navy-800">
                      <CatalogMenuIcon class="h-5 w-5" :name="item.icon" />
                    </span>
                    <span class="min-w-0 flex-1">{{ item.label }}</span>
                    <svg
                      v-if="item.children.length"
                      class="mr-1 h-3.5 w-3.5 flex-none text-slate-500"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      aria-hidden="true"
                    ><path d="m9 5 7 7-7 7" /></svg>
                  </NuxtLink>
                  <ul
                    v-if="item.children.length"
                    class="absolute top-0 z-[3] hidden max-h-80 w-[260px] list-none overflow-y-auto rounded-btn
                           border border-[#e5e7eb] bg-white p-1.5 shadow-[0_12px_30px_rgba(6,21,55,.16)]
                           group-hover/item:block group-focus-within/item:block left-full
                           [.catalog-group-last_&]:right-full [.catalog-group-last_&]:left-auto"
                    :aria-label="`Danh mục con: ${item.label}`"
                  >
                    <li v-for="child in item.children" :key="child.href + child.label">
                      <NuxtLink
                        :external="isExternalLink(shellHref(child.href))"
                        class="block rounded-chip px-3 py-[11px] text-[13px] leading-[1.4] text-[#10243a] no-underline
                               hover:bg-[#fff3d8] hover:text-navy-800"
                        :to="shellHref(child.href)"
                        @click="closeCatalog(false)"
                      >
                        {{ child.label }}
                      </NuxtLink>
                    </li>
                  </ul>
                </li>
              </ul>
              <NuxtLink
                v-if="group.id === 'devices'"
                :external="isExternalLink(shellHref('/lien-he/#yeu-cau-tu-van'))"
                class="mt-6 flex min-h-[96px] items-center gap-4 rounded-btn border border-gold-300/60 bg-gold-50 px-[18px] py-4
                       text-[#392a12] no-underline transition-colors hover:bg-[#ffecc5] hover:text-navy-800"
                :to="shellHref('/lien-he/#yeu-cau-tu-van')"
                @click="closeCatalog(false)"
              >
                <CatalogMenuIcon class="h-10 w-10 flex-none text-gold-700" name="headset" />
                <span>
                  <strong class="block text-[16px] font-bold">Cần hỗ trợ lựa chọn?</strong>
                  <small class="mt-1 block text-[14px] font-semibold text-gold-800">Nhận tư vấn →</small>
                </span>
              </NuxtLink>
            </section>
          </div>
          <p v-else-if="hasCategories" class="m-0 py-4 text-[15px] text-slate-500">Không tìm thấy danh mục phù hợp.</p>
          <p v-else class="m-0 py-4 text-[15px] text-slate-500">Danh mục sản phẩm chưa có dữ liệu.</p>

          <div
            class="flex min-h-14 flex-col items-start justify-center gap-[9px] border-t border-[#e5e7eb] py-3
                   wd:flex-row wd:items-center wd:justify-between wd:gap-[18px] wd:py-0"
          >
            <NuxtLink
              :external="isExternalLink(shellHref('/san-pham/'))"
              class="inline-flex items-center gap-[9px] text-[15px] font-bold text-navy-800 no-underline hover:text-gold-700"
              :to="shellHref('/san-pham/')"
              @click="closeCatalog(false)"
            >
              Xem tất cả sản phẩm
              <span class="text-gold-700" aria-hidden="true">→</span>
            </NuxtLink>
            <NuxtLink
              :external="isExternalLink(shellHref('/lien-he/#yeu-cau-tu-van'))"
              class="inline-flex items-center gap-[9px] text-[15px] font-semibold text-navy-800 no-underline hover:text-gold-700"
              :to="shellHref('/lien-he/#yeu-cau-tu-van')"
              @click="closeCatalog(false)"
            >
              Tư vấn chọn sản phẩm
              <span class="text-gold-700" aria-hidden="true">→</span>
            </NuxtLink>
          </div>
        </section>
      </div>
    </header>

    <!-- Drawer điều hướng mobile, dưới mốc nav (1321px) -->
    <div v-if="mobileOpen" class="fixed inset-0 z-[1100] overflow-auto bg-[rgb(8_18_34_/_58%)]">
      <div
        class="fixed inset-0 h-full cursor-default bg-transparent"
        aria-hidden="true"
        @click="closeMobileMenu()"
      />
      <aside
        id="ptsMobileMenu"
        ref="mobilePanel"
        class="relative z-[1] min-h-full w-[min(420px,calc(100vw-24px))] overflow-y-auto bg-white p-4
               shadow-[16px_0_36px_rgb(6_21_55_/_20%)] ph:w-[min(420px,calc(100vw-32px))] ph:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ptsMobileTitle"
      >
        <div class="mb-[18px] flex items-center justify-between gap-4">
          <h2 id="ptsMobileTitle" class="m-0 text-xl font-black text-navy-800">Điều hướng</h2>
          <button
            class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-chip text-hdr-ink transition-colors hover:bg-hdr-surface hover:text-gold-700"
            type="button"
            aria-label="Đóng danh mục điều hướng"
            @click="closeMobileMenu()"
          >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
          </button>
        </div>

        <form class="mb-5 grid grid-cols-[minmax(0,1fr)_50px] overflow-hidden rounded-btn border border-[#cbd3dc]" role="search" @submit.prevent="submitSearch">
          <label class="sr-only" for="ptsMobileSearchInput">Tìm kiếm sản phẩm hoặc giải pháp</label>
          <input
            id="ptsMobileSearchInput"
            ref="mobileSearchInput"
            v-model="searchKeyword"
            class="min-h-11 min-w-0 border-0 px-4 text-[15px] font-medium text-hdr-ink outline-none"
            type="search"
            placeholder="Tìm kiếm sản phẩm, giải pháp..."
            autocomplete="off"
          >
          <button class="inline-flex min-h-11 items-center justify-center bg-hdr-gold text-hdr-ink" type="submit" aria-label="Tìm kiếm">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.5" /><path d="m16 16 4.5 4.5" /></svg>
          </button>
        </form>

        <nav aria-label="Điều hướng mobile">
          <template v-for="(item, index) in navItems" :key="item.href">
            <div class="flex min-h-12 w-full items-center gap-2 border-b border-hdr-line">
              <NuxtLink
                :external="isExternalLink(shellHref(item.href))"
                class="flex min-h-12 min-w-0 flex-1 items-center gap-4 py-2 text-base font-semibold no-underline"
                :class="isActiveNavItem(item) ? 'font-bold text-gold-700' : 'text-hdr-ink'"
                :to="shellHref(item.href)"
                @click="closeMobileMenu(false)"
              >
                {{ item.label }}
              </NuxtLink>
              <button
                v-if="item.children?.length"
                class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-chip text-hdr-ink transition-colors hover:bg-hdr-surface hover:text-gold-700"
                type="button"
                :aria-expanded="mobileNavOpen === item.href"
                :aria-controls="`ptsMobileNav-${index}`"
                :aria-label="`${mobileNavOpen === item.href ? 'Thu gọn' : 'Mở'} menu ${item.label}`"
                @click="mobileNavOpen = mobileNavOpen === item.href ? null : item.href"
              >
                <svg class="h-4 w-4 transition-transform duration-150" :class="{ 'rotate-180': mobileNavOpen === item.href }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" aria-hidden="true"><path d="m7 10 5 5 5-5" /></svg>
              </button>
            </div>
            <div v-if="item.children?.length && mobileNavOpen === item.href" :id="`ptsMobileNav-${index}`" class="grid gap-0 border-b border-hdr-line py-1 pl-4" role="group" :aria-label="`${item.label} - menu con`">
              <NuxtLink
                v-for="entry in flattenLinks(item.children)"
                :key="entry.key"
                :external="isExternalLink(shellHref(entry.link.href))"
                class="flex min-h-11 items-center py-[9px] text-[15px] leading-[1.45] text-hdr-sub no-underline"
                :style="entry.depth ? { paddingInlineStart: `${entry.depth * 12}px` } : undefined"
                :to="shellHref(entry.link.href)"
                @click="closeMobileMenu(false)"
              >
                {{ entry.link.label }}
              </NuxtLink>
            </div>
          </template>

          <button
            class="flex min-h-12 w-full items-center justify-between gap-4 border-0 border-b border-hdr-line
                   bg-transparent py-2 text-left text-base font-semibold text-hdr-ink"
            type="button"
            :aria-expanded="mobileCatalogOpen"
            aria-controls="ptsMobileCatalog"
            @click="mobileCatalogOpen = !mobileCatalogOpen"
          >
            <span>Danh mục sản phẩm</span>
            <svg
              class="h-4 w-4 transition-transform duration-150"
              :class="{ 'rotate-180': mobileCatalogOpen }"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.8"
              aria-hidden="true"
            >
              <path d="m7 10 5 5 5-5" />
            </svg>
          </button>

          <div v-if="mobileCatalogOpen" id="ptsMobileCatalog" class="grid gap-0 border-b border-hdr-line py-1 pl-4" role="group" aria-label="Danh mục sản phẩm">
            <NuxtLink
              :external="isExternalLink(shellHref('/san-pham/'))"
              class="flex min-h-11 items-center py-[9px] text-[15px] font-semibold leading-[1.45] text-gold-700 no-underline"
              :to="shellHref('/san-pham/')"
              @click="closeMobileMenu(false)"
            >
              Xem tất cả sản phẩm →
            </NuxtLink>
            <template v-if="hasCategories">
              <template v-for="group in catalogGroups" :key="group.id">
                <p class="mb-1 mt-3 text-[13px] font-bold uppercase tracking-wide text-gold-700 first:mt-1">{{ group.label }}</p>
                <NuxtLink
                  v-for="entry in catalogGroupLinks(group)"
                  :key="entry.key"
                  :external="isExternalLink(shellHref(entry.link.href))"
                  class="flex min-h-11 items-center py-[9px] text-[15px] leading-[1.45] text-hdr-sub no-underline"
                  :class="entry.depth === 0 ? 'font-semibold text-hdr-ink' : ''"
                  :style="entry.depth ? { paddingInlineStart: `${entry.depth * 12}px` } : undefined"
                  :to="shellHref(entry.link.href)"
                  @click="closeMobileMenu(false)"
                >
                  {{ entry.link.label }}
                </NuxtLink>
              </template>
            </template>
            <span v-if="!hasCategories" class="py-4 text-[15px] text-slate-500">Danh mục sản phẩm chưa có dữ liệu.</span>
          </div>
        </nav>

        <NuxtLink
          :external="isExternalLink(shellHref('/lien-he/#yeu-cau-tu-van'))"
          class="mt-6 flex min-h-[50px] w-full items-center justify-center rounded-btn border border-hdr-gold
                 bg-hdr-gold px-[21px] text-sm font-bold text-hdr-ink no-underline transition-colors
                 hover:border-hdr-gold-deep hover:bg-hdr-gold-deep hover:text-white"
          :to="shellHref('/lien-he/#yeu-cau-tu-van')"
          @click="closeMobileMenu(false)"
        >
          Nhận tư vấn
        </NuxtLink>
      </aside>
    </div>
  </div>
</template>
