<script setup lang="ts">
import type { PublicAboutPayload } from '#shared/contracts/public-about'

defineProps<{ about: PublicAboutPayload }>()
</script>

<template>
  <main class="about-preview__main pts-about-page">
    <section id="ve-phuc-thinh" class="about-story pts-container" aria-labelledby="about-story-title">
      <div class="about-story__grid">
        <div class="about-story__copy">
          <p class="about-eyebrow">{{ about.story.eyebrow }}</p>
          <h1 id="about-story-title">{{ about.story.title }}</h1>
          <p v-if="about.story.intro" class="about-lead">{{ about.story.intro }}</p>
          <p v-if="about.story.body" class="about-body">{{ about.story.body }}</p>
        </div>
        <figure v-if="about.story.image" class="about-media about-story__media"><img :src="about.story.image" :alt="about.story.title" width="1600" height="900" fetchpriority="high"></figure>
      </div>
    </section>

    <nav class="about-section-nav" aria-label="Đi nhanh trong trang Giới thiệu">
      <div class="pts-container about-section-nav__inner">
        <a href="#ve-phuc-thinh">{{ about.labels.story }}</a>
        <a v-if="about.history.items.length" href="#lich-su-phat-trien">{{ about.labels.history }}</a>
        <a href="#con-nguoi">{{ about.labels.people }}</a>
        <a v-if="about.compass.items.length" href="#tam-nhin-su-menh">{{ about.labels.compass }}</a>
        <a v-if="about.certificates.items.length" href="#about-certificates">{{ about.labels.certificates }}</a>
      </div>
    </nav>

    <section v-if="about.history.items.length" id="lich-su-phat-trien" class="about-section about-history" aria-labelledby="about-history-title">
      <div class="pts-container about-history__grid">
        <div>
          <h2 id="about-history-title">{{ about.history.title }}</h2>
          <ol class="about-timeline" :aria-label="about.labels.timeline">
            <li v-for="(item, index) in about.history.items" :key="`${item.year}-${index}`" class="about-timeline__item">
              <span class="about-index" aria-hidden="true">{{ String(index + 1).padStart(2, '0') }}</span>
              <strong>{{ item.year }}</strong>
              <h3 v-if="item.title && about.history.items.length > 1">{{ item.title }}</h3>
              <p v-if="item.description">{{ item.description }}</p>
            </li>
          </ol>
        </div>
        <figure v-if="about.history.image" class="about-media about-history__media"><img :src="about.history.image" :alt="about.history.title" width="780" height="600" loading="lazy"></figure>
      </div>
    </section>

    <section id="con-nguoi" class="about-section about-people" aria-labelledby="about-people-title">
      <div class="pts-container">
        <div class="about-people__grid">
          <figure v-if="about.people.image" class="about-media about-people__media"><img :src="about.people.image" :alt="about.people.title" width="780" height="620" loading="lazy"></figure>
          <div class="about-people__copy">
            <p class="about-eyebrow">{{ about.people.eyebrow }}</p>
            <h2 id="about-people-title">{{ about.people.title }}</h2>
            <p v-if="about.people.intro" class="about-lead">{{ about.people.intro }}</p>
            <p v-if="about.people.body" class="about-body">{{ about.people.body }}</p>
          </div>
        </div>
        <div v-if="about.people.principles.length" class="about-principles" :aria-label="about.people.eyebrow">
          <article v-for="(item, index) in about.people.principles" :key="`${item.title}-${index}`" class="about-principle">
            <img v-if="item.image" :src="item.image" alt="" width="56" height="56" loading="lazy">
            <span v-else class="about-index" aria-hidden="true">{{ String(index + 1).padStart(2, '0') }}</span>
            <div><h3>{{ item.title }}</h3><p v-if="item.description">{{ item.description }}</p></div>
          </article>
        </div>
      </div>
    </section>

    <section v-if="about.trust.items.length" class="about-section about-trust" aria-labelledby="about-trust-title">
      <div class="pts-container about-trust__layout"><h2 id="about-trust-title">{{ about.trust.title }}</h2><div class="about-list"> <article v-for="(item, index) in about.trust.items" :key="`${item.title}-${index}`"><img v-if="item.image" :src="item.image" alt="" width="64" height="64" loading="lazy"><span v-else class="about-index" aria-hidden="true">{{ String(index + 1).padStart(2, '0') }}</span><div><h3>{{ item.title }}</h3><p>{{ item.description }}</p></div></article></div></div>
    </section>

    <section v-if="about.compass.items.length" id="tam-nhin-su-menh" class="about-section about-compass" aria-labelledby="about-compass-title">
      <div class="pts-container"><h2 id="about-compass-title">{{ about.compass.title }}</h2><div class="about-compass__grid"><article v-for="(item, index) in about.compass.items" :key="`${item.title}-${index}`"><img v-if="item.image" :src="item.image" alt="" width="56" height="56" loading="lazy"><span v-else class="about-index" aria-hidden="true">{{ String(index + 1).padStart(2, '0') }}</span><div><h3>{{ item.title }}</h3><p v-if="item.description">{{ item.description }}</p></div></article></div></div>
    </section>

    <section v-if="about.certificates.items.length" id="about-certificates" class="about-section about-certificates" aria-labelledby="about-certificates-title">
      <div class="pts-container"><h2 id="about-certificates-title">{{ about.certificates.title }}</h2><p>{{ about.certificates.intro }}</p><div class="about-certificates__grid"><article v-for="item in about.certificates.items" :key="item.id"><a :href="item.href" :aria-label="`Mở ${item.name}`"><img :src="item.image" :alt="item.name" width="260" height="360" loading="lazy"></a><div><span>{{ about.labels.certLabel }}</span><h3>{{ item.name }}</h3><p v-if="item.description">{{ item.description }}</p><a :href="item.href">{{ about.labels.certDetail }} →</a></div></article></div></div>
    </section>

    <section class="about-cta" aria-labelledby="about-cta-title"><div class="pts-container"><div class="about-cta__band"><div><span class="about-cta__mark" aria-hidden="true">PTS</span><div><h2 id="about-cta-title">{{ about.cta.title }}</h2><p>{{ about.cta.description }}</p></div></div><div class="about-cta__actions"><a v-if="about.cta.hotline" :href="`tel:${about.cta.hotline.tel}`">{{ about.cta.hotline.text }}</a><a v-if="about.cta.href" :href="about.cta.href">{{ about.cta.buttonLabel }} →</a><span v-else class="about-cta__muted">{{ about.cta.buttonLabel }}</span></div></div></div></section>
  </main>
</template>
