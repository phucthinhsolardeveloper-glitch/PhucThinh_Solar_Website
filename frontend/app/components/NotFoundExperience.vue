<script setup lang="ts">
const props = withDefaults(defineProps<{ statusCode?: number; hotline?: string }>(), { statusCode: 404, hotline: '' })
const { openConsultation } = useStorefrontModal()
const isMissing = computed(() => props.statusCode === 404)
const searchDraft = ref('')
const popularKeywords = ['Đèn đường', 'Blue Carbon', 'LONGi', 'SolaX', 'Pin lưu trữ', 'Bơm nước']
const destinations = [
  { to: '/san-pham/', icon: 'i-lucide-package-search', title: 'Danh mục thiết bị', text: 'Đèn năng lượng mặt trời, tấm pin, biến tần, pin lưu trữ và bơm mặt trời.' },
  { to: '/giai-phap/', icon: 'i-lucide-sun', title: 'Giải pháp năng lượng', text: 'Điện mặt trời gia đình, doanh nghiệp, lưu trữ và chiếu sáng công trình.' },
  { to: '/du-an-da-thuc-hien/', icon: 'i-lucide-building-2', title: 'Dự án đã thực hiện', text: 'Các công trình thực tế Phúc Thịnh Solar đã bàn giao.' },
  { to: '/lien-he/', icon: 'i-lucide-headset', title: 'Liên hệ hỗ trợ', text: 'Đội ngũ kỹ thuật giúp bạn tìm đúng thiết bị và cấu hình.' },
]

function submitSearch(term?: string) {
  const q = (term ?? searchDraft.value).trim()
  if (term !== undefined) searchDraft.value = term
  return navigateTo({ path: '/tim-kiem/', query: q ? { keyword: q } : {} })
}
function reload() { if (import.meta.client) window.location.reload() }
</script>

<template>
  <section class="nf-sky" aria-labelledby="nf-title">
    <span class="nf-stars" aria-hidden="true" />
    <span class="nf-sun" aria-hidden="true"><span class="nf-sun__rays" /></span>
    <span class="nf-horizon" aria-hidden="true" />
    <div class="pts-container nf-copy">
      <p class="nf-code" aria-hidden="true">{{ statusCode }}</p>
      <h1 id="nf-title">{{ isMissing ? 'Trang này đã khuất nắng' : 'Trang chưa thể hiển thị' }}</h1>
      <p class="nf-lede">
        {{ isMissing
          ? 'Đường dẫn có thể đã thay đổi hoặc nội dung không còn được công bố. Thử tìm thiết bị bạn cần, hoặc đi tiếp từ một trong các lối bên dưới.'
          : 'Nguồn nội dung đang tạm gián đoạn. Bạn có thể tải lại trang sau ít giây hoặc liên hệ để được hỗ trợ ngay.' }}
      </p>

      <form v-if="isMissing" class="nf-search" role="search" @submit.prevent="submitSearch()">
        <label class="sr-only" for="nf-search">Tìm kiếm sản phẩm</label>
        <UIcon name="i-lucide-search" class="ml-5 size-5 flex-none text-gold-300" aria-hidden="true" />
        <input id="nf-search" v-model="searchDraft" type="search" placeholder="Bạn cần tìm gì?" autocomplete="off">
        <button type="submit">Tìm kiếm</button>
      </form>
      <div v-if="isMissing" class="nf-chips">
        <button v-for="kw in popularKeywords" :key="kw" type="button" @click="submitSearch(kw)">{{ kw }}</button>
      </div>
      <div v-else class="nf-actions">
        <button type="button" class="nf-primary" @click="reload">Tải lại trang</button>
        <a v-if="hotline" class="nf-call" :href="`tel:${hotline.replace(/\D/g, '')}`"><UIcon name="i-lucide-phone-call" class="size-5" aria-hidden="true" />{{ hotline }}</a>
      </div>
    </div>
  </section>

  <section class="pts-container nf-paths" aria-label="Đi tiếp">
    <NuxtLink v-for="item in destinations" :key="item.to" :to="item.to" class="nf-path">
      <span class="nf-path__icon" aria-hidden="true"><UIcon :name="item.icon" class="size-6" /></span>
      <strong>{{ item.title }}</strong>
      <span>{{ item.text }}</span>
      <UIcon name="i-lucide-arrow-right" class="nf-path__go size-5" aria-hidden="true" />
    </NuxtLink>
    <button type="button" class="nf-help" @click="openConsultation('Hỗ trợ tìm kiếm sản phẩm / nội dung')">
      <UIcon name="i-lucide-message-circle-question-mark" class="size-5 flex-none" aria-hidden="true" />
      Không thấy thứ bạn cần? <strong>Nhận tư vấn kỹ thuật</strong>
    </button>
  </section>
