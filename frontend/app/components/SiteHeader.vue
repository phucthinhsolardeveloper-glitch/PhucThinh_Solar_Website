<script setup lang="ts">
import type { PublicCatalogGroup, PublicProductCategoryNode, PublicSiteLink, PublicSitePayload } from '#shared/contracts/public-site'

type MenuMode = 'hover' | 'intent' | null

const props = defineProps<{
  site: PublicSitePayload
}>()

const { active: isMigrationPreview, generic: shellHref, external: isExternalLink } = useMigrationLinks()
const { cart: migrationCart, refresh: refreshMigrationCart } = useMigrationCart()

const brandName = computed(() => props.site.brand.name.trim() || 'Phúc Thịnh Solar')
const hotline = computed(() => props.site.brand.hotline.trim())
const brandLogo = computed(() => props.site.brand.logo || '')
const searchOpen = ref(false)
const searchKeyword = ref('')
const catalogOpen = ref(false)
const catalogMode = ref<MenuMode>(null)
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
const navItems = computed(() => (props.site.navigation?.length ? props.site.navigation : fallbackNav).filter((item) => item.label !== 'Danh mục sản phẩm'))

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

function catalogGroupChildren(group: PublicCatalogGroup): PublicSiteLink[] {
  return group.items.flatMap((item) => item.children)
}

const catalogGroups = computed<RenderedCatalogGroup[]>(() => {
  const dtoGroups = props.site.catalog
    ? Object.entries(props.site.catalog)
      .map(([id, group]) => ({ id, ...group }))
      .filter((group) => group.items.length)
    : []
  if (dtoGroups.length) return dtoGroups
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

function focusFirst(container: HTMLElement | null) {
  if (!container) return
  const first = container.querySelector<HTMLElement>('a, button, input, [tabindex]:not([tabindex="-1"])')
  first?.focus()
}

/**
 * Khoá cuộn nền khi overlay (mega-menu hoặc drawer mobile) đang mở.
 *
 * PHÁT HIỆN KHI CHUYỂN SANG TAILWIND: class `pts-shell-menu-open` được toggle
 * lên <body> nhưng KHÔNG hề có rule CSS nào định nghĩa nó trong shell.css hay
 * bất kỳ file nào khác — tức tính năng khoá cuộn chưa từng hoạt động, overlay
 * mở ra nhưng trang nền vẫn cuộn được phía sau nó. Sửa trực tiếp bằng
 * `overflow: hidden` trên body thay vì thêm một class CSS rời chỉ để giữ đúng
 * tên cũ.
 */
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

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
  if (isMigrationPreview.value) void refreshMigrationCart()
})
onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeydown)
  if (import.meta.client && bodyOverflowBeforeLock !== null) document.body.style.overflow = bodyOverflowBeforeLock
})
</script>

