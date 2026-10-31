<script setup lang="ts">
import type { PublicProductNeed } from '#shared/contracts/public-product-extras'

const props = defineProps<{
  productId: number
  productName: string
  profileKey: string
  needs: PublicProductNeed[]
}>()

const { security, ensureSecurity, submit } = useLeadForm()
const form = reactive({
  fullname: '',
  phone: '',
  email: '',
  city: 0,
  ward: 0,
  loaihinh: 'Người dùng cá nhân',
  needCode: '',
  quantity: '',
  details: '',
})
const wards = ref<Array<{ id: number; name: string }>>([])
const loadingWards = ref(false)
const submitting = ref(false)
const success = ref('')
const error = ref('')

onMounted(() => void ensureSecurity().catch(() => {}))

watch(() => form.city, async (city) => {
  wards.value = []
  form.ward = 0
  if (!city) return
  loadingWards.value = true
  try {
    const payload: any = await $fetch('/_migration/checkout-wards', { query: { city: String(city) } })
    wards.value = Array.isArray(payload?.wards) ? payload.wards : []
  } catch {
    wards.value = []
  } finally {
    loadingWards.value = false
  }
})

async function send() {
  success.value = ''
  error.value = ''
  if ((form.city && !form.ward) || (!form.city && form.ward)) {
    error.value = 'Nếu chọn tỉnh/thành phố, vui lòng chọn cả phường/xã.'
    return
  }
  submitting.value = true
  try {
    const result = await submit('product', {
      fullname: form.fullname,
      phone: form.phone,
      email: form.email,
      loaihinh: form.loaihinh,
      congsuat: form.quantity,
      product_id: props.productId,
      profile_key: props.profileKey,
      need_code: form.needCode,
      details: form.details,
      city_id: form.city || '',
      ward_id: form.ward || '',
    })
    success.value = result.message
    form.fullname = ''
    form.phone = ''
    form.email = ''
    form.city = 0
    form.ward = 0
    form.loaihinh = 'Người dùng cá nhân'
    form.needCode = ''
    form.quantity = ''
    form.details = ''
  } catch (reason: any) {
    error.value = reason?.data?.statusMessage || reason?.statusMessage || reason?.message || 'Không thể gửi yêu cầu tư vấn.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section id="pts-pdp-consultation" class="scroll-mt-24 border-t border-gray-200 py-12 tb:py-16" aria-labelledby="product-consultation-title">
    <div class="grid min-w-0 gap-8 bg-slate-50 p-6 lg:grid-cols-[minmax(280px,.85fr)_minmax(0,1.35fr)] lg:gap-12 lg:p-8">
      <div class="min-w-0">
        <h2 id="product-consultation-title" class="mb-3 text-[clamp(24px,3vw,34px)] font-black tracking-tight text-navy-800">Nhận tư vấn cho sản phẩm này</h2>
        <p class="leading-7 text-slate-600">Đội ngũ Phúc Thịnh Solar sẽ tư vấn giải pháp phù hợp với nhu cầu thực tế của bạn.</p>
        <strong class="block text-sm text-gold-700">Sản phẩm đang chọn: <span class="text-navy-800">{{ productName }}</span></strong>
      </div>

      <form class="min-w-0" @submit.prevent="send">
        <div class="grid gap-4 tb:grid-cols-2">
          <label class="grid gap-1.5 text-sm font-bold">Họ và tên <span class="text-red-600">*</span>
            <input v-model.trim="form.fullname" required maxlength="100" autocomplete="name" class="h-11 w-full min-w-0 rounded-chip border border-gray-300 px-3 font-normal">
          </label>
          <label class="grid gap-1.5 text-sm font-bold">Số điện thoại <span class="text-red-600">*</span>
            <input v-model.trim="form.phone" required maxlength="20" autocomplete="tel" inputmode="tel" class="h-11 w-full min-w-0 rounded-chip border border-gray-300 px-3 font-normal">
          </label>
          <label class="grid gap-1.5 text-sm font-bold">Email
            <input v-model.trim="form.email" type="email" maxlength="150" autocomplete="email" class="h-11 w-full min-w-0 rounded-chip border border-gray-300 px-3 font-normal">
          </label>
          <label class="grid gap-1.5 text-sm font-bold">Tỉnh/Thành phố
            <select v-model.number="form.city" class="h-11 w-full min-w-0 rounded-chip border border-gray-300 bg-white px-3 font-normal">
              <option :value="0">Chọn tỉnh/thành</option>
              <option v-for="city in security?.cities ?? []" :key="city.id" :value="city.id">{{ city.name }}</option>
            </select>
          </label>
          <label class="grid gap-1.5 text-sm font-bold">Phường/Xã
            <select v-model.number="form.ward" :disabled="!form.city || loadingWards" class="h-11 w-full min-w-0 rounded-chip border border-gray-300 bg-white px-3 font-normal disabled:bg-slate-50">
              <option :value="0">{{ loadingWards ? 'Đang tải…' : 'Chọn phường/xã' }}</option>
              <option v-for="ward in wards" :key="ward.id" :value="ward.id">{{ ward.name }}</option>
            </select>
          </label>
          <fieldset class="m-0 grid gap-3 border-0 p-0 tb:col-span-2 tb:grid-cols-2">
            <legend class="mb-1 w-full text-sm font-bold">Bạn là...</legend>
            <label class="flex min-h-10 items-center gap-2 text-sm font-normal">
              <input v-model="form.loaihinh" type="radio" value="Người dùng cá nhân" class="h-4 w-4 accent-gold-600">
              Người dùng cá nhân
            </label>
            <label class="flex min-h-10 items-center gap-2 text-sm font-normal">
              <input v-model="form.loaihinh" type="radio" value="Đại lý/Nhà phân phối, bán lẻ/Nhà thầu…" class="h-4 w-4 accent-gold-600">
              Đại lý/Nhà phân phối, bán lẻ/Nhà thầu…
            </label>
          </fieldset>
          <label class="grid gap-1.5 text-sm font-bold">Nhu cầu cần tư vấn
            <select v-model="form.needCode" class="h-11 w-full min-w-0 rounded-chip border border-gray-300 bg-white px-3 font-normal">
              <option value="">Chọn nhu cầu</option>
              <option v-for="need in needs" :key="need.code" :value="need.code">{{ need.label }}</option>
            </select>
          </label>
          <label class="grid gap-1.5 text-sm font-bold">Số lượng dự kiến
            <input v-model.trim="form.quantity" maxlength="255" placeholder="Ví dụ: 2 bộ" class="h-11 w-full min-w-0 rounded-chip border border-gray-300 px-3 font-normal">
          </label>
          <label class="grid gap-1.5 text-sm font-bold tb:col-span-2">Thông tin cần tư vấn thêm
          <textarea v-model.trim="form.details" maxlength="1000" rows="4" placeholder="Mô tả vị trí lắp đặt, tải sử dụng hoặc yêu cầu của bạn" class="w-full min-w-0 rounded-chip border border-gray-300 px-3 py-2 font-normal" />
          </label>
        </div>

        <p v-if="success" class="mt-4 rounded-chip bg-emerald-50 p-3 text-sm font-semibold text-emerald-800" role="status">{{ success }}</p>
        <p v-if="error" class="mt-4 rounded-chip bg-red-50 p-3 text-sm font-semibold text-red-800" role="alert">{{ error }}</p>
        <div class="mt-5 flex justify-center">
          <button type="submit" :disabled="submitting" class="min-h-11 rounded-chip bg-gold-500 px-6 font-extrabold text-navy-950 disabled:cursor-wait disabled:opacity-60">
            {{ submitting ? 'Đang gửi…' : 'Gửi yêu cầu tư vấn' }}
          </button>
        </div>
      </form>
    </div>
  </section>
</template>
