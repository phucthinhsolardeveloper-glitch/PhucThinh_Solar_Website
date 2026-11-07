<script setup lang="ts">
import type { PublicSolutionCard } from '../../../shared/contracts/public-solutions'

const props = defineProps<{ cards: PublicSolutionCard[] }>()
const emit = defineEmits<{ recommend: [slugs: string[]] }>()
const links = usePublicLinks()
const { openConsultation } = useStorefrontModal()

type Place = 'home' | 'business' | 'public' | 'farm'
type Goal = 'saving' | 'backup'
const places: Array<{ id: Place; label: string; icon: string }> = [
  { id: 'home', label: 'Nhà ở', icon: 'i-lucide-house' },
  { id: 'business', label: 'Doanh nghiệp, nhà xưởng', icon: 'i-lucide-factory' },
  { id: 'public', label: 'Đường, sân vườn, khu công cộng', icon: 'i-lucide-lamp' },
  { id: 'farm', label: 'Nông trại, tưới tiêu', icon: 'i-lucide-sprout' },
]
const goals: Array<{ id: Goal; label: string; icon: string }> = [
  { id: 'saving', label: 'Giảm tiền điện ban ngày', icon: 'i-lucide-sun' },
  { id: 'backup', label: 'Có điện cả ban đêm và khi mất lưới', icon: 'i-lucide-battery-charging' },
]
const place = ref<Place | null>(null)
const goal = ref<Goal | null>(null)
const needsGoal = computed(() => place.value === 'home' || place.value === 'business')

// Keyword match on the CMS slug keeps this working if slugs are renamed slightly.
function find(...keys: string[]) {
  return props.cards.find((card) => keys.some((key) => card.slug.includes(key)))
}
const picks = computed<PublicSolutionCard[]>(() => {
  if (!place.value || (needsGoal.value && !goal.value)) return []
  const storage = goal.value === 'backup' ? find('luu-tru', 'bess') : undefined
  const main = place.value === 'home' ? find('gia-dinh', 'ho-gia')
    : place.value === 'business' ? find('doanh-nghiep')
      : place.value === 'public' ? find('chieu-sang')
        : find('bom-nuoc', 'bom')
  return [main, storage].filter((card): card is PublicSolutionCard => Boolean(card))
})
watch(picks, (value) => emit('recommend', value.map((card) => card.slug)))

function choosePlace(id: Place) {
  place.value = id
  if (!needsGoal.value) goal.value = null
}
function reset() { place.value = null; goal.value = null }
const summary = computed(() => [places.find((p) => p.id === place.value)?.label, goals.find((g) => g.id === goal.value)?.label].filter(Boolean).join(' · '))
</script>

<template>
  <section class="finder" aria-labelledby="solution-finder-title">
    <div class="finder-head">
      <h2 id="solution-finder-title">Tìm giải pháp trong 2 bước</h2>
      <button v-if="place" type="button" class="finder-reset" @click="reset">Chọn lại</button>
    </div>

    <fieldset class="finder-step">
      <legend><b>1</b> Bạn cần năng lượng cho đâu?</legend>
      <div class="finder-options">
        <button v-for="item in places" :key="item.id" type="button" class="finder-option" :aria-pressed="place === item.id" @click="choosePlace(item.id)">
          <UIcon :name="item.icon" class="size-5 flex-none" aria-hidden="true" />{{ item.label }}
        </button>
      </div>
    </fieldset>

    <Transition name="finder-reveal">
      <fieldset v-if="needsGoal" class="finder-step">
        <legend><b>2</b> Điều bạn quan tâm nhất?</legend>
        <div class="finder-options is-two">
          <button v-for="item in goals" :key="item.id" type="button" class="finder-option" :aria-pressed="goal === item.id" @click="goal = item.id">
            <UIcon :name="item.icon" class="size-5 flex-none" aria-hidden="true" />{{ item.label }}
          </button>
        </div>
      </fieldset>
    </Transition>

    <div class="finder-result" aria-live="polite">
      <Transition name="finder-reveal" mode="out-in">
        <div v-if="picks.length" :key="picks.map((card) => card.slug).join()" class="finder-picks">
          <p class="finder-summary">Phù hợp với: <strong>{{ summary }}</strong></p>
          <a v-for="card in picks" :key="card.slug" class="finder-pick" :href="links.solution(card.slug)">
            <img v-if="card.image" :src="optimizedImage(card.image, 240, 180)" alt="" width="120" height="90" loading="lazy" decoding="async">
            <span><strong>{{ card.title }}</strong><small>Xem giải pháp →</small></span>
          </a>
          <button type="button" class="finder-cta" @click="openConsultation(`Giải pháp: ${picks.map((card) => card.title).join(' + ')} (${summary})`)">
            Nhận tư vấn phương án này <span aria-hidden="true">→</span>
          </button>
        </div>
        <p v-else key="hint" class="finder-hint">Chọn {{ place ? 'mục tiêu' : 'nơi sử dụng' }} để xem giải pháp phù hợp và đánh dấu nó ở danh sách bên dưới.</p>
      </Transition>
    </div>
  </section>
