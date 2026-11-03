<script setup lang="ts">
import type { PublicAboutPayload } from '#shared/contracts/public-about'

const props = defineProps<{ about: PublicAboutPayload }>()
const links = useMigrationLinks()
const { openConsultation } = useStorefrontModal()
const { isHeaderHidden } = useSiteHeader()
const aboutNavRef = ref<HTMLElement | null>(null)
useActiveHorizontalRail(aboutNavRef)
const activeAboutSection = ref('ve-phuc-thinh')
let aboutSectionObserver: IntersectionObserver | null = null
const galleryCertificates = computed(() => props.about.certificates.items)
const visibleCertificates = computed(() => {
  const featured = props.about.certificates.items.filter((item) => item.featured)
  return (featured.length ? featured : props.about.certificates.items).slice(0, 3)
})
const certificateDialog = ref<HTMLDialogElement | null>(null)
const certificateCloseButton = ref<HTMLButtonElement | null>(null)
const certificateTrigger = ref<HTMLElement | null>(null)
const certificateGalleryOpen = ref(false)
const activeCertificateIndex = ref(0)
const activeCertificate = computed(() => galleryCertificates.value[activeCertificateIndex.value] ?? null)
const aboutSectionNavItems = computed(() => [
  { id: 've-phuc-thinh', label: props.about.labels.story },
  ...(props.about.history.items.length ? [{ id: 'lich-su-phat-trien', label: props.about.labels.history }] : []),
  { id: 'con-nguoi', label: props.about.labels.people },
  ...(props.about.compass.items.length ? [{ id: 'tam-nhin-su-menh', label: props.about.labels.compass }] : []),
  ...(props.about.certificates.items.length ? [{ id: 'about-certificates', label: props.about.labels.certificates }] : []),
])

function setupAboutSectionObserver() {
  aboutSectionObserver?.disconnect()
  if (!import.meta.client) return
  const sections = aboutSectionNavItems.value
    .map((item) => document.getElementById(item.id))
    .filter((section): section is HTMLElement => Boolean(section))
  if (!sections.length) return
  aboutSectionObserver = new IntersectionObserver((entries) => {
    const current = entries
      .filter((entry) => entry.isIntersecting)
      .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0]?.target as HTMLElement | undefined
    if (current?.id) activeAboutSection.value = current.id
  }, { rootMargin: '-150px 0px -58% 0px', threshold: [0, 0.01] })
  sections.forEach((section) => aboutSectionObserver?.observe(section))
}

function navigateAboutSection(id: string) {
  const target = document.getElementById(id)
  if (!target) return
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  activeAboutSection.value = id
  window.history.replaceState(null, '', `${window.location.pathname}${window.location.search}#${id}`)
  target.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' })
}

onMounted(() => nextTick(setupAboutSectionObserver))
watch(aboutSectionNavItems, () => nextTick(setupAboutSectionObserver), { deep: true })
onBeforeUnmount(() => aboutSectionObserver?.disconnect())

async function openCertificate(item: PublicAboutPayload['certificates']['items'][number], event: MouseEvent) {
  const index = galleryCertificates.value.findIndex((candidate) => candidate.id === item.id)
  if (index < 0) return
  certificateTrigger.value = event.currentTarget instanceof HTMLElement ? event.currentTarget : null
  activeCertificateIndex.value = index
  certificateGalleryOpen.value = true
  await nextTick()
  if (certificateDialog.value && !certificateDialog.value.open) certificateDialog.value.showModal()
  certificateCloseButton.value?.focus()
}

function openAllCertificates(event: MouseEvent) {
  const first = galleryCertificates.value[0]
  if (first) void openCertificate(first, event)
}

function previousCertificate() {
  if (activeCertificateIndex.value > 0) activeCertificateIndex.value--
}

function nextCertificate() {
  if (activeCertificateIndex.value < galleryCertificates.value.length - 1) activeCertificateIndex.value++
}

function closeCertificateGallery() {
  if (certificateDialog.value?.open) certificateDialog.value.close()
  else certificateGalleryOpen.value = false
}

function onCertificateDialogClose() {
  certificateGalleryOpen.value = false
  const trigger = certificateTrigger.value
  certificateTrigger.value = null
  if (trigger?.isConnected) nextTick(() => trigger.focus())
}

