<script setup lang="ts">
import SearchSuggestions from '~/components/search/SearchSuggestions.vue'
import type { PublicCatalogGroup, PublicProductCategoryNode, PublicSiteLink, PublicSitePayload } from '#shared/contracts/public-site'
import { AnimatePresence, motion, useReducedMotion } from 'motion-v'
import {
  catalogGroupMeta,
  catalogItemIcon,
  catalogMenuSearchText,
  resolveEquipmentIcon,
  sortCatalogGroups,
} from '#shared/catalog-menu-presentation'
import { publicSiteStructuredData } from '#shared/public-seo'

type MenuMode = 'hover' | 'intent' | null

const props = defineProps<{
  site: PublicSitePayload
}>()

const route = useRoute()
const { generic: shellHref, external: isExternalLink } = usePublicLinks()
const { cart: shopCart, refresh: refreshShopCart } = useShopCart()
const { openConsultation } = useStorefrontModal()
const config = useRuntimeConfig()
const siteStructuredData = computed(() => publicSiteStructuredData(config.public.canonicalSiteOrigin, route.path, props.site))
const adminFavicon = computed(() => props.site.brand.favicon || '')
useHead({
  link: computed(() => adminFavicon.value ? [{ key: 'favicon', rel: 'icon', href: adminFavicon.value }] : []),
  script: computed(() => siteStructuredData.value ? [{
    key: 'pts-public-organization-schema',
    type: 'application/ld+json',
    innerHTML: siteStructuredData.value,
  }] : []),
})
const cartCountAvailable = computed(() => true)
// The cart page fills this state after the header has rendered on the server, so the count is client-only.
const cartBadgeReady = ref(false)
onMounted(() => { cartBadgeReady.value = true })
const reducedMotion = useReducedMotion()

const brandName = computed(() => props.site.brand.name.trim() || 'Phúc Thịnh Solar')
const hotline = computed(() => props.site.brand.hotline.trim())
const brandLogo = computed(() => props.site.brand.logo || '')
// Phones reach the cart from the dock. Product and checkout pages replace the dock, so they keep the header icon.
const dockShowsCart = computed(() => !/^\/san-pham\/[^/]+\/?$/.test(route.path) && !/^\/thanh-toan\/?$/.test(route.path))
const brandLogoSrc = computed(() => {
  const src = brandLogo.value
  return /^\/upload\/photo\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(src) ? phpThumb(src, 520, 112, 3) : src
})

const searchOpen = ref(false)
const searchKeyword = ref('')
const headerSuggest = ref<InstanceType<typeof SearchSuggestions> | null>(null)
const defaultSearchTerms = ['Đèn đường', 'Blue Carbon', 'LONGi', 'SolaX', 'Pin lưu trữ', 'Jindian', 'Bơm nước']
// Admin "Tìm kiếm - Từ khóa nhanh" replaces the defaults when it has visible terms.
const popularSearchTerms = computed(() => props.site.quickSearches?.length ? props.site.quickSearches : defaultSearchTerms)

async function selectPopularSearch(term: string) {
  searchKeyword.value = term
  await submitSearch()
}
const catalogOpen = ref(false)
const catalogMode = ref<MenuMode>(null)
const catalogKeyword = ref('')
const catalogSearchInput = ref<HTMLInputElement | null>(null)
const mobileOpen = ref(false)
const mobileCatalogOpen = ref(false)
const mobileNavOpen = ref<string | null>(null)
const navDropdownOpen = ref<string | null>(null)
const navDropdownMode = ref<MenuMode>(null)
const catalogTrigger = ref<HTMLElement | null>(null)
const mobileTrigger = ref<HTMLButtonElement | null>(null)
const catalogPanel = ref<HTMLElement | null>(null)
const catalogOverlay = ref<HTMLElement | null>(null)
const mobilePanel = ref<HTMLElement | null>(null)
const mobileOverlay = ref<HTMLElement | null>(null)
const searchInput = ref<HTMLInputElement | null>(null)
const mobileSearchInput = ref<HTMLInputElement | null>(null)
const searchTrigger = ref<HTMLButtonElement | null>(null)
const searchPanel = ref<HTMLElement | null>(null)
const searchOverlay = ref<HTMLElement | null>(null)
const navDropdownTrigger = ref<HTMLElement | null>(null)
const setDocumentScrollLock = useDocumentScrollLock()
let catalogCloseTimer: ReturnType<typeof setTimeout> | null = null
const inertBeforeModal = new Map<HTMLElement, boolean>()

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
const mobileNavOrder = ['/giai-phap/', '/du-an-da-thuc-hien/', '/gioi-thieu/', '/tin-tuc/', '/dai-ly/']
const mobileNavItems = computed(() => [...navItems.value].sort((a, b) => mobileNavOrder.indexOf(a.href) - mobileNavOrder.indexOf(b.href)))

