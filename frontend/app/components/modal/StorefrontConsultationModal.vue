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
const inputUi = {
  base: 'h-14 w-full rounded-btn border border-slate-300 bg-white px-4 text-base font-normal text-navy-950 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
}
const selectUi = {
  base: 'h-14 w-full rounded-btn border border-slate-300 bg-white px-4 text-base font-normal text-navy-950 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
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
      content: 'w-[min(880px,calc(100vw-32px))] max-w-none max-h-[calc(100dvh-24px)] overflow-y-auto rounded-btn border border-slate-200 bg-white shadow-2xl divide-y-0 text-navy-950',
      header: 'px-6 pt-7 pb-3 border-b-0 relative sm:px-10 sm:pt-10',
      title: 'text-[clamp(24px,3vw,32px)] font-black tracking-tight text-navy-950',
      description: 'mt-2 max-w-3xl text-[16px] leading-7 text-slate-600',
      body: 'px-6 pt-3 pb-7 sm:px-10 sm:pb-10',
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

      <form v-else class="grid gap-7" @submit.prevent="send">
        <div class="grid min-w-0 gap-x-6 gap-y-5 sm:grid-cols-2">
          <UFormField label="Họ và tên" name="fullname" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800' }">
            <UInput v-model.trim="form.fullname" name="fullname" required maxlength="100" autocomplete="name" placeholder="Nguyễn Văn A" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <UFormField label="Số điện thoại" name="phone" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800' }">
            <UInput v-model.trim="form.phone" name="phone" type="tel" required maxlength="20" autocomplete="tel" inputmode="tel" placeholder="090xxxxxxx" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <UFormField label="Email" hint="Không bắt buộc" name="email" class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800', hint: 'text-sm font-normal text-slate-500' }">
            <UInput v-model.trim="form.email" name="email" type="email" maxlength="150" autocomplete="email" placeholder="email@example.com" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <UFormField label="Nhu cầu tư vấn" name="subject" class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800' }">
            <USelect v-model="form.subject" name="subject" aria-label="Nhu cầu tư vấn" :items="predefinedSubjects" color="neutral" variant="none" class="w-full" :ui="selectUi" />
          </UFormField>
          <UFormField label="Ghi chú thêm" hint="Không bắt buộc" name="notes" class="grid min-w-0 content-start gap-2 sm:col-span-2" :ui="{ label: 'text-[15px] font-bold text-navy-800', hint: 'text-sm font-normal text-slate-500' }">
            <UTextarea v-model.trim="form.notes" name="notes" maxlength="2000" :rows="4" placeholder="Ví dụ: diện tích mái, hóa đơn tiền điện hằng tháng, địa chỉ lắp đặt..." variant="none" class="w-full" :ui="textareaUi" />
          </UFormField>
        </div>

        <p v-if="error" class="rounded-[4px] bg-red-50 p-2.5 text-xs font-semibold text-red-700" role="alert">
          {{ error }}
        </p>

        <div v-if="securityError" class="rounded-[4px] bg-red-50 p-3 text-sm font-semibold text-red-700" role="alert">
          <span>{{ securityError }}</span>
          <button v-if="!loadingSecurity" type="button" class="ml-2 font-bold underline" @click="loadSecurity(true)">
            Thử tải lại
          </button>
        </div>

        <UButton type="submit" color="primary" size="xl" :loading="submitting" :disabled="submitting || loadingSecurity || !security" class="min-h-14 w-full justify-center rounded-btn bg-gold-500 px-6 text-base font-extrabold text-navy-950">
          {{ submitting ? 'Đang gửi yêu cầu…' : 'Gửi yêu cầu tư vấn' }}
          <UIcon v-if="!submitting" name="i-lucide-arrow-right" class="size-5" aria-hidden="true" />
        </UButton>

      </form>
    </template>
  </UModal>
</template>
