<script setup lang="ts">
import type { PublicHomePayload, PublicHomeCategory, PublicHomeEnergyCard } from '#shared/contracts/public-home'

const props = withDefaults(defineProps<{
  categories: PublicHomePayload['categories']
  campaigns: PublicHomePayload['campaigns']
  energyChain?: PublicHomeEnergyCard[]
}>(), { energyChain: () => [] })

// Ordered along the real energy chain: collect → convert → store → use.
const slots = [
  {
    key: 'panel',
    stage: 'Đón nắng',
    needles: ['tam-pin'],
    name: 'Tấm pin điện mặt trời',
    description: 'Thiết bị tạo điện từ ánh nắng, sử dụng trong hệ thống điện mặt trời.',
    action: 'Xem tấm pin',
    image: '/assets/images/site-visuals/category-panels-user-2026.jpg',
    position: 'center top',
    href: '/san-pham/',
  },
  {
    key: 'inverter',
    stage: 'Chuyển đổi',
    needles: ['bien-tan-inverter', 'inverter'],
    name: 'Biến tần (Inverter)',
    description: 'Thiết bị chuyển đổi và quản lý nguồn điện trong hệ thống điện mặt trời.',
    action: 'Xem biến tần',
    image: '/assets/images/site-visuals/category-inverter-user-2026.jpg',
    position: 'center',
    href: '/san-pham/',
  },
  {
    key: 'storage',
    stage: 'Lưu trữ',
    needles: ['pin-luu-tru', 'luu-tru'],
    name: 'Pin lưu trữ',
    description: 'Lưu trữ điện để sử dụng theo nhu cầu và cấu hình hệ thống.',
    action: 'Xem pin lưu trữ',
    image: '/assets/images/site-visuals/category-storage-user-2026.jpg',
    position: 'center',
    href: '/san-pham/',
  },
  {
    key: 'lighting',
    stage: 'Chiếu sáng',
    needles: ['den-nlmt'],
    name: 'Đèn năng lượng mặt trời',
    description: 'Đèn đường, đèn pha và đèn sân vườn cho nhiều không gian sử dụng.',
    action: 'Xem các dòng đèn',
    image: '/assets/images/site-visuals/home-street-lighting-v2.png',
    position: 'center 58%',
    href: '/san-pham/',
  },
  {
    key: 'pump',
    stage: 'Bơm nước',
    needles: ['bien-tan-bom', 'bom-nang-luong', 'bom'],
    name: 'Bơm nước năng lượng mặt trời',
    description: 'Thiết bị bơm nước phục vụ tưới tiêu và cấp nước bằng năng lượng mặt trời.',
    action: 'Xem bơm nước',
    image: '/assets/images/site-visuals/category-pump-user-2026.jpg',
    position: 'center',
    href: '/giai-phap/',
  },
] as const

type SlotKey = typeof slots[number]['key']
type HomeCategorySlot = Omit<typeof slots[number], 'stage' | 'name' | 'description' | 'action' | 'image' | 'position'> & {
  category: PublicHomeCategory | undefined
  title: string
  stage: string
  description: string
  action: string
  image: string
  position: string
}

// Default card art lives in both trees, so PHP can serve a resized WebP; on error the original is used.
const thumbFailed = ref(new Set<string>())
const siteVisual = (src: string) => thumbFailed.value.has(src) ? src : thumbUrl(src, 1200, 900, 3)
function onCardImageError(slot: { key: string; image: string }) {
  const original = slots.find((item) => item.key === slot.key)?.image
  if (original && !thumbFailed.value.has(original)) thumbFailed.value = new Set([...thumbFailed.value, original])
}

// Admin "Trang chủ - Chuỗi năng lượng" overrides card N field by field; blanks keep the defaults.
const adminImage = (src: string) => /^\/upload\/news\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|webp)$/i.test(src) ? thumbUrl(src, 1200, 900) : src
const chain = computed<HomeCategorySlot[]>(() => slots.map((slot, index) => {
  const admin = props.energyChain[index]
  const category = props.categories.find((item) => slot.needles.some((needle) => item.slug.toLowerCase().includes(needle)))
  return {
    ...slot,
    category,
    title: admin?.name || category?.name || slot.name,
    stage: admin?.stage || slot.stage,
    description: admin?.description || slot.description,
    action: admin?.action || slot.action,
    image: admin?.image ? adminImage(admin.image) : siteVisual(slot.image),
    position: admin?.image ? 'center' : slot.position,
  }
}))