function isActiveNavItem(item: PublicSiteLink): boolean {
  const path = route.path
  if (item.href === '/gioi-thieu/' && (path.startsWith('/gioi-thieu') || path.startsWith('/ve-chung-toi') || path.includes('about-preview') || path.startsWith('/lien-he') || path.includes('contact-preview'))) return true
  if (item.href === '/giai-phap/' && (path.startsWith('/giai-phap') || path.includes('solution'))) return true
  if (item.href === '/du-an-da-thuc-hien/' && (path.startsWith('/du-an') || path.startsWith('/danh-muc-du-an') || path.includes('project'))) return true
  if (item.href === '/tin-tuc/' && (path.startsWith('/tin-tuc') || path.includes('news'))) return true
  if (item.href === '/dai-ly/' && (path.startsWith('/dai-ly') || path.startsWith('/he-thong-dai-ly') || path.includes('dealer'))) return true
  if (path === item.href) return true

  const currentPath = path.replace(/\/+$/, '') || '/'
  const currentSegments = currentPath.split('/').filter(Boolean)
  const currentLeaf = currentSegments.at(-1)
  const matchesBranch = (links: PublicSiteLink[]): boolean => links.some((link) => {
    let linkPath = link.href
    try { linkPath = new URL(link.href, 'https://pts.local').pathname } catch { /* Keep a valid relative path as supplied. */ }
    const normalizedLinkPath = linkPath.replace(/\/+$/, '') || '/'
    const exactOrNested = currentPath === normalizedLinkPath || currentPath.startsWith(`${normalizedLinkPath}/`)
    const bareSlugAlias = currentSegments.length === 1 && currentLeaf === normalizedLinkPath.split('/').filter(Boolean).at(-1)
    return exactOrNested || bareSlugAlias || matchesBranch(link.children ?? [])
  })

  return matchesBranch(item.children ?? [])
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

type MobileCategoryEntry = {
  node: PublicProductCategoryNode
  key: string
  depth: number
  parentKeys: string[]
}

const mobileCategoryExpanded = ref<Record<string, boolean>>({})

function flattenProductCategories(
  nodes: PublicProductCategoryNode[],
  depth = 0,
  parentKeys: string[] = [],
): MobileCategoryEntry[] {
  return nodes.flatMap((node) => {
    const key = `ptsMobileCategory-${node.id}`
    const entry = { node, key, depth, parentKeys }
    return [entry, ...flattenProductCategories(node.children, depth + 1, [...parentKeys, key])]
  })
}

const mobileCategoryEntries = computed(() => {
  const tree = props.site.productCategoryTree?.length
    ? props.site.productCategoryTree
    : props.site.productCategories.map((category) => ({ ...category, children: [] }))
  return flattenProductCategories(tree)
})

function mobileCategoryChildren(entry: MobileCategoryEntry): MobileCategoryEntry[] {
  return mobileCategoryEntries.value.filter((candidate) => candidate.parentKeys.at(-1) === entry.key)
}

function mobileCategoryVisible(entry: MobileCategoryEntry): boolean {
  return entry.parentKeys.every((key) => mobileCategoryExpanded.value[key])
}

function toggleMobileCategory(entry: MobileCategoryEntry) {
  mobileCategoryExpanded.value = {
    ...mobileCategoryExpanded.value,
    [entry.key]: !mobileCategoryExpanded.value[entry.key],
  }
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
      icon: catalogItemIcon(item.label, item.href),
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
  const overlayOpen = searchOpen.value || mobileOpen.value || (catalogOpen.value && catalogMode.value === 'intent')
  setDocumentScrollLock(overlayOpen)
}

function restoreModalBackground() {
  for (const [element, wasInert] of inertBeforeModal) element.inert = wasInert
  inertBeforeModal.clear()
}

function syncModalBackground() {
  if (!import.meta.client) return
  const overlay = mobileOpen.value
    ? mobileOverlay.value
    : searchOpen.value
      ? searchOverlay.value
      : catalogOpen.value && catalogMode.value === 'intent'
        ? catalogOverlay.value
        : null

  if (!overlay || !document.body.contains(overlay)) {
    restoreModalBackground()
    return
  }

  restoreModalBackground()
  let branch: HTMLElement = overlay
  let parent = branch.parentElement
  while (parent) {
    for (const sibling of [...parent.children]) {
      if (sibling !== branch && sibling instanceof HTMLElement) {
        inertBeforeModal.set(sibling, sibling.inert)
        sibling.inert = true
      }
    }
    if (parent === document.body) break
    branch = parent
    parent = parent.parentElement
  }
}

function scheduleModalBackgroundSync() {
  nextTick(syncModalBackground)
}

function openCatalog(mode: Exclude<MenuMode, null> = 'intent') {
  if (mode === 'hover' && import.meta.client && !window.matchMedia('(hover: hover)').matches) return
  cancelCatalogClose()
  closeSearch(false)
  closeNavDropdown(false)
  closeMobileMenu(false)
  catalogMode.value = mode
  catalogOpen.value = true
  syncBodyLock()
  scheduleModalBackgroundSync()
  if (mode === 'intent') {
    nextTick(() => {
      if (catalogSearchInput.value) catalogSearchInput.value.focus()
      else focusFirst(catalogPanel.value)
    })
  }
}

// A flyout near the bottom of the panel moves up, so all of its items stay in view.
function fitOpenFlyout() {
  const flyout = document.querySelector<HTMLElement>('.is-flyout-open > .catalog-flyout')
  const panel = document.getElementById('ptsCatalogMenu')
  if (!flyout || !panel) return
  flyout.style.top = ''
  const panelBox = panel.getBoundingClientRect()
  const box = flyout.getBoundingClientRect()
  const limit = Math.min(panelBox.bottom, window.innerHeight) - 12
  const overflow = box.bottom - limit
  if (overflow > 0) flyout.style.top = `${-Math.min(overflow, Math.max(0, box.top - panelBox.top - 8))}px`
}
// Sub-category flyouts: a short grace period stops a diagonal move toward an open
// flyout from switching it to the item the pointer crosses on the way.
const flyoutKey = ref('')
watch(flyoutKey, () => nextTick(fitOpenFlyout))
let flyoutTimer: ReturnType<typeof setTimeout> | null = null
function clearFlyoutTimer() { if (flyoutTimer) clearTimeout(flyoutTimer); flyoutTimer = null }
// Only a mostly-horizontal move (heading for the open flyout) gets the grace period;
// running up and down the list switches instantly.
let pointerLast = { x: 0, y: 0 }
let pointerDelta = { x: 0, y: 0 }
function trackPointer(event: MouseEvent) {
  pointerDelta = { x: event.clientX - pointerLast.x, y: event.clientY - pointerLast.y }
  pointerLast = { x: event.clientX, y: event.clientY }
}
function hoverFlyout(key: string, hasChildren: boolean) {
  clearFlyoutTimer()
  const next = hasChildren ? key : ''
  if (flyoutKey.value === next) return
  const aimingAtFlyout = Math.abs(pointerDelta.x) > Math.abs(pointerDelta.y) * 1.2
  if (!flyoutKey.value || !aimingAtFlyout) { flyoutKey.value = next; return }
  flyoutTimer = setTimeout(() => { flyoutKey.value = next }, 110)
}
function leaveFlyout() {
  clearFlyoutTimer()
  flyoutTimer = setTimeout(() => { flyoutKey.value = '' }, 180)
}

function closeCatalog(restoreFocus = true) {
  clearFlyoutTimer()
  flyoutKey.value = ''
  cancelCatalogClose()
  const shouldRestore = restoreFocus && catalogOpen.value
  catalogOpen.value = false
  catalogMode.value = null
  catalogKeyword.value = ''
  syncBodyLock()
  syncModalBackground()
  if (shouldRestore) nextTick(() => catalogTrigger.value?.focus())
}

function cancelCatalogClose() {
  if (catalogCloseTimer) clearTimeout(catalogCloseTimer)
  catalogCloseTimer = null
}

function scheduleCatalogClose() {
  cancelCatalogClose()
  if (catalogMode.value !== 'hover') return
  catalogCloseTimer = setTimeout(() => {
    catalogCloseTimer = null
    if (catalogMode.value === 'hover') closeCatalog(false)
  }, 180)
}

async function submitCatalogSearch() {
  const keyword = catalogKeyword.value.trim()
  if (!keyword) return
  closeCatalog(false)
  await navigateTo({ path: shellHref('/tim-kiem/'), query: { keyword } })
}

function toggleCatalog() {
  if (!catalogOpen.value) {
    openCatalog('intent')
    return
  }
  if (catalogMode.value === 'hover') {
    catalogMode.value = 'intent'
    syncBodyLock()
    scheduleModalBackgroundSync()
    nextTick(() => catalogSearchInput.value?.focus())
    return
  }
  closeCatalog()
}

function openMobileMenu() {
  closeSearch(false)
  closeCatalog(false)
  closeNavDropdown(false)
  mobileOpen.value = true
  mobileCatalogOpen.value = true
  mobileCategoryExpanded.value = {}
  syncBodyLock()
  nextTick(() => {
    syncModalBackground()
    focusFirst(mobilePanel.value)
  })
}

function closeMobileMenu(restoreFocus = true) {
  const shouldRestore = restoreFocus && mobileOpen.value
  mobileOpen.value = false
  mobileCatalogOpen.value = false
  mobileCategoryExpanded.value = {}
  mobileNavOpen.value = null
  syncBodyLock()
  syncModalBackground()
  if (shouldRestore) nextTick(() => mobileTrigger.value?.focus())
}

function closeSearch(restoreFocus = true) {
  const shouldRestore = restoreFocus && searchOpen.value
  searchOpen.value = false
  syncBodyLock()
  syncModalBackground()
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
  nextTick(() => {
    syncModalBackground()
    searchInput.value?.focus()
  })
}

async function submitSearch() {
  const keyword = searchKeyword.value.trim()
  if (!keyword) return
  headerSuggest.value?.remember(keyword)
  closeSearch(false)
  closeMobileMenu(false)
  await navigateTo({ path: shellHref('/tim-kiem/'), query: { keyword } })
}

function openNavDropdown(item: PublicSiteLink, event: Event) {
  if (!item.children?.length) return
  if (event.type === 'mouseenter' && import.meta.client && !window.matchMedia('(hover: hover) and (pointer: fine)').matches) return
  if (navDropdownOpen.value === item.href && navDropdownTrigger.value) {
    // Keep the disclosure's original focus target while focus moves into the
    // submenu. Its links are removed when Escape closes the dropdown.
    if (event.type === 'focusin') navDropdownMode.value = 'intent'
    return
  }
  const currentTarget = event.currentTarget
  navDropdownTrigger.value = currentTarget instanceof HTMLElement
    ? (currentTarget.contains(document.activeElement) && document.activeElement instanceof HTMLElement
        ? document.activeElement
        : currentTarget.querySelector('a'))
    : null
  navDropdownOpen.value = item.href
  navDropdownMode.value = event.type === 'mouseenter' ? 'hover' : 'intent'
}

function closeNavDropdown(restoreFocus = false) {
  const shouldRestore = restoreFocus && navDropdownOpen.value !== null
  const trigger = navDropdownTrigger.value
  navDropdownOpen.value = null
  navDropdownMode.value = null
  navDropdownTrigger.value = null
  if (shouldRestore) nextTick(() => trigger?.focus())
}

function handleNavFocusout(event: FocusEvent) {
  const currentTarget = event.currentTarget
  const relatedTarget = event.relatedTarget
  if (currentTarget instanceof HTMLElement && (!relatedTarget || !currentTarget.contains(relatedTarget as Node))) closeNavDropdown()
}

function handleNavMouseleave() {
  if (navDropdownMode.value === 'hover') closeNavDropdown()
}

function handleNavFocusin(item: PublicSiteLink, event: FocusEvent) {
  if (event.target instanceof HTMLButtonElement) return
  openNavDropdown(item, event)
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
  else if (catalogOpen.value && catalogMode.value === 'intent') trapFocus(event, catalogPanel.value)
  else if (searchOpen.value) trapFocus(event, searchPanel.value)
}

function handleShellLeave() {
  // The absolute mega-menu extends beyond the header's layout box. Keep the
  // hover grace period while the pointer crosses that boundary into the panel.
  scheduleCatalogClose()
}

function telHrefFor(value: string): string {
  return `tel:${value.replace(/[^+\d]/g, '')}`
}

const { isHeaderHidden, isScrolled } = useSiteHeader()
// Only one overlay at a time: the header's panels give way to the dock's support sheet, and the other way round.
const activeOverlay = useState<string | null>('active-overlay', () => null)
watch(activeOverlay, (overlay) => {
  if (overlay !== 'support') return
  if (searchOpen.value) closeSearch(false)
  if (mobileOpen.value) closeMobileMenu(false)
  if (catalogOpen.value) closeCatalog(false)
})
watch([searchOpen, mobileOpen, catalogOpen], (open) => {
  if (open.some(Boolean)) activeOverlay.value = 'header'
})
// Full-bleed hero crosses the header on the homepage only; everywhere else the
// header stays solid. See docs/15-design-delivery/06-LAYOUT-LAB.md #principles.
const isHomeRoute = computed(() => route.path === '/')
// Any open overlay needs a solid bar: hero imagery behind it makes the controls unreadable.
const transparentAtTop = computed(() => isHomeRoute.value && !isScrolled.value && !searchOpen.value && !catalogOpen.value && !mobileOpen.value && navDropdownOpen.value === null)
let lastScrollY = 0
let scrollTicking = false
let accumulatedDelta = 0

function handleWindowScroll() {
  if (!import.meta.client || scrollTicking) return
  scrollTicking = true
  requestAnimationFrame(() => {
    const currentScrollY = Math.max(window.scrollY, 0)
    isScrolled.value = currentScrollY > 10

    const anyOverlayOpen = searchOpen.value || catalogOpen.value || mobileOpen.value || navDropdownOpen.value !== null
    if (anyOverlayOpen || currentScrollY <= 80) {
      isHeaderHidden.value = false
      lastScrollY = currentScrollY
      accumulatedDelta = 0
      scrollTicking = false
      return
    }

    const deltaY = currentScrollY - lastScrollY
    // Owner decision: scrolling up does not bring the header back; it returns near the top of the page.
    if (deltaY < 0) accumulatedDelta = 0
    else accumulatedDelta += deltaY

    if (accumulatedDelta > 12 && currentScrollY > 80) {
      isHeaderHidden.value = true
    }

    lastScrollY = currentScrollY
    scrollTicking = false
  })
}

// The header now outlives page changes, so it has to reset itself on navigation.
watch(() => route.path, () => {
  searchOpen.value = false
  catalogOpen.value = false
  navDropdownOpen.value = null
  if (mobileOpen.value) closeMobileMenu(false)
  isHeaderHidden.value = false
  isScrolled.value = false
  lastScrollY = 0
  accumulatedDelta = 0
})

function handleHeaderFocusin() {
  isHeaderHidden.value = false
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
  window.addEventListener('scroll', handleWindowScroll, { passive: true })
  if (cartCountAvailable.value) void refreshShopCart()
})
onBeforeUnmount(() => {
  cancelCatalogClose()
  document.removeEventListener('keydown', handleKeydown)
  window.removeEventListener('scroll', handleWindowScroll)
  setDocumentScrollLock(false)
  restoreModalBackground()
})
</script>

