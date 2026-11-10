<script setup lang="ts">
import { useStorefrontModal } from '~/composables/useStorefrontModal'
import { useLeadForm } from '~/composables/useLeadForm'

const { isConsultationOpen, consultationSubject, closeConsultation } = useStorefrontModal()
const { security, loadingSecurity, ensureSecurity, refreshSecurity, submit } = useLeadForm()

const form = reactive({
  fullname: '',
  phone: '',
  email: '',
  subject: '',
  notes: '',
})

const submitting = ref(false)
const success = ref('')
const error = ref('')
const securityError = ref('')

const predefinedSubjects = [
  'Điện mặt trời doanh nghiệp',
  'Điện mặt trời gia đình',
  'Đèn NLMT',
  'Bơm nước NLMT',
  'Khác',
]

// Reading an article or the About page says nothing about what the visitor needs, so these never appear as their
// interest. The page still reaches the sales team inside the message.
const READING_PAGE = /^(Kiến thức|Bài viết|Giới thiệu|Chính sách & Hỗ trợ)\s*:/
const readingContext = computed(() => READING_PAGE.test(consultationSubject.value) ? consultationSubject.value.trim() : '')
const needSubject = computed(() => readingContext.value ? '' : consultationSubject.value.trim())

const subjectItems = computed(() => {
  const custom = needSubject.value
  if (custom && !predefinedSubjects.includes(custom)) {
    return [custom, ...predefinedSubjects]
  }
  return predefinedSubjects
})

const quickNoteChips = [
  'Hóa đơn điện ~2–5 triệu/tháng',
  'Mái nhà xưởng / doanh nghiệp',
  'Cần báo giá thiết bị & lắp đặt',
  'Cần tư vấn đèn NLMT công trình',
]

function appendNote(chip: string) {
  const current = form.notes.trim()
  if (current.includes(chip)) return
  form.notes = current ? `${current}; ${chip}` : chip
}
const inputUi = {
  base: 'h-12 sm:h-14 w-full rounded-btn border border-slate-300 bg-white px-4 text-base font-normal text-navy-950 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
}
const selectUi = {
  base: 'h-12 sm:h-14 w-full rounded-btn border border-slate-300 bg-white px-4 text-base font-normal text-navy-950 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
}
const textareaUi = {
  base: 'min-h-[150px] w-full rounded-btn border border-slate-300 bg-white px-4 py-3 text-base font-normal text-navy-950 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
}

async function loadSecurity(refresh = false) {
  securityError.value = ''
  try {
    if (refresh) await refreshSecurity()
    else await ensureSecurity()
  } catch {
    securityError.value = 'Không thể chuẩn bị biểu mẫu. Vui lòng thử tải lại.'
  }
}

// Optional fields start folded, unless the visitor already wrote something in them.
const detailsOpen = ref(false)

watch(
  () => isConsultationOpen.value,
  (open) => {
    if (open) {
      detailsOpen.value = Boolean(form.email || form.notes)
      success.value = ''
      error.value = ''
      if (needSubject.value) {
        form.subject = needSubject.value
      } else if (!predefinedSubjects.includes(form.subject)) {
        form.subject = predefinedSubjects[0] ?? ''
      }
      void loadSecurity()
    }
  }
)

