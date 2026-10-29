<script setup lang="ts">
const { security, ensureSecurity, refreshSecurity, submit } = useLeadForm()
const form = reactive({ fullname: '', phone: '', email: '', address: '', company: '' })
const submitting = ref(false)
const securityLoading = ref(true)
const success = ref('')
const error = ref('')

async function loadSecurity(refresh = false) {
  securityLoading.value = true
  error.value = ''
  try {
    const payload = refresh ? await refreshSecurity() : await ensureSecurity()
    if (!payload.cities.length) error.value = 'Chưa có danh sách tỉnh/thành phố để gửi đăng ký. Vui lòng thử tải lại.'
  } catch {
    error.value = 'Không thể tải phiên bảo mật và danh sách tỉnh/thành phố. Vui lòng thử tải lại.'
  } finally {
    securityLoading.value = false
  }
}

onMounted(() => void loadSecurity())

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
  <form class="grid grid-cols-1 gap-x-3.5 gap-y-4 min-[640px]:grid-cols-2" @submit.prevent="send">
    <label class="grid gap-1.5 text-sm font-bold text-white">Họ và tên
      <input v-model.trim="form.fullname" required maxlength="100" autocomplete="name" placeholder="Nguyễn Văn A" class="h-12 rounded-chip border border-white/20 bg-white px-3 font-normal text-navy-900 outline-none focus:border-gold-500">
    </label>
    <label class="grid gap-1.5 text-sm font-bold text-white">Số điện thoại
      <input v-model.trim="form.phone" required maxlength="20" autocomplete="tel" inputmode="tel" placeholder="09xx xxx xxx" class="h-12 rounded-chip border border-white/20 bg-white px-3 font-normal text-navy-900 outline-none focus:border-gold-500">
    </label>
    <label class="grid gap-1.5 text-sm font-bold text-white">Email
      <input v-model.trim="form.email" required type="email" maxlength="150" autocomplete="email" placeholder="email@doanhnghiep.vn" class="h-12 rounded-chip border border-white/20 bg-white px-3 font-normal text-navy-900 outline-none focus:border-gold-500">
    </label>
    <label class="grid gap-1.5 text-sm font-bold text-white">Tỉnh/Thành phố
      <select v-model="form.address" required class="h-12 rounded-chip border border-white/20 bg-white px-3 font-normal text-navy-900 outline-none focus:border-gold-500">
        <option value="">{{ securityLoading ? 'Đang tải tỉnh/thành…' : 'Chọn tỉnh/thành' }}</option>
        <option v-for="city in security?.cities ?? []" :key="city.id" :value="city.name">{{ city.name }}</option>
      </select>
    </label>
    <label class="grid gap-1.5 text-sm font-bold text-white min-[640px]:col-span-2">Doanh nghiệp hoặc cửa hàng
      <input v-model.trim="form.company" required maxlength="180" autocomplete="organization" placeholder="Tên đơn vị của bạn" class="h-12 rounded-chip border border-white/20 bg-white px-3 font-normal text-navy-900 outline-none focus:border-gold-500">
    </label>

    <p v-if="success" class="rounded-chip bg-emerald-50 p-3 text-sm font-semibold text-emerald-800 min-[640px]:col-span-2" role="status">{{ success }}</p>
    <div v-if="error" class="rounded-chip bg-red-50 p-3 text-sm font-semibold text-red-800 min-[640px]:col-span-2" role="alert">
      <p class="mb-2">{{ error }}</p>
      <button v-if="!securityLoading" type="button" class="font-bold underline" @click="loadSecurity(true)">Thử tải lại</button>
    </div>
    <button type="submit" :disabled="submitting || securityLoading || !security?.cities.length" class="min-h-[50px] w-full rounded-chip bg-gold-500 px-5 font-extrabold text-navy-950 hover:bg-gold-600 disabled:cursor-not-allowed disabled:opacity-60 min-[640px]:col-span-2">
      {{ submitting ? 'Đang gửi…' : 'Đăng ký ngay' }}
    </button>
    <small class="block text-xs leading-relaxed text-blue-200 min-[640px]:col-span-2">CSRF, reCAPTCHA v3 và rate-limit vẫn được PHP xác minh trước khi lưu yêu cầu.</small>
  </form>
</template>
