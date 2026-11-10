<script setup lang="ts">
import { publicEmailHref, publicPhoneHref } from '#shared/contact-links'
import type { PublicHomePayload } from '#shared/contracts/public-home'

const props = defineProps<{ contact: PublicHomePayload['contact'] }>()
const phoneHref = computed(() => publicPhoneHref(props.contact.hotline))
const emailHref = computed(() => publicEmailHref(props.contact.email))
const { ensureSecurity, submit } = useLeadForm()
const form = reactive({ fullname: '', phone: '', address: '', subject: '', content: '' })
const detailsOpen = ref(false)

const needs = [
  { value: 'Điện mặt trời gia đình', icon: 'i-lucide-house', keys: ['gia đình', 'hộ', 'nhà ở', 'mái nhà'] },
  { value: 'Điện mặt trời doanh nghiệp', icon: 'i-lucide-factory', keys: ['doanh nghiệp', 'nhà máy', 'nhà xưởng', 'công nghiệp'] },
  { value: 'Pin lưu trữ', icon: 'i-lucide-battery-charging', keys: ['lưu trữ', 'pin lưu', 'backup', 'dự phòng'] },
  { value: 'Đèn năng lượng mặt trời', icon: 'i-lucide-lamp', keys: ['đèn', 'chiếu sáng'] },
  { value: 'Tấm pin và biến tần', icon: 'i-lucide-sun', keys: ['tấm pin', 'biến tần', 'inverter'] },
  { value: 'Bơm nước năng lượng mặt trời', icon: 'i-lucide-droplets', keys: ['bơm', 'tưới', 'nông nghiệp'] },
  { value: 'Hợp tác đại lý', icon: 'i-lucide-handshake', keys: ['đại lý', 'hợp tác'] },
  { value: 'Khác', icon: 'i-lucide-message-circle', keys: [] },
]
const hints = ['Tiền điện mỗi tháng khoảng: ', 'Diện tích mái: ', 'Cần dùng điện cả ban đêm: ', 'Thời gian muốn lắp đặt: ']

const inputUi = {
  base: 'h-12 w-full rounded-chip border border-slate-300 bg-white px-3.5 text-base font-normal text-navy-950 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
}
const textareaUi = {
  base: 'min-h-[136px] w-full rounded-chip border border-slate-300 bg-white px-3.5 py-3 text-base font-normal text-navy-950 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
}
const leadIntent = useState<string>('public-home-lead-intent', () => '')
const prefilledFrom = ref('')
const submitting = ref(false)
const success = ref('')
const error = ref('')
const contentRef = ref<{ textareaRef?: HTMLTextAreaElement } | null>(null)

function matchNeed(requested: string) {
  const needle = requested.trim().toLocaleLowerCase()
  return needs.find((need) => need.value.toLocaleLowerCase() === needle || need.keys.some((key) => needle.includes(key)))?.value
}

watch(leadIntent, (requested) => {
  if (!requested) return
  const matched = matchNeed(requested)
  form.subject = matched || 'Khác'
  if (!matched && !form.content) form.content = `Quan tâm: ${requested}. `
  prefilledFrom.value = requested
  leadIntent.value = ''
})

function addHint(hint: string) {
  form.content = form.content && !form.content.endsWith('\n') ? `${form.content}\n${hint}` : `${form.content}${hint}`
  nextTick(() => {
    const el = contentRef.value?.textareaRef
    if (!el) return
    el.focus()
    el.setSelectionRange(el.value.length, el.value.length)
  })
}

onMounted(() => void ensureSecurity().catch(() => {}))

