<script setup lang="ts">
const props = withDefaults(defineProps<{
  selectedProduct?: string
  hotline?: { text: string; tel: string } | null
}>(), {
  selectedProduct: '',
  hotline: null,
})
const emit = defineEmits<{ (e: 'clearProduct'): void }>()

const { security, ensureSecurity, refreshSecurity, submit } = useLeadForm()
const form = reactive({ fullname: '', phone: '', email: '', address: '', company: '' })
const partnerProfiles = [
  'Đại lý thiết bị điện',
  'Nhà thầu cơ điện / XD',
  'Cửa hàng chiếu sáng',
  'Mới bắt đầu kinh doanh',
]
const selectedProfile = ref('')
const submitting = ref(false)
const securityLoading = ref(true)
const success = ref('')
const error = ref('')
const cityOptions = computed(() => (security.value?.cities ?? []).map((city) => ({ label: city.name, value: city.name })))
const inputUi = {
  base: 'h-[52px] w-full rounded-btn border border-slate-300 bg-white px-3.5 text-base font-normal text-navy-950 placeholder:text-slate-400 focus-visible:border-gold-500 focus-visible:ring-2 focus-visible:ring-gold-500/30',
}
const selectUi = {
  base: 'h-[52px] w-full rounded-btn border border-slate-300 bg-white px-3.5 text-base font-normal text-navy-950 focus-visible:border-gold-500 focus-visible:ring-2 focus-visible:ring-gold-500/30',
}

function toggleProfile(profile: string) {
  if (selectedProfile.value === profile) {
    selectedProfile.value = ''
    return
  }
  selectedProfile.value = profile
  if (!form.company) {
    form.company = profile
  }
}

