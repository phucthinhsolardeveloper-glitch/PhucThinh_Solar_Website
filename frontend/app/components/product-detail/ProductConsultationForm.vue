<script setup lang="ts">
import type { PublicProductNeed } from '#shared/contracts/public-product-extras'

// Phones ask for name and phone first; everything else is optional and one tap away.
const detailsOpen = ref(false)

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
const customerTypeOptions = [
  { label: 'Người dùng cá nhân', value: 'Người dùng cá nhân' },
  { label: 'Đại lý/Nhà phân phối, bán lẻ/Nhà thầu…', value: 'Đại lý/Nhà phân phối, bán lẻ/Nhà thầu…' },
]

const quickPrompts = computed(() => {
  if (props.profileKey === 'lighting') {
    return [
      'Diện tích cần chiếu sáng: ~… m²',
      'Chiều cao trụ / vị trí gắn: ~… m',
      'Cần báo giá số lượng cho công trình',
    ]
  }
  return [
    'Tiền điện trung bình: ~… đ/tháng',
    'Nguồn điện hiện tại: 1 pha / 3 pha',
    'Cần tư vấn cấu hình đồng bộ & lắp đặt',
  ]
})

function appendPrompt(prompt: string) {
  if (form.details.includes(prompt)) return
  form.details = form.details ? `${form.details}\n${prompt}` : prompt
}

