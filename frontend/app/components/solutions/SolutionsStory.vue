<script setup lang="ts">
import type { PublicSanitizedRichText } from '#shared/contracts/editorial-rich-text'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'
import { parseSolutionStory, storyIcon, stripStepNumber } from '~/utils/solutionStory'

const props = withDefaults(defineProps<{ contents: PublicSanitizedRichText[]; indexLabel?: string; showIndex?: boolean }>(), { indexLabel: 'Các phần trong trang', showIndex: true })
const chapters = computed(() => parseSolutionStory(props.contents.map((content) => content.html || '').join('')))
// Long chapter lists (detail pages) would overflow the index; it lists the first eight.
const indexChapters = computed(() => chapters.value.filter((chapter) => chapter.kind !== 'faq').slice(0, 8))
const faqChapter = computed(() => chapters.value.find((chapter) => chapter.kind === 'faq'))

function splitFit(text: string) { return text.split(/,\s*/).map((part) => part.replace(/\.$/, '')).filter(Boolean) }

const activeId = ref('')
let observer: IntersectionObserver | undefined
onMounted(() => {
  if (!('IntersectionObserver' in window)) return
  observer = new IntersectionObserver((records) => {
    for (const record of records) if (record.isIntersecting) activeId.value = record.target.id
  }, { rootMargin: '-35% 0px -55% 0px' })
  for (const chapter of chapters.value) {
    const el = chapter.id ? document.getElementById(chapter.id) : null
    if (el) observer.observe(el)
  }
})
onBeforeUnmount(() => observer?.disconnect())
</script>