<template>
  <div @mouseleave="handleShellLeave">
    <!--
      Mọi con số hình học dưới đây lấy từ assets/css/header-v3.css và đã đối
      chiếu bằng cách đo pixel trên ảnh chụp production 2026-10-26 ở 1920px:
        cao hàng   clamp(148px,8vw,164px) -> 153,6px   (ảnh đo 154px)
        cột logo   clamp(360px,22vw,460px) -> 422px    (ảnh đo 422px)
        nút consult cao 46px                            (ảnh đo 46px)
      Đừng "làm tròn cho đẹp": trong giai đoạn chạy song song, lệch vài pixel ở
      header là thứ người dùng thấy ngay khi đi qua ranh giới route PHP/Nuxt.
    -->
    <header
      :inert="mobileOpen ? true : undefined"
      class="relative z-[1000] border-b border-hdr-line bg-white/98 backdrop-blur-[10px]"
    >
      <div
        class="mx-auto flex w-full max-w-[1680px] items-center gap-[13px] px-4 min-h-[102px]
               tb:min-h-[94px] tb:py-2
               max-ph:min-h-[64px] max-ph:gap-2 max-ph:py-1.5
               nav:grid nav:grid-cols-[280px_minmax(0,1fr)_auto] nav:gap-[18px] nav:min-h-[132px]
               wd:grid-cols-[clamp(360px,22vw,460px)_minmax(0,1fr)_auto] wd:min-h-[clamp(148px,8vw,164px)] wd:py-2
               ul:grid-cols-[460px_minmax(0,1fr)_auto]"
      >
        <!-- Hamburger: chỉ dưới mốc nav (1321px), đúng như .btn-mmenu của PHP. -->
        <button
          ref="mobileTrigger"
          class="order-first flex h-11 w-11 flex-none items-center justify-center rounded-chip border
                 border-[#d8dee6] bg-white text-hdr-ink transition-colors hover:bg-hdr-surface
                 max-ph:h-11 max-ph:w-11 nav:hidden"
          type="button"
          :aria-expanded="mobileOpen"
          aria-controls="ptsMobileMenu"
          aria-label="Mở danh mục điều hướng"
          @click="mobileOpen ? closeMobileMenu() : openMobileMenu()"
        >
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" /></svg>
        </button>

        <a class="mr-auto flex min-w-0 items-center nav:mr-0" :href="shellHref('/')" :aria-label="`${brandName} - Trang chủ`">
          <!--
            Logo là ảnh do CMS cấp (brand.logo). Chỉ giới hạn kích thước, không
            ép tỉ lệ: file thật là wordmark ngang, ép aspect sẽ méo chữ.
            Nếu CMS chưa publish asset hợp lệ, chỉ dùng tên thương hiệu làm
            fallback an toàn; không vẽ hoặc nhúng logo giả trong component.
          -->
          <img
            v-if="brandLogo"
            :src="brandLogo"
            :alt="brandName"
            width="248"
            height="35"
            class="block h-auto w-auto object-contain max-w-[248px] max-h-[68px]
                   max-ph:max-w-[198px] max-ph:max-h-[55px]
                   tb:max-w-[228px] tb:max-h-[63px]
                   nav:max-w-[270px] nav:max-h-[72px]
                   wd:max-w-[min(530px,100%)] wd:max-h-[108px]
                   ul:max-w-[590px] ul:max-h-[130px]"
          >
          <span v-else class="truncate text-xl font-black tracking-tight text-hdr-ink">{{ brandName }}</span>
        </a>

        <nav class="hidden min-w-0 nav:block" aria-label="Điều hướng chính">
          <ul class="flex items-center justify-center gap-0.5">
            <!-- Nút danh mục đứng đầu (order:-2 ở bản PHP) và là nút gold duy nhất trong thanh nav. -->
            <li class="mr-1 flex flex-none items-center" @mouseenter="openCatalog('hover')">
              <button
                ref="catalogTrigger"
                class="flex items-center gap-[9px] whitespace-nowrap rounded-chip bg-hdr-gold font-bold
                       text-hdr-ink transition-colors hover:bg-hdr-gold-hover hover:text-white
                       min-h-[50px] px-[13px] text-[15.5px]
                       nav:px-2 nav:text-[13.2px]
                       wd:px-[10px] wd:text-[17px]
                       ul:px-[13px] ul:text-[15.5px]"
                type="button"
                :aria-expanded="catalogOpen"
                aria-controls="ptsCatalogMenu"
                @click="toggleCatalog"
              >
                <svg class="h-[13px] w-[13px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
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
                class="flex items-center whitespace-nowrap rounded-chip font-semibold leading-tight
                       text-hdr-text transition-colors hover:bg-hdr-surface hover:text-hdr-ink
                       min-h-[50px] px-3 text-[15.5px]
                       nav:px-[5px] nav:text-[13.2px]
                       wd:min-h-[48px] wd:px-[7px] wd:text-[17.5px]
                       ul:min-h-[50px] ul:px-3 ul:text-[15.5px]"
                :to="shellHref(item.href)"
                :aria-expanded="item.children?.length ? navDropdownOpen === item.href : undefined"
                :aria-controls="item.children?.length ? `ptsNavMenu-${index}` : undefined"
              >
                {{ item.label }}
                <!-- Mũi tên chỉ hiện ở mục thật sự có menu con, giống .has-child của PHP. -->
                <svg v-if="item.children?.length" class="ml-[7px] mb-[4px] h-[7px] w-[7px] text-hdr-muted" viewBox="0 0 10 10" aria-hidden="true"><path d="M1 3l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
              </NuxtLink>
              <div
                v-if="item.children?.length && navDropdownOpen === item.href"
                :id="`ptsNavMenu-${index}`"
                class="absolute left-0 top-full z-20 min-w-[250px] border border-hdr-line bg-white p-2 shadow-[0_14px_28px_rgba(16,36,58,.12)]"
                role="menu"
                :aria-label="`${item.label} - menu con`"
              >
                <NuxtLink
                  v-for="entry in flattenLinks(item.children)"
                  :key="entry.key"
                  :external="isExternalLink(shellHref(entry.link.href))"
                  class="block py-2 text-sm text-hdr-ink no-underline hover:bg-hdr-surface hover:text-gold-700"
                  :class="entry.depth ? 'pl-6' : 'px-3 font-semibold'"
                  :to="shellHref(entry.link.href)"
                  role="menuitem"
                >
                  {{ entry.link.label }}
                </NuxtLink>
              </div>
            </li>
          </ul>
        </nav>

        <div class="flex min-w-0 items-center justify-end gap-[6px] max-ph:gap-[3px] nav:gap-2">
          <!--
            Ô ngôn ngữ: bản PHP render một <div class="gtranslate_wrapper"> RỖNG
            và để script bên thứ ba (cdn.gtranslate.net) chèn cờ vào. Ở đây giữ
            đúng chỗ trống đó, không tự vẽ lá cờ giả — một nút cờ không bấm được
            còn tệ hơn là không có. Nạp script GTranslate hay không là quyết định
            riêng (hiệu năng, CSP, SEO), chưa thuộc phạm vi bước này.
          -->
          <div class="hidden max-w-[58px] flex-none items-center nav:flex" aria-label="Chọn ngôn ngữ" />

          <button
            ref="searchTrigger"
            class="relative inline-flex h-11 w-11 items-center justify-center rounded-chip text-[19px]
                   text-hdr-ink transition-colors hover:bg-hdr-surface hover:text-hdr-gold-deep
                   max-ph:h-11 max-ph:w-11 nav:h-12 nav:w-12"
            type="button"
            :aria-expanded="searchOpen"
            aria-controls="ptsHeaderSearch"
            :aria-label="searchOpen ? 'Đóng tìm kiếm' : 'Mở tìm kiếm'"
            @click="toggleSearch"
          >
            <svg class="h-[19px] w-[19px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.5" /><path d="m16 16 4.5 4.5" /></svg>
          </button>

          <a
            v-if="hotline"
            class="flex items-center gap-2 rounded-chip text-hdr-ink transition-colors hover:bg-hdr-surface
                   max-tb:hidden"
            :href="`tel:${hotline.replace(/[^+\d]/g, '')}`"
            :aria-label="`Gọi ${hotline}`"
          >
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-hdr-surface text-[15px] text-hdr-gold-deep">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.5 3.5 9 3l2 5-2 1.7a14 14 0 0 0 5.3 5.3L16 13l5 2-.5 2.5c-.3 1.5-1.7 2.5-3.2 2.2C10.8 18.5 5.5 13.2 4.3 6.7 4 5.2 5 3.8 6.5 3.5Z" /></svg>
            </span>
            <!-- Số điện thoại chỉ hiện dưới 1501px; từ 1501px trở lên bản PHP ẩn nó. -->
            <span class="flex flex-col gap-0.5 leading-[1.05] whitespace-nowrap wd:hidden">
              <small class="text-[11px] font-semibold text-hdr-muted">Tư vấn bán hàng</small>
              <b class="text-[14.5px] font-bold text-hdr-ink">{{ hotline }}</b>
            </span>
          </a>

          <NuxtLink
            :external="isExternalLink(shellHref('/gio-hang/'))"
            class="relative inline-flex h-11 w-11 items-center justify-center rounded-chip text-hdr-ink
                   transition-colors hover:bg-hdr-surface hover:text-hdr-gold-deep
                   max-ph:h-11 max-ph:w-11 nav:h-12 nav:w-12"
            :to="shellHref('/gio-hang/')"
            :aria-label="isMigrationPreview && migrationCart ? `Giỏ hàng có ${migrationCart.count} sản phẩm` : 'Giỏ hàng'"
            title="Giỏ hàng"
          >
            <span
              v-if="isMigrationPreview && migrationCart?.count"
              class="absolute right-0.5 top-0.5 grid min-h-4 min-w-4 place-items-center rounded-full bg-gold-500 px-1 text-[10px] font-black leading-4 text-navy-950"
            >{{ migrationCart.count }}</span>
            <svg class="h-[23px] w-[23px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h2l1 12h11l1.6-8H7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /><circle cx="9" cy="20" r="1.5" fill="currentColor" /><circle cx="16" cy="20" r="1.5" fill="currentColor" /></svg>
          </NuxtLink>

          <NuxtLink
            :external="isExternalLink(shellHref('/lien-he/#yeu-cau-tu-van'))"
            class="ml-[3px] inline-flex items-center justify-center whitespace-nowrap rounded-chip border
                   border-hdr-gold bg-hdr-gold font-bold text-hdr-ink transition-colors
                   hover:border-hdr-gold-deep hover:bg-hdr-gold-deep hover:text-white
                   min-h-[50px] px-[21px] text-[14.5px] max-ph:hidden
                   wd:min-h-[46px] wd:px-4 wd:text-[14px]
                   ul:min-h-[50px] ul:px-[21px] ul:text-[14.5px]"
            :to="shellHref('/lien-he/#yeu-cau-tu-van')"
          >
            Nhận tư vấn
          </NuxtLink>
        </div>
      </div>

      <div
        v-if="searchOpen"
        id="ptsHeaderSearch"
        ref="searchPanel"
        class="absolute inset-x-0 top-full z-[25] w-full border-t border-hdr-line-soft border-b-hdr-line
               bg-white px-4 pt-[18px] pb-4 shadow-[0_14px_28px_rgba(16,36,58,.1)] max-ph:py-[13px]"
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
                class="min-h-[46px] w-full max-w-[720px] rounded-chip border border-hdr-line px-4 text-[14.5px]
                       text-hdr-ink outline-none placeholder:text-hdr-muted focus:border-hdr-gold"
                type="search"
                placeholder="Bạn cần tìm sản phẩm hoặc giải pháp nào?"
                autocomplete="off"
              >
              <button
                class="inline-flex min-h-[46px] items-center justify-center rounded-chip bg-hdr-gold px-5
                       font-bold text-hdr-ink transition-colors hover:bg-hdr-gold-deep hover:text-white"
                type="submit"
                aria-label="Tìm kiếm"
              >
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.5" /><path d="m16 16 4.5 4.5" /></svg>
              </button>
            </form>
            <button
              class="rounded-chip p-2.5 text-hdr-ink transition-colors hover:bg-hdr-surface hover:text-gold-700"
              type="button"
              aria-label="Đóng tìm kiếm"
              @click="closeSearch()"
            >
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!--
      Overlay mega-menu. header-v3.css: inset-block-start 148px (<1501px) /
      132px (>=1501px) — đúng bằng chiều cao header thật ở từng dải, để lớp phủ
      không đè lên chính header đang mở nó.
    -->
    <div
      v-if="catalogOpen"
      class="fixed inset-x-0 bottom-0 top-[148px] z-[100] overflow-auto bg-[rgb(8_18_34_/_58%)] wd:top-[132px]"
      aria-hidden="false"
    >
      <div
        class="fixed inset-x-0 bottom-0 top-[148px] h-full cursor-default bg-transparent wd:top-[132px]"
        aria-hidden="true"
        @click="closeCatalog()"
      />
      <section
        id="ptsCatalogMenu"
        ref="catalogPanel"
        class="relative z-[1] mx-auto my-3 max-h-[calc(100vh-156px)] w-[calc(100vw-24px)] overflow-auto rounded-btn
               border border-hdr-line bg-white px-[18px] pt-5 shadow-[0_18px_48px_rgb(6_21_55_/_20%)]
               wd:w-[min(1200px,calc(100vw-64px))] wd:px-8 wd:pt-[30px]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ptsCatalogTitle"
        @mouseenter="catalogMode = 'hover'"
      >
        <div
          class="grid grid-cols-[minmax(0,1fr)_40px] items-center gap-2.5 pb-[18px]
                 wd:grid-cols-[minmax(0,1fr)_auto_40px] wd:gap-5"
        >
          <div class="col-span-2 row-start-2 wd:col-span-1 wd:row-start-1">
            <h2 id="ptsCatalogTitle" class="m-0 text-[21px] leading-[1.25] text-navy-800 wd:text-[25px]">
              Danh mục sản phẩm
            </h2>
            <p class="mt-1 text-[13px] text-slate-500 wd:text-sm">Dữ liệu danh mục đang được cung cấp từ API public.</p>
          </div>
          <NuxtLink
            :external="isExternalLink(shellHref('/san-pham/'))"
            class="justify-self-start text-sm font-bold text-navy-800 no-underline hover:text-gold-700 wd:justify-self-auto"
            :to="shellHref('/san-pham/')"
            @click="closeCatalog(false)"
          >
            Xem tất cả sản phẩm
          </NuxtLink>
          <button
            class="justify-self-end rounded-chip p-2.5 text-hdr-ink transition-colors hover:bg-hdr-surface hover:text-gold-700"
            type="button"
            aria-label="Đóng danh mục sản phẩm"
            @click="closeCatalog()"
          >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
          </button>
        </div>

        <div v-if="hasCategories" class="grid grid-cols-1 gap-[22px] pb-[26px] pt-1 wd:grid-cols-3 wd:gap-6">
          <section
            v-for="group in catalogGroups"
            :key="group.id"
            class="min-w-0 wd:[&:not(:first-child)]:border-l wd:[&:not(:first-child)]:border-hdr-line wd:[&:not(:first-child)]:pl-6"
          >
            <h3 class="mb-2 text-[17px] leading-[1.3]">
              <NuxtLink :external="isExternalLink(shellHref(group.href))" class="text-gold-700 no-underline" :to="shellHref(group.href)" @click="closeCatalog(false)">{{ group.label }}</NuxtLink>
            </h3>
            <ul class="m-0 list-none p-0">
              <li v-for="item in group.items" :key="item.href + item.label" class="border-b border-[#edf0f3] last:border-b-0">
                <NuxtLink
                  :external="isExternalLink(shellHref(item.href))"
                  class="flex min-h-12 items-center justify-between gap-3 py-1 text-[15px] leading-[1.4]
                         text-hdr-ink no-underline hover:text-gold-700"
                  :to="shellHref(item.href)"
                  @click="closeCatalog(false)"
                >
                  <span>{{ item.label }}</span>
                  <svg class="h-[18px] w-[18px] flex-none text-hdr-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" aria-hidden="true"><path d="m9 5 7 7-7 7" /></svg>
                </NuxtLink>
                <!-- border-gray-200 = #e5e7eb = --pts-line, như CatalogCategoryBranch. -->
                <ul v-if="item.children.length" class="mb-2 ml-4 list-none border-l border-gray-200 pl-3">
                  <li v-for="child in item.children" :key="child.href + child.label">
                    <NuxtLink
                      :external="isExternalLink(shellHref(child.href))"
                      class="flex min-h-9 items-center text-sm text-hdr-ink no-underline hover:text-gold-700"
                      :to="shellHref(child.href)"
                      @click="closeCatalog(false)"
                    >
                      {{ child.label }}
                    </NuxtLink>
                  </li>
                </ul>
              </li>
            </ul>
          </section>
        </div>
        <p v-else class="m-0 py-4 text-[15px] text-slate-500">Danh mục sản phẩm chưa có dữ liệu.</p>
      </section>
    </div>

    <!-- Drawer điều hướng mobile, dưới mốc nav (1321px). -->
    <div v-if="mobileOpen" class="fixed inset-0 z-[100] overflow-auto bg-[rgb(8_18_34_/_58%)]">
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
          <h2 id="ptsMobileTitle" class="m-0 text-2xl text-navy-800">Điều hướng</h2>
          <button
            class="rounded-chip p-2.5 text-hdr-ink transition-colors hover:bg-hdr-surface hover:text-gold-700"
            type="button"
            aria-label="Đóng danh mục điều hướng"
            @click="closeMobileMenu()"
          >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
          </button>
        </div>

        <form class="mb-5 grid grid-cols-[minmax(0,1fr)_50px] overflow-hidden rounded-chip border border-[#cbd3dc]" role="search" @submit.prevent="submitSearch">
          <label class="sr-only" for="ptsMobileSearchInput">Tìm kiếm sản phẩm hoặc giải pháp</label>
          <input
            id="ptsMobileSearchInput"
            ref="mobileSearchInput"
            v-model="searchKeyword"
            class="min-w-0 border-0 px-4 text-[15px] font-medium text-hdr-ink outline-none"
            type="search"
            placeholder="Tìm kiếm sản phẩm"
            autocomplete="off"
          >
          <button class="inline-flex items-center justify-center bg-hdr-gold text-hdr-ink" type="submit" aria-label="Tìm kiếm">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.5" /><path d="m16 16 4.5 4.5" /></svg>
          </button>
        </form>

        <nav aria-label="Điều hướng mobile">
          <template v-for="(item, index) in navItems" :key="item.href">
            <div class="flex min-h-12 w-full items-center gap-2 border-b border-hdr-line">
              <NuxtLink
                :external="isExternalLink(shellHref(item.href))"
                class="flex min-h-12 min-w-0 flex-1 items-center gap-4 py-2 text-base font-semibold text-hdr-ink no-underline"
                :to="shellHref(item.href)"
                @click="closeMobileMenu(false)"
              >
                {{ item.label }}
              </NuxtLink>
              <button
                v-if="item.children?.length"
                class="rounded-chip p-2.5 text-hdr-ink transition-colors hover:bg-hdr-surface hover:text-gold-700"
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
                class="py-[9px] text-[15px] leading-[1.45] text-hdr-sub no-underline"
                :class="entry.depth ? 'pl-3' : ''"
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
              class="py-[9px] text-[15px] leading-[1.45] text-hdr-sub no-underline"
              :to="shellHref('/san-pham/')"
              @click="closeMobileMenu(false)"
            >
              Xem tất cả sản phẩm
            </NuxtLink>
            <template v-if="hasCategories">
              <template v-for="group in catalogGroups" :key="group.id">
                <p class="mb-1 mt-3 text-[13px] font-bold uppercase tracking-wide text-gold-700 first:mt-1">{{ group.label }}</p>
                <NuxtLink
                  v-for="item in group.items"
                  :key="item.href + item.label"
                  :external="isExternalLink(shellHref(item.href))"
                  class="py-[9px] text-[15px] leading-[1.45] text-hdr-sub no-underline"
                  :to="shellHref(item.href)"
                  @click="closeMobileMenu(false)"
                >
                  {{ item.label }}
                </NuxtLink>
                <NuxtLink
                  v-for="child in catalogGroupChildren(group)"
                  :key="child.href + child.label"
                  :external="isExternalLink(shellHref(child.href))"
                  class="py-[9px] pl-3 text-[15px] leading-[1.45] text-hdr-sub no-underline"
                  :to="shellHref(child.href)"
                  @click="closeMobileMenu(false)"
                >
                  {{ child.label }}
                </NuxtLink>
              </template>
            </template>
            <span v-if="!hasCategories" class="py-4 text-[15px] text-slate-500">Danh mục sản phẩm chưa có dữ liệu.</span>
          </div>
        </nav>

        <NuxtLink
          :external="isExternalLink(shellHref('/lien-he/#yeu-cau-tu-van'))"
          class="mt-6 flex min-h-[50px] w-full items-center justify-center rounded-chip border border-hdr-gold
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
