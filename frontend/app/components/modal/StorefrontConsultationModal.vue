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

async function loadSecurity(refresh = false) {
  securityError.value = ''
  try {
    if (refresh) await refreshSecurity()
    else await ensureSecurity()
  } catch {
    securityError.value = 'Không thể tải phiên bảo mật. Vui lòng bấm thử lại trước khi gửi yêu cầu.'
  }
}

watch(
  () => isConsultationOpen.value,
  (open) => {
    if (open) {
      success.value = ''
      error.value = ''
      if (consultationSubject.value) {
        form.subject = consultationSubject.value
      } else if (!form.subject) {
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
    const contentText = noteText ? (subjectText ? `[${subjectText}] ${noteText}` : noteText) : `Đăng ký nhận tư vấn giải pháp: ${subjectText}`

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
    :description="'Để lại thông tin, kỹ sư Phúc Thịnh Solar sẽ liên hệ tư vấn trong vòng 15 phút.'"
    :ui="{
      overlay: 'bg-navy-950/70 backdrop-blur-md',
      content: 'w-[calc(100vw-2rem)] max-w-lg rounded-[6px] border border-slate-200 bg-white shadow-2xl divide-y-0 text-navy-950',
      header: 'px-6 pt-6 pb-2 border-b-0 relative',
      title: 'text-[20px] font-black tracking-tight text-navy-900',
      description: 'text-sm text-slate-500 mt-1 leading-relaxed',
      body: 'px-6 pt-2 pb-6',
      close: 'text-slate-400 hover:text-slate-700 top-5 right-5',
    }"
  >
    <template #body>
      <div v-if="success" class="space-y-4 py-3 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <div class="space-y-1">
          <h3 class="text-lg font-bold text-navy-900">Tiếp nhận yêu cầu thành công</h3>
          <p class="text-sm leading-relaxed text-slate-600">{{ success }}</p>
        </div>
        <div class="pt-2">
          <button
            type="button"
            class="min-h-[42px] w-full rounded-[4px] bg-[#c58a2d] px-5 text-sm font-bold text-white transition-colors hover:bg-[#b07824]"
            @click="closeConsultation"
          >
            Đóng cửa sổ
          </button>
        </div>
      </div>

      <form v-else class="space-y-4" @submit.prevent="send">
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
          <label class="grid gap-1 text-xs font-bold uppercase tracking-wider text-navy-900">
            <span>Họ và tên <span class="text-red-600">*</span></span>
            <input
              v-model.trim="form.fullname"
              required
              maxlength="100"
              autocomplete="name"
              placeholder="Nguyễn Văn A"
              class="h-10 w-full rounded-[4px] border border-slate-300 bg-white px-3 font-normal text-sm text-navy-950 outline-none transition-colors placeholder:text-slate-400 focus:border-[#c58a2d] focus:ring-1 focus:ring-[#c58a2d]"
            >
          </label>

          <label class="grid gap-1 text-xs font-bold uppercase tracking-wider text-navy-900">
            <span>Số điện thoại <span class="text-red-600">*</span></span>
            <input
              v-model.trim="form.phone"
              required
              maxlength="20"
              autocomplete="tel"
              inputmode="tel"
              placeholder="090xxxxxxx"
              class="h-10 w-full rounded-[4px] border border-slate-300 bg-white px-3 font-normal text-sm text-navy-950 outline-none transition-colors placeholder:text-slate-400 focus:border-[#c58a2d] focus:ring-1 focus:ring-[#c58a2d]"
            >
          </label>
        </div>

        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
          <label class="grid gap-1 text-xs font-bold uppercase tracking-wider text-navy-900">
            <span>Email <span class="font-normal lowercase text-slate-400">(không bắt buộc)</span></span>
            <input
              v-model.trim="form.email"
              type="email"
              maxlength="150"
              autocomplete="email"
              placeholder="email@example.com"
              class="h-10 w-full rounded-[4px] border border-slate-300 bg-white px-3 font-normal text-sm text-navy-950 outline-none transition-colors placeholder:text-slate-400 focus:border-[#c58a2d] focus:ring-1 focus:ring-[#c58a2d]"
            >
          </label>

          <label class="grid gap-1 text-xs font-bold uppercase tracking-wider text-navy-900">
            <span>Nhu cầu tư vấn</span>
            <select
              v-model="form.subject"
              class="h-10 w-full rounded-[4px] border border-slate-300 bg-white px-3 font-normal text-sm text-navy-950 outline-none transition-colors focus:border-[#c58a2d] focus:ring-1 focus:ring-[#c58a2d]"
            >
              <option v-for="item in predefinedSubjects" :key="item" :value="item">{{ item }}</option>
              <option v-if="form.subject && !predefinedSubjects.includes(form.subject)" :value="form.subject">
                {{ form.subject }}
              </option>
            </select>
          </label>
        </div>

        <label class="grid gap-1 text-xs font-bold uppercase tracking-wider text-navy-900">
          <span>Ghi chú thêm <span class="font-normal lowercase text-slate-400">(không bắt buộc)</span></span>
          <textarea
            v-model.trim="form.notes"
            rows="3"
            maxlength="2000"
            placeholder="Ví dụ: diện tích mái, hóa đơn tiền điện hàng tháng, địa chỉ lắp đặt..."
            class="w-full rounded-[4px] border border-slate-300 bg-white p-3 font-normal text-sm text-navy-950 outline-none transition-colors placeholder:text-slate-400 focus:border-[#c58a2d] focus:ring-1 focus:ring-[#c58a2d]"
          />
        </label>

        <p v-if="error" class="rounded-[4px] bg-red-50 p-2.5 text-xs font-semibold text-red-700" role="alert">
          {{ error }}
        </p>

        <div v-if="securityError" class="rounded-[4px] bg-red-50 p-2.5 text-xs font-semibold text-red-700" role="alert">
          <span>{{ securityError }}</span>
          <button v-if="!loadingSecurity" type="button" class="ml-2 font-bold underline" @click="loadSecurity(true)">
            Thử tải lại
          </button>
        </div>

        <div class="pt-1">
          <button
            type="submit"
            :disabled="submitting || loadingSecurity || !security"
            class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-[4px] bg-[#c58a2d] px-6 text-sm font-extrabold text-white transition-colors hover:bg-[#b07824] disabled:cursor-wait disabled:opacity-60"
          >
            <svg
              v-if="submitting"
              class="h-4 w-4 animate-spin text-white"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            <span>{{ submitting ? 'Đang gửi yêu cầu…' : 'Gửi yêu cầu' }}</span>
          </button>
        </div>

        <p class="text-center text-[11px] text-slate-400">
          Thông tin của bạn được bảo mật tuyệt đối theo chính sách bảo mật của Phúc Thịnh Solar.
        </p>
      </form>
    </template>
  </UModal>
</template>