async function send() {
  success.value = ''
  error.value = ''
  submitting.value = true
  try {
    const result = await submit('contact', {
      fullname: form.fullname,
      phone: form.phone,
      email: '',
      address: form.address,
      subject: form.subject,
      // The phone form asks only for need, name and number; staff still receive a readable request.
      content: form.content.trim() || `Khách để lại số điện thoại, cần tư vấn: ${form.subject || 'chưa chọn nhu cầu'}.`,
    })
    success.value = result.message
    Object.assign(form, { fullname: '', phone: '', address: '', subject: '', content: '' })
    prefilledFrom.value = ''
  } catch (reason: any) {
    error.value = reason?.data?.statusMessage || 'Chưa gửi được yêu cầu. Vui lòng thử lại hoặc gọi hotline để được hỗ trợ ngay.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section
    id="yeu-cau-tu-van"
    class="bg-hdr-surface py-[clamp(64px,7vw,112px)] max-tb:py-8"
    aria-labelledby="home-contact-title"
  >
    <div class="hc pts-container grid grid-cols-[minmax(0,1fr)] gap-[clamp(32px,4vw,72px)] max-tb:gap-4 min-[1101px]:grid-cols-[minmax(0,.78fr)_minmax(0,1.22fr)]">
      <!-- Left: promise + direct channels -->
      <div class="hc-aside flex flex-col">
        <h2 id="home-contact-title" class="m-0 max-w-[13ch] text-[clamp(32px,3.4vw,56px)] font-black leading-[1.04] tracking-[-.035em] text-navy-950">
          Bạn đang cần giải pháp nào?
        </h2>
        <p class="mt-5 max-w-[44ch] text-[clamp(15px,1.05vw,18px)] leading-[1.7] text-slate-600 max-tb:mt-2">
          Chọn nhu cầu và để lại số điện thoại. Đội ngũ Phúc Thịnh Solar sẽ gọi lại để trao đổi cụ thể.
        </p>

        <a
          v-if="phoneHref"
          :href="phoneHref"
          class="hc-call group mt-8 flex max-tb:mt-4 max-w-[420px] items-center gap-4 rounded-btn bg-navy-950 p-4 pr-6 text-white no-underline transition-colors hover:bg-navy-900"
        >
          <span class="grid size-12 flex-none place-items-center rounded-full bg-gold-400 text-navy-950 transition-transform group-hover:scale-105">
            <UIcon name="i-lucide-phone-call" class="size-5" aria-hidden="true" />
          </span>
          <span class="flex flex-col">
            <span class="text-sm text-slate-300">Gọi ngay để được tư vấn</span>
            <strong class="text-[clamp(20px,1.8vw,26px)] font-black tracking-[-.01em]">{{ contact.hotline }}</strong>
          </span>
        </a>

        <ul class="hc-list m-0 mt-8 grid max-tb:mt-4 list-none gap-4 p-0 text-[15px] leading-[1.55] text-slate-600">
          <li v-if="contact.email" class="flex gap-3">
            <UIcon name="i-lucide-mail" class="mt-0.5 size-5 flex-none text-gold-700" aria-hidden="true" />
            <span><a v-if="emailHref" class="font-semibold text-navy-950 no-underline hover:text-gold-700" :href="emailHref">{{ contact.email }}</a><span v-else>{{ contact.email }}</span></span>
          </li>
          <li v-if="contact.address" class="flex gap-3">
            <UIcon name="i-lucide-map-pin" class="mt-0.5 size-5 flex-none text-gold-700" aria-hidden="true" />
            <span>{{ contact.address }}</span>
          </li>
          <li v-if="contact.workingHours" class="flex gap-3">
            <UIcon name="i-lucide-clock-3" class="mt-0.5 size-5 flex-none text-gold-700" aria-hidden="true" />
            <span>{{ contact.workingHours }}</span>
          </li>
        </ul>
      </div>

      <!-- Right: form -->
      <div class="hc-card rounded-btn bg-white p-[clamp(22px,3vw,44px)] shadow-[0_24px_60px_-28px_rgba(6,21,55,.28)]">
        <div v-if="success" class="hc-success flex min-h-[420px] flex-col items-start justify-center" role="status">
          <span class="grid size-14 place-items-center rounded-full bg-emerald-50 text-emerald-700">
            <UIcon name="i-lucide-check" class="size-7" aria-hidden="true" />
          </span>
          <h3 class="mt-5 text-[clamp(22px,2vw,30px)] font-black text-navy-950">Đã nhận yêu cầu của bạn</h3>
          <p class="mt-3 max-w-[46ch] text-base leading-[1.7] text-slate-600">{{ success }}</p>
          <button type="button" class="mt-6 border-b-2 border-gold-500 pb-1 text-[15px] font-extrabold text-navy-950" @click="success = ''">Gửi thêm một yêu cầu khác</button>
        </div>

        <form v-else @submit.prevent="send">
          <fieldset class="m-0 min-w-0 border-0 p-0">
            <legend class="mb-3 text-[15px] font-extrabold text-navy-950">Anh/chị quan tâm đến</legend>
            <p v-if="prefilledFrom" class="-mt-1 mb-3 text-sm text-slate-600" role="status">
              Đã chọn theo mục bạn vừa xem: <strong class="text-navy-950">{{ prefilledFrom }}</strong>
            </p>
            <div class="need-grid">
              <label v-for="need in needs" :key="need.value" class="need" :class="{ 'is-on': form.subject === need.value }">
                <input v-model="form.subject" class="sr-only" type="radio" name="subject" :value="need.value">
                <UIcon :name="need.icon" class="size-5 flex-none" aria-hidden="true" />
                <span>{{ need.value }}</span>
              </label>
            </div>
          </fieldset>

          <div class="hc-fields mt-6 grid gap-x-4 max-tb:mt-4 max-tb:grid-cols-2 tb:grid-cols-3">
            <UFormField label="Họ và tên" name="fullname" required class="mb-4 grid min-w-0 content-start gap-1.5" :ui="{ label: 'text-sm font-bold text-navy-950' }">
              <UInput v-model.trim="form.fullname" name="fullname" type="text" maxlength="100" autocomplete="name" placeholder="Nguyễn Văn A" required variant="none" class="w-full" :ui="inputUi" />
            </UFormField>
            <UFormField label="Số điện thoại" name="phone" required class="mb-4 grid min-w-0 content-start gap-1.5" :ui="{ label: 'text-sm font-bold text-navy-950' }">
              <UInput v-model.trim="form.phone" name="phone" type="tel" maxlength="20" autocomplete="tel" inputmode="tel" placeholder="090xxxxxxx" pattern="(\+?84|0)[0-9 .\-]{8,12}" title="Nhập số điện thoại Việt Nam, ví dụ 0944 555 957" required variant="none" class="w-full" :ui="inputUi" />
            </UFormField>
            <UFormField label="Tỉnh/Thành phố" name="address" :class="detailsOpen ? '' : 'max-tb:hidden'" class="hc-province mb-4 grid min-w-0 content-start gap-1.5" :ui="{ label: 'text-sm font-bold text-navy-950' }">
              <UInput v-model.trim="form.address" name="address" type="text" maxlength="255" autocomplete="address-level1" placeholder="Ví dụ: Gia Lai" variant="none" class="w-full" :ui="inputUi" />
            </UFormField>
          </div>

          <FormMoreToggle v-if="!detailsOpen" class="mb-3" phone-only @click="detailsOpen = true" />
          <UFormField label="Nội dung cần trao đổi" name="content" :class="detailsOpen ? '' : 'max-tb:hidden'" class="grid min-w-0 content-start gap-1.5" :ui="{ label: 'text-sm font-bold text-navy-950' }">
            <UTextarea ref="contentRef" v-model="form.content" name="content" :rows="4" maxlength="5000" placeholder="Mô tả ngắn nhu cầu của bạn..." variant="none" class="w-full" :ui="textareaUi" />
          </UFormField>
          <div class="mt-2.5 flex flex-wrap gap-2" :class="detailsOpen ? '' : 'max-tb:hidden'" aria-label="Gợi ý thông tin nên có">
            <button v-for="hint in hints" :key="hint" type="button" class="hint" @click="addHint(hint)">+ {{ hint.replace(/: $/, '') }}</button>
          </div>

          <p v-if="error" class="mt-4 rounded-chip bg-red-50 p-3 text-sm font-semibold text-red-800" role="alert">{{ error }}</p>
          <div class="hc-submit mt-6 flex flex-wrap items-center gap-x-5 gap-y-3">
            <UButton :loading="submitting" :disabled="submitting" class="max-tb:w-full min-h-[52px] items-center justify-center rounded-btn border border-gold-500 bg-gold-400 px-7 text-[15px] font-extrabold text-navy-950 transition-colors hover:bg-gold-500 disabled:cursor-wait disabled:opacity-60 cursor-pointer" type="submit">
              {{ submitting ? 'Đang gửi…' : 'Gửi yêu cầu tư vấn' }}
            </UButton>
            <span class="text-sm text-slate-500">Thông tin chỉ dùng để liên hệ tư vấn.</span>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<style scoped>
.need-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 8px;
}
.need {
  display: flex;
  min-height: 64px;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border: 1px solid rgb(10 34 81 / 14%);
  border-radius: var(--radius-btn);
  background: #fff;
  color: var(--color-navy-900);
  font-size: 14px;
  font-weight: 700;
  line-height: 1.3;
  cursor: pointer;
  transition: border-color 0.2s ease, background-color 0.2s ease, color 0.2s ease;
}
.need :deep(svg) { color: var(--color-gold-700); }
.need:hover { border-color: var(--color-gold-500); }
.need.is-on { border-color: transparent; background: var(--pts-gold-gradient); box-shadow: inset 0 1px 0 rgb(255 255 255 / 30%), 0 2px 6px rgb(6 21 55 / 16%); color: var(--color-navy-950); }
.need.is-on :deep(svg) { color: var(--color-navy-950); }
.need:has(input:focus-visible) { outline: 2px solid var(--color-gold-600); outline-offset: 2px; }

.hint {
  min-height: 34px;
  padding: 0 12px;
  border: 1px dashed rgb(10 34 81 / 28%);
  border-radius: 999px;
  background: transparent;
  color: var(--color-navy-800);
  font: inherit;
  font-size: 13.5px;
  font-weight: 600;
  cursor: pointer;
}
.hint:hover { border-color: var(--color-gold-600); color: var(--color-gold-800, #7a5a12); }
.hint:focus-visible { outline: 2px solid var(--color-gold-600); outline-offset: 2px; }

@media (max-width: 1100px) { .need-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (prefers-reduced-motion: reduce) { .need { transition: none; } }

/* Phones: heading, then the form at once; the hotline and addresses follow it. */
@media (max-width: 820px) {
  .hc { gap: 14px; }
  .hc-aside { display: contents; }
  .hc-aside > h2 { max-width: none; font-size: 28px; line-height: 1.12; }
  .hc-aside > p { max-width: none; margin-top: -6px; font-size: 15px; line-height: 1.55; }
  .hc-card { order: 1; min-width: 0; padding: 16px; border-radius: var(--radius-md); }
  .hc-call { order: 2; max-width: none; margin-top: 0; padding: 10px 16px 10px 10px; border-radius: var(--radius-md); }
  .hc-list { order: 3; margin-top: 0; gap: 6px; font-size: 14.5px; }
  .hc-list li { min-height: 28px; gap: 10px; }
  .hc-list a { display: inline-flex; align-items: center; min-height: 44px; margin-block: -10px; }
  .hc-success { min-height: 240px; }
  .hc-success button { min-height: 44px; }

  .need-grid { gap: 8px; }
  .need { position: relative; min-height: 52px; gap: 8px; padding: 6px 10px; border-radius: var(--radius-sm); font-size: 13.5px; line-height: 1.25; }
  .hc-card legend { margin-bottom: 8px; }
  .hc-card :deep(input:not(.sr-only)), .hc-card :deep(textarea) { border-radius: var(--radius-sm); }
  .hc-card :deep(textarea) { min-height: 112px; }
  .hc-fields { column-gap: 10px; margin-top: 14px; }
  .hc-fields > :deep(*) { margin-bottom: 14px; }
  .hc-fields > :deep(.hc-province) { grid-column: 1 / -1; }
  .hint { min-height: 44px; }
  .hc-submit { margin-top: 14px; row-gap: 8px; }
  .hc-submit > span { width: 100%; font-size: 13px; text-align: center; }
}
</style>