const inputUi = {
  base: 'h-11 w-full min-w-0 rounded-btn border border-slate-300 bg-white px-3 text-base font-normal text-navy-950 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25 max-tb:h-12 max-tb:rounded-sm',
}
const selectUi = {
  base: 'h-11 w-full min-w-0 rounded-btn border border-slate-300 bg-white px-3 text-base font-normal text-navy-950 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25 max-tb:h-12 max-tb:rounded-sm',
}
const textareaUi = {
  base: 'min-h-[112px] w-full min-w-0 rounded-btn border border-slate-300 bg-white px-3 py-2 text-base font-normal text-navy-950 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25 max-tb:rounded-sm',
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
    const payload: any = await $fetch('/_api/checkout-wards', { query: { city: String(city) } })
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
    error.value = reason?.data?.statusMessage || 'Chưa gửi được yêu cầu. Vui lòng thử lại hoặc gọi hotline để được hỗ trợ ngay.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section id="pts-pdp-consultation" class="pdp-consult scroll-mt-24 border-t border-gray-200 py-12 tb:py-16 max-tb:py-8" aria-labelledby="product-consultation-title">
    <div class="grid min-w-0 gap-8 bg-slate-50 p-6 lg:grid-cols-[minmax(280px,.85fr)_minmax(0,1.35fr)] lg:gap-12 lg:p-10 max-tb:grid-cols-[minmax(0,1fr)] max-tb:gap-5 max-tb:bg-transparent max-tb:p-0">
      <div class="min-w-0">
        <h2 id="product-consultation-title" class="mb-3 text-[clamp(24px,3vw,34px)] font-black tracking-tight text-navy-900">Nhận tư vấn cho sản phẩm này</h2>
        <p class="mb-5 text-[15.5px] leading-7 text-slate-600">Đội ngũ kỹ thuật Phúc Thịnh Solar đối chiếu đúng model, tính toán số lượng và gửi báo giá chi tiết theo khu vực lắp đặt của bạn.</p>

        <div class="border-l-4 border-gold-500 bg-white p-4 max-tb:rounded-md max-tb:border-l-0 max-tb:bg-slate-50 max-tb:p-3">
          <span class="block text-xs font-bold text-slate-500">Sản phẩm đang chọn</span>
          <strong class="mt-1 block text-base font-black leading-snug text-navy-900">{{ productName }}</strong>
        </div>

        <ul class="mt-5 space-y-2.5 pl-0 text-sm text-slate-600 [list-style:none]">
          <li class="flex items-start gap-2.5">
            <UIcon name="i-lucide-check" class="mt-0.5 size-4 shrink-0 text-gold-700" aria-hidden="true" />
            <span>Kiểm tra độ tương thích và công suất thực tế trước khi đặt hàng</span>
          </li>
          <li class="flex items-start gap-2.5">
            <UIcon name="i-lucide-check" class="mt-0.5 size-4 shrink-0 text-gold-700" aria-hidden="true" />
            <span>Báo giá riêng cho đơn công trình, nhà thầu và đại lý</span>
          </li>
        </ul>
      </div>

      <form class="min-w-0" @submit.prevent="send">
        <div v-if="needs.length" class="mb-5">
          <span class="mb-2 block text-sm font-semibold text-navy-900">Nhu cầu cần tư vấn</span>
          <div class="flex flex-wrap gap-2" role="group" aria-label="Nhu cầu cần tư vấn">
            <button
              v-for="need in needs"
              :key="need.code"
              type="button"
              class="min-h-11 rounded-btn border px-3.5 py-2 text-sm font-bold transition-colors cursor-pointer"
              :class="form.needCode === need.code
                ? 'pts-gold-fill'
                : 'border-slate-300 bg-white text-navy-800 hover:border-gold-600'"
              :aria-pressed="form.needCode === need.code"
              @click="form.needCode = form.needCode === need.code ? '' : need.code"
            >
              {{ need.label }}
            </button>
          </div>
        </div>

        <div class="grid gap-4 tb:grid-cols-2 max-tb:grid-cols-[minmax(0,1fr)]">
          <UFormField label="Họ và tên" name="fullname" required class="grid min-w-0 grid-cols-[minmax(0,1fr)] content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-900' }">
            <UInput v-model.trim="form.fullname" name="fullname" required maxlength="100" autocomplete="name" placeholder="Nguyễn Văn A" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <UFormField label="Số điện thoại" name="phone" required class="grid min-w-0 grid-cols-[minmax(0,1fr)] content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-900' }">
            <UInput v-model.trim="form.phone" name="phone" type="tel" required maxlength="20" autocomplete="tel" inputmode="tel" placeholder="090xxxxxxx" pattern="(\+?84|0)[0-9 .\-]{8,12}" title="Nhập số điện thoại Việt Nam, ví dụ 0944 555 957" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <FormMoreToggle v-if="!detailsOpen" phone-only @click="detailsOpen = true" />
          <UFormField label="Email" name="email" :class="detailsOpen ? '' : 'max-tb:hidden'" class="grid min-w-0 grid-cols-[minmax(0,1fr)] content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-900' }">
            <UInput v-model.trim="form.email" name="email" type="email" maxlength="150" autocomplete="email" placeholder="email@example.com" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <UFormField label="Số lượng dự kiến" name="quantity" :class="detailsOpen ? '' : 'max-tb:hidden'" class="grid min-w-0 grid-cols-[minmax(0,1fr)] content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-900' }">
            <UInput v-model.trim="form.quantity" name="quantity" maxlength="255" placeholder="Ví dụ: 2 bộ" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <UFormField label="Tỉnh/Thành phố" name="city" :class="detailsOpen ? '' : 'max-tb:hidden'" class="grid min-w-0 grid-cols-[minmax(0,1fr)] content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-900' }">
            <USelect v-model.number="form.city" name="city" :items="cityOptions" color="neutral" variant="none" class="w-full" :ui="selectUi" />
          </UFormField>
          <UFormField label="Phường/Xã" name="ward" :class="detailsOpen ? '' : 'max-tb:hidden'" class="grid min-w-0 grid-cols-[minmax(0,1fr)] content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-900' }">
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
            :class="detailsOpen ? '' : 'max-tb:hidden'"
            :ui="{ legend: 'mb-1 w-full text-sm font-semibold text-navy-900', root: 'grid gap-1.5', item: 'flex min-h-10 items-center gap-2 max-tb:min-h-11', label: 'text-sm font-normal text-navy-800' }"
          />
          <UFormField label="Thông tin cần tư vấn thêm" name="details" :class="detailsOpen ? '' : 'max-tb:hidden'" class="grid min-w-0 grid-cols-[minmax(0,1fr)] content-start gap-2 tb:col-span-2" :ui="{ label: 'text-sm font-semibold text-navy-900' }">
            <div class="mb-2 flex flex-wrap gap-1.5">
              <button
                v-for="prompt in quickPrompts"
                :key="prompt"
                type="button"
                class="rounded-chip border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-600 transition-colors hover:border-gold-600 hover:text-navy-900 cursor-pointer max-tb:min-h-11 max-tb:rounded-sm max-tb:text-left max-tb:text-[13px]"
                @click="appendPrompt(prompt)"
              >
                + {{ prompt }}
              </button>
            </div>
            <UTextarea v-model.trim="form.details" name="details" maxlength="1000" :rows="4" placeholder="Mô tả vị trí lắp đặt, tải sử dụng hoặc yêu cầu của bạn" variant="none" class="w-full" :ui="textareaUi" />
          </UFormField>
        </div>

        <p v-if="success" class="mt-4 rounded-chip bg-emerald-50 p-3 text-sm font-semibold text-emerald-800" role="status">{{ success }}</p>
        <p v-if="error" class="mt-4 rounded-chip bg-red-50 p-3 text-sm font-semibold text-red-800" role="alert">{{ error }}</p>
        <div class="mt-5 flex justify-start">
          <UButton type="submit" :loading="submitting" :disabled="submitting" class="min-h-11 rounded-btn bg-gold-500 px-6 font-extrabold text-navy-950 disabled:cursor-wait disabled:opacity-60 cursor-pointer max-tb:min-h-[52px] max-tb:w-full max-tb:justify-center">
            {{ submitting ? 'Đang gửi…' : 'Gửi yêu cầu tư vấn' }}
          </UButton>
        </div>
      </form>
    </div>
  </section>
</template>

<style scoped>
/* Phones: the fold button stays centred once open (the plugin's button is otherwise only as wide as its label). */
@media (max-width: 820px) {
  .pdp-consult :deep(.m-fold-more) { width: 100%; }
}
</style>
