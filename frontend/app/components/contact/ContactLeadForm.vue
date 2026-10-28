<script setup lang="ts">
const { ensureSecurity, submit } = useLeadForm()
const form = reactive({ fullname: '', phone: '', email: '', subject: '', content: '' })
const submitting = ref(false)
const success = ref('')
const error = ref('')

onMounted(() => void ensureSecurity().catch(() => {}))

async function send() {
  success.value = ''
  error.value = ''
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
  <form id="yeu-cau-tu-van" class="min-w-0 rounded-btn bg-slate-50 p-6 nav:p-8" @submit.prevent="send">
    <h2 class="mb-2 text-2xl font-black tracking-tight">Đăng ký nhận tư vấn</h2>
    <p class="mb-6 max-w-[62ch] leading-relaxed text-slate-500">Để lại thông tin và nhu cầu, Phúc Thịnh Solar sẽ liên hệ để tư vấn giải pháp phù hợp.</p>

    <div class="grid gap-4 tb:grid-cols-2">
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
        <textarea v-model.trim="form.content" required maxlength="5000" rows="5" placeholder="Ví dụ: nhu cầu chiếu sáng sân vườn, điện mặt trời cho gia đình..." class="w-full min-w-0 rounded-chip border border-gray-300 bg-white px-3 py-2 font-normal outline-none focus:border-gold-600" />
      </label>
    </div>

    <p v-if="success" class="mt-4 rounded-chip bg-emerald-50 p-3 text-sm font-semibold text-emerald-800" role="status">{{ success }}</p>
    <p v-if="error" class="mt-4 rounded-chip bg-red-50 p-3 text-sm font-semibold text-red-800" role="alert">{{ error }}</p>
    <button type="submit" :disabled="submitting" class="mt-5 min-h-11 rounded-chip bg-gold-500 px-6 font-extrabold text-navy-950 hover:bg-gold-600 disabled:cursor-wait disabled:opacity-60">
      {{ submitting ? 'Đang gửi…' : 'Gửi yêu cầu tư vấn' }}
    </button>
    <p class="mb-0 mt-3 text-xs leading-relaxed text-slate-500">Biểu mẫu dùng CSRF, reCAPTCHA v3 và giới hạn tần suất từ backend PHP hiện hành.</p>
  </form>
</template>