function onCertificateDialogBackdrop(event: MouseEvent) {
  if (event.target === event.currentTarget) closeCertificateGallery()
}
</script>

<template>
  <div class="about-editorial text-navy-800">
    <section id="ve-phuc-thinh" class="about-story" aria-labelledby="about-story-title">
      <div class="pts-container about-story-layout">
        <div class="about-story-copy">
          <h1 id="about-story-title">{{ about.story.title }}</h1>
          <p v-if="about.story.intro" class="about-story-intro">{{ about.story.intro }}</p>
          <p v-if="about.story.body" class="about-story-body">{{ about.story.body }}</p>
        </div>
        <figure v-if="about.story.image" class="about-story-image">
          <img :src="about.story.image" :alt="about.story.title" width="1600" height="1000" fetchpriority="high">
        </figure>
        <div v-else class="about-story-illustration" aria-hidden="true">
          <svg viewBox="0 0 720 520" fill="none">
            <defs>
              <linearGradient id="about-sun" x1="0" y1="0" x2="1" y2="1"><stop stop-color="var(--color-gold-400)"/><stop offset="1" stop-color="var(--color-gold-500)"/></linearGradient>
              <linearGradient id="about-panel" x1="0" y1="0" x2="1" y2="1"><stop stop-color="var(--color-navy-700)"/><stop offset="1" stop-color="var(--color-navy-800)"/></linearGradient>
            </defs>
            <circle cx="536" cy="139" r="68" fill="url(#about-sun)" fill-opacity=".95"/>
            <path d="M0 338 356 218l364 126v176H0V338Z" fill="var(--color-navy-800)"/>
            <path d="m130 284 226 75 211-70-228-77-209 72Z" fill="url(#about-panel)" stroke="rgba(255,255,255,.82)" stroke-opacity=".65" stroke-width="2"/>
            <path d="m130 284 209 70v108l-209-69V284Zm209 70 228-65v108l-228 65V354Z" fill="var(--color-navy-950)" stroke="rgba(255,255,255,.82)" stroke-opacity=".55" stroke-width="2"/>
            <path d="m180 269 209 70m-149-90 209 70m-110-90 209 70M200 307v108m76-82v108m76-83v108m76-107v108m76-130v108" stroke="rgba(255,255,255,.82)" stroke-opacity=".5" stroke-width="2"/>
            <path d="M0 465h720" stroke="var(--color-gold-400)" stroke-opacity=".65" stroke-width="2"/>
          </svg>
        </div>
      </div>
    </section>

    <nav class="about-chapter-nav" :class="isHeaderHidden ? 'is-header-hidden' : ''" aria-label="Đi nhanh trong trang Giới thiệu">
      <div ref="aboutNavRef" class="pts-container about-chapter-nav-track" aria-label="Mục nội dung Giới thiệu">
        <a
          v-for="item in aboutSectionNavItems"
          :key="item.id"
          :data-about-section="item.id"
          :href="`#${item.id}`"
          :aria-current="activeAboutSection === item.id ? 'location' : undefined"
          @click.prevent="navigateAboutSection(item.id)"
        >{{ item.label }}</a>
      </div>
    </nav>

    <section v-if="about.history.items.length" id="lich-su-phat-trien" class="about-chapter about-history" aria-labelledby="about-history-title">
      <div class="pts-container about-history-layout">
        <header class="about-chapter-heading">
          <h2 id="about-history-title">{{ about.history.title }}</h2>
        </header>
        <ol class="about-timeline" :aria-label="about.labels.timeline">
          <li v-for="(item, index) in about.history.items" :key="`${item.year}-${index}`">
            <time class="about-timeline-year" :datetime="item.year">{{ item.year }}</time>
            <div class="about-timeline-copy">
              <h3 v-if="item.title && about.history.items.length > 1">{{ item.title }}</h3>
              <p v-if="item.description">{{ item.description }}</p>
            </div>
          </li>
        </ol>
        <figure v-if="about.history.image" class="about-history-image">
          <img :src="about.history.image" :alt="about.history.title" width="960" height="600" loading="lazy">
        </figure>
      </div>
    </section>

    <section id="con-nguoi" class="about-chapter about-people" aria-labelledby="about-people-title">
      <div class="pts-container">
        <div class="about-people-layout">
          <figure v-if="about.people.image" class="about-people-image">
            <img :src="about.people.image" :alt="about.people.title" width="960" height="600" loading="lazy">
          </figure>
          <div class="about-people-copy">
            <h2 id="about-people-title">{{ about.people.title }}</h2>
            <p v-if="about.people.intro">{{ about.people.intro }}</p>
            <p v-if="about.people.body" class="about-people-body">{{ about.people.body }}</p>
          </div>
        </div>
        <div v-if="about.people.principles.length" :aria-label="about.people.eyebrow" class="about-principles">
          <article v-for="(item, index) in about.people.principles" :key="`${item.title}-${index}`">
            <img v-if="item.image" :src="item.image" alt="" width="56" height="56" loading="lazy">
            <span v-else class="about-index" aria-hidden="true">{{ String(index + 1).padStart(2, '0') }}</span>
            <div>
              <h3>{{ item.title }}</h3>
              <p v-if="item.description">{{ item.description }}</p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section v-if="about.trust.items.length" class="about-chapter about-trust" aria-labelledby="about-trust-title">
      <div class="pts-container about-trust-layout">
        <header class="about-chapter-heading"><h2 id="about-trust-title">{{ about.trust.title }}</h2></header>
        <div class="about-trust-list">
          <article v-for="(item, index) in about.trust.items" :key="`${item.title}-${index}`">
            <img v-if="item.image" :src="item.image" alt="" width="64" height="64" loading="lazy">
            <span v-else class="about-index" aria-hidden="true">{{ String(index + 1).padStart(2, '0') }}</span>
            <div><h3>{{ item.title }}</h3><p>{{ item.description }}</p></div>
          </article>
        </div>
      </div>
    </section>

    <section v-if="about.compass.items.length" id="tam-nhin-su-menh" class="about-chapter about-compass" aria-labelledby="about-compass-title">
      <div class="pts-container">
        <header class="about-compass-heading"><h2 id="about-compass-title">{{ about.compass.title }}</h2></header>
        <div class="about-compass-list">
          <article v-for="(item, index) in about.compass.items" :key="`${item.title}-${index}`">
            <img v-if="item.image" :src="item.image" alt="" width="56" height="56" loading="lazy">
            <span v-else class="about-index" aria-hidden="true">{{ String(index + 1).padStart(2, '0') }}</span>
            <div><h3>{{ item.title }}</h3><p v-if="item.description">{{ item.description }}</p></div>
          </article>
        </div>
      </div>
    </section>

    <section v-if="about.certificates.items.length" id="about-certificates" class="about-chapter about-certificates" aria-labelledby="about-certificates-title">
      <div class="pts-container">
        <header class="about-certificates-heading">
          <div>
            <h2 id="about-certificates-title">{{ about.certificates.title }}</h2>
            <p>{{ about.certificates.intro }}</p>
          </div>
          <button v-if="galleryCertificates.length" type="button" class="about-all-certificates" @click="openAllCertificates">
            {{ about.labels.certAll }} <span aria-hidden="true">→</span>
          </button>
        </header>

        <div class="about-certificate-list">
          <article v-for="item in visibleCertificates" :key="item.id" :class="{ 'has-image': item.image }">
            <button v-if="item.image" type="button" class="about-certificate-image" :aria-label="`Mở chứng nhận ${item.name}`" @click="openCertificate(item, $event)">
              <img :src="item.image" :alt="item.name" width="520" height="360" loading="lazy">
            </button>
            <div class="about-certificate-copy">
              <span class="about-certificate-label">{{ about.labels.certLabel }}</span>
              <h3>{{ item.name }}</h3>
              <p v-if="item.description">{{ item.description }}</p>
              <button v-if="item.image" type="button" class="about-certificate-detail" @click="openCertificate(item, $event)">
                {{ about.labels.certDetail }} <span aria-hidden="true">→</span>
              </button>
            </div>
          </article>
        </div>
      </div>
    </section>

    <dialog
      v-if="certificateGalleryOpen && activeCertificate"
      ref="certificateDialog"
      class="about-certificate-dialog"
      role="dialog"
      aria-modal="true"
      aria-labelledby="about-certificate-dialog-title"
      @close="onCertificateDialogClose"
      @cancel.prevent="closeCertificateGallery"
      @keydown.left.prevent="previousCertificate"
      @keydown.right.prevent="nextCertificate"
      @click="onCertificateDialogBackdrop"
    >
      <div class="about-certificate-modal">
        <header class="about-certificate-modal-header">
          <div class="about-certificate-modal-title">
            <span class="about-modal-mark" aria-hidden="true">PTS</span>
            <div>
              <h2 id="about-certificate-dialog-title">{{ activeCertificate.name || about.labels.certLabel }}</h2>
              <span aria-live="polite">{{ activeCertificateIndex + 1 }} / {{ galleryCertificates.length }}</span>
            </div>
          </div>
          <button ref="certificateCloseButton" type="button" class="about-certificate-close" aria-label="Đóng xem chứng nhận" @click="closeCertificateGallery">Đóng <span aria-hidden="true">×</span></button>
        </header>
        <div class="about-certificate-modal-image">
          <button type="button" aria-label="Chứng nhận trước" :disabled="activeCertificateIndex === 0" @click="previousCertificate">←</button>
          <img v-if="activeCertificate.image" :key="activeCertificate.id" :src="activeCertificate.image" :alt="activeCertificate.name">
          <div v-else role="status">Ảnh chứng nhận này chưa được cập nhật.</div>
          <button type="button" aria-label="Chứng nhận tiếp theo" :disabled="activeCertificateIndex === galleryCertificates.length - 1" @click="nextCertificate">→</button>
        </div>
        <footer class="about-certificate-modal-footer">
          <p v-if="activeCertificate.description">{{ activeCertificate.description }}</p>
          <span>Hồ sơ {{ activeCertificateIndex + 1 }} / {{ galleryCertificates.length }}</span>
        </footer>
      </div>
    </dialog>

    <section class="about-contact">
      <div class="pts-container">
        <PublicCtaBanner variant="corporate" :image="about.cta.image" labelled-by="about-cta-title">
          <div class="about-contact-copy">
            <span class="about-contact-mark" aria-hidden="true">PTS</span>
            <div>
              <h2 id="about-cta-title">{{ about.cta.title }}</h2>
              <p>{{ about.cta.description }}</p>
            </div>
          </div>
          <template #actions>
            <div class="about-contact-actions">
              <a v-if="about.cta.hotline" :href="`tel:${about.cta.hotline.tel}`">{{ about.cta.hotline.text }}</a>
              <a v-if="about.cta.zaloUrl" :href="about.cta.zaloUrl" target="_blank" rel="noopener noreferrer">Zalo</a>
              <a :href="links.generic(about.cta.href || '/lien-he/#yeu-cau-tu-van')" @click.prevent="openConsultation('Giới thiệu: ' + about.cta.title)">{{ about.cta.buttonLabel }} <span aria-hidden="true">→</span></a>
            </div>
          </template>
        </PublicCtaBanner>
      </div>
    </section>
  </div>
