<script setup lang="ts">
import type { PublicProject, PublicProjectBenefit } from '#shared/contracts/public-projects'

const props = defineProps<{
  title: string
  description?: string
  project: PublicProject | null
  benefits: PublicProjectBenefit[]
  label: string
  viewLabel: string
  backdrop?: string
}>()
const links = useMigrationLinks()
const image = computed(() => props.project?.image || props.backdrop || '')

const heroRef = ref<HTMLElement | null>(null)
let frame = 0
function onScroll() {
  cancelAnimationFrame(frame)
  frame = requestAnimationFrame(() => {
    const el = heroRef.value
    if (!el) return
    el.style.setProperty('--shift', `${Math.round(Math.min(window.scrollY, el.offsetHeight) * 0.22)}px`)
  })
}
onMounted(() => {
  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) window.addEventListener('scroll', onScroll, { passive: true })
})
onBeforeUnmount(() => { window.removeEventListener('scroll', onScroll); cancelAnimationFrame(frame) })
</script>

<template>
  <section ref="heroRef" class="projects-hero" aria-labelledby="projects-title">
    <img v-if="image" :src="image" alt="" class="projects-hero__image" fetchpriority="high" decoding="async">
    <span class="projects-hero__shade" aria-hidden="true" />
    <div class="pts-container projects-hero__inner">
      <div class="projects-hero__copy">
        <h1 id="projects-title">{{ title }}</h1>
        <p v-if="description">{{ description }}</p>
      </div>

      <article v-if="project" class="projects-hero__feature">
        <a class="group" :href="links.project(project.slug)" :aria-label="`${viewLabel}: ${project.title}`">
          <span class="projects-hero__label">{{ label }}</span>
          <h2>{{ project.title }}</h2>
          <p v-if="project.address" class="projects-hero__place">
            <UIcon name="i-lucide-map-pin" class="size-4 flex-none text-gold-400" aria-hidden="true" />
            <span>Khu vực triển khai: <strong>{{ project.address }}</strong></span>
          </p>
          <ul v-if="benefits.length">
            <li v-for="benefit in benefits" :key="benefit.label">
              <img v-if="benefit.image" :src="benefit.image" alt="" class="size-4 object-contain" loading="lazy">
              <UIcon v-else name="i-lucide-check" class="size-4 flex-none text-gold-300" aria-hidden="true" />
              {{ benefit.label }}
            </li>
          </ul>
          <span class="projects-hero__go">{{ viewLabel }} <span aria-hidden="true"><UIcon name="i-lucide-arrow-right" class="size-5" /></span></span>
        </a>
      </article>
    </div>
  </section>
</template>

<style scoped>
.projects-hero { --ease: cubic-bezier(0.22, 1, 0.36, 1); position: relative; isolation: isolate; display: flex; min-height: clamp(480px, 62vh, 640px); align-items: flex-end; overflow: hidden; background: #061537; color: #fff; }
.projects-hero__image { position: absolute; inset: -8% 0 0; z-index: -2; width: 100%; height: 108%; object-fit: cover; transform: translate3d(0, var(--shift, 0), 0) scale(1.03); animation: projects-in 1.2s var(--ease) both; }
.projects-hero__shade { position: absolute; inset: 0; z-index: -1; background: linear-gradient(0deg, rgb(6 21 55 / 92%) 0%, rgb(6 21 55 / 40%) 50%, rgb(6 21 55 / 10%) 100%), linear-gradient(90deg, rgb(6 21 55 / 60%), transparent 60%); }
.projects-hero__inner { display: grid; width: 100%; grid-template-columns: minmax(0, 1fr) minmax(320px, 440px); align-items: end; gap: clamp(28px, 5vw, 80px); padding-block: 56px clamp(40px, 5vw, 72px); }
.projects-hero__copy h1 { margin: 0; color: #fff; font-size: clamp(38px, 5vw, 76px); font-weight: 850; line-height: 1.02; letter-spacing: -0.045em; text-wrap: balance; }
.projects-hero__copy p { max-width: 54ch; margin: 22px 0 0; color: #d3dcea; font-size: clamp(17px, 1.35vw, 20px); line-height: 1.65; }
.projects-hero__feature a { display: flex; flex-direction: column; gap: 14px; padding: clamp(22px, 2.2vw, 32px); border: 1px solid rgb(255 255 255 / 16%); border-radius: var(--radius-md); background: rgb(6 21 55 / 55%); color: #fff; text-decoration: none; backdrop-filter: blur(14px); transition: border-color 0.35s ease, transform 0.45s var(--ease); }
.projects-hero__feature a:hover { transform: translateY(-4px); border-color: rgb(228 168 73 / 60%); }
.projects-hero__feature a:focus-visible { outline: 3px solid var(--color-gold-300); outline-offset: 3px; }
.projects-hero__label { color: var(--color-gold-300); font-size: 14px; font-weight: 800; }
.projects-hero__feature h2 { margin: 0; color: #fff; font-size: clamp(22px, 1.9vw, 28px); font-weight: 850; line-height: 1.2; letter-spacing: -0.02em; }
.projects-hero__place { display: flex; align-items: center; gap: 8px; margin: 0; color: #c3cfdf; font-size: 14.5px; }
.projects-hero__place strong { color: #fff; }
.projects-hero__feature ul { display: grid; gap: 8px; margin: 0; padding: 14px 0 0; border-top: 1px solid rgb(255 255 255 / 14%); list-style: none; }
.projects-hero__feature li { display: flex; align-items: center; gap: 10px; color: #dbe4f0; font-size: 14.5px; }
.projects-hero__go { display: flex; min-height: 52px; align-items: center; justify-content: space-between; margin-top: 4px; padding: 0 8px 0 18px; border-radius: var(--radius-sm); background: var(--pts-gold-gradient); color: var(--color-navy-950); font-weight: 850; transition: filter 0.25s ease; }
.projects-hero__go > span { display: grid; width: 38px; height: 38px; place-items: center; border-radius: 4px; background: var(--color-navy-950); color: var(--color-gold-300); transition: transform 0.35s var(--ease); }
.projects-hero__feature a:hover .projects-hero__go { filter: brightness(1.06); }
.projects-hero__feature a:hover .projects-hero__go > span { transform: translateX(4px); }
@keyframes projects-in { from { opacity: 0; transform: scale(1.1); } }
@media (max-width: 900px) {
  .projects-hero { min-height: 0; }
  .projects-hero__inner { grid-template-columns: 1fr; padding-top: clamp(200px, 40vw, 280px); }
}
@media (prefers-reduced-motion: reduce) {
  .projects-hero__image { animation: none; transform: none; }
  .projects-hero__feature a, .projects-hero__go > span { transition: none; }
  .projects-hero__feature a:hover { transform: none; }
}
</style>