<template>
  <div v-if="!chapters.length" class="story-fallback">
    <EditorialRichText v-for="(content, index) in contents" :key="index" :content="content" variant="solution" />
  </div>

  <div v-else class="story">
    <nav v-if="showIndex && indexChapters.length > 1" class="story-index" :aria-label="indexLabel">
      <div class="pts-container">
        <ul>
          <li v-for="chapter in indexChapters" :key="chapter.id">
            <a :href="`#${chapter.id}`" :aria-current="activeId === chapter.id ? 'true' : undefined">
              <span>{{ chapter.title }}</span>
            </a>
          </li>
        </ul>
      </div>
    </nav>

    <template v-for="chapter in chapters" :key="chapter.id">
      <!-- Need -> recommended solution, read as a pairing table. -->
      <section v-if="chapter.kind === 'match'" class="chapter chapter-match" :aria-labelledby="chapter.id">
        <div class="pts-container match-grid">
          <header class="match-head">
            <h2 :id="chapter.id">{{ chapter.title }}</h2>
            <div v-if="chapter.lead" class="chapter-lead" v-html="chapter.lead" />
            <template v-for="item in chapter.items.filter((i) => !i.fit)" :key="item.id">
              <h3 :id="item.id" class="match-premise">{{ item.title }}</h3>
              <div class="chapter-lead" v-html="item.html" />
            </template>
          </header>
          <ol class="match-list">
            <li v-for="item in chapter.items.filter((i) => i.fit)" :key="item.id" class="match-row">
              <div class="match-need">
                <span class="match-icon" aria-hidden="true"><UIcon :name="storyIcon(item.title)" class="size-6" /></span>
                <div>
                  <h3 :id="item.id">{{ item.title }}</h3>
                  <div class="match-text" v-html="item.html" />
                </div>
              </div>
              <span class="match-arrow" aria-hidden="true"><UIcon name="i-lucide-arrow-right" class="size-5" /></span>
              <p class="match-answer"><span class="sr-only">{{ item.fit!.label }}: </span>{{ item.fit!.text }}</p>
            </li>
          </ol>
        </div>
      </section>

      <!-- Building blocks of one system, on the brand navy. -->
      <section v-else-if="chapter.kind === 'ecosystem'" class="chapter chapter-eco" :aria-labelledby="chapter.id">
        <div class="eco-glow" aria-hidden="true" />
        <div class="pts-container">
          <header class="eco-head">
            <h2 :id="chapter.id">{{ chapter.title }}</h2>
            <div v-if="chapter.lead" class="chapter-lead" v-html="chapter.lead" />
          </header>
          <ul class="eco-grid" :data-count="chapter.items.length">
            <li v-for="item in chapter.items" :key="item.id" class="eco-card">
              <span class="eco-icon" aria-hidden="true"><UIcon :name="storyIcon(item.title)" class="size-7" /></span>
              <h3 :id="item.id">{{ item.title }}</h3>
              <div class="eco-text" v-html="item.html" />
              <div v-if="item.fit" class="eco-fit">
                <span class="sr-only">{{ item.fit.label }}:</span>
                <ul :aria-label="item.fit.label">
                  <li v-for="tag in item.fit.list.length ? item.fit.list : splitFit(item.fit.text)" :key="tag">{{ tag }}</li>
                </ul>
              </div>
            </li>
          </ul>
        </div>
      </section>

      <!-- Survey factors: the checklist next to the one sentence that explains why. -->
      <section v-else-if="chapter.kind === 'checklist'" class="chapter chapter-check" :aria-labelledby="chapter.id">
        <div class="pts-container check-grid">
          <header class="check-head">
            <h2 :id="chapter.id">{{ chapter.title }}</h2>
            <div v-if="chapter.lead" class="chapter-lead" v-html="chapter.lead" />
            <blockquote v-if="chapter.quote" class="check-quote">{{ chapter.quote }}</blockquote>
          </header>
          <ul class="check-list">
            <li v-for="factor in chapter.list" :key="factor">
              <span class="check-mark" aria-hidden="true"><UIcon name="i-lucide-check" class="size-4" /></span>{{ factor }}
            </li>
          </ul>
        </div>
      </section>

      <!-- Working process as a connected route (from numbered h3s or an ordered list). -->
      <section v-else-if="chapter.kind === 'process'" class="chapter chapter-process" :aria-labelledby="chapter.id">
        <div class="pts-container">
          <header class="process-head">
            <h2 :id="chapter.id">{{ chapter.title }}</h2>
            <div v-if="chapter.lead" class="chapter-lead" v-html="chapter.lead" />
          </header>
          <ol class="process-route" :data-many="(chapter.items.length || chapter.list.length) > 6 ? '' : undefined" :style="{ '--steps': chapter.items.length || chapter.list.length }">
            <li v-for="item in chapter.items" :key="item.id" class="process-step">
              <span class="process-node" aria-hidden="true"><UIcon :name="storyIcon(item.title)" class="size-5" /></span>
              <h3 :id="item.id">{{ stripStepNumber(item.title) }}</h3>
              <div class="process-text" v-html="item.html" />
            </li>
            <li v-for="step in chapter.list" :key="step" class="process-step">
              <span class="process-node" aria-hidden="true"><UIcon :name="storyIcon(step)" class="size-5" /></span>
              <p class="process-label">{{ step }}</p>
            </li>
          </ol>
          <blockquote v-if="chapter.quote" class="story-quote">{{ chapter.quote }}</blockquote>
        </div>
      </section>

      <!-- A few alternatives side by side, each with its "suits when" list. -->
      <section v-else-if="chapter.kind === 'options'" class="chapter chapter-options" :aria-labelledby="chapter.id">
        <div class="pts-container">
          <header class="pillars-head">
            <h2 :id="chapter.id">{{ chapter.title }}</h2>
            <div v-if="chapter.lead" class="chapter-lead" v-html="chapter.lead" />
          </header>
          <ul class="options-grid" :data-count="chapter.items.length">
            <li v-for="item in chapter.items" :key="item.id" class="option-card">
              <span class="option-icon" aria-hidden="true"><UIcon :name="storyIcon(item.title)" class="size-7" /></span>
              <h3 :id="item.id">{{ stripStepNumber(item.title) }}</h3>
              <div class="option-text story-rich" v-html="item.html" />
              <div v-if="item.fit" class="option-fit">
                <strong>{{ item.fit.label }}</strong>
                <p v-if="item.fit.text">{{ item.fit.text }}</p>
                <ul v-if="item.fit.list.length">
                  <li v-for="entry in item.fit.list" :key="entry"><UIcon name="i-lucide-check" class="size-4 flex-none" aria-hidden="true" />{{ entry }}</li>
                </ul>
              </div>
            </li>
          </ul>
          <blockquote v-if="chapter.quote" class="story-quote">{{ chapter.quote }}</blockquote>
        </div>
      </section>

      <section v-else-if="chapter.kind === 'pillars'" class="chapter chapter-pillars" :aria-labelledby="chapter.id">
        <div class="pts-container">
          <header class="pillars-head">
            <h2 :id="chapter.id">{{ chapter.title }}</h2>
            <div v-if="chapter.lead" class="chapter-lead story-rich" v-html="chapter.lead" />
          </header>
          <ul class="pillars-grid" :data-count="chapter.items.length">
            <li v-for="item in chapter.items" :key="item.id">
              <UIcon :name="storyIcon(item.title)" class="pillar-icon size-8" aria-hidden="true" />
              <h3 :id="item.id">{{ item.title }}</h3>
              <div class="pillar-text story-rich" v-html="item.html" />
            </li>
          </ul>
          <blockquote v-if="chapter.quote" class="story-quote">{{ chapter.quote }}</blockquote>
        </div>
      </section>

      <!-- FAQ is rendered once, at the end, as an accordion. -->
      <template v-else-if="chapter.kind === 'faq'" />

      <section v-else class="chapter chapter-prose" :aria-labelledby="chapter.id">
        <div class="pts-container prose-wrap">
          <h2 :id="chapter.id">{{ chapter.title }}</h2>
          <div v-if="chapter.lead" class="chapter-lead story-rich" v-html="chapter.lead" />
          <ul v-if="chapter.list.length" class="check-list prose-list">
            <li v-for="entry in chapter.list" :key="entry"><span class="check-mark" aria-hidden="true"><UIcon name="i-lucide-check" class="size-4" /></span>{{ entry }}</li>
          </ul>
          <template v-for="item in chapter.items" :key="item.id">
            <h3 :id="item.id">{{ item.title }}</h3>
            <div class="chapter-lead story-rich" v-html="item.html" />
          </template>
          <blockquote v-if="chapter.quote" class="story-quote">{{ chapter.quote }}</blockquote>
        </div>
      </section>
    </template>

    <section v-if="faqChapter" class="chapter chapter-faq" :aria-labelledby="faqChapter.id">
      <div class="pts-container faq-grid">
        <header>
          <h2 :id="faqChapter.id">Câu hỏi thường gặp</h2>
          <div v-if="faqChapter.lead" class="chapter-lead" v-html="faqChapter.lead" />
        </header>
        <div class="faq-list">
          <details v-for="(item, index) in faqChapter.items" :key="item.id" class="faq-item" :open="index === 0">
            <summary><h3 :id="item.id">{{ item.title }}</h3><span class="faq-toggle" aria-hidden="true" /></summary>
            <div class="faq-answer story-rich" v-html="item.html" />
          </details>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.story { --ease: cubic-bezier(0.22, 1, 0.36, 1); color: var(--color-navy-950); }