</template>

<style scoped>
.about-editorial { overflow-x: clip; }
.about-story { background: var(--color-slate-50); }
.about-story-layout { display: grid; grid-template-columns: minmax(0, .84fr) minmax(0, 1.16fr); align-items: stretch; min-height: clamp(470px, 49vw, 680px); }
.about-story-copy { align-self: center; max-width: 660px; padding: clamp(34px, 5.4vw, 78px) clamp(28px, 4vw, 60px) clamp(40px, 6vw, 90px) 0; }
.about-story-copy h1 { max-width: 12ch; margin: 0; color: var(--color-navy-800); font-size: clamp(42px, 5.3vw, 76px); font-weight: 760; line-height: 1.02; letter-spacing: -.045em; text-wrap: balance; }
.about-story-intro { max-width: 50ch; margin: 24px 0 0; color: var(--color-slate-700); font-size: clamp(17px, 1.45vw, 20px); line-height: 1.65; }
.about-story-body { max-width: 54ch; margin: 16px 0 0; color: var(--color-slate-500); font-size: 15px; line-height: 1.75; }
.about-story-image, .about-story-illustration { position: relative; align-self: stretch; min-height: 420px; margin: 0; overflow: hidden; background: var(--color-navy-950); }
.about-story-image::after { position: absolute; inset: 0; background: linear-gradient(90deg, rgba(6,21,55,.12), transparent 32%), linear-gradient(0deg, rgba(6,21,55,.16), transparent 38%); content: ''; pointer-events: none; }
.about-story-image img { display: block; width: 100%; height: 100%; object-fit: cover; object-position: center; }
.about-story-illustration { display: grid; place-items: center; background: linear-gradient(150deg, var(--color-navy-700), var(--color-navy-950) 76%); }
.about-story-illustration::before { position: absolute; inset: 0; background-image: linear-gradient(90deg, rgba(226,236,247,.09) 1px, transparent 1px), linear-gradient(rgba(226,236,247,.07) 1px, transparent 1px); background-size: 36px 36px; mask-image: linear-gradient(100deg, transparent, #000 28%, #000 90%, transparent); content: ''; }
.about-story-illustration svg { position: relative; display: block; width: 100%; height: auto; max-height: 100%; }
.about-chapter-nav { position: sticky; z-index: 30; top: 65px; border-bottom: 1px solid var(--color-slate-200); background: rgba(255,255,255,.96); backdrop-filter: blur(12px); }
.about-chapter-nav.is-header-hidden { top: 0; }
@media (min-width: 821px) { .about-chapter-nav { top: 88px; } }
@media (min-width: 1321px) { .about-chapter-nav { top: 104px; } }
@media (min-width: 1501px) { .about-chapter-nav { top: 120px; } }
@media (min-width: 1800px) { .about-chapter-nav { top: 136px; } }
.about-chapter-nav-track { display: flex; gap: clamp(14px, 2.4vw, 34px); overflow-x: auto; scrollbar-width: none; }
.about-chapter-nav-track::-webkit-scrollbar { display: none; }
.about-chapter-nav-track a { display: inline-flex; min-height: 52px; flex: 0 0 auto; align-items: center; border-bottom: 2px solid transparent; padding: 0 2px; color: var(--color-navy-800); font-size: 14px; font-weight: 700; text-decoration: none; white-space: nowrap; }
.about-chapter-nav-track a:hover, .about-chapter-nav-track a:focus-visible, .about-chapter-nav-track a[aria-current='location'] { border-bottom-color: var(--color-gold-500); color: var(--color-gold-700); }
.about-chapter-nav-track a:focus-visible { outline: 2px solid var(--color-navy-800); outline-offset: 2px; }
.about-chapter { scroll-margin-top: 136px; padding: clamp(58px, 8vw, 112px) 0; }
.about-chapter-heading h2, .about-people-copy h2, .about-certificates-heading h2, .about-compass-heading h2 { max-width: 18ch; margin: 0; color: var(--color-navy-800); font-size: clamp(32px, 3.3vw, 48px); font-weight: 720; line-height: 1.08; letter-spacing: -.035em; text-wrap: balance; }
.about-history { background: var(--color-slate-50); }
.about-history-layout { display: grid; grid-template-columns: minmax(210px, .68fr) minmax(0, 1fr); align-items: start; column-gap: clamp(32px, 7vw, 106px); row-gap: 34px; }
.about-history-layout .about-chapter-heading { grid-column: 1; }
.about-timeline { grid-column: 1; display: grid; gap: 0; margin: 0; padding: 0; list-style: none; }
.about-timeline li { position: relative; display: grid; grid-template-columns: 4.5rem minmax(0, 1fr); gap: 8px 18px; padding: 20px 0 26px; }
.about-timeline li + li { border-top: 1px solid var(--color-slate-200); }
.about-timeline-year { grid-row: span 2; color: var(--color-gold-700); font-size: clamp(22px, 2vw, 28px); font-weight: 750; line-height: 1.1; }
.about-timeline-copy h3 { margin: 0; color: var(--color-navy-800); font-size: 18px; font-weight: 700; line-height: 1.3; }
.about-timeline-copy p { margin: 0; color: var(--color-slate-600); font-size: 15px; line-height: 1.72; }
.about-history-image { grid-column: 2; grid-row: 1 / span 2; position: sticky; top: 160px; aspect-ratio: 8 / 5; margin: 0; overflow: hidden; background: var(--color-slate-100); }
.about-history-image img, .about-people-image img { display: block; width: 100%; height: 100%; object-fit: cover; object-position: center; }
.about-people { background: #fff; }
.about-people-layout { display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(280px, .95fr); align-items: center; gap: clamp(32px, 7vw, 104px); }
.about-people-image { aspect-ratio: 8 / 5; margin: 0; overflow: hidden; background: var(--color-slate-100); }
.about-people-copy p { max-width: 58ch; margin: 18px 0 0; color: var(--color-slate-600); font-size: 17px; line-height: 1.72; }
.about-people-copy .about-people-body { margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--color-slate-200); font-size: 15px; }
.about-principles { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: clamp(44px, 6vw, 76px); border-top: 1px solid var(--color-slate-200); }
.about-principles article { display: grid; grid-template-columns: 44px minmax(0, 1fr); align-content: start; gap: 18px; padding: 24px 24px 0; }
.about-principles article:first-child { padding-left: 0; }
.about-principles img { width: 44px; height: 44px; object-fit: contain; }
.about-index { display: grid; width: 42px; height: 42px; place-items: center; color: var(--color-gold-700); font: 700 13px/1 ui-monospace, monospace; }
.about-principles h3, .about-trust-list h3, .about-compass-list h3 { margin: 0; color: var(--color-navy-800); font-size: 18px; font-weight: 700; line-height: 1.3; }
.about-principles p, .about-trust-list p, .about-compass-list p { margin: 8px 0 0; color: var(--color-slate-600); font-size: 14px; line-height: 1.7; }
.about-trust { background: var(--color-slate-50); }
.about-trust-layout { display: grid; grid-template-columns: minmax(220px, .65fr) minmax(0, 1.35fr); gap: clamp(32px, 7vw, 104px); }
.about-trust-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); column-gap: clamp(20px, 4vw, 56px); }
.about-trust-list article { display: grid; grid-template-columns: 48px minmax(0, 1fr); gap: 16px; padding: 18px 0; border-top: 1px solid var(--color-slate-200); }
.about-trust-list img, .about-compass-list img { width: 44px; height: 44px; object-fit: contain; }
.about-compass { background: var(--color-navy-950); }
.about-compass-heading h2 { color: #fff; }
.about-compass-list { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: 42px; border-top: 1px solid rgba(226,236,247,.26); }
.about-compass-list article { display: grid; grid-template-columns: 48px minmax(0, 1fr); gap: 18px; padding: 24px 24px 0; }
.about-compass-list article:first-child { padding-left: 0; }
.about-compass-list article + article { border-left: 1px solid rgba(226,236,247,.2); }
.about-compass-list h3 { color: #fff; }
.about-compass-list p { color: rgba(255,255,255,.82); }
.about-compass-list .about-index { color: var(--color-gold-400); }
.about-certificates { background: #fff; }
.about-certificates-heading { display: flex; align-items: end; justify-content: space-between; gap: 30px; }
.about-certificates-heading p { max-width: 58ch; margin: 16px 0 0; color: var(--color-slate-600); line-height: 1.7; }
.about-all-certificates { display: inline-flex; min-height: 44px; flex: none; align-items: center; gap: 10px; border: 0; padding: 4px 0; background: transparent; color: var(--color-navy-800); font: inherit; font-weight: 700; cursor: pointer; }
.about-all-certificates:hover { color: var(--color-gold-700); }
.about-all-certificates:focus-visible, .about-certificate-detail:focus-visible, .about-certificate-image:focus-visible, .about-certificate-close:focus-visible { outline: 2px solid var(--color-gold-700); outline-offset: 3px; }
.about-certificate-list { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: clamp(18px, 3vw, 40px); margin-top: 42px; }
.about-certificate-list article { min-width: 0; padding-top: 15px; border-top: 1px solid var(--color-slate-200); }
.about-certificate-list article.has-image { display: grid; grid-template-columns: minmax(0, 1fr); align-content: start; }
.about-certificate-image { display: grid; width: 100%; height: 220px; place-items: center; border: 0; padding: 12px; background: var(--color-slate-50); cursor: pointer; }
.about-certificate-image img { display: block; width: 100%; height: 100%; object-fit: contain; }
.about-certificate-copy { padding-top: 16px; }
.about-certificate-label { color: var(--color-gold-700); font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.about-certificate-copy h3 { margin: 8px 0 0; color: var(--color-navy-800); font-size: 19px; font-weight: 700; line-height: 1.3; overflow-wrap: anywhere; }
.about-certificate-copy p { margin: 8px 0 0; color: var(--color-slate-600); font-size: 14px; line-height: 1.7; }
.about-certificate-detail { display: inline-flex; min-height: 44px; align-items: center; gap: 9px; margin-top: 4px; border: 0; padding: 0; background: transparent; color: var(--color-navy-800); font: inherit; font-weight: 700; cursor: pointer; }
.about-certificate-dialog { position: fixed; inset: 0; width: min(calc(100% - 24px), 1040px); max-width: none; max-height: calc(100dvh - 24px); margin: auto; overflow: visible; border: 0; padding: 0; background: transparent; color: var(--color-navy-800); }
.about-certificate-dialog::backdrop { background: rgba(6,21,55,.86); backdrop-filter: blur(8px); }
.about-certificate-modal { display: flex; max-height: calc(100dvh - 24px); flex-direction: column; overflow: hidden; border: 1px solid rgba(255,255,255,.25); border-radius: 4px; background: #fff; }
.about-certificate-modal-header, .about-certificate-modal-footer { display: flex; flex: none; align-items: center; justify-content: space-between; gap: 20px; padding: 16px 22px; }
.about-certificate-modal-header { border-bottom: 1px solid var(--color-slate-200); background: var(--color-slate-50); }
.about-certificate-modal-title { display: flex; min-width: 0; align-items: center; gap: 14px; }
.about-certificate-modal-title h2 { overflow: hidden; margin: 0; color: var(--color-navy-950); font-size: 18px; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
.about-certificate-modal-title span:not(.about-modal-mark) { display: inline-block; margin-top: 5px; color: var(--color-slate-500); font-size: 12px; }
.about-modal-mark { display: grid; width: 40px; height: 40px; flex: none; place-items: center; border: 1px solid var(--color-gold-400); color: var(--color-gold-700); font-size: 11px; font-weight: 800; }
.about-certificate-close { display: inline-flex; min-height: 44px; flex: none; align-items: center; gap: 10px; border: 0; padding: 0 8px; background: transparent; color: var(--color-navy-800); font: inherit; font-weight: 700; cursor: pointer; }
.about-certificate-close span { color: var(--color-gold-700); font-size: 23px; }
.about-certificate-modal-image { position: relative; display: flex; min-height: 260px; flex: 1; align-items: center; justify-content: center; gap: 12px; overflow-y: auto; padding: clamp(16px, 4vw, 36px); background: var(--color-slate-100); }
.about-certificate-modal-image img { display: block; max-width: min(100%, 760px); max-height: min(66dvh, 680px); object-fit: contain; }
.about-certificate-modal-image > div { display: grid; min-height: 180px; place-items: center; padding: 24px; color: var(--color-slate-500); text-align: center; }
.about-certificate-modal-image button { display: grid; width: 44px; height: 44px; flex: none; place-items: center; border: 1px solid var(--color-slate-200); border-radius: 50%; background: #fff; color: var(--color-navy-800); font-size: 20px; cursor: pointer; }
.about-certificate-modal-image button:disabled { cursor: not-allowed; opacity: .35; }
.about-certificate-modal-image button:focus-visible { outline: 2px solid var(--color-gold-700); outline-offset: 2px; }
.about-certificate-modal-footer { justify-content: center; border-top: 1px solid var(--color-slate-200); }
.about-certificate-modal-footer p { margin: 0; color: var(--color-slate-600); font-size: 14px; line-height: 1.6; text-align: center; }
.about-certificate-modal-footer > span { flex: none; color: var(--color-slate-500); font-size: 12px; }
.about-contact { padding: 0 0 clamp(48px, 7vw, 88px); }
.about-contact-copy { display: flex; align-items: center; gap: 16px; }
.about-contact-mark { display: grid; width: 48px; height: 48px; flex: none; place-items: center; border: 1px solid var(--color-gold-400); border-radius: 50%; color: var(--color-gold-400); font-size: 11px; font-weight: 800; }
.about-contact-copy h2 { margin: 0 0 5px; color: #fff; font-size: clamp(21px, 2vw, 26px); font-weight: 800; line-height: 1.25; }
.about-contact-copy p { margin: 0; color: rgba(255,255,255,.82); line-height: 1.65; }
.about-contact-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
.about-contact-actions a { display: inline-flex; min-height: 44px; align-items: center; justify-content: center; gap: 8px; border-radius: 4px; padding: 10px 16px; background: var(--color-gold-400); color: var(--color-navy-950); font-weight: 800; text-decoration: none; }
.about-contact-actions a:hover { background: var(--color-gold-300); }
.about-contact-actions a:nth-child(2):not(:last-child) { background: #fff; color: var(--color-navy-800); }
.about-contact-actions a:focus-visible { outline: 2px solid #fff; outline-offset: 3px; }
@media (max-width: 1100px) {
  .about-story-layout { grid-template-columns: minmax(0, .95fr) minmax(0, 1.05fr); }
  .about-story-copy h1 { font-size: clamp(40px, 5vw, 60px); }
  .about-history-layout { grid-template-columns: minmax(180px, .65fr) minmax(0, 1.35fr); column-gap: 32px; }
  .about-certificate-list { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 800px) {
  .about-chapter-nav { top: 65px; }
  .about-story-layout { grid-template-columns: 1fr; }
  .about-story-copy { max-width: none; padding: 40px 0 34px; }
  .about-story-copy h1 { max-width: 13ch; font-size: clamp(38px, 8vw, 58px); }
  .about-story-image, .about-story-illustration { min-height: 0; aspect-ratio: 8 / 5; }
  .about-history-layout { grid-template-columns: 1fr; gap: 22px; }
  .about-history-layout .about-chapter-heading, .about-timeline, .about-history-image { grid-column: 1; }
  .about-history-image { grid-row: auto; position: static; }
  .about-people-layout { grid-template-columns: 1fr; gap: 25px; }
  .about-people-image { order: 2; }
  .about-people-copy { order: 1; }
  .about-trust-layout { grid-template-columns: 1fr; gap: 22px; }
  .about-compass-list { grid-template-columns: 1fr; margin-top: 30px; }
  .about-compass-list article { padding: 20px 0; }
  .about-compass-list article:first-child { padding-top: 22px; }
  .about-compass-list article + article { border-top: 1px solid rgba(226,236,247,.2); border-left: 0; }
}
@media (max-width: 560px) {
  .about-chapter-nav-track { gap: 18px; }
  .about-chapter-nav-track a { min-height: 48px; font-size: 13px; }
  .about-chapter { padding: 48px 0; scroll-margin-top: 118px; }
  .about-story-copy { padding: 32px 0 28px; }
  .about-story-copy h1 { font-size: clamp(34px, 10vw, 44px); }
  .about-story-intro { margin-top: 17px; font-size: 16px; }
  .about-story-body { margin-top: 12px; font-size: 14px; }
  .about-timeline li { grid-template-columns: 3.6rem minmax(0,1fr); gap: 7px 12px; padding: 17px 0 21px; }
  .about-timeline-year { font-size: 22px; }
  .about-principles { grid-template-columns: 1fr; margin-top: 38px; }
  .about-principles article { padding: 18px 0; border-top: 1px solid var(--color-slate-200); }
  .about-principles article:first-child { border-top: 0; }
  .about-trust-list { grid-template-columns: 1fr; }
  .about-certificates-heading { align-items: start; flex-direction: column; gap: 14px; }
  .about-certificate-list { grid-template-columns: 1fr; gap: 22px; margin-top: 28px; }
  .about-certificate-image { height: 190px; }
  .about-certificate-modal-header, .about-certificate-modal-footer { padding: 13px 14px; }
  .about-certificate-modal-image { min-height: 200px; gap: 8px; padding: 12px; }
  .about-certificate-modal-image button { width: 40px; height: 40px; }
  .about-certificate-modal-footer { align-items: flex-start; flex-direction: column; gap: 8px; }
  .about-contact-copy { align-items: flex-start; }
  .about-contact-mark { display: none; }
  .about-contact-actions { display: grid; grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) {
  .about-chapter-nav { scroll-behavior: auto; transition-duration: .01ms; }
}
</style>