async function loadSecurity(refresh = false) {
  securityLoading.value = true
  error.value = ''
  try {
    const payload = refresh ? await refreshSecurity() : await ensureSecurity()
    if (!payload.cities.length) error.value = 'Chưa tải được danh sách tỉnh/thành phố. Vui lòng thử tải lại.'
  } catch {
    error.value = 'Không thể chuẩn bị biểu mẫu và danh sách tỉnh/thành phố. Vui lòng thử tải lại.'
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
    const companyWithContext = [
      form.company.trim(),
      props.selectedProduct ? `(Quan tâm: ${props.selectedProduct})` : '',
    ].filter(Boolean).join(' ').slice(0, 180)
    const result = await submit('dealer', {
      ...form,
      company: companyWithContext || form.company,
    })
    success.value = result.message
    form.fullname = ''
    form.phone = ''
    form.email = ''
    form.address = ''
    form.company = ''
    selectedProfile.value = ''
    emit('clearProduct')
  } catch (reason: any) {
    error.value = reason?.data?.statusMessage || reason?.statusMessage || reason?.message || 'Không thể gửi đăng ký đại lý.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <form class="grid min-w-0 grid-cols-1 gap-x-5 gap-y-5 min-[640px]:grid-cols-2" @submit.prevent="send">
    <div v-if="selectedProduct" class="flex items-center justify-between gap-2 rounded-[4px] border border-gold-400/50 bg-gold-500/15 px-3.5 py-2.5 text-xs font-bold text-gold-200 min-[640px]:col-span-2">
      <span class="flex items-center gap-2">
        <UIcon name="i-lucide-check-circle-2" class="size-4 flex-none text-gold-400" aria-hidden="true" />
        <span>Dòng sản phẩm quan tâm: <strong class="text-white">{{ selectedProduct }}</strong></span>
      </span>
      <button type="button" class="text-xs font-extrabold text-gold-300 underline hover:text-white cursor-pointer" @click="emit('clearProduct')">Bỏ chọn</button>
    </div>

    <UFormField label="Họ và tên" name="fullname" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-white' }">
      <UInput v-model.trim="form.fullname" name="fullname" required maxlength="100" autocomplete="name" placeholder="Nguyễn Văn A" variant="none" class="w-full" :ui="inputUi" />
    </UFormField>
    <UFormField label="Số điện thoại" name="phone" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-white' }">
      <UInput v-model.trim="form.phone" name="phone" type="tel" required maxlength="20" autocomplete="tel" inputmode="tel" placeholder="09xx xxx xxx" variant="none" class="w-full" :ui="inputUi" />
    </UFormField>
    <UFormField label="Email" name="email" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-white' }">
      <UInput v-model.trim="form.email" name="email" type="email" required maxlength="150" autocomplete="email" placeholder="email@doanhnghiep.vn" variant="none" class="w-full" :ui="inputUi" />
    </UFormField>
    <UFormField label="Tỉnh/Thành phố" name="address" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-white' }">
      <USelect v-model="form.address" name="address" required :disabled="securityLoading || !cityOptions.length" :items="cityOptions" :placeholder="securityLoading ? 'Đang tải tỉnh/thành…' : 'Chọn tỉnh/thành'" color="neutral" variant="none" class="w-full" :ui="selectUi" />
    </UFormField>
    <UFormField label="Doanh nghiệp hoặc cửa hàng" name="company" required class="grid min-w-0 content-start gap-2 min-[640px]:col-span-2" :ui="{ label: 'text-sm font-semibold text-white' }">
      <div class="mb-2 flex flex-wrap gap-1.5" role="group" aria-label="Chọn nhanh mô hình kinh doanh">
        <button
          v-for="profile in partnerProfiles"
          :key="profile"
          type="button"
          class="rounded-[3px] border px-2.5 py-1 text-xs font-bold transition-colors cursor-pointer"
          :class="selectedProfile === profile
            ? 'border-gold-400 bg-gold-500 text-navy-950'
            : 'border-white/20 bg-white/10 text-blue-100 hover:border-gold-400/70 hover:text-white'"
          @click="toggleProfile(profile)"
        >
          + {{ profile }}
        </button>
      </div>
      <UInput v-model.trim="form.company" name="company" required maxlength="180" autocomplete="organization" placeholder="Tên đơn vị của bạn" variant="none" class="w-full" :ui="inputUi" />
    </UFormField>

    <p v-if="success" class="rounded-chip bg-emerald-50 p-3 text-sm font-semibold text-emerald-800 min-[640px]:col-span-2" role="status">{{ success }}</p>
    <div v-if="error" class="rounded-chip bg-red-50 p-3 text-sm font-semibold text-red-800 min-[640px]:col-span-2" role="alert">
      <p class="mb-2">{{ error }}</p>
      <button v-if="!securityLoading" type="button" class="font-bold underline" @click="loadSecurity(true)">Thử tải lại</button>
    </div>
    <UButton type="submit" color="primary" size="xl" :loading="submitting" :disabled="submitting || securityLoading || !cityOptions.length" class="min-h-14 w-full justify-center rounded-btn bg-gold-500 px-5 text-base font-extrabold text-navy-950 transition-colors hover:bg-gold-400 min-[640px]:col-span-2">
      {{ submitting ? 'Đang gửi…' : 'Đăng ký ngay' }}
      <UIcon v-if="!submitting" name="i-lucide-arrow-right" class="size-5" aria-hidden="true" />
    </UButton>

    <a
      v-if="hotline?.tel"
      :href="`tel:${hotline.tel}`"
      class="flex items-center justify-between gap-3 rounded-[4px] border border-white/15 bg-white/[.06] px-4 py-3 text-sm text-blue-100 no-underline transition-colors hover:border-gold-400/60 hover:bg-white/10 min-[640px]:col-span-2"
    >
      <span class="flex items-center gap-2.5">
        <span class="grid size-8 place-items-center rounded-full bg-gold-500 text-navy-950">
          <UIcon name="i-lucide-phone-call" class="size-4" aria-hidden="true" />
        </span>
        <span>Cần trao đổi chính sách chiết khấu ngay?</span>
      </span>
      <strong class="font-black tabular-nums text-gold-300">{{ hotline.text }}</strong>
    </a>
  </form>
</template>
