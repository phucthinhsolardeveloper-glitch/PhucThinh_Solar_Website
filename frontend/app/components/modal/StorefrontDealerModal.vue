<script setup lang="ts">
import { useStorefrontModal } from '~/composables/useStorefrontModal'
import { useLeadForm } from '~/composables/useLeadForm'

const { isDealerOpen, closeDealer } = useStorefrontModal()
const { security, ensureSecurity, refreshSecurity, submit } = useLeadForm()

const detailsOpen = ref(false)
const form = reactive({
  fullname: '',
  phone: '',
  email: '',
  cityId: '',
  company: '',
  notes: '',
})

const submitting = ref(false)
const securityLoading = ref(false)
const success = ref('')
const error = ref('')
const securityError = ref('')
const cityOptions = computed(() => (security.value?.cities ?? []).map((city) => ({ label: city.name, value: String(city.id) })))
const partnerProfiles = [
  'Đại lý thiết bị điện',
  'Nhà thầu cơ điện / XD',
  'Cửa hàng chiếu sáng',
  'Quan tâm đèn Blue Carbon',
  'Quan tâm tấm pin & biến tần',
]

function appendDealerNote(chip: string) {
  const current = form.notes.trim()
  if (current.includes(chip)) return
  form.notes = current ? `${current}; ${chip}` : chip
}
const inputUi = {
  base: 'h-12 sm:h-14 w-full rounded-btn border border-slate-300 bg-white px-3 sm:px-4 max-sm:placeholder:text-[14px] text-base font-normal text-navy-950 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
}
const selectUi = {
  base: 'h-12 sm:h-14 w-full rounded-btn border border-slate-300 bg-white px-3 sm:px-4 max-sm:placeholder:text-[14px] text-base font-normal text-navy-950 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
}
const textareaUi = {
  base: 'min-h-[144px] w-full rounded-btn border border-slate-300 bg-white px-4 py-3 text-base font-normal text-navy-950 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
}

async function loadSecurity(refresh = false) {
  securityLoading.value = true
  securityError.value = ''
  try {
    const payload = refresh ? await refreshSecurity() : await ensureSecurity()
    if (!payload?.cities?.length) {
      securityError.value = 'Chưa thể tải danh sách tỉnh/thành phố. Vui lòng bấm thử lại.'
    }
  } catch {
      securityError.value = 'Không thể chuẩn bị biểu mẫu. Vui lòng thử tải lại.'
  } finally {
    securityLoading.value = false
  }
}

watch(
  () => isDealerOpen.value,
  (open) => {
    if (open) {
      success.value = ''
      error.value = ''
      void loadSecurity()
    }
  }
)