</template>

<style scoped>
.nf-sky { --ease: cubic-bezier(0.22, 1, 0.36, 1); position: relative; isolation: isolate; overflow: hidden; padding: clamp(72px, 10vw, 140px) 0 clamp(150px, 16vw, 220px); background: linear-gradient(180deg, #030b1f 0%, #061537 55%, #0d2a5c 100%); color: #fff; text-align: center; }
.nf-stars { position: absolute; inset: 0; z-index: -3; background-image: radial-gradient(1px 1px at 12% 20%, #fff8 50%, transparent), radial-gradient(1px 1px at 28% 62%, #fff6 50%, transparent), radial-gradient(1.5px 1.5px at 44% 14%, #fff9 50%, transparent), radial-gradient(1px 1px at 63% 38%, #fff5 50%, transparent), radial-gradient(1.5px 1.5px at 78% 18%, #fff8 50%, transparent), radial-gradient(1px 1px at 90% 54%, #fff6 50%, transparent), radial-gradient(1px 1px at 6% 72%, #fff5 50%, transparent), radial-gradient(1px 1px at 54% 80%, #fff4 50%, transparent); animation: nf-twinkle 6s ease-in-out infinite alternate; }
.nf-sun { position: absolute; bottom: calc(-1 * clamp(160px, 18vw, 260px)); left: 50%; z-index: -2; width: clamp(320px, 36vw, 520px); aspect-ratio: 1; border-radius: 50%; background: radial-gradient(circle, #ffd98a 0%, var(--color-gold-400) 45%, rgb(201 150 46 / 0%) 70%); transform: translateX(-50%); animation: nf-rise 2.4s var(--ease) both; }
.nf-sun__rays { position: absolute; inset: -40%; border-radius: 50%; background: repeating-conic-gradient(from 0deg, rgb(255 217 138 / 12%) 0deg 6deg, transparent 6deg 18deg); mask-image: radial-gradient(circle, #000 30%, transparent 70%); animation: nf-spin 60s linear infinite; }
.nf-horizon { position: absolute; right: 0; bottom: 0; left: 0; z-index: -1; height: clamp(70px, 8vw, 110px); background: linear-gradient(180deg, rgb(6 21 55 / 0%), #061537 70%); border-top: 1px solid rgb(228 168 73 / 25%); }
.nf-copy { position: relative; max-width: 860px; }
.nf-code { margin: 0; background: linear-gradient(180deg, rgb(255 255 255 / 30%), rgb(255 255 255 / 4%)); background-clip: text; color: transparent; -webkit-text-fill-color: transparent; font-size: clamp(96px, 16vw, 200px); font-weight: 900; line-height: 0.9; letter-spacing: -0.06em; font-variant-numeric: tabular-nums; }
.nf-copy h1 { margin: -0.3em 0 0; color: #fff; font-size: clamp(32px, 4.4vw, 60px); font-weight: 850; line-height: 1.1; letter-spacing: -0.03em; text-wrap: balance; }
.nf-lede { max-width: 60ch; margin: 18px auto 0; color: #c3cfdf; font-size: clamp(16px, 1.25vw, 18.5px); line-height: 1.7; }
.nf-search { display: flex; max-width: 620px; height: 58px; align-items: center; margin: 32px auto 0; border: 1px solid rgb(255 255 255 / 18%); border-radius: 999px; background: rgb(255 255 255 / 8%); backdrop-filter: blur(10px); transition: border-color 0.2s ease, box-shadow 0.2s ease; }
.nf-search:focus-within { border-color: var(--color-gold-400); box-shadow: 0 0 0 4px rgb(228 168 73 / 20%); }
.nf-search input { min-width: 0; flex: 1; height: 100%; padding: 0 12px; border: 0; background: transparent; color: #fff; font: inherit; font-size: 15.5px; outline: none; }
.nf-search input::placeholder { color: #9fb0c8; }
.nf-search input:focus-visible { outline: none; }
.nf-search button { height: 46px; margin-right: 6px; padding: 0 22px; border: 0; border-radius: 999px; background: var(--pts-gold-gradient); color: var(--color-navy-950); font: inherit; font-weight: 850; cursor: pointer; }
.nf-search button:hover { box-shadow: inset 0 0 0 999px rgb(255 255 255 / 9%); }
.nf-chips { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-top: 16px; }
.nf-chips button { min-height: 36px; padding: 0 14px; border: 1px solid rgb(255 255 255 / 16%); border-radius: 999px; background: transparent; color: #dbe4f0; font: inherit; font-size: 13.5px; font-weight: 600; cursor: pointer; transition: border-color 0.2s ease, color 0.2s ease; }
.nf-chips button:hover { border-color: var(--color-gold-400); color: #fff; }
.nf-actions { display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 16px 28px; margin-top: 28px; }
.nf-primary { min-height: 54px; padding: 0 28px; border: 0; border-radius: var(--radius-sm); background: var(--pts-gold-gradient); color: var(--color-navy-950); font: inherit; font-weight: 850; cursor: pointer; }
.nf-call { display: inline-flex; align-items: center; gap: 10px; color: #fff; font-size: 20px; font-weight: 850; text-decoration: none; }
.nf-sky :is(a, button):focus-visible { outline: 3px solid var(--color-gold-300); outline-offset: 3px; }

.nf-paths { position: relative; z-index: 1; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-top: calc(-1 * clamp(70px, 8vw, 110px)); padding-bottom: clamp(56px, 7vw, 96px); }
.nf-path { position: relative; display: flex; flex-direction: column; gap: 8px; padding: 24px 24px 56px; border: 1px solid rgb(10 34 81 / 8%); border-radius: var(--radius-lg); background: #fff; box-shadow: 0 24px 48px -32px rgb(6 21 55 / 55%); color: var(--color-navy-900); text-decoration: none; transition: transform 0.4s var(--ease, cubic-bezier(0.22, 1, 0.36, 1)), border-color 0.3s ease; }
.nf-path:hover { transform: translateY(-6px); border-color: rgb(201 150 46 / 60%); }
.nf-path:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 3px; }
.nf-path__icon { display: grid; width: 52px; height: 52px; margin-bottom: 6px; place-items: center; border-radius: var(--radius-md); background: var(--color-navy-950); color: var(--color-gold-300); }
.nf-path strong { color: var(--color-navy-950); font-size: 18px; font-weight: 850; }
.nf-path > span:not(.nf-path__icon) { color: #52627a; font-size: 14.5px; line-height: 1.6; }
.nf-path__go { position: absolute; bottom: 22px; left: 24px; color: var(--color-gold-600); transition: transform 0.35s ease; }
.nf-path:hover .nf-path__go { transform: translateX(6px); }
.nf-help { grid-column: 1 / -1; display: inline-flex; justify-self: center; align-items: center; gap: 10px; min-height: 48px; margin-top: 10px; padding: 0 20px; border: 0; border-radius: 999px; background: #f1f5f9; color: var(--color-navy-800); font: inherit; font-size: 15px; cursor: pointer; transition: background-color 0.2s ease; }
.nf-help strong { color: var(--color-navy-950); }
.nf-help:hover { background: #e7edf5; }
.nf-help :deep(.iconify), .nf-help > :first-child { color: var(--color-gold-600); }

@keyframes nf-rise { from { opacity: 0; transform: translate(-50%, 18%); } }
@keyframes nf-spin { to { transform: rotate(360deg); } }
@keyframes nf-twinkle { from { opacity: 0.6; } to { opacity: 1; } }
@media (max-width: 1100px) { .nf-paths { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 560px) {
  .nf-paths { grid-template-columns: 1fr; }
  .nf-search button { padding: 0 16px; }
  .nf-help { justify-self: stretch; justify-content: flex-start; padding: 12px 16px; border-radius: var(--radius-md); text-align: left; }
}
@media (prefers-reduced-motion: reduce) {
  .nf-sun, .nf-sun__rays, .nf-stars { animation: none; }
  .nf-path, .nf-path__go { transition: none; }
  .nf-path:hover { transform: none; }
}
</style>
