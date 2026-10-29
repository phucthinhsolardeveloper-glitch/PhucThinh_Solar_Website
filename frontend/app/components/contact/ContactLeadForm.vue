<script setup lang="ts">
const { security, loadingSecurity, ensureSecurity, refreshSecurity, submit } = useLeadForm()
const form = reactive({ fullname: '', phone: '', email: '', subject: '', content: '' })
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
    securityError.value = 'Không thể tải phiên bảo mật. Vui lòng thử tải lại trước khi gửi yêu cầu.'
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
  <form id="yeu-cau-tu-van" class="grid min-w-0 grid-cols-1 gap-7 rounded-[4px] bg-slate-50 p-6 nav:grid-cols-[minmax(245px,315px)_minmax(0,1fr)] nav:grid-rows-[auto_1fr] nav:gap-x-[clamp(48px,6vw,92px)] nav:gap-y-0 nav:p-[clamp(38px,4vw,58px)_clamp(26px,4vw,64px)_clamp(44px,5vw,68px)]" @submit.prevent="send">
    <div class="min-w-0 nav:row-span-2">
      <h2 class="mb-3 text-[clamp(30px,2.7vw,43px)] font-black leading-tight tracking-tight">Đăng ký nhận tư vấn</h2>
      <p class="mb-0 leading-relaxed text-slate-500">Để lại thông tin và nhu cầu, Phúc Thịnh Solar sẽ liên hệ để tư vấn giải pháp phù hợp.</p>
    </div>

    <div class="grid gap-4 tb:grid-cols-2 nav:col-start-2 nav:row-span-2">
      <label class="grid min-w-0 gap-1.5 text-sm font-bold">Họ và tên <span class="text-red-600">*</span>
        <input v-model.trim="form.fullname" required maxlength="100" autocomplete="name" placeholder="Nguyễn Văn A" class="h-11 w-full min-w-0 rounded-chip border border-gray-300 bg-white px-3 font-normal outline-none focus:border-gold-600">
      </label>
      <label class="grid min-w-0 gap-1.5 text-sm font-bold">Số điện thoại <span class="text-red-600">*</span>
        <input v-model.trim="form.phone" required maxlength="20" autocomplete="tel" inputmode="tel" placeholder="090xxxxxxx" class="h-11 w-full min-w-0 rounded-chip border border-gray-300 bg-white px-3 font-normal outline-none focus:border-gold-600">
      </label>
      <label class="grid min-w-0 gap-1.5 text-sm font-bold">Email <span class="font-normal text-slate-400">(không bắt buộc)</span>
        <input v-model.trim="form.email" type="email" maxlength="150" autocomplete="email" placeholder="abc@gmail.com" class="h-11 w-full min-w-0 rounded-chip border border-gray-300 bg-white px-3 font-normal outline-none focus:border-gold-600">
      </label>
      <label class="grid min-w-0 gap-1.5 text-sm font-bold">Anh/chị quan tâm đến
        <span class="font-normal text-slate-400">(không bắt buộc)</span>
        <select v-model="form.subject" class="h-11 w-full min-w-0 rounded-chip border border-gray-300 bg-white px-3 font-normal outline-none focus:border-gold-600">
          <option value="">-- Chọn nhu cầu --</option>
          <option>Đèn năng lượng mặt trời</option>
          <option>Điện mặt trời</option>
          <option>Thiết bị (inverter, tấm pin, pin lưu trữ...)</option>
          <option>Hợp tác đại lý</option>
          <option>Khác</option>
        </select>
      </label>
      <label class="grid min-w-0 gap-1.5 text-sm font-bold tb:col-span-2">Nhu cầu cần tư vấn <span class="text-red-600">*</span>
        <textarea v-model.trim="form.content" required maxlength="5000" rows="5" placeholder="Ví dụ: nhu cầu chiếu sáng sân vườn, điện mặt trời cho gia đình..." class="min-h-[156px] w-full min-w-0 rounded-chip border border-gray-300 bg-white px-3 py-2 font-normal outline-none focus:border-gold-600" />
      </label>

      <p v-if="success" class="rounded-chip bg-emerald-50 p-3 text-sm font-semibold text-emerald-800 tb:col-span-2" role="status">{{ success }}</p>
      <p v-if="error" class="rounded-chip bg-red-50 p-3 text-sm font-semibold text-red-800 tb:col-span-2" role="alert">{{ error }}</p>
      <div v-if="securityError" class="rounded-chip bg-red-50 p-3 text-sm font-semibold text-red-800 tb:col-span-2" role="alert">
        <p class="mb-2">{{ securityError }}</p>
        <button v-if="!loadingSecurity" type="button" class="font-bold underline" @click="loadSecurity(true)">Thử tải lại</button>
      </div>
      <p v-else-if="loadingSecurity" class="text-sm text-slate-500 tb:col-span-2" role="status">Đang tải phiên bảo mật…</p>
      <button type="submit" :disabled="submitting || loadingSecurity || !security" class="min-h-[54px] w-full rounded-chip bg-gold-500 px-6 font-extrabold text-navy-950 hover:bg-gold-600 disabled:cursor-wait disabled:opacity-60 nav:w-80 tb:col-span-2">
        {{ submitting ? 'Đang gửi…' : 'Gửi yêu cầu tư vấn' }}
      </button>
      <p class="mb-0 text-xs leading-relaxed text-slate-500 tb:col-span-2">Biểu mẫu dùng CSRF, reCAPTCHA v3 và giới hạn tần suất từ backend PHP hiện hành.</p>
    </div>
  </form>
</template>