</template>

<style scoped>
.finder {
  width: 100%;
  max-width: 560px;
  justify-self: end;
  padding: clamp(22px, 2.6vw, 36px);
  border-radius: var(--radius-sm);
  background: linear-gradient(160deg, #0f2f5e 0%, var(--color-navy-950) 70%);
  color: #fff;
  box-shadow: 0 30px 70px -32px rgb(6 21 55 / 60%);
}
.finder-head { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
.finder-head h2 { margin: 0; font-size: clamp(20px, 1.7vw, 26px); font-weight: 900; letter-spacing: -0.02em; }
.finder-reset { border: 0; background: none; color: var(--color-gold-300); font: inherit; font-size: 14px; font-weight: 700; cursor: pointer; }
.finder-reset:hover { color: #fff; }

.finder-step { margin: 0 0 18px; padding: 0; border: 0; }
.finder-step legend { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; font-size: 15px; font-weight: 700; color: #dbe4ef; }
.finder-step legend b { display: grid; width: 24px; height: 24px; place-items: center; border-radius: 50%; background: var(--pts-gold-gradient); color: var(--color-navy-950); font-size: 13px; }
.finder-options { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
.finder-option {
  display: flex;
  min-height: 56px;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border: 1px solid rgb(255 255 255 / 18%);
  border-radius: var(--radius-btn);
  background: rgb(255 255 255 / 4%);
  color: #e2e8f0;
  font: inherit;
  font-size: 14.5px;
  font-weight: 700;
  line-height: 1.3;
  text-align: left;
  cursor: pointer;
  transition: border-color 0.2s ease, background-color 0.2s ease, color 0.2s ease;
}
.finder-option :deep(svg) { color: var(--color-gold-400); }
.finder-option:hover { border-color: rgb(224 182 84 / 70%); }
.finder-option[aria-pressed="true"] { border-color: var(--color-gold-400); background: var(--pts-gold-gradient); color: var(--color-navy-950); }
.finder-option[aria-pressed="true"] :deep(svg) { color: var(--color-navy-950); }
.finder-option:focus-visible, .finder-cta:focus-visible, .finder-pick:focus-visible { outline: 2px solid var(--color-gold-300); outline-offset: 2px; }

.finder-result { min-height: 64px; padding-top: 18px; border-top: 1px solid rgb(255 255 255 / 14%); }
.finder-hint { margin: 0; color: #a9b8ca; font-size: 14.5px; line-height: 1.6; }
.finder-summary { margin: 0 0 10px; color: #a9b8ca; font-size: 14px; }
.finder-summary strong { color: #fff; }
.finder-picks { display: grid; gap: 10px; }
.finder-pick { display: flex; align-items: center; gap: 14px; padding: 8px; border-radius: var(--radius-btn); background: #fff; color: var(--color-navy-950); text-decoration: none; transition: transform 0.25s ease; }
.finder-pick:hover { transform: translateX(4px); }
.finder-pick img { width: 84px; height: 63px; flex: none; border-radius: var(--radius-chip); object-fit: cover; }
.finder-pick span { display: flex; flex-direction: column; gap: 2px; }
.finder-pick strong { font-size: 17px; font-weight: 900; }
.finder-pick small { color: var(--color-gold-700); font-size: 13.5px; font-weight: 800; }
.finder-cta { margin-top: 4px; min-height: 48px; border: 0; border-radius: var(--radius-btn); background: var(--pts-gold-gradient); color: var(--color-navy-950); font: inherit; font-size: 15px; font-weight: 800; cursor: pointer; }
.finder-cta:hover { box-shadow: inset 0 0 0 999px rgb(255 255 255 / 9%); }

.finder-reveal-enter-active, .finder-reveal-leave-active { transition: opacity 0.3s ease, transform 0.35s cubic-bezier(0.22, 1, 0.36, 1); }
.finder-reveal-enter-from, .finder-reveal-leave-to { opacity: 0; transform: translateY(8px); }

@media (max-width: 900px) { .finder { justify-self: stretch; max-width: none; } }
@media (max-width: 359px) { .finder-options { grid-template-columns: 1fr; } }
@media (prefers-reduced-motion: reduce) {
  .finder-reveal-enter-active, .finder-reveal-leave-active, .finder-option, .finder-pick { transition: none; }
}
@media (max-width: 820px) {
  .finder { padding: 16px; border-radius: var(--radius-md); }
  .finder-head { align-items: center; min-height: 44px; margin-bottom: 6px; }
  .finder-head h2 { font-size: 19px; font-weight: 800; }
  .finder-reset { min-height: 44px; padding-inline: 4px; }
  .finder-step { margin-bottom: 12px; }
  .finder-step legend { margin-bottom: 8px; font-size: 14.5px; }
  .finder-option { min-height: 52px; gap: 8px; padding: 8px 10px; font-size: 14px; }
  .finder-result { min-height: 0; padding-top: 12px; }
  .finder-hint { font-size: 14px; line-height: 1.5; }
}
</style>