<template>
  <!-- The PHP header keeps navigation, phone, search, cart and CTA on one row. -->
  <div
    class="pts-site-header sticky top-0 z-[1000]"
    :class="['transition-transform duration-300 ease-[cubic-bezier(0.16,1,0.3,1)] will-change-transform transform-gpu', isHeaderHidden ? '-translate-y-full' : 'translate-y-0']"
    @mouseleave="handleShellLeave"
    @focusin="handleHeaderFocusin"
  >
    <header
      class="pts-header-capsule relative z-[1000] transition-[background-color,border-color,box-shadow,backdrop-filter] duration-300"
      :class="transparentAtTop
        ? 'border-b border-transparent bg-transparent shadow-none'
        : ['pts-header-glass border-b border-hdr-line bg-white/96 backdrop-blur-md', isScrolled ? 'shadow-[0_4px_20px_rgba(10,34,81,0.08)]' : 'shadow-[0_1px_4px_rgba(10,34,81,0.06)]']"
    >
      <div
      class="mx-auto flex min-h-16 w-full max-w-[max(1680px,var(--container-page))] items-center justify-between gap-3 px-4 py-2 max-[359px]:px-3
               min-[821px]:min-h-[88px] min-[821px]:py-3
               min-[1321px]:min-h-[104px] min-[1321px]:py-3.5
               min-[1681px]:min-h-[120px] min-[1681px]:py-4
               min-[1800px]:min-h-[136px] min-[1800px]:py-4.5
               max-ph:gap-2 max-ph:py-1.5
               tb:px-6
               nav:gap-4 nav:px-(--spacing-gutter) wd:gap-5"
      >
        <!-- Keep the drawer until the header has enough room for every desktop control. -->
        <button
          ref="mobileTrigger"
          class="pts-mobile-nav-trigger public-neutral-button order-first relative flex h-11 w-11 flex-none items-center justify-center rounded-chip text-hdr-ink min-[1321px]:hidden"
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
        <a class="mr-auto flex min-w-0 flex-1 items-center nav:mr-0 nav:w-[270px] nav:flex-none min-[1321px]:max-[1346px]:w-[244px] min-[1681px]:w-[clamp(360px,22vw,460px)]" :href="shellHref('/')" :aria-label="`${brandName} - Trang chủ`">
          <img
            v-if="brandLogo"
            :src="brandLogoSrc"
            :alt="brandName"
            width="520"
            height="73"
            class="block h-auto w-auto object-contain max-w-[min(240px,100%)] max-h-[46px]
                   min-[561px]:max-w-[min(270px,100%)] min-[561px]:max-h-[52px]
                   min-[821px]:max-w-[min(265px,100%)] min-[821px]:max-h-[66px]
                   min-[1321px]:max-w-[min(300px,100%)] min-[1321px]:max-h-[80px]
                   min-[1681px]:max-w-[min(clamp(320px,20vw,420px),100%)] min-[1681px]:max-h-[96px]
                   min-[1800px]:max-h-[108px]
                   min-[2300px]:max-w-[460px] min-[2300px]:max-h-[120px]
                   max-[380px]:max-w-[min(214px,100%)] max-[380px]:max-h-[42px]
                   max-[340px]:max-w-[min(170px,100%)] max-[340px]:max-h-[34px]"
          >
          <span v-else class="truncate text-xl font-black tracking-tight text-hdr-ink">{{ brandName }}</span>
        </a>

        <!-- Điều hướng chính desktop (Danh mục sản phẩm + đúng 5 nhóm IA chuẩn) -->
        <nav class="pts-desktop-nav hidden min-w-0 min-[1321px]:flex-1 min-[1321px]:block" aria-label="Điều hướng chính">
          <ul class="flex items-center justify-center gap-1 nav:gap-0 min-[1681px]:gap-3">
            <li class="mr-1.5 flex flex-none items-center" @mouseenter="openCatalog('hover')" @mouseleave="scheduleCatalogClose">
              <NuxtLink custom :to="shellHref('/san-pham/')" v-slot="{ href, navigate }">
                <a
                  ref="catalogTrigger"
                  class="flex min-h-11 min-[1681px]:min-h-[48px] items-center gap-2 whitespace-nowrap rounded-btn border border-hdr-gold bg-hdr-gold px-3.5 min-[1681px]:px-4 text-[14px] min-[1681px]:text-[17px] font-semibold
                         text-hdr-ink no-underline transition-colors duration-200 ease-out hover:border-hdr-gold-hover hover:bg-hdr-gold-hover active:bg-hdr-gold-hover cursor-pointer"
                  :href="href || shellHref('/san-pham/')"
                  :aria-expanded="catalogOpen"
                  aria-haspopup="dialog"
                  aria-controls="ptsCatalogMenu"
                  @click="closeCatalog(false); navigate($event)"
                  @keydown.arrow-down.prevent="openCatalog('intent')"
                >
                  <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                  <span>Danh mục sản phẩm</span>
                  <svg class="h-[7px] w-[7px]" viewBox="0 0 10 10" aria-hidden="true"><path d="M1 3l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
                </a>
              </NuxtLink>
            </li>
            <li
              v-for="(item, index) in navItems"
              :key="item.href"
              class="relative flex items-center"
              @mouseenter="openNavDropdown(item, $event)"
              @mouseleave="handleNavMouseleave"
              @focusin="handleNavFocusin(item, $event)"
              @focusout="handleNavFocusout"
            >
              <NuxtLink
                :external="isExternalLink(shellHref(item.href))"
                class="relative flex min-h-11 min-[1681px]:min-h-[48px] items-center whitespace-nowrap px-3 text-[14.5px] min-[1681px]:text-[18px] font-semibold leading-tight text-hdr-ink no-underline transition-colors duration-200 ease-out after:absolute after:inset-x-3 after:bottom-0 after:h-0.5 after:origin-left after:scale-x-0 after:bg-hdr-gold after:transition-transform after:duration-200 hover:after:scale-x-100 hover:text-navy-950 focus-visible:after:scale-x-100 nav:px-2 min-[1681px]:px-2.5"
                :class="isActiveNavItem(item)
                  ? 'text-navy-950 after:scale-x-100'
                  : ''"
                :aria-current="isActiveNavItem(item) ? 'page' : undefined"
                :to="shellHref(item.href)"
              >
                {{ item.label }}
              </NuxtLink>
              <AnimatePresence :initial="false">
                <motion.div
                  v-if="item.children?.length && navDropdownOpen === item.href"
                  :key="item.href"
                  :id="`ptsNavMenu-${index}`"
                  :initial="reducedMotion ? false : { opacity: 0, y: 8 }"
                  :animate="{ opacity: 1, y: 0 }"
                  :exit="{ opacity: 0, y: 5 }"
                  :transition="{ duration: reducedMotion ? 0 : 0.16, ease: 'easeOut' }"
                  class="absolute left-0 top-full z-20 min-w-[320px] max-w-[calc(100vw-2rem)] rounded-btn border border-hdr-line bg-white p-3 shadow-[0_14px_28px_rgba(16,36,58,.12)]"
                  role="group"
                  :aria-label="`${item.label} - menu con`"
                >
                  <NuxtLink
                    v-for="entry in flattenLinks(item.children)"
                    :key="entry.key"
                    :external="isExternalLink(shellHref(entry.link.href))"
                    class="flex min-h-14 items-center rounded-chip px-5 py-3.5 text-[17px] font-medium leading-7 text-hdr-ink no-underline transition-colors hover:bg-hdr-surface hover:text-gold-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-gold-600"
                    :style="entry.depth ? { paddingInlineStart: `${16 + entry.depth * 12}px` } : undefined"
                    :to="shellHref(entry.link.href)"
                  >
                    {{ entry.link.label }}
                  </NuxtLink>
                </motion.div>
              </AnimatePresence>
            </li>
          </ul>
        </nav>

        <!-- Cụm thao tác bên phải -->
        <div class="flex min-w-0 items-center justify-end gap-1.5 max-ph:gap-1">
          <!-- GTranslate adds this marker class synchronously while it mounts. Keep
               it in SSR markup too so hydration does not report a class mismatch. -->
          <div class="hidden h-11 w-[45px] flex-none items-center justify-center min-[1681px]:flex" role="group" aria-label="Chọn ngôn ngữ">
            <div class="gtranslate_wrapper gt_container-unisv1 hidden h-11 w-[45px] flex-none items-center justify-center min-[1681px]:flex" />
          </div>

          <button
            ref="searchTrigger"
            class="relative inline-flex h-11 w-11 items-center justify-center rounded-chip text-hdr-ink
                   transition-colors hover:bg-hdr-surface hover:text-hdr-gold-deep"
            type="button"
            :aria-expanded="searchOpen"
            aria-controls="ptsHeaderSearch"
            :aria-label="searchOpen ? 'Đóng tìm kiếm' : 'Mở tìm kiếm'"
            @click="toggleSearch"
          >
            <UIcon name="i-lucide-search" class="size-[21px]" aria-hidden="true" />
          </button>

          <a
            v-if="hotline"
            class="pts-header-call hidden min-h-11 items-center justify-center gap-2.5 text-hdr-ink no-underline min-[821px]:inline-flex"
            :href="telHrefFor(hotline)"
            :aria-label="`Gọi ngay: ${hotline}`"
            :title="hotline"
          >
            <span class="pts-header-call__icon grid size-9 flex-none place-items-center" aria-hidden="true">
              <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.19 18a19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.09 3.18 2 2 0 0 1 4.08 1h3a2 2 0 0 1 2 1.72c.12.96.36 1.9.7 2.8a2 2 0 0 1-.45 2.11L8.06 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.84.58 2.8.7A2 2 0 0 1 22 16.92Z" /></svg>
            </span>
            <span class="hidden min-[1681px]:flex min-[1681px]:flex-col min-[1681px]:gap-0.5 min-[1681px]:whitespace-nowrap text-left">
              <small class="pts-header-call__label text-[11.5px] leading-none font-semibold">Gọi ngay</small>
              <b class="pts-header-call__number text-[15px] leading-tight font-extrabold">{{ hotline }}</b>
            </span>
          </a>

          <NuxtLink
            :external="isExternalLink(shellHref('/gio-hang/'))"
            class="relative h-11 w-11 items-center justify-center rounded-chip text-hdr-ink
                   transition-colors hover:bg-hdr-surface hover:text-hdr-gold-deep min-[821px]:inline-flex"
            :class="dockShowsCart ? 'hidden' : 'inline-flex'"
            :to="shellHref('/gio-hang/')"
            :aria-label="cartBadgeReady && cartCountAvailable && shopCart ? `Giỏ hàng có ${shopCart.count} sản phẩm` : 'Giỏ hàng'"
            title="Giỏ hàng"
            data-cart-target
          >
            <ClientOnly>
            <span
              v-if="cartCountAvailable && shopCart?.count"
              class="absolute right-0.5 top-0.5 grid min-h-4 min-w-4 place-items-center rounded-full bg-gold-500 px-1 text-[10px] font-black leading-4 text-navy-950"
            >{{ shopCart.count }}</span>
            </ClientOnly>
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h2l1 12h11l1.6-8H7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /><circle cx="9" cy="20" r="1.5" fill="currentColor" /><circle cx="16" cy="20" r="1.5" fill="currentColor" /></svg>
          </NuxtLink>

          <button
            type="button"
            class="ml-1 inline-flex min-h-11 min-[1681px]:min-h-[48px] items-center justify-center whitespace-nowrap rounded-btn border
                   border-hdr-gold bg-hdr-gold px-4 min-[1681px]:px-5 text-[14px] min-[1681px]:text-[15px] font-extrabold text-hdr-ink no-underline transition-all
                   hover:border-hdr-gold-deep hover:bg-hdr-gold-deep hover:shadow-xs active:scale-[0.98] max-ph:hidden cursor-pointer"
            @click="openConsultation()"
          >
            Nhận tư vấn
          </button>
        </div>
      </div>

      <!-- Backdrop mờ cho hộp thoại tìm kiếm: click ra ngoài để đóng nhanh -->
      <div
        v-if="searchOpen"
        ref="searchOverlay"
        class="contents"
      >
        <div
          class="pts-search-scrim absolute inset-x-0 top-full z-[24] h-[calc(100dvh-100%)] bg-navy-950/12 cursor-pointer"
          aria-hidden="true"
          @click="closeSearch()"
        />

        <!-- Hộp thoại tìm kiếm thả xuống cho màn hình <1501px -->
        <div
          id="ptsHeaderSearch"
          ref="searchPanel"
          class="pts-search-panel m-sheet-glass absolute inset-x-0 top-full z-[25] w-full border-t border-hdr-line-soft
                 bg-white/97 py-3.5 shadow-[0_18px_40px_-24px_rgba(16,36,58,.35)] backdrop-blur-md"
          role="dialog"
          aria-modal="true"
          aria-labelledby="ptsHeaderSearchTitle"
        >
        <div class="mx-auto w-full max-w-[max(1680px,var(--container-page))] px-4 max-[359px]:px-3 tb:px-6 nav:px-(--spacing-gutter)">
          <div class="flex flex-wrap items-center gap-x-6 gap-y-3 max-[1099px]:pr-12 min-[1100px]:flex-nowrap">
            <h2 id="ptsHeaderSearchTitle" class="sr-only">Tìm kiếm</h2>
            <form class="pts-search-field flex w-full min-w-0 items-center min-[1100px]:order-2 min-[1100px]:w-[min(520px,36vw)] min-[1100px]:flex-none" role="search" @submit.prevent="submitSearch">
              <label class="sr-only" for="ptsHeaderSearchInput">Tìm kiếm sản phẩm hoặc giải pháp</label>
              <UIcon name="i-lucide-search" class="ml-4 size-[18px] flex-none text-slate-400" aria-hidden="true" />
              <input
                id="ptsHeaderSearchInput"
                ref="searchInput"
                v-model="searchKeyword"
                class="h-12 w-full min-w-0 border-0 bg-transparent px-3 text-[15px] text-hdr-ink outline-none placeholder:text-hdr-muted"
                type="search"
                placeholder="Tên, công suất, thương hiệu"
                autocomplete="off"
                role="combobox"
                aria-autocomplete="list"
                aria-expanded="true"
                :aria-controls="headerSuggest?.listId"
                :aria-activedescendant="headerSuggest?.activeOptionId"
                @keydown="headerSuggest?.handleKey($event)"
              >
              <button
                class="mr-1 inline-flex h-10 flex-none items-center justify-center rounded-full bg-hdr-gold px-4 text-sm font-bold text-hdr-ink transition-colors hover:bg-hdr-gold-deep"
                type="submit"
              >
                <span class="max-tb:hidden">Tìm kiếm</span><span class="tb:hidden">Tìm</span>
              </button>
            </form>
            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-1.5 min-[1100px]:order-1" :class="{ 'max-tb:hidden': searchKeyword.trim().length >= 2 }">
              <span class="mr-1.5 text-[13px] font-semibold text-slate-500">Tra cứu nhanh</span>
              <button
                v-for="term in popularSearchTerms"
                :key="term"
                type="button"
                class="min-h-9 max-w-[220px] cursor-pointer truncate rounded-full border border-slate-200 bg-white px-3.5 text-[13px] font-semibold text-navy-800 transition-colors hover:border-gold-500 hover:bg-gold-50 hover:text-navy-950"
                @click="selectPopularSearch(term)"
              >
                {{ term }}
              </button>
            </div>
            <button
              class="inline-flex min-h-11 min-w-11 flex-none items-center justify-center rounded-chip text-hdr-ink transition-colors hover:bg-hdr-surface hover:text-gold-700 min-[1100px]:order-3 max-[1099px]:absolute max-[1099px]:right-3 max-[1099px]:top-3"
              type="button"
              aria-label="Đóng tìm kiếm"
              @click="closeSearch()"
            >
              <UIcon name="i-lucide-x" class="size-5" aria-hidden="true" />
            </button>
          </div>
          <SearchSuggestions
            ref="headerSuggest"
            class="mt-3"
            :keyword="searchKeyword"
            :site="site"
            :search-path="shellHref('/tim-kiem/')"
            id-prefix="pts-header-suggest"
            @leave="closeSearch(false)"
            @pick-term="selectPopularSearch"
          />
        </div>
        </div>
      </div>

      <!-- Mega-menu Danh mục sản phẩm: neo sát ngay dưới thanh header sticky -->
      <Transition name="pts-catalog-menu">
      <div
        v-if="catalogOpen"
        ref="catalogOverlay"
        class="absolute inset-x-0 top-full z-[100] h-[calc(100dvh-100%)] min-h-[calc(100vh-80px)] overflow-hidden catalog-overlay"
        aria-hidden="false"
      >
        <button
          class="absolute inset-0 h-full w-full cursor-default border-0 bg-transparent"
          type="button"
          aria-label="Đóng danh mục sản phẩm"
          @click="closeCatalog()"
        />
        <section
          id="ptsCatalogMenu"
          ref="catalogPanel"
          class="relative z-[1] mx-auto my-3 max-h-[calc(100dvh-88px)] w-[calc(100vw-24px)]
                 catalog-menu overflow-auto rounded-btn border border-[#e5e7eb] bg-white px-[18px] pt-5 text-navy-800
                 shadow-[0_18px_48px_rgba(6,21,55,.22)]
                 min-[1200px]:my-5 min-[1200px]:max-h-[calc(100dvh-64px)] min-[1200px]:w-[min(1220px,calc(100vw-64px))] min-[1200px]:px-8 min-[1200px]:pt-7"
          role="dialog"
          :aria-modal="catalogMode === 'intent' ? 'true' : undefined"
          aria-labelledby="ptsCatalogTitle"
          @mouseenter="cancelCatalogClose"
          @mouseleave="scheduleCatalogClose"
          @mousemove.passive="trackPointer"
        >
          <div
            class="grid grid-cols-[minmax(0,1fr)_40px] items-center gap-[13px] pb-[17px]
                   min-[1200px]:grid-cols-[minmax(0,1fr)_minmax(280px,400px)_36px] min-[1200px]:gap-6 min-[1200px]:pb-5"
          >
            <div class="min-w-0">
              <h2 id="ptsCatalogTitle" class="m-0 text-[21px] font-bold leading-[1.25] text-navy-800 min-[1200px]:text-[26px]">
                Khám phá sản phẩm
              </h2>
              <p class="mt-1 text-[13px] leading-normal text-slate-500 min-[1200px]:text-[15px]">
                Chọn thiết bị phù hợp với nhu cầu của bạn
              </p>
            </div>
            <form
              class="col-span-2 row-start-2 flex h-11 min-w-0 items-center gap-3 rounded-btn border border-[#d6dce4]
                     bg-slate-50/70 px-3.5 text-navy-800 focus-within:border-gold-700 focus-within:bg-white focus-within:shadow-[0_0_0_2px_rgba(201,150,46,.16)]
                     min-[1200px]:col-span-1 min-[1200px]:row-start-1 min-[1200px]:h-11"
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
            class="grid grid-cols-1 gap-[22px] py-1 pb-6 min-[1200px]:grid-cols-3 min-[1200px]:gap-6"
          >
            <section
              v-for="(group, groupIndex) in catalogColumns"
              :key="group.id"
              :aria-labelledby="`ptsCatalogGroup-${group.id}`"
              class="relative min-w-0 min-[1200px]:[&:not(:first-child)]:before:absolute min-[1200px]:[&:not(:first-child)]:before:inset-y-0
                     min-[1200px]:[&:not(:first-child)]:before:left-[-12px] min-[1200px]:[&:not(:first-child)]:before:border-l
                     min-[1200px]:[&:not(:first-child)]:before:border-[#e5e7eb] min-[1200px]:[&:not(:first-child)]:before:content-['']"
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
                  :class="{ 'is-flyout-open': flyoutKey === item.href + item.label }"
                  :data-search="item.searchText"
                  @mouseenter="hoverFlyout(item.href + item.label, item.children.length > 0)"
                  @mouseleave="leaveFlyout"
                >
                  <NuxtLink
                    :external="isExternalLink(shellHref(item.href))"
                  class="flex min-h-12 items-center gap-3 px-2 py-1 text-[15px] font-medium leading-6 text-[#10243a]
                           no-underline transition-colors duration-150 hover:bg-[#fff8e9] hover:text-navy-800
                           focus-visible:bg-[#fff8e9] group-focus-within/item:bg-[#fff8e9] group-[.is-flyout-open]/item:bg-[#fff8e9]"
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
                    class="catalog-flyout absolute top-0 z-[3] max-h-[min(400px,calc(100dvh-160px))] w-[260px] list-none overflow-y-auto rounded-btn
                           border border-[#e5e7eb] bg-white p-1.5 shadow-[0_12px_30px_rgba(6,21,55,.16)] left-full
                           [.catalog-group-last_&]:right-full [.catalog-group-last_&]:left-auto"
                    :aria-label="`Danh mục con: ${item.label}`"
                  >
                    <li v-for="child in item.children" :key="child.href + child.label">
                      <NuxtLink
                        :external="isExternalLink(shellHref(child.href))"
                        class="block min-h-11 rounded-chip px-3 py-3 text-[15px] font-medium leading-6 text-[#10243a] no-underline
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
              <button
                v-if="group.id === 'devices'"
                type="button"
                class="mt-6 flex min-h-[108px] w-full items-center gap-4 rounded-btn border border-gold-300/60 bg-gold-50 px-[18px] py-4 text-left
                       text-[#392a12] no-underline transition-colors duration-200 hover:bg-[#fff1d4] hover:text-navy-800 cursor-pointer"
                @click="openConsultation('Thiết bị (inverter, tấm pin, pin lưu trữ...)'); closeCatalog(false)"
              >
                <CatalogMenuIcon class="h-10 w-10 flex-none text-gold-700" name="headset" />
                <span>
                  <strong class="block text-[16px] font-bold">Cần hỗ trợ lựa chọn?</strong>
                  <small class="mt-1 block text-[14px] font-semibold text-gold-800">Nhận tư vấn →</small>
                </span>
              </button>
            </section>
          </div>
          <p v-else-if="hasCategories" class="m-0 py-4 text-[15px] text-slate-500">Không tìm thấy danh mục phù hợp.</p>
          <p v-else class="m-0 py-4 text-[15px] text-slate-500">Danh mục sản phẩm chưa có dữ liệu.</p>

          <div
            class="flex min-h-14 flex-col items-start justify-center gap-[9px] border-t border-[#e5e7eb] py-3
                   min-[1200px]:flex-row min-[1200px]:items-center min-[1200px]:justify-between min-[1200px]:gap-[18px] min-[1200px]:py-0"
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
      </Transition>
    </header>

    <!-- Drawer điều hướng mobile/tablet, used through 1320px -->
    <Teleport to="body">
    <Transition name="pts-drawer" :duration="{ enter: 380, leave: 260 }">
    <div v-if="mobileOpen" ref="mobileOverlay" class="pts-drawer fixed inset-0 z-[1100] overflow-hidden">
      <div
        class="fixed inset-0 h-full cursor-default bg-transparent"
        aria-hidden="true"
        @click="closeMobileMenu()"
      />
      <aside
        id="ptsMobileMenu"
        ref="mobilePanel"
        class="relative z-[1] flex h-dvh max-h-dvh pts-drawer__panel w-[min(326px,calc(100vw-24px))] flex-col overflow-hidden overscroll-y-contain p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))]
               shadow-[16px_0_36px_rgb(6_21_55_/_20%)] min-[761px]:w-[360px] min-[1501px]:w-[min(420px,calc(100vw-32px))]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ptsMobileTitle"
      >
        <div class="pts-drawer__head mb-[18px] flex items-center justify-between gap-3">
          <h2 id="ptsMobileTitle" class="sr-only">{{ brandName }}</h2>
          <img v-if="brandLogo" :src="brandLogoSrc" alt="" width="520" height="73" class="pts-drawer__logo">
          <button class="pts-morph m-lg-dark" type="button" aria-label="Đóng danh mục điều hướng" @click="closeMobileMenu()">
            <span aria-hidden="true" /><span aria-hidden="true" /><span aria-hidden="true" />
          </button>
        </div>

        <form class="mb-3 grid grid-cols-[minmax(0,1fr)_44px] overflow-hidden rounded-btn border border-[#d9dee5] bg-[#f7f8fa]" role="search" @submit.prevent="submitSearch">
          <label class="sr-only" for="ptsMobileSearchInput">Tìm kiếm sản phẩm hoặc giải pháp</label>
          <input
            id="ptsMobileSearchInput"
            ref="mobileSearchInput"
            v-model="searchKeyword"
            class="min-h-10 min-w-0 border-0 bg-transparent px-3 text-[13px] font-medium text-hdr-ink outline-none"
            type="search"
            placeholder="Tìm sản phẩm..."
            autocomplete="off"
          >
          <button class="inline-flex min-h-10 items-center justify-center bg-hdr-gold text-hdr-ink" type="submit" aria-label="Tìm kiếm">
            <UIcon name="i-lucide-search" class="size-5" aria-hidden="true" />
          </button>
        </form>

        <nav class="flex min-h-0 flex-1 flex-col overflow-y-auto overscroll-y-contain" aria-label="Điều hướng mobile">
          <div class="flex min-h-10 w-full items-center gap-2 border-b border-hdr-line">
            <NuxtLink
              :external="isExternalLink(shellHref('/san-pham/'))"
              class="flex min-h-12 min-w-0 flex-1 items-center gap-4 py-2 text-left text-[16px] font-semibold text-hdr-ink no-underline transition-colors hover:text-gold-700"
              :to="shellHref('/san-pham/')"
              @click="closeMobileMenu(false)"
            >
              Danh mục sản phẩm
            </NuxtLink>
            <button
              class="inline-flex min-h-10 min-w-10 flex-none items-center justify-center rounded-chip text-hdr-ink transition-colors hover:bg-hdr-surface hover:text-gold-700"
              type="button"
              :aria-expanded="mobileCatalogOpen"
              aria-controls="ptsMobileCatalog"
              :aria-label="`${mobileCatalogOpen ? 'Thu gọn' : 'Mở'} danh mục sản phẩm`"
              @click="mobileCatalogOpen = !mobileCatalogOpen"
            >
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
          </div>

          <div v-if="mobileCatalogOpen" id="ptsMobileCatalog" class="grid gap-0 border-b border-hdr-line py-1" role="group" aria-label="Danh mục sản phẩm">
            <template v-if="mobileCategoryEntries.length">
              <div
                v-for="entry in mobileCategoryEntries"
                :id="entry.key"
                :key="entry.key"
                v-show="mobileCategoryVisible(entry)"
                class="flex min-h-10 items-center gap-1 border-b border-hdr-line last:border-b-0"
                :style="entry.depth ? { paddingInlineStart: `${entry.depth * 16}px` } : undefined"
              >
                <NuxtLink
                  :external="isExternalLink(shellHref(entry.node.url))"
                  class="flex min-h-10 min-w-0 flex-1 items-center gap-3 py-1.5 text-[14px] leading-[1.4] text-hdr-ink no-underline transition-colors hover:text-gold-700"
                  :class="entry.depth === 0 ? 'font-semibold' : 'font-normal'"
                  :to="shellHref(entry.node.url)"
                  @click="closeMobileMenu(false)"
                >
                  <CatalogMenuIcon class="size-[19px] flex-none text-navy-800" :name="resolveEquipmentIcon(entry.node.name, entry.node.url)" />
                  <span class="min-w-0">{{ entry.node.name }}</span>
                </NuxtLink>
                <button
                  v-if="entry.node.children.length"
                  class="inline-flex min-h-10 min-w-10 flex-none items-center justify-center rounded-chip text-hdr-ink transition-colors hover:bg-hdr-surface hover:text-gold-700"
                  type="button"
                  :aria-expanded="Boolean(mobileCategoryExpanded[entry.key])"
                  :aria-controls="mobileCategoryChildren(entry).map((child) => child.key).join(' ')"
                  :aria-label="`${mobileCategoryExpanded[entry.key] ? 'Thu gọn' : 'Mở'} ${entry.node.name}`"
                  @click="toggleMobileCategory(entry)"
                >
                  <UIcon name="i-lucide-chevron-right" class="size-4 transition-transform duration-150" :class="{ 'rotate-90': mobileCategoryExpanded[entry.key] }" aria-hidden="true" />
                </button>
              </div>
            </template>
            <span v-if="!hasCategories" class="py-4 text-[15px] text-slate-500">Danh mục sản phẩm chưa có dữ liệu.</span>
            <NuxtLink
              :external="isExternalLink(shellHref('/san-pham/'))"
              class="mt-1 flex min-h-9 items-center py-2 text-[12px] font-bold leading-[1.4] text-gold-700 no-underline"
              :to="shellHref('/san-pham/')"
              @click="closeMobileMenu(false)"
            >
              Xem tất cả sản phẩm →
            </NuxtLink>
          </div>

          <template v-for="(item, index) in mobileNavItems" :key="item.href">
            <div class="flex min-h-10 w-full items-center gap-2 border-b border-hdr-line">
              <NuxtLink
                :external="isExternalLink(shellHref(item.href))"
                class="flex min-h-12 min-w-0 flex-1 items-center gap-4 py-2 text-[16px] font-semibold no-underline"
                :class="isActiveNavItem(item) ? 'font-bold text-gold-700' : 'text-hdr-ink'"
                :to="shellHref(item.href)"
                @click="closeMobileMenu(false)"
              >
                {{ item.label }}
              </NuxtLink>
              <button
                v-if="item.children?.length"
                class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-chip text-hdr-ink transition-colors hover:bg-hdr-surface hover:text-gold-700"
                type="button"
                :aria-expanded="mobileNavOpen === item.href"
                :aria-controls="`ptsMobileNav-${index}`"
                :aria-label="`${mobileNavOpen === item.href ? 'Thu gọn' : 'Mở'} menu ${item.label}`"
                @click="mobileNavOpen = mobileNavOpen === item.href ? null : item.href"
              >
                <UIcon name="i-lucide-chevron-right" class="size-4 transition-transform duration-150" :class="{ 'rotate-90': mobileNavOpen === item.href }" aria-hidden="true" />
              </button>
            </div>
            <div v-if="item.children?.length && mobileNavOpen === item.href" :id="`ptsMobileNav-${index}`" class="grid gap-0 border-b border-hdr-line py-1 pl-4" role="group" :aria-label="`${item.label} - menu con`">
              <NuxtLink
                v-for="entry in flattenLinks(item.children)"
                :key="entry.key"
                :external="isExternalLink(shellHref(entry.link.href))"
                class="flex min-h-11 items-center py-2 text-[15px] leading-6 text-hdr-sub no-underline hover:text-gold-700"
                :style="entry.depth ? { paddingInlineStart: `${entry.depth * 12}px` } : undefined"
                :to="shellHref(entry.link.href)"
                @click="closeMobileMenu(false)"
              >
                {{ entry.link.label }}
              </NuxtLink>
            </div>
          </template>
        </nav>

        <button
          type="button"
          class="mt-3 flex min-h-[50px] w-full flex-none items-center justify-center rounded-btn border border-hdr-gold
                 bg-hdr-gold px-[21px] text-sm font-bold text-hdr-ink no-underline transition-colors
                 hover:border-hdr-gold-deep hover:bg-hdr-gold-deep cursor-pointer"
          @click="openConsultation(); closeMobileMenu(false)"
        >
          Nhận tư vấn
        </button>
      </aside>
    </div>
    </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
/* Mobile menu: navy liquid glass; the panel slides while the menu icon morphs in place. */
.pts-drawer { background: rgb(6 21 55 / 42%); }
.pts-drawer__panel {
  --color-hdr-ink: #fff;
  --color-hdr-sub: #c9d5e5;
  --color-hdr-line: rgb(255 255 255 / 13%);
  --color-hdr-surface: rgb(255 255 255 / 9%);
  color: #fff;
  background: var(--m-glass-bg);
  -webkit-backdrop-filter: var(--m-glass-filter);
  backdrop-filter: var(--m-glass-filter);
  border-right: 1px solid var(--m-glass-rim);
  box-shadow: var(--m-glass-shadow);
}
.pts-drawer__head { margin-top: -6px; margin-right: -4px; }
.pts-drawer__logo { width: auto; max-width: 190px; height: 28px; object-fit: contain; object-position: left; }
.pts-drawer__panel :deep(.text-navy-800), .pts-drawer__panel :deep(.text-navy-950) { color: #fff; }
.pts-drawer__panel form[role="search"] { border-color: rgb(255 255 255 / 20%); border-radius: 22px; background: rgb(255 255 255 / 10%); box-shadow: inset 0 1px 0 rgb(255 255 255 / 14%); }
.pts-drawer__panel form[role="search"] input { color: #fff; }
.pts-drawer__panel form[role="search"] input::placeholder { color: #c9d5e5; }
.pts-drawer__panel form[role="search"] button { margin: 3px; min-height: 36px; border-radius: 18px; background: var(--pts-gold-gradient); color: var(--color-navy-950); }
.pts-morph { position: relative; flex: none; width: 44px; height: 44px; border-radius: 50%; color: #fff; cursor: pointer; }
.pts-morph span { position: absolute; top: 21px; left: 12px; width: 20px; height: 2px; border-radius: 2px; background: currentColor; }
.pts-morph span:nth-child(1) { transform: rotate(45deg); }
.pts-morph span:nth-child(2) { opacity: 0; }
.pts-morph span:nth-child(3) { transform: rotate(-45deg); }
.pts-drawer-enter-active, .pts-drawer-leave-active { transition: background-color .26s ease; }
.pts-drawer-enter-from, .pts-drawer-leave-to { background-color: rgb(6 21 55 / 0%); }
.pts-drawer-enter-active .pts-drawer__panel { transition: transform .38s cubic-bezier(.22, 1, .36, 1); }
.pts-drawer-leave-active .pts-drawer__panel { transition: transform .26s cubic-bezier(.4, 0, 1, 1); }
.pts-header-call { color: var(--color-navy-950); transition: transform .18s ease; }
.pts-header-call__icon { border-radius: var(--radius-btn); background: var(--pts-gold-gradient); box-shadow: 0 2px 6px rgb(6 21 55 / 18%), inset 0 1px 0 rgb(255 255 255 / 35%); color: var(--color-navy-950); transition: filter .18s ease; }
.pts-header-call__number { color: var(--color-navy-950); font-variant-numeric: tabular-nums; }
.pts-header-call__label { color: var(--color-slate-600, #475569); }
/* No lift on hover: a moving button slides out from under the pointer and flickers. */
.pts-header-call:hover .pts-header-call__icon { box-shadow: inset 0 0 0 999px rgb(255 255 255 / 9%); }
.pts-header-call:focus-visible { outline: 2px solid var(--color-gold-500); outline-offset: 2px; border-radius: var(--radius-md); }
@media (min-width: 1681px) {
  .pts-header-call { padding: 3px 14px 3px 3px; border: 1px solid rgb(10 34 81 / 12%); border-radius: var(--radius-md); background: rgb(255 255 255 / 88%); box-shadow: 0 1px 2px rgb(6 21 55 / 8%), inset 0 1px 0 #fff; transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease; }
  .pts-header-call:hover { border-color: var(--color-gold-400); box-shadow: 0 4px 12px rgb(6 21 55 / 12%), inset 0 1px 0 #fff; }
}
@media (prefers-reduced-motion: reduce) { .pts-header-call, .pts-header-call:hover { transform: none; transition: none; } }
.pts-drawer-enter-from .pts-drawer__panel, .pts-drawer-leave-to .pts-drawer__panel { transform: translateX(-100%); }
.pts-drawer-enter-active .pts-morph span { transition: transform .34s cubic-bezier(.3, 1.4, .5, 1), opacity .16s; }
.pts-drawer-leave-active .pts-morph span { transition: transform .24s ease-in, opacity .16s; }
.pts-drawer-enter-from .pts-morph span:nth-child(1), .pts-drawer-leave-to .pts-morph span:nth-child(1) { transform: translateY(-6px); }
.pts-drawer-enter-from .pts-morph span:nth-child(2), .pts-drawer-leave-to .pts-morph span:nth-child(2) { opacity: 1; }
.pts-drawer-enter-from .pts-morph span:nth-child(3), .pts-drawer-leave-to .pts-morph span:nth-child(3) { transform: translateY(6px); }
.pts-mobile-nav-trigger { transition: opacity .12s; }
@media (prefers-reduced-motion: reduce) {
  .pts-drawer-enter-active .pts-drawer__panel, .pts-drawer-leave-active .pts-drawer__panel, .pts-drawer-enter-active .pts-morph span, .pts-drawer-leave-active .pts-morph span { transition: none; }
}
.pts-search-field { border: 1px solid rgb(10 34 81 / 16%); border-radius: 999px; background: #fff; transition: border-color 0.2s ease, box-shadow 0.2s ease; }
/* The pill itself shows focus; the global 3px outline on the inner input would draw a box inside it. */
.pts-search-field input:focus, .pts-search-field input:focus-visible { outline: none; }
.pts-search-field input::-webkit-search-cancel-button { display: none; }
.pts-search-field:focus-within { border-color: var(--color-gold-500); box-shadow: 0 0 0 4px rgb(228 168 73 / 18%); }
.pts-search-panel { animation: pts-search-in 0.22s cubic-bezier(0.22, 1, 0.36, 1) both; }
.pts-search-scrim { animation: pts-fade-in 0.2s ease both; }
@keyframes pts-search-in { from { opacity: 0; transform: translateY(-6px); } }
@keyframes pts-fade-in { from { opacity: 0; } }
@media (prefers-reduced-motion: reduce) { .pts-search-panel, .pts-search-scrim { animation: none; } }
/* Flyouts fade in place instead of popping with display toggles. */
.catalog-flyout { visibility: hidden; opacity: 0; transform: translateX(-6px); pointer-events: none; transition: opacity 0.1s ease, transform 0.1s ease, visibility 0s linear 0.1s; }
.is-flyout-open > .catalog-flyout, li:focus-within > .catalog-flyout { visibility: visible; opacity: 1; transform: none; pointer-events: auto; transition: opacity 0.16s ease, transform 0.2s cubic-bezier(0.22, 1, 0.36, 1), visibility 0s; }
@media (prefers-reduced-motion: reduce) { .catalog-flyout { transition: none; transform: none; } }

.pts-catalog-menu-enter-active,
.pts-catalog-menu-leave-active {
  transition: opacity 180ms cubic-bezier(0.22, 0.61, 0.36, 1);
}

.pts-catalog-menu-enter-active #ptsCatalogMenu,
.pts-catalog-menu-leave-active #ptsCatalogMenu {
  transition:
    opacity 180ms cubic-bezier(0.22, 0.61, 0.36, 1),
    transform 220ms cubic-bezier(0.16, 1, 0.3, 1);
}

.pts-catalog-menu-enter-from,
.pts-catalog-menu-leave-to {
  opacity: 0;
}

.pts-catalog-menu-enter-from #ptsCatalogMenu,
.pts-catalog-menu-leave-to #ptsCatalogMenu {
  opacity: 0;
  transform: translate3d(0, -8px, 0);
}

@media (prefers-reduced-motion: reduce) {
  .pts-catalog-menu-enter-active,
  .pts-catalog-menu-leave-active,
  .pts-catalog-menu-enter-active #ptsCatalogMenu,
  .pts-catalog-menu-leave-active #ptsCatalogMenu {
    transition: none;
  }
}
</style>
