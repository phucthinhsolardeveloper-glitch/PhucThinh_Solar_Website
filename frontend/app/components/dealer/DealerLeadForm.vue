<script setup lang="ts">
const { security, ensureSecurity, submit } = useLeadForm()
const form = reactive({ fullname: '', phone: '', email: '', address: '', company: '' })
const submitting = ref(false)
const success = ref('')
const error = ref('')

onMounted(() => void ensureSecurity().catch(() => {}))

async function send() {
  success.value = ''
  error.value = ''
  submitting.value = true
  try {
    const result = await submit('dealer', form)
    success.value = result.message
    form.fullname = ''
    form.phone = ''
    form.email = ''
    form.address = ''
    form.company = ''
  } catch (reason: any) {
    error.value = reason?.data?.statusMessage || reason?.statusMessage || reason?.message || 'Không thể gửi đăng ký đại lý.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <form class="grid grid-cols-1 gap-x-3.5 gap-y-4 ph:grid-cols-2" @submit.prevent="send">
    <label class="grid gap-1.5 text-sm font-bold text-white">Họ và tên
      <input v-model.trim="form.fullname" required maxlength="100" autocomplete="name" class="h-11 rounded-chip border border-white/20 bg-white px-3 font-normal text-navy-900 outline-none focus:border-gold-500">
    </label>
    <label class="grid gap-1.5 text-sm font-bold text-white">Số điện thoại
      <input v-model.trim="form.phone" required maxlength="20" autocomplete="tel" inputmode="tel" class="h-11 rounded-chip border border-white/20 bg-white px-3 font-normal text-navy-900 outline-none focus:border-gold-500">
    </label>
    <label class="grid gap-1.5 text-sm font-bold text-white">Email
      <input v-model.trim="form.email" required type="email" maxlength="150" autocomplete="email" class="h-11 rounded-chip border border-white/20 bg-white px-3 font-normal text-navy-900 outline-none focus:border-gold-500">
    </label>
    <label class="grid gap-1.5 text-sm font-bold text-white">Tỉnh/Thành phố
      <select v-model="form.address" required class="h-11 rounded-chip border border-white/20 bg-white px-3 font-normal text-navy-900 outline-none focus:border-gold-500">
        <option value="">Chọn tỉnh/thành</option>
        <option v-for="city in security?.cities ?? []" :key="city.id" :value="city.name">{{ city.name }}</option>
      </select>
    </label>
    <label class="grid gap-1.5 text-sm font-bold text-white ph:col-span-2">Doanh nghiệp hoặc cửa hàng
      <input v-model.trim="form.company" required maxlength="180" autocomplete="organization" class="h-11 rounded-chip border border-white/20 bg-white px-3 font-normal text-navy-900 outline-none focus:border-gold-500">
    </label>

    <p v-if="success" class="rounded-chip bg-emerald-50 p-3 text-sm font-semibold text-emerald-800 ph:col-span-2" role="status">{{ success }}</p>
    <p v-if="error" class="rounded-chip bg-red-50 p-3 text-sm font-semibold text-red-800 ph:col-span-2" role="alert">{{ error }}</p>
    <button type="submit" :disabled="submitting" class="min-h-11 w-full rounded-chip bg-gold-500 px-5 font-extrabold text-navy-950 hover:bg-gold-600 disabled:cursor-wait disabled:opacity-60 ph:col-span-2">
      {{ submitting ? 'Đang gửi…' : 'Đăng ký ngay' }}
    </button>
  </form>
</template>
