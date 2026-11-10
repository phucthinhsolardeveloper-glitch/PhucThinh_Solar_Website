<script setup lang="ts">
import type { PublicSolarEstimate, PublicSolarEstimatorForm } from '#shared/contracts/public-solar-estimator'
import { isPublicSolarEstimate, isPublicSolarEstimatorForm } from '#shared/contracts/public-solar-estimator'

// The figures are only computed by Laravel (POST /api/v1/solar-estimate); this page asks, shows and hands the
// result to staff with the visitor's phone number.
const { data, error } = await useFetch<unknown>('/_api/solar-estimator')
const config = computed<PublicSolarEstimatorForm | null>(() => isPublicSolarEstimatorForm(data.value) ? data.value : null)
if (!error.value && config.value && !config.value.enabled) throw createError({ statusCode: 404, statusMessage: 'Not Found', fatal: true })
if (import.meta.server && (error.value || !config.value)) setResponseStatus(502, 'Bad Gateway')

const title = 'Ước tính điện mặt trời cho mái nhà của bạn'
const description = 'Nhập tiền điện mỗi tháng để biết công suất, số tấm pin, diện tích mái, chi phí và thời gian hoàn vốn của hệ điện mặt trời.'
useSeoMeta({ title: `${title} | Phúc Thịnh Solar`, description, robots: () => config.value?.enabled ? 'index, follow' : 'noindex, nofollow' })
usePublicSocialMeta({ enabled: computed(() => !!config.value?.enabled), title, description, canonicalPath: '/uoc-tinh-dien-mat-troi/', image: '/assets/images/site-visuals/solution-home-solar-v2.jpg' })

const form = reactive({ type: 'household', bill: 2000000, region: 'tay-nguyen', habit: 'mixed', battery: false, roof: '', price: '' })
const moreOpen = ref(false)
const result = ref<PublicSolarEstimate | null>(null)
const problems = ref<string[]>([])
const busy = ref(false)
watch(config, (value) => {
  if (!value) return
  if (!value.regions.some((region) => region.key === form.region)) form.region = value.regions[0]!.key
}, { immediate: true })

const range = computed(() => config.value?.bill[form.type as 'household' | 'business'] ?? { min: 0, max: 0, default: 0 })
watch(() => form.type, () => { form.bill = range.value.default })

const money = (value: number) => `${Math.round(value).toLocaleString('vi-VN')}đ`
const million = (value: number) => value >= 1e9 ? `${(value / 1e9).toLocaleString('vi-VN', { maximumFractionDigits: 2 })} tỷ` : `${(value / 1e6).toLocaleString('vi-VN', { maximumFractionDigits: 1 })} triệu`
const decimal = (value: number) => value.toLocaleString('vi-VN', { maximumFractionDigits: 1 })
const billText = computed({
  get: () => form.bill ? form.bill.toLocaleString('vi-VN') : '',
  set: (text: string) => { form.bill = Number(text.replace(/\D/g, '')) || 0 },
})

let timer: ReturnType<typeof setTimeout> | undefined
let sequence = 0
async function estimate() {
  const ticket = ++sequence
  result.value = null
  problems.value = []
  busy.value = true
  try {
    type Answer = { ok: boolean; result?: unknown; errors?: string[] }
    const answer: Answer = await $fetch<Answer>('/api/v1/solar-estimate', {
      method: 'POST',
      body: { type: form.type, bill: form.bill, region: form.region, habit: form.habit, battery: form.battery,
        roof_m2: form.roof ? Number(form.roof) : undefined, price: form.price ? Number(form.price) : undefined },
    }).catch((reason: { data?: { errors?: string[] } }): Answer => ({ ok: false, errors: reason?.data?.errors ?? ['Chưa tính được lúc này, vui lòng thử lại.'] }))
    if (ticket !== sequence) return
    problems.value = answer.ok ? [] : (answer.errors ?? [])
    if (answer.ok && isPublicSolarEstimate(answer.result)) result.value = answer.result
    else if (answer.ok) problems.value = ['Kết quả chưa hợp lệ, vui lòng thử lại.']
  } finally {
    if (ticket === sequence) busy.value = false
  }
}
watch(() => ({ ...form }), () => {
  ++sequence
  clearTimeout(timer)
  result.value = null
  busy.value = false
  sent.value = ''
  sendError.value = ''
  if (form.bill < range.value.min || form.bill > range.value.max) {
    problems.value = [`Tiền điện mỗi tháng từ ${money(range.value.min)} đến ${money(range.value.max)}`]
    return
  }
  problems.value = []
  timer = setTimeout(estimate, 350)
}, { deep: true })
onMounted(() => { if (config.value?.enabled) estimate() })

