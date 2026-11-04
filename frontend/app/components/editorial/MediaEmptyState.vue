<script setup lang="ts">
defineProps<{ kind: 'video' | 'gallery'; title: string; description: string; consultSubject: string; consultLabel: string }>()
const links = useMigrationLinks()
const { openConsultation } = useStorefrontModal()
const paths = [
  { to: '/du-an-da-thuc-hien/', icon: 'i-lucide-building-2', title: 'Dự án đã thực hiện', text: 'Hồ sơ công trình điện mặt trời và chiếu sáng đã bàn giao.' },
  { to: '/giai-phap/', icon: 'i-lucide-sun', title: 'Giải pháp năng lượng', text: 'Cấu hình hòa lưới, lưu trữ và chiếu sáng công trình.' },
  { to: '/san-pham/', icon: 'i-lucide-package-search', title: 'Danh mục thiết bị', text: 'Tấm pin, biến tần, pin lưu trữ và đèn năng lượng mặt trời.' },
]
</script>

<template>
  <section class="media-empty" role="status">
    <div class="media-empty__stage">
      <div class="media-empty__art" :class="`is-${kind}`" aria-hidden="true">
        <template v-if="kind === 'video'">
          <span class="media-empty__strip" />
          <span class="media-empty__play"><UIcon name="i-lucide-play" class="size-8" /></span>
        </template>
        <template v-else>
          <span class="media-empty__frame is-back" />
          <span class="media-empty__frame is-mid" />
          <span class="media-empty__frame is-front"><UIcon name="i-lucide-image" class="size-8" /></span>
        </template>
      </div>
      <div class="media-empty__copy">
        <h2>{{ title }}</h2>
        <p>{{ description }}</p>
        <button type="button" class="media-empty__cta" @click="openConsultation(consultSubject)">
          {{ consultLabel }}<span aria-hidden="true"><UIcon name="i-lucide-arrow-right" class="size-5" /></span>
        </button>
      </div>
    </div>
    <ul class="media-empty__paths">
      <li v-for="item in paths" :key="item.to">
        <NuxtLink :to="links.generic(item.to)">
          <span class="media-empty__icon" aria-hidden="true"><UIcon :name="item.icon" class="size-6" /></span>
          <strong>{{ item.title }}</strong>
          <span>{{ item.text }}</span>
        </NuxtLink>
      </li>
    </ul>
  </section>
</template>

<style scoped>
.media-empty { overflow: hidden; border-radius: var(--radius-lg); background: #fff; box-shadow: 0 1px 0 rgb(10 34 81 / 4%); }
.media-empty__stage { display: grid; grid-template-columns: minmax(220px, 0.8fr) minmax(0, 1.2fr); align-items: center; gap: clamp(24px, 4vw, 64px); padding: clamp(28px, 4vw, 56px); background: radial-gradient(90% 120% at 0% 100%, rgb(228 168 73 / 18%), transparent 60%), var(--color-navy-950); color: #fff; }
.media-empty__art { position: relative; height: 200px; }
.media-empty__strip { position: absolute; inset: 30px 0; border-radius: var(--radius-md); background: repeating-linear-gradient(90deg, transparent 0 14px, rgb(255 255 255 / 10%) 14px 16px), linear-gradient(180deg, rgb(255 255 255 / 10%) 0 14px, rgb(255 255 255 / 4%) 14px calc(100% - 14px), rgb(255 255 255 / 10%) calc(100% - 14px)); animation: strip-run 8s linear infinite; }
.media-empty__play { position: absolute; top: 50%; left: 50%; display: grid; width: 84px; height: 84px; place-items: center; border-radius: 50%; background: var(--pts-gold-gradient); color: var(--color-navy-950); box-shadow: 0 0 0 12px rgb(228 168 73 / 16%); transform: translate(-50%, -50%); animation: play-pulse 2.4s ease-in-out infinite; }
.media-empty__frame { position: absolute; top: 50%; left: 50%; display: grid; width: 150px; height: 112px; place-items: center; border: 6px solid #fff; border-radius: var(--radius-md); background: linear-gradient(160deg, #1a3a6e, #0d2a5c); color: var(--color-gold-300); box-shadow: 0 20px 40px -20px rgb(0 0 0 / 60%); }
.media-empty__frame.is-back { transform: translate(-50%, -50%) rotate(-12deg) translateX(-34px); opacity: 0.55; }
.media-empty__frame.is-mid { transform: translate(-50%, -50%) rotate(7deg) translateX(30px); opacity: 0.75; }
.media-empty__frame.is-front { transform: translate(-50%, -50%) rotate(-2deg); }
.media-empty__copy h2 { margin: 0; color: #fff; font-size: clamp(24px, 2.4vw, 34px); font-weight: 850; line-height: 1.2; }
.media-empty__copy p { max-width: 56ch; margin: 12px 0 0; color: #c3cfdf; font-size: 16px; line-height: 1.7; }
.media-empty__cta { display: inline-flex; min-height: 54px; align-items: center; gap: 14px; margin-top: 22px; padding: 0 8px 0 20px; border: 0; border-radius: var(--radius-sm); background: var(--pts-gold-gradient); color: var(--color-navy-950); font: inherit; font-weight: 850; cursor: pointer; }
.media-empty__cta > span { display: grid; width: 38px; height: 38px; place-items: center; border-radius: 4px; background: var(--color-navy-950); color: var(--color-gold-300); }
.media-empty__cta:focus-visible { outline: 3px solid #fff; outline-offset: 3px; }
.media-empty__paths { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); margin: 0; padding: 0; list-style: none; }
.media-empty__paths li + li { border-left: 1px solid rgb(10 34 81 / 8%); }
.media-empty__paths a { display: flex; height: 100%; flex-direction: column; gap: 6px; padding: 24px clamp(18px, 2vw, 28px); color: var(--color-navy-900); text-decoration: none; transition: background-color 0.2s ease; }
.media-empty__paths a:hover { background: #f6f8fb; }
.media-empty__paths a:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: -3px; }
.media-empty__icon { display: grid; width: 46px; height: 46px; margin-bottom: 6px; place-items: center; border-radius: var(--radius-md); background: rgb(228 168 73 / 14%); color: var(--color-gold-700); }
.media-empty__paths strong { color: var(--color-navy-950); font-size: 17px; font-weight: 850; }
.media-empty__paths a > span:last-child { color: #52627a; font-size: 14.5px; line-height: 1.6; }
@keyframes strip-run { to { background-position: 160px 0, 0 0; } }
@keyframes play-pulse { 50% { box-shadow: 0 0 0 20px rgb(228 168 73 / 8%); } }
@media (max-width: 820px) {
  .media-empty__stage { grid-template-columns: 1fr; text-align: center; }
  .media-empty__copy p { margin-inline: auto; }
  .media-empty__art { height: 170px; }
  .media-empty__paths { grid-template-columns: 1fr; }
  .media-empty__paths li + li { border-left: 0; border-top: 1px solid rgb(10 34 81 / 8%); }
}
@media (prefers-reduced-motion: reduce) { .media-empty__strip, .media-empty__play { animation: none; } }
</style>
