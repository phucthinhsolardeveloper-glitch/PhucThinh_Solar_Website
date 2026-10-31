<script setup lang="ts">
const { security, loadingSecurity, ensureSecurity, refreshSecurity, submit } = useLeadForm()
const form = reactive({ fullname: '', phone: '', email: '', subject: '', content: '' })
const subjectOptions = [
  'Đèn năng lượng mặt trời',
  'Điện mặt trời',
  'Thiết bị (inverter, tấm pin, pin lưu trữ...)',
  'Hợp tác đại lý',
  'Khác',
]
const submitting = ref(false)
const success = ref('')
const error = ref('')
const securityError = ref('')

async function loadSecurity(refresh = false) {
  securityError.value = ''
  try {
    if (refresh) await refreshSecurity()
    else await ensureSecurity()
  } catch {
    securityError.value = 'Không thể chuẩn bị biểu mẫu. Vui lòng thử tải lại.'
  }
}

onMounted(() => void loadSecurity())

async function send() {
  success.value = ''
  error.value = ''
  if (loadingSecurity.value || !security.value) {
    if (!loadingSecurity.value) await loadSecurity()
    return
  }
  submitting.value = true
  try {
    const result = await submit('contact', form)
    success.value = result.message
    form.fullname = ''
    form.phone = ''
    form.email = ''
    form.subject = ''
    form.content = ''
  } catch (reason: any) {
    error.value = reason?.data?.statusMessage || reason?.statusMessage || reason?.message || 'Không thể gửi yêu cầu tư vấn.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <form id="yeu-cau-tu-van" class="grid min-w-0 gap-8 rounded-chip bg-slate-50 px-5 py-9 tb:px-8 lg:grid-cols-[minmax(230px,.75fr)_minmax(0,1.25fr)] lg:gap-12 lg:px-12 lg:py-12" @submit.prevent="send">
    <div class="min-w-0">
      <h2 class="m-0 max-w-md text-[clamp(28px,2.7vw,40px)] font-bold leading-tight tracking-tight text-navy-800">Đăng ký nhận tư vấn</h2>
      <p class="mb-0 mt-4 max-w-md text-base leading-7 text-slate-600">Cho chúng tôi biết nhu cầu của bạn. Đội ngũ Phúc Thịnh Solar sẽ liên hệ để cùng chọn giải pháp phù hợp.</p>
    </div>

    <div class="grid min-w-0 gap-5 tb:grid-cols-2">
      <UFormField label="Họ và tên" name="fullname" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-800' }">
        <UInput v-model.trim="form.fullname" name="fullname" required maxlength="100" autocomplete="name" placeholder="Nguyễn Văn A" variant="none" class="w-full" :ui="{ base: 'h-12 w-full min-w-0 rounded-chip border border-slate-300 bg-white px-3 text-base font-normal text-navy-800 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/30' }" />
      </UFormField>
      <UFormField label="Số điện thoại" name="phone" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-800' }">
        <UInput v-model.trim="form.phone" name="phone" type="tel" required maxlength="20" autocomplete="tel" inputmode="tel" placeholder="090xxxxxxx" variant="none" class="w-full" :ui="{ base: 'h-12 w-full min-w-0 rounded-chip border border-slate-300 bg-white px-3 text-base font-normal text-navy-800 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/30' }" />
      </UFormField>
      <UFormField label="Email" hint="Không bắt buộc" name="email" class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-800', hint: 'text-xs font-normal text-slate-500' }">
        <UInput v-model.trim="form.email" name="email" type="email" maxlength="150" autocomplete="email" placeholder="abc@gmail.com" variant="none" class="w-full" :ui="{ base: 'h-12 w-full min-w-0 rounded-chip border border-slate-300 bg-white px-3 text-base font-normal text-navy-800 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/30' }" />
      </UFormField>
      <UFormField label="Nhu cầu quan tâm" hint="Không bắt buộc" name="subject" class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-800', hint: 'text-xs font-normal text-slate-500' }">
        <USelect v-model="form.subject" name="subject" aria-label="Nhu cầu quan tâm" :items="subjectOptions" placeholder="-- Chọn nhu cầu --" color="neutral" variant="none" class="w-full" :ui="{ base: 'h-12 w-full min-w-0 rounded-chip border border-slate-300 bg-white px-3 text-base font-normal text-navy-800 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/30', placeholder: 'text-slate-400' }" />
      </UFormField>
      <UFormField label="Chi tiết nhu cầu tư vấn" name="content" required class="grid min-w-0 content-start gap-2 tb:col-span-2" :ui="{ label: 'text-sm font-semibold text-navy-800' }">
        <UTextarea v-model.trim="form.content" name="content" required maxlength="5000" :rows="5" placeholder="Ví dụ: cần tư vấn điện mặt trời cho gia đình, mức sử dụng điện hàng tháng..." variant="none" class="w-full" :ui="{ base: 'min-h-[156px] w-full min-w-0 rounded-chip border border-slate-300 bg-white px-3 py-3 text-base font-normal text-navy-800 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/30' }" />
      </UFormField>

      <p v-if="success" class="rounded-chip bg-emerald-50 p-3 text-sm font-semibold text-emerald-800 tb:col-span-2" role="status">{{ success }}</p>
      <p v-if="error" class="rounded-chip bg-red-50 p-3 text-sm font-semibold text-red-800 tb:col-span-2" role="alert">{{ error }}</p>
      <div v-if="securityError" class="rounded-chip bg-red-50 p-3 text-sm font-semibold text-red-800 tb:col-span-2" role="alert">
        <p class="mb-2">{{ securityError }}</p>
        <button v-if="!loadingSecurity" type="button" class="font-bold underline" @click="loadSecurity(true)">Thử tải lại</button>
      </div>
      <p v-else-if="loadingSecurity" class="text-sm text-slate-500 tb:col-span-2" role="status">Đang chuẩn bị biểu mẫu…</p>
      <UButton type="submit" color="primary" size="xl" :loading="submitting" :disabled="submitting || loadingSecurity || !security" class="inline-flex min-h-[52px] w-full items-center justify-center gap-2 rounded-chip bg-gold-500 px-6 font-bold text-navy-950 transition-colors hover:bg-gold-400 disabled:cursor-wait disabled:opacity-60 tb:col-span-2">
        {{ submitting ? 'Đang gửi…' : 'Gửi yêu cầu tư vấn' }}
        <UIcon v-if="!submitting" name="i-lucide-arrow-right" class="size-5" aria-hidden="true" />
      </UButton>
    </div>
  </form>
</template>