async function send() {
  success.value = ''
  error.value = ''
  securityError.value = ''

  if (securityLoading.value || !security.value) {
    await loadSecurity()
    if (!security.value) return
  }

  const selectedCity = security.value.cities.find((city) => String(city.id) === form.cityId)
  if (!selectedCity) {
    error.value = 'Vui lòng chọn tỉnh/thành phố trong danh sách.'
    return
  }

  submitting.value = true
  try {
    const companyText = form.notes.trim()
      ? `${form.company.trim()} (${form.notes.trim()})`.slice(0, 180)
      : form.company.trim()

    const result = await submit('dealer', {
      fullname: form.fullname,
      phone: form.phone,
      email: form.email,
      city_id: selectedCity.id,
      company: companyText,
    })

    success.value = result.message || 'Đăng ký đại lý thành công. Bộ phận phát triển đại lý Phúc Thịnh Solar sẽ liên hệ với bạn.'
    form.fullname = ''
    form.phone = ''
    form.email = ''
    form.cityId = ''
    form.company = ''
    form.notes = ''
    detailsOpen.value = false
  } catch (reason: any) {
    error.value = reason?.data?.statusMessage || reason?.statusMessage || reason?.message || 'Không thể gửi đăng ký đối tác & đại lý. Vui lòng thử lại.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="isDealerOpen"
    :title="'Đăng ký đối tác & Đại lý phân phối'"
    :description="'Hợp tác phát triển năng lượng xanh cùng Phúc Thịnh Solar với chính sách ưu đãi.'"
    :ui="{
      overlay: 'glass-overlay',
      content: 'shadow-[0_28px_70px_-24px_rgb(6_21_55/45%)] w-[min(880px,calc(100vw-32px))] max-w-none max-h-[calc(100dvh-24px)] overflow-y-auto rounded-btn border border-slate-200 bg-white shadow-2xl divide-y-0 text-navy-950',
      header: 'px-5 pt-5 pb-1 border-b-0 relative sm:px-10 sm:pt-10 sm:pb-3',
      title: 'pr-8 text-[clamp(21px,3vw,32px)] leading-tight font-black tracking-tight text-navy-950',
      description: 'mt-1.5 max-w-3xl text-[14.5px] leading-6 text-slate-600 sm:mt-2 sm:text-[16px] sm:leading-7',
      body: 'px-5 pt-2 pb-5 sm:px-10 sm:pt-3 sm:pb-10',
      close: 'text-slate-400 hover:text-slate-700 top-5 right-5',
    }"
  >
    <template #body>
      <div v-if="success" class="space-y-4 py-3 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <div class="space-y-1">
          <h3 class="text-lg font-bold text-navy-900">Đăng ký thành công</h3>
          <p class="text-sm leading-relaxed text-slate-600">{{ success }}</p>
        </div>
        <div class="pt-2">
          <button
            type="button"
            class="min-h-[42px] w-full rounded-btn bg-gold-500 px-5 text-sm font-bold text-navy-950 transition-colors hover:bg-gold-400"
            @click="closeDealer"
          >
            Đóng cửa sổ
          </button>
        </div>
      </div>

      <form v-else class="grid grid-cols-[minmax(0,1fr)] gap-6 max-tb:gap-3" @submit.prevent="send">
        <div class="grid grid-cols-4 gap-2 rounded-btn bg-navy-950 p-3.5 text-xs text-white max-sm:hidden">
          <div class="border-l-2 border-gold-500 pl-2.5">
            <strong class="block text-gold-300">Chiết khấu gốc</strong>
            <span class="text-white/75">Nhập khẩu trực tiếp</span>
          </div>
          <div class="border-l-2 border-gold-500 pl-2.5">
            <strong class="block text-gold-300">Đủ CO/CQ & VAT</strong>
            <span class="text-white/75">Hồ sơ minh bạch</span>
          </div>
          <div class="border-l-2 border-gold-500 pl-2.5">
            <strong class="block text-gold-300">Hỗ trợ trưng bày</strong>
            <span class="text-white/75">Catalogue & bảng hiệu</span>
          </div>
          <div class="border-l-2 border-gold-500 pl-2.5">
            <strong class="block text-gold-300">Kho sẵn 3 miền</strong>
            <span class="text-white/75">Gia Lai · Đà Nẵng · HCM</span>
          </div>
        </div>

        <div class="grid min-w-0 grid-cols-2 gap-x-3 gap-y-3 sm:gap-x-6 sm:gap-y-5">
          <UFormField label="Họ và tên" name="fullname" required class="grid min-w-0 grid-cols-[minmax(0,1fr)] content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800' }">
            <UInput v-model.trim="form.fullname" name="fullname" required maxlength="100" autocomplete="name" placeholder="Nguyễn Văn A" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <UFormField label="Số điện thoại" name="phone" required class="grid min-w-0 grid-cols-[minmax(0,1fr)] content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800' }">
            <UInput v-model.trim="form.phone" name="phone" type="tel" required maxlength="20" autocomplete="tel" inputmode="tel" placeholder="090xxxxxxx" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <UFormField label="Email" name="email" required class="grid min-w-0 grid-cols-[minmax(0,1fr)] content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800' }">
            <UInput v-model.trim="form.email" name="email" type="email" required maxlength="150" autocomplete="email" placeholder="ten@congty.vn" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <UFormField label="Tỉnh/Thành phố" name="cityId" required class="grid min-w-0 grid-cols-[minmax(0,1fr)] content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800' }">
            <USelect v-model="form.cityId" name="cityId" required :disabled="securityLoading || !cityOptions.length" :items="cityOptions" :placeholder="securityLoading ? 'Đang tải tỉnh/thành…' : 'Tỉnh/thành'" color="neutral" variant="none" class="w-full" :ui="selectUi" />
          </UFormField>
        </div>

        <UFormField label="Khu vực kinh doanh / Tên cửa hàng" name="company" required class="grid min-w-0 grid-cols-[minmax(0,1fr)] content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800' }">
          <UInput v-model.trim="form.company" name="company" required maxlength="180" autocomplete="organization" placeholder="Tên cửa hàng hoặc khu vực" variant="none" class="w-full" :ui="inputUi" />
        </UFormField>

        <FormMoreToggle v-if="!detailsOpen" @click="detailsOpen = true" />
        <UFormField v-if="detailsOpen" label="Ghi chú thêm" name="notes" class="grid min-w-0 grid-cols-[minmax(0,1fr)] content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800' }">
          <div class="mb-1.5 flex flex-wrap gap-1.5" aria-label="Mô hình đối tác gợi ý">
            <button
              v-for="chip in partnerProfiles"
              :key="chip"
              type="button"
              class="cursor-pointer rounded-chip border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600 transition-colors hover:border-gold-500 hover:bg-gold-50/50 hover:text-navy-900"
              @click="appendDealerNote(chip)"
            >
              + {{ chip }}
            </button>
          </div>
          <UTextarea v-model.trim="form.notes" name="notes" :rows="3" maxlength="1000" placeholder="Năng lực hiện tại, sản phẩm chính quan tâm (đèn mặt trời, điện mặt trời, inverter...)" variant="none" class="w-full" :ui="textareaUi" />
        </UFormField>

        <p v-if="error" class="rounded-btn bg-red-50 p-2.5 text-xs font-semibold text-red-700" role="alert">
          {{ error }}
        </p>

        <div v-if="securityError" class="rounded-btn bg-red-50 p-3 text-sm font-semibold text-red-700" role="alert">
          <span>{{ securityError }}</span>
          <button v-if="!securityLoading" type="button" class="ml-2 font-bold underline" @click="loadSecurity(true)">
            Thử tải lại
          </button>
        </div>

        <div class="grid gap-3">
          <UButton type="submit" color="primary" size="xl" :loading="submitting" :disabled="submitting || securityLoading || !cityOptions.length" class="min-h-14 w-full justify-center rounded-btn bg-gold-500 px-6 text-base font-extrabold text-navy-950">
            {{ submitting ? 'Đang gửi đăng ký…' : 'Đăng ký ngay' }}
            <UIcon v-if="!submitting" name="i-lucide-arrow-right" class="size-5" aria-hidden="true" />
          </UButton>
          <p class="m-0 text-center text-xs text-slate-500">
            Bộ phận phát triển đại lý: <a href="tel:0944555957" class="font-bold text-navy-900 no-underline hover:text-gold-700">0944 555 957</a>
          </p>
        </div>
      </form>
    </template>
  </UModal>
</template>
