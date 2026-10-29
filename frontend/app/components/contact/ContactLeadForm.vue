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
  <form id="yeu-cau-tu-van" class="min-w-0" @submit.prevent="send">
    <div class="grid gap-4 tb:grid-cols-2">
      <label class="grid gap-2 text-sm font-bold"><span>Họ và tên <span class="text-red-600">*</span></span>
        <input v-model.trim="form.fullname" required maxlength="100" autocomplete="name" placeholder="Nguyễn Văn A" class="h-[54px] rounded-chip border border-gray-300 bg-white px-4 font-normal outline-none focus:border-gold-600">
      </label>
      <label class="grid gap-2 text-sm font-bold"><span>Số điện thoại <span class="text-red-600">*</span></span>
        <input v-model.trim="form.phone" required maxlength="20" autocomplete="tel" inputmode="tel" placeholder="090xxxxxxx" class="h-[54px] rounded-chip border border-gray-300 bg-white px-4 font-normal outline-none focus:border-gold-600">
      </label>
      <label class="grid gap-2 text-sm font-bold"><span>Email <span class="font-normal text-slate-400">(không bắt buộc)</span></span>
        <input v-model.trim="form.email" type="email" maxlength="150" autocomplete="email" placeholder="abc@gmail.com" class="h-[54px] rounded-chip border border-gray-300 bg-white px-4 font-normal outline-none focus:border-gold-600">
      </label>
      <label class="grid gap-2 text-sm font-bold"><span>Anh/chị quan tâm đến <span class="font-normal text-slate-400">(không bắt buộc)</span></span>
        <select v-model="form.subject" class="h-[54px] rounded-chip border border-gray-300 bg-white px-4 font-normal outline-none focus:border-gold-600">
          <option value="">-- Chọn nhu cầu --</option>
          <option>Đèn năng lượng mặt trời</option>
          <option>Điện mặt trời</option>
          <option>Thiết bị (inverter, tấm pin, pin lưu trữ...)</option>
          <option>Hợp tác đại lý</option>
          <option>Khác</option>
        </select>
      </label>
      <label class="grid gap-2 text-sm font-bold tb:col-span-2"><span>Nhu cầu cần tư vấn <span class="text-red-600">*</span></span>
        <textarea v-model.trim="form.content" required maxlength="5000" rows="5" placeholder="Ví dụ: nhu cầu chiếu sáng sân vườn, điện mặt trời cho gia đình..." class="h-[156px] rounded-chip border border-gray-300 bg-white px-4 py-3 font-normal outline-none focus:border-gold-600" />
      </label>
    </div>

    <p v-if="success" class="mt-4 rounded-chip bg-emerald-50 p-3 text-sm font-semibold text-emerald-800" role="status">{{ success }}</p>
    <p v-if="error" class="mt-4 rounded-chip bg-red-50 p-3 text-sm font-semibold text-red-800" role="alert">{{ error }}</p>
    <button type="submit" :disabled="submitting" class="mt-5 h-[54px] w-full rounded-chip bg-gold-500 px-6 font-extrabold text-navy-950 hover:bg-gold-600 disabled:cursor-wait disabled:opacity-60 ph:w-[320px]">
      {{ submitting ? 'Đang gửi…' : 'Gửi yêu cầu tư vấn' }}
    </button>
  </form>
</template>
