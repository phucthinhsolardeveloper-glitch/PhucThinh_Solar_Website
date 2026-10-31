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
const cityOptions = computed(() => [
  { label: 'Chọn tỉnh/thành', value: 0 },
  ...(security.value?.cities ?? []).map((city) => ({ label: city.name, value: city.id })),
])
const wardOptions = computed(() => [
  { label: loadingWards.value ? 'Đang tải…' : 'Chọn phường/xã', value: 0 },
  ...wards.value.map((ward) => ({ label: ward.name, value: ward.id })),
])
const needOptions = computed(() => props.needs.map((need) => ({ label: need.label, value: need.code })))
const customerTypeOptions = [
  { label: 'Người dùng cá nhân', value: 'Người dùng cá nhân' },
  { label: 'Đại lý/Nhà phân phối, bán lẻ/Nhà thầu…', value: 'Đại lý/Nhà phân phối, bán lẻ/Nhà thầu…' },
]
const inputUi = {
  base: 'h-11 w-full min-w-0 rounded-[4px] border border-slate-300 bg-white px-3 text-base font-normal text-navy-950 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
}
const selectUi = {
  base: 'h-11 w-full min-w-0 rounded-[4px] border border-slate-300 bg-white px-3 text-base font-normal text-navy-950 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
}
const textareaUi = {
  base: 'min-h-[112px] w-full min-w-0 rounded-[4px] border border-slate-300 bg-white px-3 py-2 text-base font-normal text-navy-950 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
}
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
          <UFormField label="Họ và tên" name="fullname" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-900' }">
            <UInput v-model.trim="form.fullname" name="fullname" required maxlength="100" autocomplete="name" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <UFormField label="Số điện thoại" name="phone" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-900' }">
            <UInput v-model.trim="form.phone" name="phone" type="tel" required maxlength="20" autocomplete="tel" inputmode="tel" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <UFormField label="Email" name="email" class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-900' }">
            <UInput v-model.trim="form.email" name="email" type="email" maxlength="150" autocomplete="email" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <UFormField label="Tỉnh/Thành phố" name="city" class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-900' }">
            <USelect v-model.number="form.city" name="city" :items="cityOptions" color="neutral" variant="none" class="w-full" :ui="selectUi" />
          </UFormField>
          <UFormField label="Phường/Xã" name="ward" class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-900' }">
            <USelect v-model.number="form.ward" name="ward" :items="wardOptions" :disabled="!form.city || loadingWards" color="neutral" variant="none" class="w-full" :ui="selectUi" />
          </UFormField>
          <URadioGroup
            v-model="form.loaihinh"
            name="loaihinh"
            legend="Bạn là..."
            :items="customerTypeOptions"
            orientation="vertical"
            color="primary"
            class="tb:col-span-2"
            :ui="{ legend: 'mb-1 w-full text-sm font-semibold text-navy-900', root: 'grid gap-1.5', item: 'flex min-h-10 items-center gap-2', label: 'text-sm font-normal text-navy-800' }"
          />
          <UFormField label="Nhu cầu cần tư vấn" name="needCode" class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-900' }">
            <USelect v-model="form.needCode" name="needCode" :items="needOptions" placeholder="Chọn nhu cầu" color="neutral" variant="none" class="w-full" :ui="selectUi" />
          </UFormField>
          <UFormField label="Số lượng dự kiến" name="quantity" class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-900' }">
            <UInput v-model.trim="form.quantity" name="quantity" maxlength="255" placeholder="Ví dụ: 2 bộ" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <UFormField label="Thông tin cần tư vấn thêm" name="details" class="grid min-w-0 content-start gap-2 tb:col-span-2" :ui="{ label: 'text-sm font-semibold text-navy-900' }">
            <UTextarea v-model.trim="form.details" name="details" maxlength="1000" :rows="4" placeholder="Mô tả vị trí lắp đặt, tải sử dụng hoặc yêu cầu của bạn" variant="none" class="w-full" :ui="textareaUi" />
          </UFormField>
        </div>

        <p v-if="success" class="mt-4 rounded-chip bg-emerald-50 p-3 text-sm font-semibold text-emerald-800" role="status">{{ success }}</p>
        <p v-if="error" class="mt-4 rounded-chip bg-red-50 p-3 text-sm font-semibold text-red-800" role="alert">{{ error }}</p>
        <div class="mt-5 flex justify-center">
          <UButton type="submit" :loading="submitting" :disabled="submitting" class="min-h-11 rounded-[4px] bg-gold-500 px-6 font-extrabold text-navy-950 disabled:cursor-wait disabled:opacity-60">
            {{ submitting ? 'Đang gửi…' : 'Gửi yêu cầu tư vấn' }}
          </UButton>
        </div>
      </form>
    </div>
  </section>
</template>