const activeKey = ref<SlotKey>('panel')
const activeIndex = computed(() => chain.value.findIndex((slot) => slot.key === activeKey.value))
// Must mirror .chain-link flex-grow (1 collapsed, 4.2 active) so wire nodes sit under each panel's centre.
const ACTIVE_GROW = 4.2
const nodeCenters = computed(() => {
  const grows = chain.value.map((_, index) => (index === activeIndex.value ? ACTIVE_GROW : 1))
  const total = grows.reduce((sum, grow) => sum + grow, 0)
  let offset = 0
  return grows.map((grow) => {
    const center = ((offset + grow / 2) / total) * 100
    offset += grow
    return center
  })
})

const links = usePublicLinks()
</script>

<template>
  <section id="danh-muc-thiet-bi" class="energy-chain py-[clamp(56px,5vw,96px)] max-tb:py-8" aria-labelledby="home-catalog-title">
    <div class="pts-container">
      <header class="mb-9 max-tb:mb-4 grid gap-4 max-tb:gap-2 min-[961px]:grid-cols-[minmax(0,1fr)_minmax(280px,420px)] min-[961px]:items-end">
        <h2 id="home-catalog-title" class="m-0 max-w-[16ch] text-[clamp(30px,3.2vw,52px)] font-black leading-[1.06] tracking-[-.035em] text-navy-950">
          Từ tia nắng đến nguồn điện trong nhà bạn
        </h2>
        <p class="m-0 text-[clamp(15px,1.05vw,18px)] leading-[1.65] text-slate-600">
          Năm nhóm thiết bị nối với nhau thành một hệ thống. Chọn từng mắt xích để xem thiết bị phù hợp.
        </p>
      </header>

      <!-- Desktop: expanding chain. Mobile: snap rail. -->
      <ol class="chain-track m-0 list-none p-0" :style="{ '--active': activeIndex }">
        <li
          v-for="slot in chain"
          :key="slot.key"
          class="chain-link"
          :class="{ 'is-active': slot.key === activeKey }"
          @mouseenter="activeKey = slot.key"
        >
          <a
            class="chain-card group"
            :href="links.generic(slot.category?.url || slot.href)"
            :aria-label="`${slot.stage}: ${slot.title}`"
            @focus="activeKey = slot.key"
          >
            <img
              class="chain-image"
              :src="slot.image"
              :style="{ objectPosition: slot.position }"
              alt=""
              width="960"
              height="760"
              loading="lazy"
              @error="onCardImageError(slot)"
            >
            <span class="chain-veil" aria-hidden="true" />

            <span class="chain-step">
              <span>{{ slot.stage }}</span>
            </span>

            <span class="chain-body">
              <strong class="chain-title">{{ slot.title }}</strong>
              <span class="chain-desc">{{ slot.description }}</span>
              <span class="chain-cta">{{ slot.action }} <span aria-hidden="true">→</span></span>
            </span>
          </a>
        </li>
      </ol>

      <!-- The wire: one continuous gold conductor with a current pulse travelling to the active link. -->
      <div class="chain-wire" aria-hidden="true">
        <span class="chain-wire-pulse" :style="{ '--to': `${nodeCenters[activeIndex]}%` }" />
        <span
          v-for="(slot, index) in chain"
          :key="`node-${slot.key}`"
          class="chain-node"
          :class="{ 'is-on': index <= activeIndex }"
          :style="{ left: `${nodeCenters[index]}%` }"
        />
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Mirrors .pts-container padding-inline so the mobile rail bleeds edge-to-edge but snaps on the grid. */
.energy-chain { --chain-gutter: 1rem; }
@media (max-width: 359px) { .energy-chain { --chain-gutter: 0.75rem; } }
@media (min-width: 821px) { .energy-chain { --chain-gutter: 1.5rem; } }
.chain-track {
  display: flex;
  gap: 10px;
  height: clamp(420px, 34vw, 560px);
}
.chain-link {
  position: relative;
  flex: 1 1 0;
  min-width: 0;
  transition: flex-grow 0.6s cubic-bezier(0.22, 1, 0.36, 1);
}
.chain-link.is-active { flex-grow: 4.2; }

.chain-card {
  position: relative;
  isolation: isolate;
  display: block;
  height: 100%;
  overflow: hidden;
  overflow: clip;
  border-radius: var(--radius-btn);
  background: var(--color-navy-950);
  color: #fff;
  text-decoration: none;
}
.chain-card:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 3px; }

.chain-image {
  position: absolute;
  inset: 0;
  z-index: -2;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transform: scale(1.08);
  filter: saturate(0.75) brightness(0.8);
  transition: transform 0.9s cubic-bezier(0.22, 1, 0.36, 1), filter 0.6s ease;
}
.is-active .chain-image { transform: scale(1); filter: saturate(1) brightness(1); }

