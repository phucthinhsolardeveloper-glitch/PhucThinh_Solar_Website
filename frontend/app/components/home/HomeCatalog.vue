<script setup lang="ts">
import type { PublicHomePayload } from '#shared/contracts/public-home'

const props = defineProps<{ categories: PublicHomePayload['categories'] }>()
const links = useMigrationLinks()
const rail = ref<HTMLElement | null>(null)

let activePointer: number | null = null
let dragStartX = 0
let dragStartScroll = 0
let dragMoved = false
let suppressNextClick = false

function startDrag(event: PointerEvent) {
  suppressNextClick = false
  if (event.pointerType === 'touch' || event.button !== 0 || !rail.value) return

  activePointer = event.pointerId
  dragStartX = event.clientX
  dragStartScroll = rail.value.scrollLeft
  dragMoved = false
  rail.value.setPointerCapture(event.pointerId)
}

function moveDrag(event: PointerEvent) {
  if (event.pointerId !== activePointer || !rail.value) return
  const delta = event.clientX - dragStartX
  if (!dragMoved && Math.abs(delta) > 4) dragMoved = true
  if (!dragMoved) return

  event.preventDefault()
  rail.value.scrollLeft = dragStartScroll - delta
}

function finishDrag(event: PointerEvent) {
  if (event.pointerId !== activePointer || !rail.value) return
  const pointer = activePointer
  const moved = dragMoved
  activePointer = null
  dragMoved = false
  if (moved) suppressNextClick = true
  if (rail.value.hasPointerCapture(pointer)) rail.value.releasePointerCapture(pointer)
}

function preventDraggedClick(event: MouseEvent) {
  if (!suppressNextClick) return
  suppressNextClick = false
  event.preventDefault()
  event.stopPropagation()
}

function clearSuppressedClick() {
  suppressNextClick = false
}
</script>

<template>
  <section
    v-if="props.categories.length"
    id="danh-muc-thiet-bi"
    class="py-12 md:py-[clamp(64px,6vw,92px)]"
    aria-labelledby="home-catalog-title"
  >
    <div class="mx-auto w-[min(calc(100%-32px),2200px)] max-[359px]:w-[calc(100%-24px)]">
      <h1
        id="home-catalog-title"
        class="m-0 text-[clamp(31px,2.2vw,42px)] font-bold leading-[1.2] tracking-[-.025em] text-navy-950"
      >
        Sản phẩm năng lượng mặt trời
      </h1>

      <ul
        ref="rail"
        class="m-0 mt-8 flex list-none snap-x snap-proximity gap-6 overflow-x-auto overscroll-x-contain p-0 py-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden [touch-action:pan-x] md:gap-8"
        tabindex="0"
        aria-label="Danh mục sản phẩm, có thể cuộn ngang"
        @pointerdown="startDrag"
        @pointermove="moveDrag"
        @pointerup="finishDrag"
        @pointercancel="finishDrag"
        @lostpointercapture="finishDrag"
        @click.capture="preventDraggedClick"
        @keydown="clearSuppressedClick"
      >
        <li
          v-for="category in props.categories"
          :key="category.id"
          class="w-[min(78vw,300px)] flex-none snap-start md:w-[clamp(220px,22vw,300px)]"
        >
          <a
            class="group block h-full min-w-0 text-navy-950 no-underline"
            :href="links.generic(category.url)"
          >
            <img
              v-if="category.image"
              :src="category.image"
              :alt="category.name"
              class="mb-4 block aspect-[4/3] w-full object-contain"
              width="640"
              height="480"
              loading="lazy"
              draggable="false"
            >
            <span class="block text-lg font-bold leading-snug group-hover:text-gold-700">
              {{ category.name }}
            </span>
            <span class="mt-2 inline-flex items-center gap-2 text-sm font-semibold text-slate-600 group-hover:text-navy-950">
              Xem danh mục <span aria-hidden="true">→</span>
            </span>
          </a>
        </li>
      </ul>
    </div>
  </section>
</template>