const bars = computed(() => {
  if (!result.value) return null
  const top = Math.max(result.value.bill, 1)
  return { now: 100, after: Math.max(4, Math.round(result.value.newBill / top * 100)) }
})

// Lead: only name and phone; the estimate itself travels as the message.
const { submit } = useLeadForm()
const lead = reactive({ fullname: '', phone: '' })
const touched = reactive({ fullname: false, phone: false })
const sending = ref(false)
const sent = ref('')
const sendError = ref('')
// Same rule as Laravel's LeadSubmission::isPhone, after dropping the spaces and dots people type.
const phoneDigits = computed(() => lead.phone.replace(/[\s.-]/g, ''))
const leadInvalid = computed(() => ({ fullname: !lead.fullname.trim(), phone: !/^(03|05|07|08|09)\d{8}$/.test(phoneDigits.value) }))
async function sendLead() {
  touched.fullname = touched.phone = true
  if (!result.value || busy.value || leadInvalid.value.fullname || leadInvalid.value.phone || sending.value) return
  sending.value = true
  sendError.value = ''
  const requestSequence = sequence
  const summary = result.value.summary
  try {
    const answer = await submit('contact', { fullname: lead.fullname.trim(), phone: phoneDigits.value, subject: 'Ước tính điện mặt trời', content: summary })
    if (requestSequence === sequence) sent.value = answer.message
  } catch (reason) {
    sendError.value = reason instanceof Error && reason.message ? reason.message : 'Chưa gửi được, vui lòng thử lại hoặc gọi hotline.'
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div class="text-navy-800">
    <main>
      <div class="border-b border-slate-200/70 bg-slate-50/80">
        <div class="pts-container">
          <SiteBreadcrumb :items="[{ label: 'Ước tính điện mặt trời' }]" />
        </div>
      </div>

      <div class="pts-container py-8 nav:py-14">
        <div v-if="error || !config" class="min-h-[45vh]" role="status">
          <h1 class="mb-3 text-3xl font-black tracking-tight">{{ title }}</h1>
          <p class="text-slate-500">Công cụ tạm thời chưa tải được. Vui lòng thử lại sau ít phút hoặc gọi hotline để được tư vấn.</p>
        </div>

        <template v-else>
          <header class="est-head">
            <h1>{{ title }}</h1>
            <p>{{ description }}</p>
          </header>

          <div class="est-grid">
            <form class="est-card est-form" @submit.prevent="estimate">
              <fieldset class="est-group">
                <legend>Công trình</legend>
                <div class="est-segment" role="radiogroup">
                  <label v-for="type in config.types" :key="type.key" class="est-chip">
                    <input v-model="form.type" type="radio" name="type" :value="type.key" class="sr-only">
                    <UIcon :name="type.key === 'household' ? 'i-lucide-house' : 'i-lucide-factory'" class="size-5" aria-hidden="true" />
                    {{ type.label }}
                  </label>
                </div>
              </fieldset>

              <label class="est-field">
                <span>Tiền điện mỗi tháng</span>
                <span class="est-money">
                  <input v-model="billText" inputmode="numeric" autocomplete="off" aria-describedby="est-bill-range" @focus="($event.target as HTMLInputElement).select()">
                  <b aria-hidden="true">đ</b>
                </span>
                <input v-model.number="form.bill" type="range" :min="range.min" :max="Math.min(range.max, form.type === 'household' ? 10000000 : 300000000)"
                       :step="form.type === 'household' ? 100000 : 1000000" class="est-range" aria-label="Kéo để chọn tiền điện">
                <small id="est-bill-range">Lấy số trên hóa đơn EVN gần nhất, đã gồm VAT.</small>
              </label>

              <fieldset class="est-group">
                <legend>Khu vực lắp đặt</legend>
                <div class="est-chips">
                  <label v-for="region in config.regions" :key="region.key" class="est-chip">
                    <input v-model="form.region" type="radio" name="region" :value="region.key" class="sr-only">{{ region.label }}
                  </label>
                </div>
              </fieldset>

              <fieldset class="est-group">
                <legend>Thói quen dùng điện</legend>
                <div class="est-chips">
                  <label v-for="habit in config.habits" :key="habit.key" class="est-chip">
                    <input v-model="form.habit" type="radio" name="habit" :value="habit.key" class="sr-only">{{ habit.label }}
                  </label>
                </div>
              </fieldset>

              <label class="est-switch">
                <input v-model="form.battery" type="checkbox">
                <span class="est-switch__track" aria-hidden="true" />
                <span>Có pin lưu trữ: dự kiến dùng điện mặt trời buổi tối; cấp điện khi mất lưới tùy cấu hình inverter và mạch dự phòng</span>
              </label>

              <FormMoreToggle v-if="!moreOpen" label="Thêm diện tích mái hoặc giá điện" @click="moreOpen = true" />
              <div v-else class="est-more">
                <label class="est-field">
                  <span>Diện tích mái lắp được (m²)</span>
                  <input v-model="form.roof" inputmode="numeric" maxlength="6" placeholder="Ví dụ 40">
                </label>
                <label v-if="form.type === 'business'" class="est-field">
                  <span>Giá điện bình quân (đ/kWh, trước VAT)</span>
                  <input v-model="form.price" inputmode="numeric" maxlength="5" :placeholder="String(config.businessPrice)">
                </label>
              </div>

              <ul v-if="problems.length" class="est-problems" role="alert">
                <li v-for="problem in problems" :key="problem">{{ problem }}</li>
              </ul>
            </form>

            <section class="est-card est-result" :aria-busy="busy" aria-live="polite" aria-labelledby="est-result-title">
              <h2 id="est-result-title" class="sr-only">Kết quả ước tính</h2>
              <template v-if="result">
                <div class="est-hero">
                  <div>
                    <span>Hệ đề xuất</span>
                    <strong>{{ decimal(result.kwp) }} <small>kWp</small></strong>
                  </div>
                  <ul>
                    <li><UIcon name="i-lucide-grid-3x3" class="size-4" aria-hidden="true" />{{ result.panels }} tấm {{ result.panelWatt }}W</li>
                    <li><UIcon name="i-lucide-ruler" class="size-4" aria-hidden="true" />Mái khoảng {{ result.roofM2 }} m²</li>
                    <li v-if="result.batteryKwh"><UIcon name="i-lucide-battery-charging" class="size-4" aria-hidden="true" />Pin {{ result.batteryKwh }} kWh</li>
                    <li><UIcon name="i-lucide-sun" class="size-4" aria-hidden="true" />~{{ result.outputKwh.toLocaleString('vi-VN') }} kWh/tháng</li>
                  </ul>
                </div>
                <p v-if="result.limitedByRoof" class="est-note">Mái nhập vào chỉ đủ cho hệ này; muốn tiết kiệm hơn cần thêm diện tích lắp.</p>

                <div v-if="bars" class="est-bills">
                  <div class="est-bill">
                    <span>Tiền điện hiện tại</span>
                    <i :style="{ width: bars.now + '%' }" />
                    <b>{{ money(result.bill) }}</b>
                  </div>
                  <div class="est-bill est-bill--after">
                    <span>Sau khi lắp</span>
                    <i :style="{ width: bars.after + '%' }" />
                    <b>~{{ money(result.newBill) }}</b>
                  </div>
                </div>

                <dl class="est-facts">
                  <div class="est-fact est-fact--gold">
                    <dt>Tiết kiệm mỗi tháng</dt>
                    <dd>~{{ money(result.saving) }} <small>({{ result.savingPercent }}%)</small></dd>
                  </div>
                  <div class="est-fact">
                    <dt>Chi phí dự kiến</dt>
                    <dd>{{ million(result.cost.low) }} – {{ million(result.cost.high) }}</dd>
                  </div>
                  <div v-if="result.payback" class="est-fact">
                    <dt>Hoàn vốn</dt>
                    <dd>{{ decimal(result.payback.low) }}–{{ decimal(result.payback.high) }} năm</dd>
                  </div>
                  <div class="est-fact">
                    <dt>Giảm CO₂ mỗi năm</dt>
                    <dd>~{{ decimal(result.co2Tonnes) }} tấn</dd>
                  </div>
                </dl>
                <p class="est-basis">Tính theo: {{ result.basis }} Kỹ sư khảo sát mái, hướng nắng và phụ tải để chốt cấu hình và báo giá.</p>

                <div class="est-lead">
                  <h3>Nhận phương án chi tiết cho công trình của bạn</h3>
                  <p v-if="sent" class="est-sent" role="status"><UIcon name="i-lucide-circle-check" class="size-5" aria-hidden="true" />{{ sent }}</p>
                  <form v-else class="est-lead__form" novalidate @submit.prevent="sendLead">
                    <label class="est-field" :class="{ 'is-invalid': touched.fullname && leadInvalid.fullname }">
                      <span>Họ và tên</span>
                      <input v-model="lead.fullname" autocomplete="name" maxlength="100" required placeholder="Nguyễn Văn A" @blur="touched.fullname = true">
                      <small v-if="touched.fullname && leadInvalid.fullname" role="alert">Vui lòng nhập họ và tên</small>
                    </label>
                    <label class="est-field" :class="{ 'is-invalid': touched.phone && leadInvalid.phone }">
                      <span>Số điện thoại</span>
                      <input v-model="lead.phone" type="tel" inputmode="tel" autocomplete="tel" maxlength="20" required placeholder="0944 555 957" @blur="touched.phone = true">
                      <small v-if="touched.phone && leadInvalid.phone" role="alert">Số điện thoại chưa đúng</small>
                    </label>
                    <button type="submit" class="est-submit" :disabled="sending || busy || !result">
                      <UIcon :name="sending ? 'i-lucide-loader-circle' : 'i-lucide-send'" :class="['size-5', sending && 'animate-spin']" aria-hidden="true" />
                      {{ sending ? 'Đang gửi…' : 'Gửi yêu cầu tư vấn' }}
                    </button>
                    <p v-if="sendError" class="est-problems" role="alert">{{ sendError }}</p>
                  </form>
                </div>
              </template>
              <div v-else class="est-empty" role="status">
                <UIcon name="i-lucide-sun" class="size-8" aria-hidden="true" />
                <p>{{ busy ? 'Đang tính…' : 'Nhập tiền điện để xem ước tính.' }}</p>
              </div>
            </section>
          </div>
        </template>
      </div>
    </main>
  </div>
</template>

<style scoped>
.est-head { max-width: 760px; margin-bottom: 24px; }
.est-head h1 { margin: 0 0 8px; color: var(--color-navy-950); font-size: clamp(26px, 3.2vw, 40px); font-weight: 900; line-height: 1.15; letter-spacing: -0.02em; }
.est-head p { margin: 0; color: var(--color-slate-600, #475569); font-size: 16px; line-height: 1.6; }
.est-grid { display: grid; gap: 20px; align-items: start; }
@media (min-width: 1024px) { .est-grid { grid-template-columns: minmax(0, 5fr) minmax(0, 6fr); gap: 28px; } .est-result { position: sticky; top: 96px; } }
.est-card { border: 1px solid rgb(10 34 81 / 10%); border-radius: var(--radius-lg, 14px); background: #fff; box-shadow: 0 18px 40px -34px rgb(10 34 81 / 55%); }
.est-form { display: grid; gap: 20px; padding: 20px; }
@media (min-width: 640px) { .est-form { padding: 26px; } }
.est-group { display: grid; gap: 10px; margin: 0; padding: 0; border: 0; }
.est-group legend, .est-field > span { margin-bottom: 2px; color: var(--color-navy-900); font-size: 14px; font-weight: 800; }
.est-segment { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.est-chips { display: flex; flex-wrap: wrap; gap: 8px; }
.est-chip { display: inline-flex; min-height: 46px; align-items: center; justify-content: center; gap: 8px; padding: 0 14px; border: 1px solid rgb(10 34 81 / 16%); border-radius: var(--radius-md, 10px); background: #fff; color: var(--color-navy-900); font-size: 15px; font-weight: 700; cursor: pointer; transition: border-color .2s ease, background .2s ease; }
.est-chip:hover { border-color: var(--color-gold-500); }
.est-chip:has(input:checked) { border-color: transparent; background: var(--pts-gold-gradient); color: var(--color-navy-950); box-shadow: 0 10px 22px -16px rgb(201 150 46 / 90%); }
.est-chip:has(input:focus-visible), .est-switch:has(input:focus-visible) { outline: 3px solid var(--color-gold-500); outline-offset: 2px; }
.est-field { display: grid; gap: 6px; }
.est-field input:not([type=range]) { width: 100%; min-height: 50px; padding: 0 14px; border: 1px solid rgb(10 34 81 / 16%); border-radius: var(--radius-md, 10px); background: #fff; color: var(--color-navy-950); font: inherit; font-size: 16px; font-weight: 600; outline: none; }
.est-field input:focus { border-color: var(--color-gold-500); box-shadow: 0 0 0 3px rgb(201 150 46 / 18%); }
.est-field small { color: #64748b; font-size: 13.5px; line-height: 1.5; }
.est-field.is-invalid input { border-color: #dc2626; }
.est-field.is-invalid small { color: #b91c1c; }
.est-money { position: relative; display: flex; }
.est-money input { padding-right: 36px !important; font-size: 22px !important; font-weight: 800 !important; letter-spacing: -0.01em; }
.est-money b { position: absolute; top: 50%; right: 14px; transform: translateY(-50%); color: #64748b; font-size: 18px; }
.est-range { width: 100%; accent-color: var(--color-gold-600); }
.est-switch { display: flex; min-height: 44px; align-items: center; gap: 12px; color: var(--color-navy-900); font-size: 15px; font-weight: 600; line-height: 1.45; cursor: pointer; }
.est-switch input { position: absolute; opacity: 0; pointer-events: none; }
.est-switch__track { position: relative; width: 46px; height: 26px; flex: none; border-radius: 999px; background: #cbd5e1; transition: background .2s ease; }
.est-switch__track::after { content: ''; position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgb(0 0 0 / 25%); transition: transform .2s ease; }
.est-switch input:checked + .est-switch__track { background: var(--color-navy-800); }
.est-switch input:checked + .est-switch__track::after { transform: translateX(20px); }
.est-more { display: grid; gap: 14px; }
.est-problems { margin: 0; padding: 10px 14px; border-radius: var(--radius-md, 10px); background: #fef2f2; color: #b91c1c; font-size: 14px; list-style: none; }
.est-result { overflow: hidden; }
.est-hero { display: grid; gap: 14px; padding: 22px; background: linear-gradient(135deg, var(--color-navy-950), var(--color-navy-800)); color: #fff; }
@media (min-width: 640px) { .est-hero { grid-template-columns: auto 1fr; align-items: center; gap: 24px; padding: 26px; } }
.est-hero span { display: block; color: rgb(255 255 255 / 72%); font-size: 14px; font-weight: 600; }
.est-hero strong { display: block; color: var(--color-gold-300); font-size: clamp(40px, 6vw, 56px); font-weight: 900; line-height: 1; letter-spacing: -0.03em; font-variant-numeric: tabular-nums; }
.est-hero strong small { font-size: 0.42em; font-weight: 800; }
.est-hero ul { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 14px; margin: 0; padding: 0; list-style: none; font-size: 14.5px; font-weight: 600; }
.est-hero li { display: flex; align-items: center; gap: 7px; }
.est-note { margin: 0; padding: 10px 22px; background: #fffbeb; color: #92400e; font-size: 14px; }
.est-bills { display: grid; gap: 12px; padding: 20px 22px 6px; }
.est-bill { display: grid; grid-template-columns: 120px 1fr auto; align-items: center; gap: 12px; font-size: 14px; }
.est-bill span { color: #475569; font-weight: 600; }
.est-bill i { height: 12px; border-radius: 999px; background: #cbd5e1; transition: width .45s cubic-bezier(.2,.8,.2,1); }
.est-bill--after i { background: var(--pts-gold-gradient); }
.est-bill b { color: var(--color-navy-950); font-variant-numeric: tabular-nums; }
.est-facts { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 0; padding: 16px 22px; }
.est-fact { padding: 12px 14px; border-radius: var(--radius-md, 10px); background: #f8fafc; }
.est-fact dt { color: #475569; font-size: 13.5px; font-weight: 600; }
.est-fact dd { margin: 4px 0 0; color: var(--color-navy-950); font-size: 18px; font-weight: 800; font-variant-numeric: tabular-nums; }
.est-fact dd small { color: #64748b; font-size: 13px; font-weight: 700; }
.est-fact--gold { background: #fffbf2; box-shadow: inset 0 0 0 1px rgb(201 150 46 / 35%); }
.est-basis { margin: 0; padding: 0 22px 18px; color: #64748b; font-size: 13.5px; line-height: 1.55; }
.est-lead { padding: 20px 22px 24px; border-top: 1px solid rgb(10 34 81 / 8%); background: #fbfcfe; }
.est-lead h3 { margin: 0 0 12px; color: var(--color-navy-950); font-size: 18px; font-weight: 800; }
.est-lead__form { display: grid; gap: 12px; }
@media (min-width: 640px) { .est-lead__form { grid-template-columns: 1fr 1fr; } .est-submit, .est-lead__form .est-problems { grid-column: 1 / -1; } }
.est-submit { display: inline-flex; min-height: 52px; align-items: center; justify-content: center; gap: 8px; border: 0; border-radius: var(--radius-md, 10px); background: var(--pts-gold-gradient); color: var(--color-navy-950); font: inherit; font-size: 16px; font-weight: 800; cursor: pointer; transition: transform .15s ease, filter .2s ease; }
.est-submit:hover { filter: brightness(1.04); }
.est-submit:active { transform: scale(.98); }
.est-submit:disabled { cursor: progress; opacity: .75; }
.est-sent { display: flex; align-items: center; gap: 8px; margin: 0; color: #047857; font-weight: 700; }
.est-empty { display: grid; min-height: 280px; place-items: center; align-content: center; gap: 10px; color: #64748b; }
@media (max-width: 480px) { .est-hero ul { font-size: 13.5px; gap: 8px 10px; } .est-hero li { white-space: nowrap; } .est-bill { grid-template-columns: 92px 1fr auto; } .est-facts { grid-template-columns: 1fr; } }
</style>