.story h2 { margin: 0; font-size: clamp(30px, 3.4vw, 52px); font-weight: 850; line-height: 1.08; letter-spacing: -0.035em; text-wrap: balance; }
.story h3 { margin: 0; scroll-margin-top: 120px; }
.story h2 { scroll-margin-top: 110px; }
.chapter { position: relative; padding: clamp(64px, 8vw, 120px) 0; }
.chapter-lead :deep(p) { margin: 14px 0 0; max-width: 62ch; color: #4a5a72; font-size: clamp(17px, 1.25vw, 19px); line-height: 1.72; }

/* Chapter index */
.story-index { position: relative; z-index: 2; margin-top: clamp(8px, 2vw, 24px); border-block: 1px solid rgb(10 34 81 / 10%); background: #fff; }
.story-index ul { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin: 0; padding: 0; list-style: none; }
.story-index ul::-webkit-scrollbar { display: none; }
.story-index li { min-width: 0; }
.story-index a { position: relative; display: flex; min-height: 72px; align-items: center; gap: 12px; padding: 14px 22px; color: #4a5a72; font-size: 15.5px; font-weight: 700; line-height: 1.3; text-decoration: none; transition: color 0.3s ease, background-color 0.3s ease; }
.story-index li + li a { border-left: 1px solid rgb(10 34 81 / 8%); }
.story-index a::after { content: ''; position: absolute; inset: auto 0 -1px; height: 3px; background: var(--color-gold-500); transform: scaleX(0); transform-origin: left; transition: transform 0.45s var(--ease); }
.story-index a:hover, .story-index a[aria-current] { color: var(--color-navy-950); background: #f6f8fb; }
.story-index a:hover::after, .story-index a[aria-current]::after { transform: scaleX(1); }
.story-index a:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: -3px; }

/* Match */
.match-grid { display: grid; grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr); gap: clamp(32px, 6vw, 96px); align-items: start; }
.match-head { position: sticky; top: 120px; }
.match-premise { margin-top: 28px !important; font-size: 19px; font-weight: 800; color: var(--color-navy-900); }
.match-list { margin: 0; padding: 0; list-style: none; border-top: 1px solid rgb(10 34 81 / 12%); }
.match-row { display: grid; grid-template-columns: minmax(0, 1.25fr) 44px minmax(0, 1fr); align-items: center; gap: 18px; padding: 26px 0; border-bottom: 1px solid rgb(10 34 81 / 12%); transition: background-color 0.35s ease; }
.match-need { display: flex; gap: 16px; }
.match-icon { display: grid; width: 48px; height: 48px; flex: none; place-items: center; border-radius: 50%; background: #eef2f8; color: var(--color-navy-800); transition: background-color 0.35s ease, color 0.35s ease; }
.match-row h3 { font-size: clamp(19px, 1.45vw, 22px); font-weight: 800; line-height: 1.3; }
.match-text :deep(p) { margin: 6px 0 0; color: #52627a; font-size: 16px; line-height: 1.6; }
.match-arrow { display: grid; width: 44px; height: 44px; place-items: center; border-radius: 50%; color: var(--color-gold-600); border: 1px solid rgb(201 150 46 / 45%); transition: transform 0.4s var(--ease), background-color 0.35s ease, color 0.35s ease; }
.match-answer { margin: 0; padding: 16px 18px; border-left: 3px solid var(--color-gold-500); background: linear-gradient(90deg, rgb(201 150 46 / 12%), rgb(201 150 46 / 0%)); color: var(--color-navy-950); font-size: 16.5px; font-weight: 750; line-height: 1.5; }
.match-row:hover .match-icon { background: var(--color-navy-900); color: var(--color-gold-300); }
.match-row:hover .match-arrow { transform: translateX(6px); background: var(--color-gold-500); color: var(--color-navy-950); }

/* Ecosystem */
.chapter-eco { overflow: hidden; background: var(--color-navy-950); color: #fff; }
.eco-glow { position: absolute; inset: -30% -10% auto auto; width: 70vw; height: 70vw; border-radius: 50%; background: radial-gradient(closest-side, rgb(228 168 73 / 22%), transparent); pointer-events: none; }
.eco-head { position: relative; max-width: 760px; }
.chapter-eco .chapter-lead :deep(p) { color: #b9c6d6; }
.eco-grid { position: relative; display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 14px; margin: clamp(36px, 4vw, 56px) 0 0; padding: 0; list-style: none; }
.eco-card { grid-column: span 2; display: flex; flex-direction: column; gap: 12px; padding: clamp(22px, 2vw, 32px); border: 1px solid rgb(255 255 255 / 10%); border-radius: 6px; background: linear-gradient(160deg, rgb(255 255 255 / 7%), rgb(255 255 255 / 2%)); transition: transform 0.45s var(--ease), border-color 0.35s ease, background-color 0.35s ease; }
.eco-grid[data-count="5"] .eco-card:nth-child(-n + 2) { grid-column: span 3; }
.eco-grid[data-count="4"] .eco-card { grid-column: span 3; }
.eco-card:hover { transform: translateY(-6px); border-color: rgb(228 168 73 / 55%); }
.eco-icon { display: grid; width: 56px; height: 56px; place-items: center; border-radius: 14px; background: rgb(228 168 73 / 16%); color: var(--color-gold-300); }
.eco-card h3 { font-size: clamp(20px, 1.6vw, 25px); font-weight: 800; line-height: 1.25; }
.eco-text :deep(p) { margin: 0; color: #c3cfdf; font-size: 16px; line-height: 1.65; }
.eco-fit { margin-top: auto; padding-top: 8px; }
.eco-fit ul { display: flex; flex-wrap: wrap; gap: 6px; margin: 0; padding: 0; list-style: none; }
.eco-fit li { padding: 5px 11px; border-radius: 999px; background: rgb(255 255 255 / 8%); color: #e6edf6; font-size: 13.5px; font-weight: 600; }

/* Checklist */
.chapter-check { background: linear-gradient(180deg, #f5f7fb, #fff); }
.check-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: clamp(32px, 6vw, 96px); align-items: start; }
.check-quote { position: relative; margin: clamp(28px, 3vw, 44px) 0 0; padding: 0 0 0 28px; border-left: 4px solid var(--color-gold-500); color: var(--color-navy-950); font-size: clamp(22px, 2vw, 30px); font-weight: 800; line-height: 1.35; letter-spacing: -0.02em; }
.check-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin: 0; padding: 0; list-style: none; }
.check-list li { display: flex; align-items: flex-start; gap: 12px; padding: 16px 18px; border-radius: 6px; background: #fff; box-shadow: 0 1px 0 rgb(10 34 81 / 6%), 0 10px 24px -20px rgb(10 34 81 / 40%); color: var(--color-navy-900); font-size: 16px; font-weight: 650; line-height: 1.45; transition: transform 0.35s var(--ease), box-shadow 0.35s ease; }
.check-list li:hover { transform: translateY(-2px); box-shadow: 0 1px 0 rgb(10 34 81 / 6%), 0 18px 30px -20px rgb(10 34 81 / 45%); }
.check-mark { display: grid; width: 24px; height: 24px; flex: none; margin-top: -1px; place-items: center; border-radius: 50%; background: var(--color-navy-900); color: var(--color-gold-300); }

/* Process */
.chapter-process { background: #fff; }
.process-head { max-width: 760px; }
.process-route { position: relative; display: grid; grid-template-columns: repeat(var(--steps), minmax(0, 1fr)); gap: 22px; margin: clamp(40px, 5vw, 64px) 0 0; padding: 0; list-style: none; }
.process-route::before { content: ''; position: absolute; top: 27px; left: 28px; right: 28px; height: 2px; background: linear-gradient(90deg, var(--color-gold-500), var(--color-navy-800)); }
.process-step { position: relative; display: flex; flex-direction: column; gap: 10px; }
.process-node { position: relative; display: grid; width: 56px; height: 56px; place-items: center; border: 2px solid var(--color-gold-500); border-radius: 50%; background: #fff; color: var(--color-navy-900); transition: background-color 0.35s ease, color 0.35s ease, transform 0.45s var(--ease); }
.process-step:hover .process-node { background: var(--color-navy-900); color: var(--color-gold-300); transform: scale(1.08); }
.process-step h3 { margin-top: 8px; font-size: clamp(18px, 1.35vw, 21px); font-weight: 800; line-height: 1.3; }
.process-text :deep(p) { margin: 0; color: #52627a; font-size: 15.5px; line-height: 1.62; }

/* Pillars */
.chapter-pillars { background: #f5f7fb; }
.chapter-pillars + .chapter-pillars { background: #fff; }
.pillars-head { max-width: 760px; }
.pillars-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: clamp(20px, 2.4vw, 36px) clamp(20px, 3vw, 48px); margin: clamp(36px, 4vw, 56px) 0 0; padding: 0; list-style: none; }
.pillars-grid[data-count="2"] { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.pillars-grid[data-count="4"] { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.pillars-grid > li { display: flex; flex-direction: column; gap: 12px; padding-top: 24px; border-top: 2px solid var(--color-navy-950); transition: border-color 0.35s ease; }
.pillars-grid > li:hover { border-color: var(--color-gold-500); }
.pillar-icon { color: var(--color-gold-600); }
.pillars-grid h3 { font-size: clamp(19px, 1.45vw, 22px); font-weight: 800; line-height: 1.3; }
.pillar-text :deep(p) { margin: 0; color: #52627a; font-size: 16px; line-height: 1.65; }

/* Options */
.chapter-options { background: linear-gradient(180deg, #fff, #f5f7fb); }
.options-grid { display: grid; grid-template-columns: repeat(var(--cols, 3), minmax(0, 1fr)); gap: 16px; margin: clamp(36px, 4vw, 56px) 0 0; padding: 0; list-style: none; }
.options-grid[data-count="2"] { --cols: 2; }
.options-grid[data-count="1"] { --cols: 1; }
.option-card { display: flex; flex-direction: column; gap: 14px; padding: clamp(24px, 2.4vw, 36px); border: 1px solid rgb(10 34 81 / 10%); border-radius: 8px; background: #fff; box-shadow: 0 24px 48px -36px rgb(10 34 81 / 45%); transition: transform 0.45s var(--ease), border-color 0.35s ease; }
.option-card:hover { transform: translateY(-6px); border-color: rgb(201 150 46 / 60%); }
.option-icon { display: grid; width: 56px; height: 56px; place-items: center; border-radius: 14px; background: var(--color-navy-950); color: var(--color-gold-300); }
.option-card h3 { font-size: clamp(20px, 1.6vw, 24px); font-weight: 850; line-height: 1.25; }
.option-text :deep(p) { margin: 0; color: #52627a; font-size: 16px; line-height: 1.65; }
.option-fit { margin-top: auto; padding-top: 16px; border-top: 1px dashed rgb(10 34 81 / 18%); }
.option-fit strong { display: block; margin-bottom: 10px; color: var(--color-gold-700); font-size: 15px; font-weight: 800; }
.option-fit p { margin: 0; color: var(--color-navy-900); font-size: 15.5px; line-height: 1.55; }
.option-fit ul { display: grid; gap: 8px; margin: 0; padding: 0; list-style: none; }
.option-fit li { display: flex; gap: 10px; color: var(--color-navy-900); font-size: 15.5px; font-weight: 600; line-height: 1.5; }
.option-fit li :deep(.iconify), .option-fit li > :first-child { margin-top: 3px; color: var(--color-gold-600); }

/* Shared */
.story-quote { max-width: 900px; margin: clamp(36px, 4vw, 56px) 0 0; padding: 4px 0 4px 28px; border-left: 4px solid var(--color-gold-500); color: var(--color-navy-950); font-size: clamp(20px, 1.8vw, 27px); font-weight: 800; line-height: 1.4; letter-spacing: -0.015em; }
.story-rich :deep(ul), .story-rich :deep(ol) { display: grid; gap: 8px; margin: 12px 0 0; padding: 0 0 0 1.2em; color: #3d4d66; font-size: 16px; line-height: 1.6; }
.story-rich :deep(li::marker) { color: var(--color-gold-600); font-weight: 800; }
.story-rich :deep(p + p) { margin-top: 10px; }
.story-rich :deep(strong) { color: var(--color-navy-950); }
.story-rich :deep(table) { width: 100%; margin-top: 16px; border-collapse: collapse; font-size: 15px; }
.story-rich :deep(th), .story-rich :deep(td) { padding: 10px 12px; border: 1px solid rgb(10 34 81 / 12%); text-align: left; }
.story-rich :deep(img) { max-width: 100%; height: auto; border-radius: 6px; }
.process-label { margin: 8px 0 0; color: var(--color-navy-950); font-size: 16.5px; font-weight: 750; line-height: 1.45; }
.process-route[data-many] { grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); row-gap: 36px; }
.process-route[data-many]::before { display: none; }
.prose-list { margin-top: 24px; }

/* FAQ */
.chapter-faq { background: var(--color-navy-950); color: #fff; }
.faq-grid { display: grid; grid-template-columns: minmax(0, 0.7fr) minmax(0, 1.3fr); gap: clamp(32px, 6vw, 96px); align-items: start; }
.chapter-faq h2 { color: #fff; }
.faq-list { border-top: 1px solid rgb(255 255 255 / 14%); }
.faq-item { border-bottom: 1px solid rgb(255 255 255 / 14%); }
.faq-item summary { display: flex; min-height: 72px; align-items: center; justify-content: space-between; gap: 20px; padding: 18px 0; cursor: pointer; list-style: none; }
.faq-item summary::-webkit-details-marker { display: none; }
.faq-item summary h3 { color: #fff; font-size: clamp(17px, 1.35vw, 20px); font-weight: 750; line-height: 1.4; }
.faq-item summary:hover h3 { color: var(--color-gold-300); }
.faq-item summary:focus-visible { outline: 3px solid var(--color-gold-400); outline-offset: 2px; }
.faq-toggle { position: relative; width: 36px; height: 36px; flex: none; border: 1px solid rgb(228 168 73 / 55%); border-radius: 50%; }
.faq-toggle::before, .faq-toggle::after { content: ''; position: absolute; top: 50%; left: 50%; width: 14px; height: 2px; background: var(--color-gold-300); transform: translate(-50%, -50%); transition: transform 0.35s var(--ease); }
.faq-toggle::after { transform: translate(-50%, -50%) rotate(90deg); }
.faq-item[open] .faq-toggle::after { transform: translate(-50%, -50%) rotate(0deg); }
.faq-answer { padding: 0 56px 24px 0; }
.faq-answer :deep(p) { margin: 0; color: #c3cfdf; font-size: 16.5px; line-height: 1.7; }
.faq-answer :deep(p + p) { margin-top: 10px; }
.faq-answer :deep(strong) { color: #fff; }
.faq-answer :deep(ul), .faq-answer :deep(ol) { color: #c3cfdf; }

.prose-wrap { max-width: 860px; }
.prose-wrap h3 { margin-top: 28px; font-size: 21px; font-weight: 800; }

@media (max-width: 1100px) {
  .process-route { grid-template-columns: repeat(3, minmax(0, 1fr)); row-gap: 40px; }
  .process-route::before { display: none; }
  .pillars-grid, .pillars-grid[data-count="4"] { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .options-grid { --cols: 1; }
}
@media (max-width: 900px) {
  .match-grid, .check-grid, .faq-grid { grid-template-columns: 1fr; }
  .match-head { position: static; }
  .eco-grid { grid-template-columns: 1fr 1fr; }
  .eco-card, .eco-grid[data-count] .eco-card:nth-child(-n + 2), .eco-grid[data-count="4"] .eco-card { grid-column: auto; }
  .eco-grid[data-count="5"] .eco-card:first-child { grid-column: span 2; }
}
@media (max-width: 640px) {
  .story-index ul { display: flex; overflow-x: auto; scrollbar-width: none; }
  .story-index li { flex: none; }
  .story-index a { min-height: 56px; padding: 12px 16px; font-size: 14.5px; white-space: nowrap; }
  .match-row { grid-template-columns: 1fr; gap: 12px; padding: 22px 0; }
  .match-arrow { display: none; }
  .match-answer { margin-left: 64px; }
  .eco-grid { grid-template-columns: 1fr; }
  .eco-grid[data-count="5"] .eco-card:first-child { grid-column: auto; }
  .check-list { grid-template-columns: 1fr; }
  .process-route { grid-template-columns: 1fr; gap: 0; }
  .process-step:not(:last-child)::after { content: ''; position: absolute; top: 56px; bottom: 0; left: 27px; width: 2px; background: var(--color-gold-500); }
  .process-step { display: grid; grid-template-columns: 56px minmax(0, 1fr); column-gap: 18px; padding-bottom: 28px; }
  .process-node { grid-row: span 2; }
  .process-step h3 { margin-top: 14px; }
  .pillars-grid, .pillars-grid[data-count] { grid-template-columns: 1fr; }
  .faq-answer { padding-right: 0; }
}
@media (prefers-reduced-motion: reduce) {
  .story *, .story *::after { transition: none !important; }
  .eco-card:hover, .option-card:hover, .check-list li:hover, .process-step:hover .process-node, .match-row:hover .match-arrow { transform: none; }
}
</style>