async function send() {
  success.value = ''
  error.value = ''
  securityError.value = ''

  if (loadingSecurity.value || !security.value) {
    await loadSecurity()
    if (!security.value) return
  }

  submitting.value = true
  try {
    const noteText = form.notes.trim()
    const subjectText = form.subject.trim() || 'Yêu cầu tư vấn giải pháp'
    const baseText = noteText ? (subjectText ? `[${subjectText}] ${noteText}` : noteText) : `Đăng ký nhận tư vấn giải pháp: ${subjectText}`
    const contentText = readingContext.value ? `${baseText} (Gửi từ trang: ${readingContext.value})` : baseText

    const result = await submit('contact', {
      fullname: form.fullname,
      phone: form.phone,
      email: form.email,
      subject: subjectText,
      content: contentText,
    })

    success.value = result.message || 'Gửi yêu cầu tư vấn thành công. Kỹ sư Phúc Thịnh Solar sẽ liên hệ với bạn trong vòng 15 phút.'
    form.fullname = ''
    form.phone = ''
    form.email = ''
    form.notes = ''
  } catch (reason: any) {
    error.value = reason?.data?.statusMessage || reason?.statusMessage || reason?.message || 'Không thể gửi yêu cầu tư vấn. Vui lòng thử lại.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="isConsultationOpen"
    :title="'Đăng ký nhận tư vấn giải pháp'"
    :description="'Để lại thông tin, Phúc Thịnh Solar sẽ liên hệ tư vấn trong vòng 15 phút.'"
    :ui="{
      overlay: 'glass-overlay',
      content: 'shadow-[0_28px_70px_-24px_rgb(6_21_55/45%)] w-[min(880px,calc(100vw-32px))] max-w-none max-h-[calc(100dvh-24px)] overflow-y-auto rounded-btn border border-slate-200 bg-white shadow-2xl divide-y-0 text-navy-950',
      header: 'px-5 pt-5 pb-1 border-b-0 relative sm:px-10 sm:pt-10 sm:pb-3',
      title: 'pr-8 text-[clamp(21px,3vw,32px)] leading-tight font-black tracking-tight text-navy-950',
      description: 'mt-1.5 max-w-3xl text-[14.5px] leading-6 text-slate-600 sm:mt-2 sm:text-[16px] sm:leading-7',
      body: 'px-5 pt-2 pb-5 sm:px-10 sm:pt-3 sm:pb-10',
      close: 'text-slate-400 hover:text-slate-700 top-5 right-5',
    }"
  >
    <template #body>
      <div v-if="success" class="grid gap-5 py-4 text-center">
        <UIcon name="i-lucide-circle-check" class="mx-auto size-12 text-emerald-600" aria-hidden="true" />
        <div class="space-y-2">
          <h3 class="text-2xl font-bold text-navy-950">Đã tiếp nhận yêu cầu</h3>
          <p class="mx-auto max-w-xl text-base leading-7 text-slate-600">{{ success }}</p>
        </div>
        <UButton color="primary" size="xl" class="min-h-14 justify-center rounded-btn bg-gold-500 text-base font-bold text-navy-950" @click="closeConsultation">
          Đóng cửa sổ
        </UButton>
      </div>

      <form v-else class="grid grid-cols-[minmax(0,1fr)] gap-6 max-tb:gap-3" @submit.prevent="send">
        <div v-if="needSubject" class="flex items-center justify-between gap-3 pts-note-dark px-4 py-3 text-white">
          <div class="min-w-0">
            <span class="block text-xs text-white/70">Nội dung đang quan tâm</span>
            <strong class="block truncate text-sm font-extrabold text-gold-300">{{ needSubject }}</strong>
          </div>
          <UIcon name="i-lucide-sparkles" class="size-4 shrink-0 text-gold-400" aria-hidden="true" />
        </div>

        <div class="grid min-w-0 gap-x-6 gap-y-3 sm:grid-cols-2 sm:gap-y-5">
          <UFormField label="Họ và tên" name="fullname" required class="grid min-w-0 grid-cols-[minmax(0,1fr)] content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800' }">
            <UInput v-model.trim="form.fullname" name="fullname" required maxlength="100" autocomplete="name" placeholder="Nguyễn Văn A" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <UFormField label="Số điện thoại" name="phone" required class="grid min-w-0 grid-cols-[minmax(0,1fr)] content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800' }">
            <UInput v-model.trim="form.phone" name="phone" type="tel" required maxlength="20" autocomplete="tel" inputmode="tel" placeholder="090xxxxxxx" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <UFormField v-if="detailsOpen || !needSubject" label="Nhu cầu tư vấn" name="subject" class="grid min-w-0 grid-cols-[minmax(0,1fr)] content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800' }">
            <USelect v-model="form.subject" name="subject" aria-label="Nhu cầu tư vấn" :items="subjectItems" color="neutral" variant="none" class="w-full" :ui="selectUi" />
          </UFormField>
          <UFormField v-if="detailsOpen" label="Email" name="email" class="grid min-w-0 grid-cols-[minmax(0,1fr)] content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800' }">
            <UInput v-model.trim="form.email" name="email" type="email" maxlength="150" autocomplete="email" placeholder="email@example.com" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <UFormField v-if="detailsOpen" label="Ghi chú thêm" name="notes" class="grid min-w-0 grid-cols-[minmax(0,1fr)] content-start gap-2 sm:col-span-2" :ui="{ label: 'text-[15px] font-bold text-navy-800' }">
            <div class="mb-1.5 flex flex-wrap gap-1.5" aria-label="Gợi ý điền nhanh">
              <button
                v-for="chip in quickNoteChips"
                :key="chip"
                type="button"
                class="cursor-pointer rounded-chip border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600 transition-colors hover:border-gold-500 hover:bg-gold-50/50 hover:text-navy-900"
                @click="appendNote(chip)"
              >
                + {{ chip }}
              </button>
            </div>
            <UTextarea v-model.trim="form.notes" name="notes" maxlength="2000" :rows="3" placeholder="Ví dụ: diện tích mái, hóa đơn tiền điện hằng tháng, địa chỉ lắp đặt..." variant="none" class="w-full" :ui="textareaUi" />
          </UFormField>
          <FormMoreToggle v-if="!detailsOpen" class="sm:col-span-2" @click="detailsOpen = true" />
        </div>

        <p v-if="error" class="rounded-btn bg-red-50 p-2.5 text-xs font-semibold text-red-700" role="alert">
          {{ error }}
        </p>

        <div v-if="securityError" class="rounded-btn bg-red-50 p-3 text-sm font-semibold text-red-700" role="alert">
          <span>{{ securityError }}</span>
          <button v-if="!loadingSecurity" type="button" class="ml-2 font-bold underline" @click="loadSecurity(true)">
            Thử tải lại
          </button>
        </div>

        <div class="grid gap-3">
          <UButton type="submit" color="primary" size="xl" :loading="submitting" :disabled="submitting || loadingSecurity || !security" class="min-h-12 w-full justify-center sm:min-h-14 rounded-btn bg-gold-500 px-6 text-base font-extrabold text-navy-950">
            {{ submitting ? 'Đang gửi yêu cầu…' : 'Gửi yêu cầu tư vấn' }}
            <UIcon v-if="!submitting" name="i-lucide-arrow-right" class="size-5" aria-hidden="true" />
          </UButton>
          <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-xs text-slate-500">
            <span>Cần hỗ trợ gấp?</span>
            <a href="tel:0944555957" class="font-bold text-navy-900 no-underline hover:text-gold-700">Bán hàng: 0944 555 957</a>
            <span aria-hidden="true" class="max-sm:hidden">·</span>
            <a href="tel:0945536899" class="font-bold text-navy-900 no-underline hover:text-gold-700">Kỹ thuật: 0945 536 899</a>
          </div>
        </div>
      </form>
    </template>
  </UModal>
</template>