.chain-veil {
  position: absolute;
  inset: 0;
  z-index: -1;
  background: linear-gradient(180deg, rgb(6 21 55 / 10%) 0%, rgb(6 21 55 / 35%) 45%, rgb(6 21 55 / 94%) 100%);
}

/* Collapsed links read as a vertical spine: stage label rotated up the panel. */
.chain-step {
  position: absolute;
  left: 18px;
  bottom: 22px;
  display: flex;
  align-items: center;
  gap: 12px;
  transform-origin: left bottom;
  transform: rotate(-90deg) translate(-2px, 100%);
  white-space: nowrap;
  font-size: 15px;
  font-weight: 800;
  letter-spacing: -0.01em;
  transition: transform 0.6s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.3s ease;
}
.is-active .chain-step {
  top: 22px;
  bottom: auto;
  transform: none;
}

.chain-body {
  position: absolute;
  left: clamp(20px, 2.4vw, 36px);
  right: clamp(20px, 2.4vw, 36px);
  bottom: clamp(22px, 2.4vw, 36px);
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-width: 460px;
  opacity: 0;
  transform: translateY(16px);
  pointer-events: none;
  transition: opacity 0.35s ease, transform 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}
.is-active .chain-body {
  opacity: 1;
  transform: none;
  transition-delay: 0.22s;
}
.chain-title { display: -webkit-box; overflow: hidden; -webkit-box-orient: vertical; -webkit-line-clamp: 2; font-size: clamp(24px, 2.2vw, 36px); font-weight: 900; line-height: 1.08; letter-spacing: -0.03em; }
.chain-desc { display: -webkit-box; overflow: hidden; -webkit-box-orient: vertical; -webkit-line-clamp: 3; font-size: 15.5px; line-height: 1.6; color: rgb(226 232 240); }
.chain-cta {
  margin-top: 6px;
  align-self: flex-start;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  border-bottom: 2px solid var(--color-gold-500);
  padding-bottom: 4px;
  font-size: 15px;
  font-weight: 800;
  color: var(--color-gold-300);
}
.chain-card:hover .chain-cta { color: #fff; }

/* Gold conductor under the chain */
.chain-wire {
  position: relative;
  height: 28px;
  margin-top: 14px;
}
.chain-wire::before {
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  top: 13px;
  height: 2px;
  background: linear-gradient(90deg, var(--color-gold-500), rgb(201 150 46 / 25%));
}
.chain-wire-pulse {
  position: absolute;
  left: 0;
  top: 12px;
  width: var(--to);
  height: 4px;
  border-radius: var(--radius-btn);
  background: linear-gradient(90deg, rgb(201 150 46 / 0%), var(--color-gold-400) 70%, #fff3d6);
  box-shadow: 0 0 14px rgb(224 182 84 / 70%);
  transition: width 0.7s cubic-bezier(0.22, 1, 0.36, 1);
}
.chain-node {
  position: absolute;
  top: 8px;
  width: 12px;
  height: 12px;
  margin-left: -6px;
  border-radius: 50%;
  border: 2px solid var(--color-gold-500);
  background: #fff;
  transition: left 0.6s cubic-bezier(0.22, 1, 0.36, 1), background 0.4s ease, box-shadow 0.4s ease;
}
.chain-node.is-on { background: var(--pts-gold-gradient); box-shadow: 0 0 0 5px rgb(224 182 84 / 22%); }

/* Tablet & mobile: horizontal snap rail, every link fully open */
@media (max-width: 960px) {
  .chain-track {
    height: auto;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    scroll-padding-inline: var(--chain-gutter);
    scrollbar-width: none;
    margin-inline: calc(var(--chain-gutter) * -1);
    padding-inline: var(--chain-gutter);
  }
  .chain-track::-webkit-scrollbar { display: none; }
  .chain-link, .chain-link.is-active { flex: 0 0 min(78vw, 340px); scroll-snap-align: start; }
  .chain-card { height: clamp(380px, 110vw, 460px); }
  .chain-image { transform: none; filter: none; }
  .chain-step { top: 20px; bottom: auto; transform: none; }
  .chain-body { opacity: 1; transform: none; pointer-events: auto; }
  .chain-wire { display: none; }
}

@media (prefers-reduced-motion: reduce) {
  .chain-link, .chain-image, .chain-step, .chain-body, .chain-wire-pulse, .chain-node { transition: none !important; }
}

@media (max-width: 820px) {
  .chain-card { height: clamp(340px, 96vw, 420px); border-radius: var(--radius-md); }
  .chain-title, .chain-desc { display: block; overflow: visible; -webkit-line-clamp: unset; }
  .chain-desc { font-size: 15px; line-height: 1.5; }
}
</style>
